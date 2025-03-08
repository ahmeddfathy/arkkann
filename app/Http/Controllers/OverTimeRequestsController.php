<?php

namespace App\Http\Controllers;

use App\Models\OverTimeRequests;
use App\Services\OverTimeRequestService;
use App\Services\NotificationOvertimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;

class OverTimeRequestsController extends Controller
{
    protected $overTimeRequestService;
    protected $notificationService;

    public function __construct(
        OverTimeRequestService $overTimeRequestService,
        NotificationOvertimeService $notificationService
    ) {
        $this->overTimeRequestService = $overTimeRequestService;
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
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

        $filters = [
            'employeeName' => $employeeName,
            'status' => $status,
            'startDate' => $dateStart,
            'endDate' => $dateEnd
        ];

        $canCreateOvertime = $user->hasPermissionTo('create_overtime');
        $canUpdateOvertime = $user->hasPermissionTo('update_overtime');
        $canDeleteOvertime = $user->hasPermissionTo('delete_overtime');
        $canRespondAsManager = $user->hasPermissionTo('manager_respond_overtime_request') && !$user->hasRole('hr');
        $canRespondAsHR = $user->hasPermissionTo('hr_respond_overtime_request');

        $myRequests = $this->overTimeRequestService->getUserRequests(
            $user->id,
            $dateStart,
            $dateEnd,
            $status
        );

        $teamRequests = collect([]);
        $noTeamRequests = collect([]);
        $hrRequests = collect([]);
        $users = collect([]);
        $pendingCount = 0;
        $overtimeHoursCount = [];
        $noTeamOvertimeHoursCount = [];

        if ($user->hasRole('hr')) {
            // طلبات الموظفين بدون فريق - لموظفي HR فقط
            $noTeamRequests = $this->overTimeRequestService->getNoTeamRequests(
                $employeeName,
                $status,
                $dateStart,
                $dateEnd
            );

            // طلبات موظفي الشركه - لموظفي HR فقط
            $hrQuery = OverTimeRequests::with('user')
                ->whereHas('user', function ($query) {
                    $query->whereDoesntHave('roles', function ($q) {
                        $q->whereIn('name', ['hr', 'company_manager']);
                    });
                })
                ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                ->when($employeeName, function ($query) use ($employeeName) {
                    $query->whereHas('user', function ($q) use ($employeeName) {
                        $q->where('name', 'like', "%{$employeeName}%");
                    });
                })
                ->when($status, function ($query) use ($status) {
                    $query->where('status', $status);
                });

            $hrRequests = $hrQuery->latest()->paginate(10, ['*'], 'hr_page');

            // طلبات الفريق - للفريق الذي يديره المستخدم (إذا كان لديه فريق)
            if ($user->currentTeam) {
                $teamMembers = $user->currentTeam->users->pluck('id')->toArray();

                $teamQuery = OverTimeRequests::with('user')
                    ->whereIn('user_id', $teamMembers)
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->when($employeeName, function ($query) use ($employeeName) {
                        $query->whereHas('user', function ($q) use ($employeeName) {
                            $q->where('name', 'like', "%{$employeeName}%");
                        });
                    })
                    ->when($status, function ($query) use ($status) {
                        $query->where('status', $status);
                    });

                $pendingCount = $teamQuery->clone()->where('status', 'pending')->count();
                $teamRequests = $teamQuery->latest()->paginate(10, ['*'], 'team_page');
            }

            $users = User::select('id', 'name')->get();
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

                $teamMembers = $team->users()
                    ->whereHas('roles', function ($q) use ($allowedRoles) {
                        $q->whereIn('name', $allowedRoles);
                    })
                    ->whereDoesntHave('teams', function ($q) use ($team) {
                        $q->where('teams.id', $team->id)
                            ->where(function ($q) {
                                $q->where('team_user.role', 'owner')
                                    ->orWhere('team_user.role', 'admin');
                            });
                    })
                    ->pluck('users.id')
                    ->toArray();

                $teamRequests = OverTimeRequests::query()
                    ->with('user')
                    ->whereIn('user_id', $teamMembers)
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->whereHas('user', function ($query) use ($team) {
                        $query->whereDoesntHave('teams', function ($q) use ($team) {
                            $q->where('teams.id', $team->id)
                                ->whereRaw('team_user.role = ?', ['admin']);
                        });
                    })
                    ->when($employeeName, function ($query) use ($employeeName) {
                        $query->whereHas('user', function ($q) use ($employeeName) {
                            $q->where('name', 'like', "%{$employeeName}%");
                        });
                    })
                    ->when($status, function ($query) use ($status) {
                        $query->where('status', $status);
                    });

                $pendingCount = $teamRequests->clone()->where('status', 'pending')->count();
                $teamRequests = $teamRequests->latest()->paginate(10);
                $users = $this->overTimeRequestService->getAllowedUsers(Auth::user());
            }
        }

        $myOvertimeHours = $this->overTimeRequestService->calculateOvertimeHours(
            $user->id,
            $dateStart,
            $dateEnd,
            $status
        );

        $statistics = [];
        $personalStatistics = [];
        $teamStatistics = [];
        $hrStatistics = [];

        $personalStatistics = [
            'total_requests' => OverTimeRequests::where('user_id', $user->id)
                ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                ->count(),
            'approved_requests' => OverTimeRequests::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                ->count(),
            'pending_requests' => OverTimeRequests::where('user_id', $user->id)
                ->where('status', 'pending')
                ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                ->count(),
            'rejected_requests' => OverTimeRequests::where('user_id', $user->id)
                ->where('status', 'rejected')
                ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                ->count(),
            'total_hours' => OverTimeRequests::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))/3600), 0) as total_hours')
                ->value('total_hours')
        ];

        if ($user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
            $teamStatistics = [
                'total_requests' => OverTimeRequests::whereIn('user_id', $users->pluck('id'))
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->when($status, function ($query) use ($status) {
                        return $query->where('status', $status);
                    })
                    ->count(),
                'approved_requests' => OverTimeRequests::whereIn('user_id', $users->pluck('id'))
                    ->where('status', 'approved')
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->count(),
                'pending_requests' => OverTimeRequests::whereIn('user_id', $users->pluck('id'))
                    ->where('status', 'pending')
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->count(),
                'total_hours' => OverTimeRequests::whereIn('user_id', $users->pluck('id'))
                    ->where('status', 'approved')
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))/3600), 0) as total_hours')
                    ->value('total_hours'),
                'team_employees' => User::select('users.id', 'users.name')
                    ->selectRaw('COUNT(DISTINCT over_time_requests.id) as total_requests')
                    ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "approved" THEN 1 END) as approved_requests')
                    ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "rejected" THEN 1 END) as rejected_requests')
                    ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "pending" THEN 1 END) as pending_requests')
                    ->selectRaw('COALESCE(SUM(CASE WHEN over_time_requests.status = "approved" THEN TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600 ELSE 0 END), 0) as approved_hours')
                    ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600), 0) as total_requested_hours')
                    ->leftJoin('over_time_requests', function ($join) use ($dateStart, $dateEnd) {
                        $join->on('users.id', '=', 'over_time_requests.user_id')
                            ->whereBetween('over_time_requests.overtime_date', [$dateStart, $dateEnd]);
                    })
                    ->whereIn('users.id', $users->pluck('id'))
                    ->groupBy('users.id', 'users.name')
                    ->get(),
                'most_active_employee' => User::whereIn('id', $users->pluck('id'))
                    ->withCount(['overtimeRequests' => function ($query) use ($dateStart, $dateEnd) {
                        $query->whereBetween('overtime_date', [$dateStart, $dateEnd]);
                    }])
                    ->orderByDesc('overtime_requests_count')
                    ->first()
            ];
        }

        if ($user->hasRole('hr')) {
            $hrStatistics = [
                'total_company_requests' => OverTimeRequests::whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->when($status, function ($query) use ($status) {
                        return $query->where('status', $status);
                    })
                    ->count(),
                'total_approved_hours' => OverTimeRequests::where('status', 'approved')
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))/3600), 0) as total_hours')
                    ->value('total_hours'),
                'pending_requests' => OverTimeRequests::where('status', 'pending')
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->count(),
                'departments_stats' => User::select('department')
                    ->selectRaw('COUNT(DISTINCT users.id) as total_employees')
                    ->selectRaw('COUNT(DISTINCT over_time_requests.id) as total_requests')
                    ->selectRaw('COALESCE(SUM(CASE WHEN over_time_requests.status = "approved" THEN TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600 ELSE 0 END), 0) as total_hours')
                    ->leftJoin('over_time_requests', function ($join) use ($dateStart, $dateEnd, $status) {
                        $join->on('users.id', '=', 'over_time_requests.user_id')
                            ->whereBetween('over_time_requests.overtime_date', [$dateStart, $dateEnd])
                            ->when($status, function ($query) use ($status) {
                                return $query->where('over_time_requests.status', $status);
                            });
                    })
                    ->whereNotNull('department')
                    ->groupBy('department')
                    ->get(),
                'department_employees' => User::select('users.id', 'users.name', 'users.department')
                    ->selectRaw('COUNT(DISTINCT over_time_requests.id) as total_requests')
                    ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "approved" THEN 1 END) as approved_requests')
                    ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "rejected" THEN 1 END) as rejected_requests')
                    ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "pending" THEN 1 END) as pending_requests')
                    ->selectRaw('COALESCE(SUM(CASE WHEN over_time_requests.status = "approved" THEN TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600 ELSE 0 END), 0) as approved_hours')
                    ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600), 0) as total_requested_hours')
                    ->leftJoin('over_time_requests', function ($join) use ($dateStart, $dateEnd) {
                        $join->on('users.id', '=', 'over_time_requests.user_id')
                            ->whereBetween('over_time_requests.overtime_date', [$dateStart, $dateEnd]);
                    })
                    ->whereNotNull('department')
                    ->groupBy('users.id', 'users.name', 'users.department')
                    ->get()
                    ->groupBy('department')
            ];
        } else {
            $hrStatistics = [];
        }

        return view('overtime-requests.index', compact(
            'myRequests',
            'teamRequests',
            'noTeamRequests',
            'hrRequests',
            'users',
            'canCreateOvertime',
            'canUpdateOvertime',
            'canDeleteOvertime',
            'canRespondAsManager',
            'canRespondAsHR',
            'myOvertimeHours',
            'overtimeHoursCount',
            'noTeamOvertimeHoursCount',
            'pendingCount',
            'filters',
            'currentMonthStart',
            'currentMonthEnd',
            'dateStart',
            'dateEnd',
            'personalStatistics',
            'teamStatistics',
            'hrStatistics'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'overtime_date' => 'required|date|after:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'required|string|max:255',
            'user_id' => 'sometimes|exists:users,id'
        ]);

        try {
            $this->overTimeRequestService->createRequest($request->all());
            return redirect()->route('overtime-requests.index')->with('success', 'Overtime request created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'overtime_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'required|string|max:255'
        ]);

        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);
            $this->overTimeRequestService->update($overtimeRequest, $request->all());
            return redirect()->route('overtime-requests.index')->with('success', 'Overtime request updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);
            $this->overTimeRequestService->deleteRequest($overtimeRequest);

            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Overtime request deleted successfully.']);
            }
            return redirect()->route('overtime-requests.index')->with('success', 'Overtime request deleted successfully.');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateManagerStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);
            $this->overTimeRequestService->updateManagerStatus($overtimeRequest, $request->all());
            return back()->with('success', 'Response submitted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateHrStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);
            $this->overTimeRequestService->updateHrStatus($overtimeRequest, $request->all());
            return back()->with('success', 'Response submitted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function resetManagerStatus($id)
    {
        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);
            $overtimeRequest->manager_status = 'pending';
            $overtimeRequest->manager_rejection_reason = null;
            $overtimeRequest->updateFinalStatus();
            $overtimeRequest->save();
            return back()->with('success', 'Status reset successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function resetHrStatus($id)
    {
        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);
            $overtimeRequest->hr_status = 'pending';
            $overtimeRequest->hr_rejection_reason = null;
            $overtimeRequest->updateFinalStatus();
            $overtimeRequest->save();
            return back()->with('success', 'Status reset successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function modifyManagerStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);

            if (!Auth::user()->hasPermissionTo('manager_respond_overtime_request')) {
                return back()->with('error', 'Unauthorized action.');
            }

            $overtimeRequest->manager_status = $request->status;
            $overtimeRequest->manager_rejection_reason = $request->status === 'rejected' ? $request->rejection_reason : null;
            $overtimeRequest->updateFinalStatus();
            $overtimeRequest->save();

            $this->notificationService->notifyStatusUpdate($overtimeRequest);

            return back()->with('success', 'Manager response updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function modifyHrStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);

            if (!Auth::user()->hasPermissionTo('hr_respond_overtime_request')) {
                return back()->with('error', 'Unauthorized action.');
            }

            $overtimeRequest->hr_status = $request->status;
            $overtimeRequest->hr_rejection_reason = $request->status === 'rejected' ? $request->rejection_reason : null;
            $overtimeRequest->updateFinalStatus();
            $overtimeRequest->save();

            $this->notificationService->notifyStatusUpdate($overtimeRequest);

            return back()->with('success', 'HR response updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateStatus(Request $request, OverTimeRequests $overtimeRequest)
    {
        $user = Auth::user();

        if ($user->hasRole('team_leader') && !$user->hasPermissionTo('manager_respond_overtime_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات العمل الإضافي');
        }

        if ($user->hasRole('hr') && !$user->hasPermissionTo('hr_respond_overtime_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات العمل الإضافي');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected',
            'response_type' => 'required|in:manager,hr'
        ]);

        try {
            $this->overTimeRequestService->updateStatus($overtimeRequest, $validated);
            return back()->with('success', 'تم تحديث حالة الطلب بنجاح');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
