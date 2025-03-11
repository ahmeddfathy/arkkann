<?php

namespace App\Http\Controllers;

use App\Models\AbsenceRequest;
use App\Services\AbsenceRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AbsenceRequestController extends Controller
{
    protected $absenceRequestService;

    public function __construct(AbsenceRequestService $absenceRequestService)
    {
        $this->absenceRequestService = $absenceRequestService;
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

        $canCreateAbsence = $user->hasPermissionTo('create_absence');
        $canUpdateAbsence = $user->hasPermissionTo('update_absence');
        $canDeleteAbsence = $user->hasPermissionTo('delete_absence');
        $canRespondAsManager = $user->hasPermissionTo('manager_respond_absence_request');
        $canRespondAsHR = $user->hasPermissionTo('hr_respond_absence_request');

        $statistics = $this->getStatistics($user, $dateStart, $dateEnd);

        $users = collect();
        if ($user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
            $managedTeams = $user->ownedTeams;
            foreach ($managedTeams as $team) {
                $teamMembers = $this->getTeamMembers($team, $this->getAllowedRoles($user));
                if (!empty($teamMembers)) {
                    $users = $users->concat(User::whereIn('id', $teamMembers)->get());
            }
            }
            $users = $users->unique('id');
        } elseif ($user->hasRole('hr')) {
            $users = User::whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['company_manager', 'hr']);
            })->get();
        } else {
            $users = collect([$user]);
        }

        $baseQuery = AbsenceRequest::with('user')
            ->whereBetween('absence_date', [$dateStart, $dateEnd])
            ->when($status, function ($q) use ($status) {
                return $q->where('status', $status);
            })
            ->when($employeeName, function ($q) use ($employeeName) {
                return $q->whereHas('user', function ($q) use ($employeeName) {
                    $q->where('name', 'like', "%{$employeeName}%");
                });
            });

        $myRequests = (clone $baseQuery)
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10, ['*'], 'my_page');

        $teamRequests = collect();
        $noTeamRequests = collect();
        $hrRequests = collect();

        if ($user->hasRole('hr')) {
            // طلبات موظفي الشركه - لموظفي HR فقط
            $hrRequests = (clone $baseQuery)
                ->where(function ($query) use ($user) {
                    $query->whereHas('user', function ($q) use ($user) {
                        $q->whereHas('teams');
                    })
                        ->orWhereHas('user', function ($q) use ($user) {
                        $q->whereDoesntHave('roles', function ($q) {
                                    $q->whereIn('name', ['hr', 'company_manager']);
                                });
                        });
                })
                ->latest()
                ->paginate(10, ['*'], 'hr_page');

            $noTeamRequests = (clone $baseQuery)
                ->whereHas('user', function ($q) use ($user) {
                    $q->whereDoesntHave('teams')
                        ->whereDoesntHave('roles', function ($q) {
                            $q->whereIn('name', ['hr', 'company_manager']);
                        });
                })
                ->latest()
                ->paginate(10, ['*'], 'no_team_page');

            // طلبات الفريق - للفريق الذي يديره المستخدم (إذا كان لديه فريق)
            if ($user->currentTeam) {
                $teamMembers = $user->currentTeam->users->pluck('id')->toArray();

                $teamRequests = (clone $baseQuery)
                    ->whereIn('user_id', $teamMembers)
                    ->latest()
                    ->paginate(10, ['*'], 'team_page');
            }
        } elseif ($user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
            $team = $user->currentTeam;
            if ($team) {
                $allowedRoles = $this->getAllowedRoles($user);

                $teamMembers = $team->users()
                    ->whereDoesntHave('teams', function ($q) use ($team) {
                        $q->where('teams.id', $team->id)
                            ->where(function ($q) {
                                $q->where('team_user.role', 'owner')
                                    ->orWhere('team_user.role', 'admin');
                            });
                    })
                    ->pluck('users.id')
                    ->toArray();

                if (!empty($teamMembers)) {
                    $teamRequests = (clone $baseQuery)
                        ->whereIn('user_id', $teamMembers)
                        ->latest()
                        ->paginate(10, ['*'], 'team_page');
                }
            }
        }

        $myAbsenceDays = AbsenceRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('absence_date', [$dateStart, $dateEnd])
            ->count();

        $absenceDaysCount = [];
        $noTeamAbsenceDaysCount = [];
        $hrAbsenceDaysCount = [];

        if ($teamRequests instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            foreach ($teamRequests->items() as $request) {
                if (!isset($absenceDaysCount[$request->user_id])) {
                    $absenceDaysCount[$request->user_id] = AbsenceRequest::where('user_id', $request->user_id)
                        ->where('status', 'approved')
                        ->whereBetween('absence_date', [$dateStart, $dateEnd])
                        ->count();
                }
            }
        }

        if ($noTeamRequests instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            foreach ($noTeamRequests->items() as $request) {
                if (!isset($noTeamAbsenceDaysCount[$request->user_id])) {
                    $noTeamAbsenceDaysCount[$request->user_id] = AbsenceRequest::where('user_id', $request->user_id)
                        ->where('status', 'approved')
                        ->whereBetween('absence_date', [$dateStart, $dateEnd])
                        ->count();
                }
            }
        }

        if ($hrRequests instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            foreach ($hrRequests->items() as $request) {
                if (!isset($hrAbsenceDaysCount[$request->user_id])) {
                    $hrAbsenceDaysCount[$request->user_id] = AbsenceRequest::where('user_id', $request->user_id)
                        ->where('status', 'approved')
                        ->whereBetween('absence_date', [$dateStart, $dateEnd])
                        ->count();
                }
            }
        }

        return view('absence-requests.index', compact(
            'myRequests',
            'teamRequests',
            'noTeamRequests',
            'hrRequests',
            'users',
            'statistics',
            'dateStart',
            'dateEnd',
            'currentMonthStart',
            'currentMonthEnd',
            'canCreateAbsence',
            'canUpdateAbsence',
            'canDeleteAbsence',
            'canRespondAsManager',
            'canRespondAsHR',
            'myAbsenceDays',
            'absenceDaysCount',
            'noTeamAbsenceDaysCount',
            'hrAbsenceDaysCount'
        ));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('create_absence')) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();
        $targetUserId = $request->input('user_id', $user->id);

        $validated = $request->validate([
            'absence_date' => 'required|date',
            'reason' => 'required|string|max:255',
            'user_id' => 'sometimes|exists:users,id',
        ]);

        try {
            if ($targetUserId !== $user->id) {
                $this->absenceRequestService->createRequestForUser($targetUserId, $validated);
            } else {
                $this->absenceRequestService->createRequest($validated);
            }

            return redirect()->route('absence-requests.index')
                ->with('success', 'Absence request submitted successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, AbsenceRequest $absenceRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager' && $user->id !== $absenceRequest->user_id) {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'absence_date' => 'required|date|after:today',
            'reason' => 'required|string|max:255'
        ]);

        $this->absenceRequestService->updateRequest($absenceRequest, $validated);

        return redirect()->route('absence-requests.index')
            ->with('success', 'Absence request updated successfully.');
    }

    public function destroy(AbsenceRequest $absenceRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager' && $user->id !== $absenceRequest->user_id) {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $this->absenceRequestService->deleteRequest($absenceRequest);

        return redirect()->route('absence-requests.index')
            ->with('success', 'Absence request deleted successfully.');
    }

    public function updateStatus(Request $request, AbsenceRequest $absenceRequest)
    {
        $user = Auth::user();

        if ($user->hasRole('team_leader') && !$user->hasPermissionTo('manager_respond_absence_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات الغياب');
        }

        if ($user->hasRole('hr') && !$user->hasPermissionTo('hr_respond_absence_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات الغياب');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected',
            'response_type' => 'required|in:manager,hr'
        ]);

        $this->absenceRequestService->updateStatus($absenceRequest, $validated);

        return redirect()->back()->with('success', 'تم تحديث حالة الطلب بنجاح');
    }

    public function modifyResponse(Request $request, $id)
    {
        $user = Auth::user();
        $absenceRequest = AbsenceRequest::findOrFail($id);

        if (!$absenceRequest->canModifyResponse($user)) {
            return redirect()->back()->with('error', 'غير مصرح لك بتعديل الرد');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected',
            'response_type' => 'required|in:manager,hr'
        ]);

        $this->absenceRequestService->modifyResponse($absenceRequest, $validated);

        return redirect()->back()->with('success', 'تم تعديل الرد بنجاح');
    }

    public function resetStatus(AbsenceRequest $absenceRequest)
    {
        $user = Auth::user();
        $responseType = request('response_type');

        $debugInfo = [
            'manager_id' => $user->id,
            'request_user_id' => $absenceRequest->user_id,
        ];

        $userOwnedTeams = [];
        foreach ($user->ownedTeams as $team) {
            $isTeamMember = DB::table('team_user')
                ->where('user_id', $absenceRequest->user_id)
                ->where('team_id', $team->id)
                ->exists();

            $userOwnedTeams[] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'request_user_is_member' => $isTeamMember
            ];
        }

        $userManagedTeams = [];
        $managedTeamIds = DB::table('team_user')
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin'])
            ->pluck('team_id');

        if ($managedTeamIds->isNotEmpty()) {
            $managedTeams = DB::table('teams')
                ->whereIn('id', $managedTeamIds)
                ->get();

            foreach ($managedTeams as $team) {
                $isTeamMember = DB::table('team_user')
                    ->where('user_id', $absenceRequest->user_id)
                    ->where('team_id', $team->id)
                    ->exists();

                $userManagedTeams[] = [
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'manager_role' => DB::table('team_user')
                        ->where('user_id', $user->id)
                        ->where('team_id', $team->id)
                        ->value('role'),
                    'request_user_is_member' => $isTeamMember
                ];
            }
        }

        $debugInfo['user_owned_teams'] = $userOwnedTeams;
        $debugInfo['user_managed_teams'] = $userManagedTeams;

        if ($absenceRequest->user) {
            $debugInfo['request_user_name'] = $absenceRequest->user->name;
        }

        if (!$absenceRequest->canModifyResponse($user)) {
            $errorMessage = 'غير مصرح لك بإعادة تعيين الحالة. ';

            $errorMessage .= 'يمكنك فقط إعادة تعيين الحالة للموظفين في الفرق التي تملكها أو تديرها. ';

            if ($absenceRequest->user) {
                $errorMessage .= 'المستخدم "' . $absenceRequest->user->name . '" ليس عضواً في أي من الفرق التي تملكها أو تديرها.';
            }

            return redirect()->back()->with('error', $errorMessage);
        }

        $this->absenceRequestService->resetStatus($absenceRequest, $responseType);

        return redirect()->back()->with('success', 'تم إعادة تعيين الحالة بنجاح');
    }

    protected function getStatistics($user, $dateStart, $dateEnd)
    {
        $statistics = [
            'personal' => [
                'total_requests' => 0,
                'approved_requests' => 0,
                'rejected_requests' => 0,
                'pending_requests' => 0,
                'total_days' => 0,
                'most_common_reason' => null,
            ],
            'team' => [
                'total_requests' => 0,
                'approved_requests' => 0,
                'rejected_requests' => 0,
                'pending_requests' => 0,
                'total_days' => 0,
                'employees_exceeded_limit' => 0,
                'most_absent_employee' => null,
                'highest_days_employee' => null,
                'exceeded_employees' => [],
            ],
            'hr' => [
                'total_requests' => 0,
                'approved_requests' => 0,
                'rejected_requests' => 0,
                'pending_requests' => 0,
                'total_days' => 0,
                'employees_exceeded_limit' => 0,
                'most_absent_employee' => null,
                'highest_days_employee' => null,
                'departments_stats' => [],
                'monthly_trend' => [],
            ],
        ];

        try {
            $personalStats = AbsenceRequest::where('user_id', $user->id)
                ->whereBetween('absence_date', [$dateStart, $dateEnd])
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as total_days
                ')
                ->first();

            $mostCommonReason = AbsenceRequest::where('user_id', $user->id)
                ->whereBetween('absence_date', [$dateStart, $dateEnd])
                ->groupBy('reason')
                ->select('reason', DB::raw('COUNT(*) as count'))
                ->orderByDesc('count')
                ->first();

            $statistics['personal'] = [
                'total_requests' => $personalStats->total ?? 0,
                'approved_requests' => $personalStats->approved ?? 0,
                'rejected_requests' => $personalStats->rejected ?? 0,
                'pending_requests' => $personalStats->pending ?? 0,
                'total_days' => $personalStats->total_days ?? 0,
                'most_common_reason' => $mostCommonReason ? [
                    'reason' => $mostCommonReason->reason,
                    'count' => $mostCommonReason->count
                ] : null,
            ];

            if ($user->hasRole(['team_leader', 'department_manager', 'company_manager' ])) {
                $hasTeamWithMultipleMembers = $user->ownedTeams()
                    ->withCount('users')
                    ->having('users_count', '>', 1)
                    ->exists() ||
                    $user->teams()
                    ->wherePivot('role', 'admin')
                    ->withCount('users')
                    ->having('users_count', '>', 1)
                    ->exists();

                if ($hasTeamWithMultipleMembers && $user->currentTeam) {
                    $teamMembers = $this->getTeamMembers($user->currentTeam, $this->getAllowedRoles($user));

                    if (!empty($teamMembers)) {
                        $teamStats = AbsenceRequest::whereIn('user_id', $teamMembers)
                            ->whereBetween('absence_date', [$dateStart, $dateEnd])
                            ->selectRaw('
                                COUNT(*) as total,
                                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
                                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
                                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as total_days
                            ')
                            ->first();

                        $exceededEmployees = DB::table(function ($query) use ($teamMembers, $dateStart, $dateEnd) {
                            $query->from('absence_requests')
                                ->select('user_id', DB::raw('COUNT(*) as total_days'))
                                ->whereIn('user_id', $teamMembers)
                                ->where('status', 'approved')
                                ->whereBetween('absence_date', [$dateStart, $dateEnd])
                                ->groupBy('user_id');
                        }, 'exceeded_users')
                            ->join('users', 'users.id', '=', 'exceeded_users.user_id')
                            ->select('users.name', 'users.date_of_birth', 'exceeded_users.total_days')
                            ->get()
                            ->filter(function ($employee) {
                                $maxDays = $employee->date_of_birth && Carbon::parse($employee->date_of_birth)->age >= 50 ? 45 : 21;
                                return $employee->total_days > $maxDays;
                            });

                        $mostAbsent = DB::table('absence_requests')
                            ->join('users', 'users.id', '=', 'absence_requests.user_id')
                            ->whereIn('user_id', $teamMembers)
                            ->whereBetween('absence_date', [$dateStart, $dateEnd])
                            ->groupBy('user_id', 'users.name')
                            ->select('users.name', DB::raw('COUNT(*) as absence_count'))
                            ->orderByDesc('absence_count')
                            ->first();

                        $statistics['team'] = [
                            'total_requests' => $teamStats->total ?? 0,
                            'approved_requests' => $teamStats->approved ?? 0,
                            'rejected_requests' => $teamStats->rejected ?? 0,
                            'pending_requests' => $teamStats->pending ?? 0,
                            'total_days' => $teamStats->total_days ?? 0,
                            'employees_exceeded_limit' => $exceededEmployees->count(),
                            'most_absent_employee' => $mostAbsent ? [
                                'name' => $mostAbsent->name,
                                'count' => $mostAbsent->absence_count
                            ] : null,
                            'exceeded_employees' => $exceededEmployees,
                        ];
                    }
                }
            }

            if ($user->hasRole('hr')) {
                $allEmployees = User::whereDoesntHave('roles', function ($q) {
                    $q->whereIn('name', ['company_manager', 'hr']);
                })->pluck('id');

                $hrStats = AbsenceRequest::whereIn('user_id', $allEmployees)
                    ->whereBetween('absence_date', [$dateStart, $dateEnd])
                    ->selectRaw('
                        COUNT(*) as total,
                        SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
                        SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as total_days
                    ')
                    ->first();

                $exceededEmployees = DB::table(function ($query) use ($allEmployees, $dateStart, $dateEnd) {
                    $query->from('absence_requests')
                        ->select('user_id', DB::raw('COUNT(*) as total_days'))
                        ->whereIn('user_id', $allEmployees)
                        ->where('status', 'approved')
                        ->whereBetween('absence_date', [$dateStart, $dateEnd])
                        ->groupBy('user_id');
                }, 'exceeded_users')
                    ->join('users', 'users.id', '=', 'exceeded_users.user_id')
                    ->select('users.name', 'users.date_of_birth', 'exceeded_users.total_days')
                    ->get()
                    ->filter(function ($employee) {
                        $maxDays = $employee->date_of_birth && Carbon::parse($employee->date_of_birth)->age >= 50 ? 45 : 21;
                        return $employee->total_days > $maxDays;
                    });

                $mostAbsent = DB::table('absence_requests')
                    ->join('users', 'users.id', '=', 'absence_requests.user_id')
                    ->whereIn('user_id', $allEmployees)
                    ->whereBetween('absence_date', [$dateStart, $dateEnd])
                    ->groupBy('user_id', 'users.name')
                    ->select('users.name', DB::raw('COUNT(*) as absence_count'))
                    ->orderByDesc('absence_count')
                    ->first();

                $statistics['hr'] = [
                    'total_requests' => $hrStats->total ?? 0,
                    'approved_requests' => $hrStats->approved ?? 0,
                    'rejected_requests' => $hrStats->rejected ?? 0,
                    'pending_requests' => $hrStats->pending ?? 0,
                    'total_days' => $hrStats->total_days ?? 0,
                    'employees_exceeded_limit' => $exceededEmployees->count(),
                    'most_absent_employee' => $mostAbsent ? [
                        'name' => $mostAbsent->name,
                        'count' => $mostAbsent->absence_count
                    ] : null,
                    'exceeded_employees' => $exceededEmployees
                ];
            }

            return $statistics;
        } catch (\Exception $e) {
            return $statistics;
        }
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
        if (!$team) {
            return [];
        }

        return $team->users()
            ->whereHas('roles', function ($q) use ($allowedRoles) {
                $q->whereIn('name', $allowedRoles);
            })
            ->pluck('users.id')
            ->toArray();
    }
}
