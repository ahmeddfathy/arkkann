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
        $user = Auth::user();
        $employeeName = $request->input('employee_name');
        $status = $request->input('status');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // تحديد بداية ونهاية الشهر (26 من الشهر السابق إلى 25 من الشهر الحالي)
        $now = now();
        $currentMonthStart = $now->day >= 26
            ? $now->copy()->startOfDay()->setDay(26)
            : $now->copy()->subMonth()->startOfDay()->setDay(26);

        $currentMonthEnd = $now->day >= 26
            ? $now->copy()->addMonth()->startOfDay()->setDay(25)->endOfDay()
            : $now->copy()->startOfDay()->setDay(25)->endOfDay();

        // تحديد بداية ونهاية الفترة
        $dateStart = $fromDate ? Carbon::parse($fromDate)->startOfDay() : $currentMonthStart;
        $dateEnd = $toDate ? Carbon::parse($toDate)->endOfDay() : $currentMonthEnd;

        // جلب طلبات المستخدم الحالي مع تطبيق الفلترة
        $myRequestsQuery = PermissionRequest::with('user')
            ->where('user_id', $user->id);

        if ($status) {
            $myRequestsQuery->where('status', $status);
        }

        if ($fromDate && $toDate) {
            $myRequestsQuery->whereBetween('departure_time', [$dateStart, $dateEnd]);
        }

        $myRequests = $myRequestsQuery->latest()->paginate(10);

        // حساب الدقائق المستخدمة في الفترة المحددة
        $totalUsedMinutes = PermissionRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('departure_time', [$dateStart, $dateEnd])
            ->sum('minutes_used');

        // حساب الدقائق المستخدمة لكل عضو في الفريق
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

        // جلب طلبات الفريق للمدراء و HR
        $teamRequests = PermissionRequest::where('id', 0)->paginate(10); // قيمة افتراضية فارغة
        $noTeamRequests = PermissionRequest::where('id', 0)->paginate(10); // قيمة افتراضية فارغة
        $remainingMinutes = [];

        if ($user->hasRole('hr')) {
            // جلب طلبات الموظفين الذين ليس لديهم فريق
            $teamQuery = PermissionRequest::with(['user', 'violations'])
                ->whereHas('user', function ($q) {
                    $q->whereHas('teams');
                });

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

            $teamRequests = $teamQuery->latest()->paginate(10);

            // جلب طلبات الموظفين الذين ليس لديهم فريق في جدول منفصل
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

            // حساب الدقائق المتبقية للموظفين في الفرق
            $teamUserIds = $teamRequests->pluck('user_id')->unique();
            foreach ($teamUserIds as $userId) {
                $remainingMinutes[$userId] = $this->permissionRequestService->getRemainingMinutes($userId);
            }
        } elseif ($user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
            // جلب طلبات الفريق للمدراء
            $team = $user->currentTeam;
            if ($team) {
                // تحديد الأدوار التي يمكن للمستخدم الحالي رؤية طلباتها
                $allowedRoles = [];
                if ($user->hasRole('team_leader')) {
                    $allowedRoles = ['employee']; // Team Leader يرى طلبات الموظفين فقط
                } elseif ($user->hasRole('department_manager')) {
                    $allowedRoles = ['employee', 'team_leader']; // Department Manager يرى طلبات الموظفين و Team Leaders
                } elseif ($user->hasRole('company_manager')) {
                    $allowedRoles = ['employee', 'team_leader', 'department_manager']; // Company Manager يرى الجميع عدا HR
                }

                $teamMembers = $this->permissionRequestService->getAllowedUsers($user)
                    ->pluck('id')
                    ->toArray();

                $query = PermissionRequest::with(['user', 'violations'])
                    ->whereIn('user_id', $teamMembers);

                if ($employeeName) {
                    $query->whereHas('user', function ($q) use ($employeeName) {
                        $q->where('name', 'like', "%{$employeeName}%");
                    });
                }

                if ($status) {
                    $query->where('status', $status);
                }

                if ($fromDate && $toDate) {
                    $query->whereBetween('departure_time', [$dateStart, $dateEnd]);
                }

                $teamRequests = $query->latest()->paginate(10);

                // حساب الدقائق المتبقية لأعضاء الفريق
                foreach ($teamMembers as $userId) {
                    $remainingMinutes[$userId] = $this->permissionRequestService->getRemainingMinutes($userId);
                }
            }
        }

        // جلب قائمة المستخدمين للبحث
        if (Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr'])) {
            $users = $this->permissionRequestService->getAllowedUsers(Auth::user());
        } else {
            $users = User::when($user->hasRole('hr'), function ($query) {
                // HR يرى فقط المستخدمين الذين ليس لديهم فريق
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
            'statistics'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'employee' && $user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'departure_time' => 'required|date|after:now',
            'return_time' => 'required|date|after:departure_time',
            'reason' => 'required|string|max:255',
            'user_id' => 'required_if:role,manager|exists:users,id|nullable'
        ]);

        if ($user->role === 'manager' && $request->input('user_id') && $request->input('user_id') !== $user->id) {
            $result = $this->permissionRequestService->createRequestForUser($validated['user_id'], $validated);
        } else {
            $result = $this->permissionRequestService->createRequest($validated);
        }

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        // إضافة معلومات الدقائق المستخدمة للرسالة
        $message = 'Permission request submitted successfully.';
        if (isset($result['used_minutes'])) {
            $message .= " Total minutes used this month: {$result['used_minutes']} minutes.";
            if (isset($result['remaining_minutes']) && $result['remaining_minutes'] > 0) {
                $message .= " Remaining minutes: {$result['remaining_minutes']} minutes.";
            }
        }

        return redirect()->route('permission-requests.index')
            ->with('success', $message);
    }

    public function resetStatus(PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $this->permissionRequestService->resetStatus($permissionRequest);

        return redirect()->route('permission-requests.index')
            ->with('success', 'Request status reset to pending successfully.');
    }

    public function modifyResponse(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255'
        ]);

        $this->permissionRequestService->modifyResponse($permissionRequest, $validated);

        return redirect()->route('permission-requests.index')
            ->with('success', 'Response modified successfully.');
    }

    public function update(Request $request, PermissionRequest $permissionRequest)
    {
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

        // إضافة معلومات الدقائق المستخدمة للرسالة
        $message = 'Permission request updated successfully.';
        if (isset($result['used_minutes'])) {
            $message .= " Total minutes used this month: {$result['used_minutes']} minutes.";
            if (isset($result['remaining_minutes']) && $result['remaining_minutes'] > 0) {
                $message .= " Remaining minutes: {$result['remaining_minutes']} minutes.";
            }
        }

        return redirect()->route('permission-requests.index')
            ->with('success', $message);
    }

    public function destroy(PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager' && $user->id !== $permissionRequest->user_id) {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $this->permissionRequestService->deleteRequest($permissionRequest);

        return redirect()->route('permission-requests.index')
            ->with('success', 'Permission request deleted successfully.');
    }

    public function updateStatus(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        // التحقق من الصلاحيات
        if ($user->hasRole('team_leader') && !$user->hasPermissionTo('manager_respond_permission_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات الاستئذان');
        }

        if ($user->hasRole('hr') && !$user->hasPermissionTo('hr_respond_permission_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات الاستئذان');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected',
            'response_type' => 'required|in:manager,hr'
        ]);

        // التحقق من نوع الرد وتحديث الحالة
        if ($validated['response_type'] === 'manager' && $user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
            $permissionRequest->manager_status = $validated['status'];
            $permissionRequest->manager_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
        } elseif ($validated['response_type'] === 'hr' && $user->hasRole('hr')) {
            $permissionRequest->hr_status = $validated['status'];
            $permissionRequest->hr_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
        } else {
            return redirect()->back()->with('error', 'نوع الرد غير صحيح');
        }

        // تحديث الحالة النهائية
        $permissionRequest->updateFinalStatus();
        $permissionRequest->save();

        return redirect()->back()->with('success', 'تم تحديث حالة الطلب بنجاح');
    }

    public function updateReturnStatus(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        // التحقق من الصلاحيات
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
            $maxReturnTime = $returnTime->copy()->addMinutes(10);
            $endOfWorkDay = Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0); // 4:00 PM

            // إذا كان وقت العودة 4 عصراً أو بعدها، نسجل العودة تلقائياً
            if ($returnTime->gte($endOfWorkDay)) {
                $permissionRequest->returned_on_time = 1; // تسجيل كـ "رجع"
                $permissionRequest->save();

                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل العودة تلقائياً لانتهاء يوم العمل'
                ]);
            }

            // التحقق من الوقت فقط إذا كان الإجراء هو تسجيل العودة (1)
            if ($validated['return_status'] == 1) {
                if ($now->gt($maxReturnTime)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'لقد تجاوزت الوقت المسموح به للعودة'
                    ]);
                }
            }

            // تحديث حالة العودة
            $permissionRequest->returned_on_time = (int)$validated['return_status'];

            // إذا تجاوز وقت العودة المسموح به ولم يسجل عودته (فقط للطلبات التي تنتهي قبل 4 عصراً)
            if ($returnTime->lt($endOfWorkDay) && $now->gt($maxReturnTime) && $permissionRequest->returned_on_time === null) {
                $permissionRequest->returned_on_time = 2; // تسجيل كـ "لم يرجع"
            }

            $permissionRequest->save();

            // إدارة المخالفات
            if ($permissionRequest->returned_on_time == 2) {
                // إضافة مخالفة إذا تم تحديد أن الموظف لم يرجع
                Violation::create([
                    'user_id' => $permissionRequest->user_id,
                    'permission_requests_id' => $permissionRequest->id,
                    'reason' => 'عدم العودة من الاستئذان في الوقت المحدد',
                    'manager_mistake' => false
                ]);
            } else {
                // حذف المخالفة إذا كانت موجودة
                Violation::where('permission_requests_id', $permissionRequest->id)
                        ->where('reason', 'عدم العودة من الاستئذان في الوقت المحدد')
                        ->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة العودة بنجاح'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in updateReturnStatus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة العودة'
            ], 500);
        }
    }

    public function updateHrStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $permissionRequest = PermissionRequest::findOrFail($id);
            $user = Auth::user();

            // التحقق من الصلاحيات
            if (!$user->hasRole('hr') || !$user->hasPermissionTo('hr_respond_permission_request')) {
                return back()->with('error', 'Unauthorized action.');
            }

            // تحديث حالة الطلب
            $permissionRequest->updateHrStatus(
                $request->status,
                $request->status === 'rejected' ? $request->rejection_reason : null
            );

            // إضافة إشعار تحديث حالة HR
            $this->notificationService->notifyHRStatusUpdate($permissionRequest);

            return back()->with('success', 'تم تحديث الرد بنجاح');
        } catch (\Exception $e) {
            \Log::error('Error in updateHrStatus: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while updating the status.');
        }
    }

    public function modifyHrStatus(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

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

        // إضافة إشعار تعديل رد HR
        $this->notificationService->notifyHRStatusUpdate($permissionRequest);

        return redirect()->back()->with('success', 'تم تعديل الرد بنجاح');
    }

    public function resetHrStatus(PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if (!$user->hasRole('hr') || !$user->hasPermissionTo('hr_respond_permission_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية إعادة تعيين الرد على طلبات الاستئذان');
        }

        try {
            $permissionRequest->hr_status = 'pending';
            $permissionRequest->hr_rejection_reason = null;
            $permissionRequest->updateFinalStatus();
            $permissionRequest->save();

            // استخدام دالة إشعار الريست بدلاً من الإشعار العادي
            $this->notificationService->notifyStatusReset($permissionRequest, 'hr');

            return redirect()->back()->with('success', 'تم إعادة تعيين الرد بنجاح');
        } catch (\Exception $e) {
            \Log::error('Error in resetHrStatus: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ أثناء إعادة تعيين الرد');
        }
    }

    public function updateManagerStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $permissionRequest = PermissionRequest::findOrFail($id);
            $user = Auth::user();

            // التحقق من الصلاحيات
            if (
                !$user->hasRole(['team_leader', 'department_manager', 'company_manager']) ||
                !$user->hasPermissionTo('manager_respond_permission_request')
            ) {
                return back()->with('error', 'Unauthorized action.');
            }

            // تحديث حالة الطلب
            $permissionRequest->updateManagerStatus(
                $request->status,
                $request->status === 'rejected' ? $request->rejection_reason : null
            );

            // إضافة إشعار تحديث حالة المدير
            $this->notificationService->notifyManagerStatusUpdate($permissionRequest);

            return back()->with('success', 'تم تحديث الرد بنجاح');
        } catch (\Exception $e) {
            \Log::error('Error in updateManagerStatus: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while updating the status.');
        }
    }

    public function resetManagerStatus(PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if (
            !$user->hasRole(['team_leader', 'department_manager', 'company_manager']) ||
            !$user->hasPermissionTo('manager_respond_permission_request')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        try {
            $permissionRequest->manager_status = 'pending';
            $permissionRequest->manager_rejection_reason = null;
            $permissionRequest->updateFinalStatus();
            $permissionRequest->save();

            // استخدام دالة إشعار الريست بدلاً من الإشعار العادي
            $this->notificationService->notifyStatusReset($permissionRequest, 'manager');

            return response()->json([
                'success' => true,
                'message' => 'تم إعادة تعيين رد المدير بنجاح'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in resetManagerStatus: ' . $e->getMessage());
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

            // إضافة إشعار تعديل رد المدير
            $this->notificationService->notifyManagerStatusUpdate($permissionRequest);

            return back()->with('success', 'تم تعديل الرد بنجاح');
        } catch (\Exception $e) {
            \Log::error('Error in modifyManagerStatus: ' . $e->getMessage());
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
            ],
            'monthly_trend' => [],
        ];

        // إحصائيات الطلبات الشخصية
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

        // إحصائيات الفريق (للمدراء و HR المالكين أو الأدمن في فرق)
        if (
            $user->hasRole(['team_leader', 'department_manager', 'company_manager']) ||
            ($user->hasRole('hr') && ($user->ownedTeams->count() > 0 || $user->teams()->wherePivot('role', 'admin')->exists()))
        ) {

            $teams = collect();

            if ($user->hasRole('hr')) {
                // جمع الفرق التي يملكها HR أو أدمن فيها
                $teams = $user->ownedTeams->merge(
                    $user->teams()->wherePivot('role', 'admin')->get()
                );
            } else {
                // للمدراء الآخرين، استخدم الفريق الحالي
                $teams = $user->currentTeam ? collect([$user->currentTeam]) : collect();
            }

            foreach ($teams as $team) {
                // تحديد الأعضاء المسموح برؤية طلباتهم
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

                // الموظفين الذين تجاوزوا الحد
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

                // الموظف الأكثر طلباً للاستئذان
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

                // الموظف الأكثر استخداماً للدقائق
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

                // تفاصيل الموظفين المتجاوزين للحد في الفريق
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

                // اتجاه الطلبات الشهري
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

        // إحصائيات HR
        if ($user->hasRole('hr')) {
            // استثناء المستخدمين الذين لديهم أدوار معينة
            $excludedRoles = ['company_manager', 'hr'];

            $allEmployees = User::whereDoesntHave('roles', function ($q) use ($excludedRoles) {
                $q->whereIn('name', $excludedRoles);
            })->pluck('id')->toArray();

            // الإحصائيات العامة
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

            // الموظفين الذين تجاوزوا الحد
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

            // الموظف الأكثر طلباً للاستئذان
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

            // الموظف الأكثر استخداماً للدقائق
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

            // تفاصيل الموظفين المتجاوزين للحد
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
            ];

            // اتجاه الطلبات الشهري لجميع الموظفين
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
