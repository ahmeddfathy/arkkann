<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PermissionRequest;
use App\Services\PermissionRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Violation;
use App\Services\NotificationPermissionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermissionRequestController extends Controller
{
    protected $permissionRequestService;
    protected $notificationService;

    public function __construct(PermissionRequestService $permissionRequestService, NotificationPermissionService $notificationService)
    {
        $this->permissionRequestService = $permissionRequestService;
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        // التحقق من صلاحية عرض طلبات الاستئذان
        if (!auth()->user()->hasPermissionTo('view_permission')) {
            abort(403, 'ليس لديك صلاحية عرض طلبات الاستئذان');
        }

        $user = Auth::user();
        $employeeName = $request->input('employee_name');
        $status = $request->input('status');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $now = now();
        $currentMonthStart = $now->day >= 26
            ? $now->copy()->startOfDay()->setDay(26)
            : $now->copy()->subMonth()->startOfDay()->setDay(26);

        $currentMonthEnd = $now->day >= 26
            ? $now->copy()->addMonth()->startOfDay()->setDay(25)->endOfDay()
            : $now->copy()->startOfDay()->setDay(25)->endOfDay();

        $dateStart = $fromDate ? Carbon::parse($fromDate)->startOfDay() : $currentMonthStart;
        $dateEnd = $toDate ? Carbon::parse($toDate)->endOfDay() : $currentMonthEnd;

        $myRequestsQuery = PermissionRequest::with('user')
            ->where('user_id', $user->id);

        if ($status) {
            $myRequestsQuery->where('status', $status);
        }

        if ($fromDate && $toDate) {
            $myRequestsQuery->whereBetween('departure_time', [$dateStart, $dateEnd]);
        }

        $myRequests = $myRequestsQuery->latest()->paginate(10);

        $totalUsedMinutes = PermissionRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('departure_time', [$dateStart, $dateEnd])
            ->sum('minutes_used');

        $teamMembersMinutes = [];
        if ($user->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr'])) {
            $teamMembers = $user->currentTeam ? $user->currentTeam->users->pluck('id') : collect();

            foreach ($teamMembers as $memberId) {
                $teamMembersMinutes[$memberId] = PermissionRequest::where('user_id', $memberId)
                    ->where('status', 'approved')
                    ->whereBetween('departure_time', [$currentMonthStart, $currentMonthEnd])
                    ->sum('minutes_used');
            }
        }

        $teamRequests = PermissionRequest::where('id', 0)->paginate(10);
        $noTeamRequests = PermissionRequest::where('id', 0)->paginate(10);
        $hrRequests = PermissionRequest::where('id', 0)->paginate(10);
        $remainingMinutes = [];

        if ($user->hasRole('hr')) {
            // إستعلام طلبات موظفي الشركه - لموظفي الـ HR فقط
            $hrQuery = PermissionRequest::with(['user', 'violations'])
                ->where(function ($query) use ($user) {
                    $query->whereHas('user', function ($q) use ($user) {
                        $q->whereDoesntHave('roles', function ($q) {
                            $q->whereIn('name', ['hr', 'company_manager']);
                        });
                    });
                });

            if ($employeeName) {
                $hrQuery->whereHas('user', function ($q) use ($employeeName) {
                    $q->where('name', 'like', "%{$employeeName}%");
                });
            }

            if ($status) {
                $hrQuery->where('status', $status);
            }

            if ($fromDate && $toDate) {
                $hrQuery->whereBetween('departure_time', [$dateStart, $dateEnd]);
            }

            $hrRequests = $hrQuery->latest()->paginate(10, ['*'], 'hr_page');

            // إستعلام طلبات الفريق - للفريق الذي يديره المستخدم فقط
            if ($user->currentTeam) {
                $teamMembers = $user->currentTeam->users->pluck('id')->toArray();

                $teamQuery = PermissionRequest::with(['user', 'violations'])
                    ->whereIn('user_id', $teamMembers);

                if ($employeeName) {
                    $teamQuery->whereHas('user', function ($q) use ($employeeName) {
                        $q->where('name', 'like', "%{$employeeName}%");
                    });
                }

                if ($status) {
                    $teamQuery->where('status', $status);
                }

                if ($fromDate && $toDate) {
                    $teamQuery->whereBetween('departure_time', [$dateStart, $dateEnd]);
                }

                $teamRequests = $teamQuery->latest()->paginate(10, ['*'], 'team_page');

                foreach ($teamMembers as $userId) {
                    $remainingMinutes[$userId] = $this->permissionRequestService->getRemainingMinutes($userId);
                }
            }

            // إستعلام طلبات الموظفين بدون فريق - لموظفي الـ HR فقط
            $noTeamQuery = PermissionRequest::with(['user', 'violations'])
                ->whereHas('user', function ($q) {
                    $q->whereDoesntHave('teams');
                })
                ->whereBetween('departure_time', [$dateStart, $dateEnd]);

            if ($employeeName) {
                $noTeamQuery->whereHas('user', function ($q) use ($employeeName) {
                    $q->where('name', 'like', "%{$employeeName}%");
                });
            }

            if ($status) {
                $noTeamQuery->where('status', $status);
            }

            if ($fromDate && $toDate) {
                $noTeamQuery->whereBetween('departure_time', [$dateStart, $dateEnd]);
            }

            $noTeamRequests = $noTeamQuery->latest()->paginate(10);

            $teamUserIds = $teamRequests->pluck('user_id')->unique();
            foreach ($teamUserIds as $userId) {
                $remainingMinutes[$userId] = $this->permissionRequestService->getRemainingMinutes($userId);
            }
        } elseif ($user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
            $team = $user->currentTeam;
            if ($team) {
                $allowedRoles = [];
                if ($user->hasRole('team_leader')) {
                    $allowedRoles = ['employee'];
                } elseif ($user->hasRole('department_manager')) {
                    $allowedRoles = ['employee', 'team_leader'];
                } elseif ($user->hasRole('company_manager')) {
                    $allowedRoles = ['employee', 'team_leader', 'department_manager'];
                }

                $teamMembers = $this->permissionRequestService->getAllowedUsers($user)
                    ->pluck('id')
                    ->toArray();

                $teamQuery = PermissionRequest::with(['user', 'violations'])
                    ->whereIn('user_id', $teamMembers);

                if ($employeeName) {
                    $teamQuery->whereHas('user', function ($q) use ($employeeName) {
                        $q->where('name', 'like', "%{$employeeName}%");
                    });
                }

                if ($status) {
                    $teamQuery->where('status', $status);
                }

                if ($fromDate && $toDate) {
                    $teamQuery->whereBetween('departure_time', [$dateStart, $dateEnd]);
                }

                $teamRequests = $teamQuery->latest()->paginate(10, ['*'], 'team_page');

                foreach ($teamMembers as $userId) {
                    $remainingMinutes[$userId] = $this->permissionRequestService->getRemainingMinutes($userId);
                }
            }
        }

        if (Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr'])) {
            $users = $this->permissionRequestService->getAllowedUsers(Auth::user());
        } else {
            $users = User::when($user->hasRole('hr'), function ($query) {
                return $query->whereDoesntHave('teams');
            }, function ($query) use ($user) {
                if ($user->currentTeam) {
                    return $query->whereIn('id', $user->currentTeam->users->pluck('id'));
                }
                return $query->where('id', $user->id);
            })->get();
        }

        $statistics = $this->getStatistics($user, $dateStart, $dateEnd);

        return view('permission-requests.index', compact(
            'myRequests',
            'teamRequests',
            'noTeamRequests',
            'users',
            'remainingMinutes',
            'totalUsedMinutes',
            'teamMembersMinutes',
            'currentMonthStart',
            'currentMonthEnd',
            'dateStart',
            'dateEnd',
            'statistics',
            'hrRequests'
        ));
    }

    public function store(Request $request)
    {
        // التحقق من صلاحية إنشاء طلب استئذان
        if (!auth()->user()->hasPermissionTo('create_permission')) {
            abort(403, 'ليس لديك صلاحية تقديم طلب استئذان');
        }

        $user = Auth::user();

        $validated = $request->validate([
            'departure_time' => 'required|date|after:now',
            'return_time' => 'required|date|after:departure_time',
            'reason' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'registration_type' => 'nullable|in:self,other'
        ]);

        // Check if the request is for another user based on registration_type and user_id
        if ($request->input('registration_type') === 'other' && $request->filled('user_id') && $request->input('user_id') != $user->id) {
            $result = $this->permissionRequestService->createRequestForUser($request->input('user_id'), $validated);
        } else {
            $result = $this->permissionRequestService->createRequest($validated);
        }

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        // استخدام الرسالة المخصصة إذا كانت موجودة
        $message = $result['message'] ?? 'تم إنشاء طلب الاستئذان بنجاح.';

        // إضافة معلومات الدقائق المستخدمة
        if (isset($result['used_minutes'])) {
            if (isset($result['exceeded_limit']) && $result['exceeded_limit']) {
                $message = "تنبيه: لقد تجاوزت الحد المجاني للاستئذان الشهري المسموح به (180 دقيقة). سيتم احتساب الدقائق الإضافية على حسابك.";
            } else {
                $message .= " إجمالي الدقائق المستخدمة هذا الشهر: {$result['used_minutes']} دقيقة.";
            }
        }

        return redirect()->route('permission-requests.index')->with('success', $message);
    }

    public function resetStatus(PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $this->permissionRequestService->resetStatus($permissionRequest, 'manager');

        return redirect()->route('permission-requests.index')
            ->with('success', 'Request status reset to pending successfully.');
    }

    public function modifyResponse(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        // منع المستخدم من الرد على طلباته الخاصة
        if ($user->id === $permissionRequest->user_id) {
            return redirect()->back()->with('error', 'لا يمكنك تعديل الرد على طلب الاستئذان الخاص بك');
        }

        if ($request->has('status')) {
            $status = $request->status;
            $rejectionReason = $status === 'rejected' ? $request->rejection_reason : null;

            if ($request->response_type === 'manager') {
                $permissionRequest->updateManagerStatus($status, $rejectionReason);
            } elseif ($request->response_type === 'hr') {
                $permissionRequest->updateHrStatus($status, $rejectionReason);
            }

            return redirect()->back()->with('success', 'تم تحديث الرد بنجاح');
        }

        return redirect()->back()->with('error', 'حدث خطأ أثناء تحديث الرد');
    }

    public function update(Request $request, PermissionRequest $permissionRequest)
    {
        // التحقق من صلاحية تعديل طلب الاستئذان
        if (!auth()->user()->hasPermissionTo('update_permission')) {
            abort(403, 'ليس لديك صلاحية تعديل طلب الاستئذان');
        }

        // التحقق من أن الطلب في حالة pending وأن المستخدم هو صاحب الطلب
        if ($permissionRequest->status !== 'pending' || auth()->id() !== $permissionRequest->user_id) {
            abort(403, 'لا يمكن تعديل هذا الطلب');
        }

        $user = Auth::user();

        if ($user->role !== 'manager' && $user->id !== $permissionRequest->user_id) {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'departure_time' => 'required|date|after:now',
            'return_time' => 'required|date|after:departure_time',
            'reason' => 'required|string|max:255',
            'returned_on_time' => 'nullable|boolean',
            'minutes_used' => 'nullable|integer'
        ]);

        $result = $this->permissionRequestService->updateRequest($permissionRequest, $validated);

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        $message = 'تم تحديث طلب الاستئذان بنجاح.';
        if (isset($result['used_minutes'])) {
            if (isset($result['exceeded_limit']) && $result['exceeded_limit']) {
                $message = "تنبيه: لقد تجاوزت الحد المجاني للاستئذان الشهري المسموح به (180 دقيقة). سيتم احتساب الدقائق الإضافية على حسابك.";
            } else {
                $message .= " إجمالي الدقائق المستخدمة هذا الشهر: {$result['used_minutes']} دقيقة.";
                if (isset($result['remaining_minutes']) && $result['remaining_minutes'] > 0) {
                    $message .= " الدقائق المتبقية: {$result['remaining_minutes']} دقيقة.";
                }
            }
        }

        return redirect()->route('permission-requests.index')
            ->with('success', $message);
    }

    public function destroy(PermissionRequest $permissionRequest)
    {
        try {
            // التحقق من صلاحية حذف طلب الاستئذان
            if (!auth()->user()->hasPermissionTo('delete_permission')) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية حذف طلب الاستئذان'
                ], 403);
            }

            // التحقق من أن الطلب في حالة pending وأن المستخدم هو صاحب الطلب
            if ($permissionRequest->status !== 'pending' || auth()->id() !== $permissionRequest->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف هذا الطلب'
                ], 403);
            }

            $user = Auth::user();

            // التحقق من حالة الموافقة من المدير أو HR
            if ($permissionRequest->manager_status !== 'pending' || $permissionRequest->hr_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف الطلب لأنه تمت الموافقة عليه أو رفضه من قبل المدير أو HR'
                ], 403);
            }

            try {
                $this->permissionRequestService->deleteRequest($permissionRequest);

                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'تم حذف الطلب بنجاح'
                    ]);
                }

                return redirect()->route('permission-requests.index')
                    ->with('success', 'تم حذف الطلب بنجاح');
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage()
                    ], 403);
                }

                return redirect()->route('permission-requests.index')
                    ->with('error', $e->getMessage());
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error deleting permission request: ' . $e->getMessage());

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف الطلب: ' . $e->getMessage()
                ]);
            }

            return redirect()->route('permission-requests.index')
                ->with('error', 'حدث خطأ أثناء حذف الطلب: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->hasRole('team_leader') && !$user->hasPermissionTo('manager_respond_permission_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات الاستئذان');
        }

        if ($user->hasRole('hr') && !$user->hasPermissionTo('hr_respond_permission_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات الاستئذان');
        }

        // منع المستخدم من الرد على طلباته الخاصة
        if ($user->id === $permissionRequest->user_id) {
            return redirect()->back()->with('error', 'لا يمكنك الرد على طلب الاستئذان الخاص بك');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'response_type' => 'required|in:manager,hr',
            'rejection_reason' => 'nullable|required_if:status,rejected|string|max:255',
        ]);

        if ($validated['response_type'] === 'manager' && $user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
            $permissionRequest->manager_status = $validated['status'];
            $permissionRequest->manager_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
        } elseif ($validated['response_type'] === 'hr' && $user->hasRole('hr')) {
            $permissionRequest->hr_status = $validated['status'];
            $permissionRequest->hr_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
        } else {
            return redirect()->back()->with('error', 'نوع الرد غير صحيح');
        }

        $permissionRequest->updateFinalStatus();
        $permissionRequest->save();

        return redirect()->back()->with('success', 'تم تحديث حالة الطلب بنجاح');
    }

    public function updateReturnStatus(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        // تحقق من الصلاحيات
        if (
            !$user->hasRole(['hr', 'team_leader', 'department_manager', 'company_manager']) &&
            $user->id !== $permissionRequest->user_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $validated = $request->validate([
            'return_status' => 'required|in:0,1,2',
        ]);

        try {
            $now = Carbon::now()->setTimezone('Africa/Cairo');
            $returnTime = Carbon::parse($permissionRequest->return_time);
            $maxReturnTime = $returnTime->copy();
            $endOfWorkDay = Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0);

            // إذا كان المستخدم مدير أو HR، نسمح له بتسجيل العودة بغض النظر عن الوقت
            $isManager = $user->hasRole(['hr', 'team_leader', 'department_manager', 'company_manager']);

            if ($returnTime->gte($endOfWorkDay)) {
                $permissionRequest->returned_on_time = true;
                $permissionRequest->updateActualMinutesUsed();
                $permissionRequest->save();

                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل العودة تلقائياً لانتهاء يوم العمل'
                ]);
            }

            // معالجة إعادة التعيين (return_status = 0)
            if ($validated['return_status'] == 0) {
                $permissionRequest->returned_on_time = 0;
                $permissionRequest->updateActualMinutesUsed();
                $permissionRequest->save();

                return response()->json([
                    'success' => true,
                    'message' => 'تم إعادة تعيين حالة العودة بنجاح',
                    'actual_minutes_used' => $permissionRequest->minutes_used
                ]);
            }
            // تسجيل العودة (return_status = 1)
            else if ($validated['return_status'] == 1) {
                // تخطي التحقق من الوقت للمدراء وHR
                if (!$isManager && !$permissionRequest->canMarkAsReturned($user)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'لقد تجاوزت الوقت المسموح به للعودة'
                    ]);
                }

                $isOnTime = $now->lte($maxReturnTime);
                $permissionRequest->returned_on_time = true;
                $permissionRequest->updateActualMinutesUsed();
                $permissionRequest->save();

                // إضافة مخالفة فقط إذا كان متأخراً وليس مديراً
                if (!$isOnTime && !$isManager) {
                    Violation::create([
                        'user_id' => $permissionRequest->user_id,
                        'permission_requests_id' => $permissionRequest->id,
                        'reason' => 'تسجيل العودة من الاستئذان بعد الموعد المحدد',
                        'manager_mistake' => false
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => $isOnTime ? 'تم تسجيل العودة بنجاح' : 'تم تسجيل العودة، لكن بعد انتهاء الوقت المحدد'
                ]);
            }
            // تسجيل عدم العودة (return_status = 2)
            else if ($validated['return_status'] == 2) {
                $permissionRequest->returned_on_time = 2;
                $permissionRequest->updateActualMinutesUsed();
                $permissionRequest->save();

                Violation::create([
                    'user_id' => $permissionRequest->user_id,
                    'permission_requests_id' => $permissionRequest->id,
                    'reason' => 'عدم العودة من الاستئذان في الوقت المحدد',
                    'manager_mistake' => false
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل عدم العودة بنجاح',
                    'actual_minutes_used' => $permissionRequest->minutes_used
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'قيمة غير صالحة لحالة العودة'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة العودة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateHrStatus(Request $request, $id)
    {
        // التحقق من صلاحية الرد على الطلب كـ HR
        if (!auth()->user()->hasPermissionTo('hr_respond_permission_request')) {
            abort(403, 'ليس لديك صلاحية الرد على طلبات الاستئذان كموارد بشرية');
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $permissionRequest = PermissionRequest::findOrFail($id);
            $user = Auth::user();

            if (!$user->hasRole('hr') || !$user->hasPermissionTo('hr_respond_permission_request')) {
                return back()->with('error', 'Unauthorized action.');
            }

            $permissionRequest->updateHrStatus(
                $request->status,
                $request->status === 'rejected' ? $request->rejection_reason : null
            );

            $this->notificationService->notifyHRStatusUpdate($permissionRequest);

            return back()->with('success', 'تم تحديث الرد بنجاح');
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while updating the status.');
        }
    }

    public function modifyHrStatus(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        // منع المستخدم من الرد على طلباته الخاصة
        if ($user->id === $permissionRequest->user_id) {
            return redirect()->back()->with('error', 'لا يمكنك تعديل الرد على طلب الاستئذان الخاص بك');
        }

        if (!$user->hasRole('hr') || !$user->hasPermissionTo('hr_respond_permission_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية تعديل الرد على طلبات الاستئذان');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255'
        ]);

        $permissionRequest->hr_status = $validated['status'];
        $permissionRequest->hr_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
        $permissionRequest->updateFinalStatus();
        $permissionRequest->save();

        $this->notificationService->notifyHRStatusUpdate($permissionRequest);

        return redirect()->back()->with('success', 'تم تعديل الرد بنجاح');
    }

    public function resetHrStatus(Request $request, PermissionRequest $permissionRequest)
    {
        // التحقق من صلاحية الرد على الطلب كـ HR
        if (!auth()->user()->hasPermissionTo('hr_respond_permission_request')) {
            abort(403, 'ليس لديك صلاحية إعادة تعيين الرد على طلبات الاستئذان كموارد بشرية');
        }

        $user = Auth::user();

        if (!$user->hasRole('hr') || !$user->hasPermissionTo('hr_respond_permission_request')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية إعادة تعيين الرد'
                ]);
            }
            return redirect()->back()->with('error', 'ليس لديك صلاحية إعادة تعيين الرد');
        }

        try {
            $this->permissionRequestService->resetStatus($permissionRequest, 'hr');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إعادة تعيين الرد بنجاح'
                ]);
            }
            return redirect()->back()->with('success', 'تم إعادة تعيين الرد بنجاح');
        } catch (\Exception $e) {
            \Log::error('Reset HR Status Error: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إعادة تعيين الرد'
                ]);
            }
            return redirect()->back()->with('error', 'حدث خطأ أثناء إعادة تعيين الرد');
        }
    }

    public function updateManagerStatus(Request $request, $id)
    {
        // التحقق من صلاحية الرد على الطلب كمدير
        if (!auth()->user()->hasPermissionTo('manager_respond_permission_request')) {
            abort(403, 'ليس لديك صلاحية الرد على طلبات الاستئذان كمدير');
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $permissionRequest = PermissionRequest::findOrFail($id);
            $user = Auth::user();

            if (
                !$user->hasRole(['team_leader', 'department_manager', 'company_manager']) ||
                !$user->hasPermissionTo('manager_respond_permission_request')
            ) {
                return back()->with('error', 'Unauthorized action.');
            }

            $permissionRequest->updateManagerStatus(
                $request->status,
                $request->status === 'rejected' ? $request->rejection_reason : null
            );

            $this->notificationService->notifyManagerStatusUpdate($permissionRequest);

            return back()->with('success', 'تم تحديث الرد بنجاح');
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while updating the status.');
        }
    }

    public function resetManagerStatus(PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        // منع المستخدم من الرد على طلباته الخاصة
        if ($user->id === $permissionRequest->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إعادة تعيين الرد على طلب الاستئذان الخاص بك'
            ]);
        }

        if (
            !$user->hasRole(['team_leader', 'department_manager', 'company_manager']) ||
            !$user->hasPermissionTo('manager_respond_permission_request')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية إعادة تعيين الرد على طلبات الاستئذان كمدير'
            ]);
        }

        try {
            $this->permissionRequestService->resetStatus($permissionRequest, 'manager');

            return response()->json([
                'success' => true,
                'message' => 'تم إعادة تعيين رد المدير بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعادة تعيين الرد'
            ], 500);
        }
    }

    public function modifyManagerStatus(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if (
            !$user->hasRole(['team_leader', 'department_manager', 'company_manager']) ||
            !$user->hasPermissionTo('manager_respond_permission_request')
        ) {
            return back()->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255'
        ]);

        try {
            $permissionRequest->manager_status = $validated['status'];
            $permissionRequest->manager_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
            $permissionRequest->updateFinalStatus();
            $permissionRequest->save();

            $this->notificationService->notifyManagerStatusUpdate($permissionRequest);

            return back()->with('success', 'تم تعديل الرد بنجاح');
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء تعديل الرد');
        }
    }

    protected function getStatistics($user, $dateStart, $dateEnd)
    {
        $statistics = [
            'personal' => [
                'total_requests' => 0,
                'approved_requests' => 0,
                'rejected_requests' => 0,
                'pending_requests' => 0,
                'total_minutes' => 0,
                'on_time_returns' => 0,
                'late_returns' => 0,
            ],
            'team' => [
                'total_requests' => 0,
                'approved_requests' => 0,
                'rejected_requests' => 0,
                'pending_requests' => 0,
                'total_minutes' => 0,
                'employees_exceeded_limit' => 0,
                'most_requested_employee' => null,
                'highest_minutes_employee' => null,
            ],
            'hr' => [
                'total_requests' => 0,
                'approved_requests' => 0,
                'rejected_requests' => 0,
                'pending_requests' => 0,
                'total_minutes' => 0,
                'employees_exceeded_limit' => 0,
                'most_requested_employee' => null,
                'highest_minutes_employee' => null,
                'departments_stats' => [],
                'daily_stats' => [],
                'weekly_stats' => [],
                'return_status_stats' => [],
                'busiest_days' => [],
                'busiest_hours' => [],
                'comparison_with_previous' => []
            ],
            'monthly_trend' => [],
        ];

        $personalStats = PermissionRequest::where('user_id', $user->id)
            ->whereBetween('departure_time', [$dateStart, $dateEnd])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes,
                SUM(CASE WHEN returned_on_time = 1 THEN 1 ELSE 0 END) as on_time,
                SUM(CASE WHEN returned_on_time = 2 THEN 1 ELSE 0 END) as late
            ')
            ->first();

        $statistics['personal'] = [
            'total_requests' => $personalStats->total ?? 0,
            'approved_requests' => $personalStats->approved ?? 0,
            'rejected_requests' => $personalStats->rejected ?? 0,
            'pending_requests' => $personalStats->pending ?? 0,
            'total_minutes' => $personalStats->total_minutes ?? 0,
            'on_time_returns' => $personalStats->on_time ?? 0,
            'late_returns' => $personalStats->late ?? 0,
        ];

        if (
            $user->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']) &&
            ($user->currentTeam || $user->ownedTeams->count() > 0)
        ) {
            $teams = collect();

            if ($user->hasRole('hr')) {
                $teams = $user->ownedTeams;
            } else {
                $teams = $user->currentTeam ? collect([$user->currentTeam]) : collect();
            }

            foreach ($teams as $team) {
                $allowedRoles = $this->getAllowedRoles($user);
                $teamMembers = $this->getTeamMembers($team, $allowedRoles);

                $teamStats = PermissionRequest::whereIn('user_id', $teamMembers)
                    ->whereBetween('departure_time', [$dateStart, $dateEnd])
                    ->selectRaw('
                        COUNT(*) as total,
                        SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
                        SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes
                    ')
                    ->first();

                $exceededLimit = DB::table(function ($query) use ($teamMembers, $dateStart, $dateEnd) {
                    $query->from('permission_requests')
                        ->select('user_id', DB::raw('SUM(minutes_used) as total_minutes'))
                        ->whereIn('user_id', $teamMembers)
                        ->where('status', 'approved')
                        ->whereBetween('departure_time', [$dateStart, $dateEnd])
                        ->groupBy('user_id');
                }, 'exceeded_users')
                    ->where('total_minutes', '>', 180)
                    ->count();

                $mostRequested = DB::table(function ($query) use ($teamMembers, $dateStart, $dateEnd) {
                    $query->from('permission_requests')
                        ->select('user_id', DB::raw('COUNT(*) as request_count'))
                        ->whereIn('user_id', $teamMembers)
                        ->whereBetween('departure_time', [$dateStart, $dateEnd])
                        ->groupBy('user_id');
                }, 'request_counts')
                    ->join('users', 'users.id', '=', 'request_counts.user_id')
                    ->select('users.name', 'request_counts.request_count')
                    ->orderByDesc('request_count')
                    ->first();

                $highestMinutes = DB::table(function ($query) use ($teamMembers, $dateStart, $dateEnd) {
                    $query->from('permission_requests')
                        ->select('user_id', DB::raw('SUM(minutes_used) as total_minutes'))
                        ->whereIn('user_id', $teamMembers)
                        ->where('status', 'approved')
                        ->whereBetween('departure_time', [$dateStart, $dateEnd])
                        ->groupBy('user_id');
                }, 'minute_totals')
                    ->join('users', 'users.id', '=', 'minute_totals.user_id')
                    ->select('users.name', 'minute_totals.total_minutes')
                    ->orderByDesc('total_minutes')
                    ->first();

                $exceededEmployees = DB::table(function ($query) use ($teamMembers, $dateStart, $dateEnd) {
                    $query->from('permission_requests')
                        ->select('user_id', DB::raw('SUM(minutes_used) as total_minutes'))
                        ->whereIn('user_id', $teamMembers)
                        ->where('status', 'approved')
                        ->whereBetween('departure_time', [$dateStart, $dateEnd])
                        ->groupBy('user_id')
                        ->having('total_minutes', '>', 180);
                }, 'exceeded_users')
                    ->join('users', 'users.id', '=', 'exceeded_users.user_id')
                    ->select('users.name', 'exceeded_users.total_minutes')
                    ->orderByDesc('total_minutes')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'name' => $item->name,
                            'total_minutes' => $item->total_minutes
                        ];
                    });

                $statistics['team'] = [
                    'total_requests' => $teamStats->total ?? 0,
                    'approved_requests' => $teamStats->approved ?? 0,
                    'rejected_requests' => $teamStats->rejected ?? 0,
                    'pending_requests' => $teamStats->pending ?? 0,
                    'total_minutes' => $teamStats->total_minutes ?? 0,
                    'employees_exceeded_limit' => $exceededLimit,
                    'most_requested_employee' => $mostRequested ? [
                        'name' => $mostRequested->name,
                        'count' => $mostRequested->request_count
                    ] : null,
                    'highest_minutes_employee' => $highestMinutes ? [
                        'name' => $highestMinutes->name,
                        'minutes' => $highestMinutes->total_minutes
                    ] : null,
                    'exceeded_employees' => $exceededEmployees,
                    'team_name' => $team->name
                ];

                $monthlyTrend = PermissionRequest::whereIn('user_id', $teamMembers)
                    ->whereBetween('departure_time', [$dateStart, $dateEnd])
                    ->selectRaw('
                        DATE_FORMAT(departure_time, "%Y-%m") as month,
                        COUNT(*) as total_requests,
                        SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes
                    ')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get();

                $statistics['monthly_trend'] = $monthlyTrend->map(function ($item) {
                    return [
                        'month' => $item->month,
                        'total_requests' => $item->total_requests,
                        'total_minutes' => $item->total_minutes,
                    ];
                });
            }
        }

        if ($user->hasRole('hr')) {
            $excludedRoles = ['company_manager', 'hr'];

            $allEmployees = User::whereDoesntHave('roles', function ($q) use ($excludedRoles) {
                $q->whereIn('name', $excludedRoles);
            })->pluck('id')->toArray();

            $hrStats = PermissionRequest::whereIn('user_id', $allEmployees)
                ->whereBetween('departure_time', [$dateStart, $dateEnd])
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes
                ')
                ->first();

            $exceededLimit = DB::table(function ($query) use ($allEmployees, $dateStart, $dateEnd) {
                $query->from('permission_requests')
                    ->select('user_id', DB::raw('SUM(minutes_used) as total_minutes'))
                    ->whereIn('user_id', $allEmployees)
                    ->where('status', 'approved')
                    ->whereBetween('departure_time', [$dateStart, $dateEnd])
                    ->groupBy('user_id');
            }, 'exceeded_users')
                ->where('total_minutes', '>', 180)
                ->count();

            $mostRequested = DB::table(function ($query) use ($allEmployees, $dateStart, $dateEnd) {
                $query->from('permission_requests')
                    ->select('user_id', DB::raw('COUNT(*) as request_count'))
                    ->whereIn('user_id', $allEmployees)
                    ->whereBetween('departure_time', [$dateStart, $dateEnd])
                    ->groupBy('user_id');
                }, 'request_counts')
                    ->join('users', 'users.id', '=', 'request_counts.user_id')
                    ->select('users.name', 'request_counts.request_count')
                    ->orderByDesc('request_count')
                    ->first();

            $highestMinutes = DB::table(function ($query) use ($allEmployees, $dateStart, $dateEnd) {
                $query->from('permission_requests')
                    ->select('user_id', DB::raw('SUM(minutes_used) as total_minutes'))
                    ->whereIn('user_id', $allEmployees)
                    ->where('status', 'approved')
                    ->whereBetween('departure_time', [$dateStart, $dateEnd])
                    ->groupBy('user_id');
                }, 'minute_totals')
                    ->join('users', 'users.id', '=', 'minute_totals.user_id')
                    ->select('users.name', 'minute_totals.total_minutes')
                    ->orderByDesc('total_minutes')
                    ->first();

            $exceededEmployees = DB::table(function ($query) use ($allEmployees, $dateStart, $dateEnd) {
                $query->from('permission_requests')
                    ->select('user_id', DB::raw('SUM(minutes_used) as total_minutes'))
                    ->whereIn('user_id', $allEmployees)
                    ->where('status', 'approved')
                    ->whereBetween('departure_time', [$dateStart, $dateEnd])
                    ->groupBy('user_id')
                    ->having('total_minutes', '>', 180);
                }, 'exceeded_users')
                    ->join('users', 'users.id', '=', 'exceeded_users.user_id')
                    ->select('users.name', 'exceeded_users.total_minutes')
                    ->orderByDesc('total_minutes')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'name' => $item->name,
                            'total_minutes' => $item->total_minutes
                        ];
                    });

            $departmentStats = DB::table('users')
                ->leftJoin('permission_requests', function ($join) use ($dateStart, $dateEnd) {
                    $join->on('users.id', '=', 'permission_requests.user_id')
                        ->whereBetween('permission_requests.departure_time', [$dateStart, $dateEnd]);
                })
                ->whereIn('users.id', $allEmployees)
                ->whereNotNull('users.department')
                ->select(
                    'users.department as dept_name',
                    DB::raw('COUNT(DISTINCT users.id) as employee_count'),
                    DB::raw('COUNT(permission_requests.id) as request_count'),
                    DB::raw('SUM(CASE WHEN permission_requests.status = "approved" THEN permission_requests.minutes_used ELSE 0 END) as total_minutes'),
                    DB::raw('SUM(CASE WHEN permission_requests.returned_on_time = 1 THEN 1 ELSE 0 END) as on_time_returns'),
                    DB::raw('SUM(CASE WHEN permission_requests.returned_on_time = 2 THEN 1 ELSE 0 END) as late_returns')
                )
                ->groupBy('users.department')
                ->orderByDesc('request_count')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->dept_name ?: 'غير محدد',
                        'employee_count' => $item->employee_count,
                        'request_count' => $item->request_count,
                        'total_minutes' => $item->total_minutes ?? 0,
                        'avg_minutes' => $item->employee_count > 0 ? round(($item->total_minutes ?? 0) / $item->employee_count) : 0,
                        'on_time_returns' => $item->on_time_returns ?? 0,
                        'late_returns' => $item->late_returns ?? 0
                    ];
                });

            $dailyStats = PermissionRequest::whereIn('user_id', $allEmployees)
                ->whereBetween('departure_time', [$dateStart, $dateEnd])
                ->selectRaw('
                    DATE(departure_time) as date,
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes,
                    SUM(CASE WHEN returned_on_time = 1 THEN 1 ELSE 0 END) as on_time_returns,
                    SUM(CASE WHEN returned_on_time = 2 THEN 1 ELSE 0 END) as late_returns
                ')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'total_requests' => $item->total_requests,
                        'total_minutes' => $item->total_minutes,
                        'on_time_returns' => $item->on_time_returns,
                        'late_returns' => $item->late_returns
                    ];
                });

            $weeklyStats = PermissionRequest::whereIn('user_id', $allEmployees)
                ->whereBetween('departure_time', [$dateStart, $dateEnd])
                ->selectRaw('
                    YEAR(departure_time) as year,
                    WEEK(departure_time) as week,
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes,
                    SUM(CASE WHEN returned_on_time = 1 THEN 1 ELSE 0 END) as on_time_returns,
                    SUM(CASE WHEN returned_on_time = 2 THEN 1 ELSE 0 END) as late_returns
                ')
                ->groupBy('year', 'week')
                ->orderBy('year')
                ->orderBy('week')
                ->get()
                ->map(function ($item) {
                    $date = new \DateTime();
                    $date->setISODate($item->year, $item->week);
                    return [
                        'week_start' => $date->format('Y-m-d'),
                        'year_week' => $item->year . '-' . str_pad($item->week, 2, '0', STR_PAD_LEFT),
                        'total_requests' => $item->total_requests,
                        'total_minutes' => $item->total_minutes,
                        'on_time_returns' => $item->on_time_returns,
                        'late_returns' => $item->late_returns
                    ];
                });

            $busiestDays = PermissionRequest::whereIn('user_id', $allEmployees)
                ->whereBetween('departure_time', [$dateStart, $dateEnd])
                ->selectRaw('
                    DAYNAME(departure_time) as day_name,
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes
                ')
                ->groupBy('day_name')
                ->orderByDesc('total_requests')
                ->get()
                ->map(function ($item) {
                    return [
                        'day_name' => $item->day_name,
                        'total_requests' => $item->total_requests,
                        'total_minutes' => $item->total_minutes
                    ];
                });

            $busiestHours = PermissionRequest::whereIn('user_id', $allEmployees)
                ->whereBetween('departure_time', [$dateStart, $dateEnd])
                ->selectRaw('
                    HOUR(departure_time) as hour,
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes
                ')
                ->groupBy('hour')
                ->orderByDesc('total_requests')
                ->get()
                ->map(function ($item) {
                    return [
                        'hour' => $item->hour,
                        'hour_formatted' => sprintf('%02d:00', $item->hour),
                        'total_requests' => $item->total_requests,
                        'total_minutes' => $item->total_minutes
                    ];
                });

            $returnStatusStats = PermissionRequest::whereIn('user_id', $allEmployees)
                ->whereBetween('departure_time', [$dateStart, $dateEnd])
                ->where('status', 'approved')
                ->selectRaw('
                    SUM(CASE WHEN returned_on_time = 1 THEN 1 ELSE 0 END) as on_time_returns,
                    SUM(CASE WHEN returned_on_time = 2 THEN 1 ELSE 0 END) as late_returns,
                    SUM(CASE WHEN returned_on_time IS NULL THEN 1 ELSE 0 END) as undefined_returns,
                    COUNT(*) as total_returns
                ')
                ->first();

            $previousStart = (clone $dateStart)->subDays($dateEnd->diffInDays($dateStart) + 1);
            $previousEnd = (clone $dateStart)->subDay();

            $previousStats = PermissionRequest::whereIn('user_id', $allEmployees)
                ->whereBetween('departure_time', [$previousStart, $previousEnd])
                ->selectRaw('
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes,
                    SUM(CASE WHEN returned_on_time = 1 THEN 1 ELSE 0 END) as on_time_returns,
                    SUM(CASE WHEN returned_on_time = 2 THEN 1 ELSE 0 END) as late_returns
                ')
                ->first();

            $comparisonStats = [
                'current_period' => [
                    'start' => $dateStart->format('Y-m-d'),
                    'end' => $dateEnd->format('Y-m-d'),
                    'total_requests' => $hrStats->total ?? 0,
                    'total_minutes' => $hrStats->total_minutes ?? 0,
                    'on_time_returns' => $returnStatusStats->on_time_returns ?? 0,
                    'late_returns' => $returnStatusStats->late_returns ?? 0
                ],
                'previous_period' => [
                    'start' => $previousStart->format('Y-m-d'),
                    'end' => $previousEnd->format('Y-m-d'),
                    'total_requests' => $previousStats->total_requests ?? 0,
                    'total_minutes' => $previousStats->total_minutes ?? 0,
                    'on_time_returns' => $previousStats->on_time_returns ?? 0,
                    'late_returns' => $previousStats->late_returns ?? 0
                ],
                'percentage_change' => [
                    'total_requests' => $previousStats->total_requests > 0
                        ? round((($hrStats->total - $previousStats->total_requests) / $previousStats->total_requests) * 100, 2)
                        : 100,
                    'total_minutes' => $previousStats->total_minutes > 0
                        ? round((($hrStats->total_minutes - $previousStats->total_minutes) / $previousStats->total_minutes) * 100, 2)
                        : 100,
                    'on_time_returns' => $previousStats->on_time_returns > 0
                        ? round((($returnStatusStats->on_time_returns - $previousStats->on_time_returns) / $previousStats->on_time_returns) * 100, 2)
                        : 100,
                    'late_returns' => $previousStats->late_returns > 0
                        ? round((($returnStatusStats->late_returns - $previousStats->late_returns) / $previousStats->late_returns) * 100, 2)
                        : 100
                ]
            ];

            $statistics['hr'] = [
                'total_requests' => $hrStats->total ?? 0,
                'approved_requests' => $hrStats->approved ?? 0,
                'rejected_requests' => $hrStats->rejected ?? 0,
                'pending_requests' => $hrStats->pending ?? 0,
                'total_minutes' => $hrStats->total_minutes ?? 0,
                'employees_exceeded_limit' => $exceededLimit,
                'most_requested_employee' => $mostRequested ? [
                    'name' => $mostRequested->name,
                    'count' => $mostRequested->request_count
                ] : null,
                'highest_minutes_employee' => $highestMinutes ? [
                    'name' => $highestMinutes->name,
                    'minutes' => $highestMinutes->total_minutes
                ] : null,
                'exceeded_employees' => $exceededEmployees,
                'departments' => $departmentStats,
                'daily_stats' => $dailyStats,
                'weekly_stats' => $weeklyStats,
                'return_status_stats' => [
                    'on_time_returns' => $returnStatusStats->on_time_returns ?? 0,
                    'late_returns' => $returnStatusStats->late_returns ?? 0,
                    'undefined_returns' => $returnStatusStats->undefined_returns ?? 0,
                    'total_returns' => $returnStatusStats->total_returns ?? 0
                ],
                'busiest_days' => $busiestDays,
                'busiest_hours' => $busiestHours,
                'comparison_with_previous' => $comparisonStats
            ];

            $monthlyTrend = PermissionRequest::whereIn('user_id', $allEmployees)
                ->whereBetween('departure_time', [$dateStart, $dateEnd])
                ->selectRaw('
                    DATE_FORMAT(departure_time, "%Y-%m") as month,
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes
                ')
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $statistics['monthly_trend'] = $monthlyTrend->map(function ($item) {
                return [
                    'month' => $item->month,
                    'total_requests' => $item->total_requests,
                    'total_minutes' => $item->total_minutes,
                ];
            });
        }

        return $statistics;
    }

    private function getAllowedRoles($user)
    {
        if ($user->hasRole('team_leader')) {
            return ['employee'];
        } elseif ($user->hasRole('department_manager')) {
            return ['employee', 'team_leader'];
        } elseif ($user->hasRole('company_manager')) {
            return ['employee', 'team_leader', 'department_manager'];
        } elseif ($user->hasRole('hr')) {
            return ['employee', 'team_leader', 'department_manager'];
        }
        return [];
    }

    private function getTeamMembers($team, $allowedRoles)
    {
        return $team->users()
            ->select('users.id')
            ->whereHas('roles', function ($q) use ($allowedRoles) {
                $q->whereIn('name', $allowedRoles);
            })
            ->pluck('users.id')
            ->toArray();
    }
}
