<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AbsenceRequest;
use App\Models\PermissionRequest;
use App\Models\OverTimeRequests;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeStatisticsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $employeeQuery = User::query();

        if ($user->hasRole('hr')) {
            $employeeQuery->whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['hr', 'company_manager']);
            });

            $allUsers = User::whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['hr', 'company_manager']);
            })->get();
        } elseif ($user->hasRole('department_manager')) {
            $managedTeams = $user->allTeams()->pluck('id');

            $employeeQuery->where(function ($query) use ($managedTeams) {
                $query->whereHas('teams', function ($q) use ($managedTeams) {
                    $q->whereIn('teams.id', $managedTeams);
                })->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['employee', 'team_leader']);
                })
                    ->orWhereHas('ownedTeams', function ($q) use ($managedTeams) {
                        $q->whereIn('id', $managedTeams);
                    });
            });

            $allUsers = User::where(function ($query) use ($managedTeams) {
                $query->whereHas('teams', function ($q) use ($managedTeams) {
                    $q->whereIn('teams.id', $managedTeams);
                })->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['employee', 'team_leader']);
                })
                    ->orWhereHas('ownedTeams', function ($q) use ($managedTeams) {
                        $q->whereIn('id', $managedTeams);
                    });
            })->get();
        } elseif ($user->hasRole('team_leader')) {
            if ($user->currentTeam) {
                $teamMembers = $user->currentTeam->users()
                    ->whereHas('roles', function ($q) {
                        $q->where('name', 'employee');
                    })
                    ->pluck('users.id');

                $employeeQuery->whereIn('id', $teamMembers);
                $allUsers = User::whereIn('id', $teamMembers)->get();
            } else {
                $employeeQuery->where('id', 0);
                $allUsers = collect();
            }
        } elseif ($user->hasRole('company_manager')) {
            $employeeQuery->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'hr');
            });

            $allUsers = User::whereDoesntHave('roles', function ($q) {
                $q->where('name', 'hr');
            })->get();
        } else {
            $employeeQuery->where('id', $user->id);
            $allUsers = collect([$user]);
        }

        if ($request->has('department') && $request->department != '') {
            $employeeQuery->where('department', $request->department);
        }

        if ($request->has('search') && $request->search != '') {
            $employeeQuery->where(function ($q) use ($request) {
                $q->where('employee_id', 'like', "%{$request->search}%")
                    ->orWhere('name', 'like', "%{$request->search}%");
            });
        }

        $departments = [];
        if ($user->hasRole(['hr', 'company_manager', 'department_manager'])) {
            $departments = User::select('department')
                ->distinct()
                ->whereNotNull('department')
                ->pluck('department');
        }

        $now = now();
        $startDate = $request->start_date ?? ($now->day >= 26
            ? $now->copy()->startOfDay()->setDay(26)
            : $now->copy()->subMonth()->startOfDay()->setDay(26))->format('Y-m-d');

        $endDate = $request->end_date ?? ($now->day >= 26
            ? $now->copy()->addMonth()->startOfDay()->setDay(25)->endOfDay()
            : $now->copy()->startOfDay()->setDay(25)->endOfDay())->format('Y-m-d');

        $employees = $employeeQuery->orderBy('department')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        foreach ($employees as $employee) {
            if ($startDate && $endDate) {
                $approvedLeaves = AbsenceRequest::where('user_id', $employee->id)
                    ->where('status', 'approved')
                    ->whereBetween('absence_date', [
                        Carbon::parse($startDate)->startOfYear(),
                        Carbon::parse($endDate)->endOfYear()
                    ])
                    ->get();

                $totalApprovedLeaves = $approvedLeaves->count();

                $approvedLeavesDates = [];
                if ($totalApprovedLeaves <= 21) {
                    $approvedLeavesDates = $approvedLeaves->pluck('absence_date')->toArray();
                } else {
                    $approvedLeavesDates = $approvedLeaves->take(21)->pluck('absence_date')->toArray();
                }

                $statsQuery = AttendanceRecord::where('employee_id', $employee->employee_id)
                    ->whereBetween('attendance_date', [$startDate, $endDate]);

                $totalWorkDays = (clone $statsQuery)
                    ->where(function ($query) {
                        $query->where('status', 'حضـور')
                            ->orWhere('status', 'غيــاب');
                    })
                    ->count();
                $employee->total_working_days = $totalWorkDays;

                $actualAttendanceDays = (clone $statsQuery)
                    ->where(function ($query) use ($approvedLeavesDates) {
                        $query->where(function ($q) {
                            $q->where('status', 'حضـور')
                                ->whereNotNull('entry_time');
                        })
                        ->orWhereIn('attendance_date', $approvedLeavesDates);
                    })
                    ->count();

                $employee->actual_attendance_days = $actualAttendanceDays;

                $employee->absences = (clone $statsQuery)
                    ->where('status', 'غيــاب')
                    ->whereNotIn('attendance_date', $approvedLeavesDates)
                    ->count();

                $employee->attendance_percentage = $totalWorkDays > 0
                    ? round(($actualAttendanceDays / $totalWorkDays) * 100, 1)
                    : 0;

                $employee->weekend_days = (clone $statsQuery)
                    ->where('status', 'عطله إسبوعية')
                    ->count();

                $lateRecords = (clone $statsQuery)
                    ->where('delay_minutes', '>', 0)
                    ->whereNotNull('entry_time')
                    ->get();

                $employee->delays = $lateRecords->sum('delay_minutes');

                $workingHoursRecords = (clone $statsQuery)
                    ->where('status', 'حضـور')
                    ->whereNotNull('working_hours')
                    ->get();

                $totalWorkingHours = $workingHoursRecords->sum('working_hours');
                $daysWithHours = $workingHoursRecords->count();
                $employee->average_working_hours = $daysWithHours > 0 ? round($totalWorkingHours / $daysWithHours, 2) : 0;
            } else {
                $employee->total_working_days = 0;
                $employee->actual_attendance_days = 0;
                $employee->absences = 0;
                $employee->weekend_days = 0;
                $employee->delays = 0;
                $employee->average_working_hours = 0;
                $employee->attendance_percentage = 0;
            }

            $permissionQuery = PermissionRequest::where('user_id', $employee->id)
                ->where('status', 'approved');
            if ($startDate && $endDate) {
                $permissionQuery->whereBetween('departure_time', [$startDate, $endDate]);
            }
            $employee->permissions = $permissionQuery->count();

            $overtimeQuery = OverTimeRequests::where('user_id', $employee->id)
                ->where('status', 'approved');
            if ($startDate && $endDate) {
                $overtimeQuery->whereBetween('overtime_date', [$startDate, $endDate]);
            }
            $employee->overtimes = $overtimeQuery->count();

            $takenLeaves = AbsenceRequest::where('user_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('absence_date', [
                    Carbon::parse($startDate)->startOfYear(),
                    Carbon::parse($endDate)->endOfYear()
                ])
                ->count();

            $employee->taken_leaves = $takenLeaves;
            $employee->remaining_leaves = $employee->getMaxAllowedAbsenceDays() - $takenLeaves;

            $currentMonthLeaves = AbsenceRequest::where('user_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('absence_date', [
                    Carbon::parse($startDate)->day >= 26
                        ? Carbon::parse($startDate)->startOfDay()
                        : Carbon::parse($startDate)->subMonth()->startOfDay()->setDay(26),
                    Carbon::parse($endDate)->day >= 26
                        ? Carbon::parse($endDate)->addMonth()->startOfDay()->setDay(25)->endOfDay()
                        : Carbon::parse($endDate)->startOfDay()->setDay(25)->endOfDay()
                ])
                ->count();

            $employee->current_month_leaves = $currentMonthLeaves;

            // Add performance analysis
            $employee->performance_metrics = $this->calculatePerformanceMetrics($employee, $startDate, $endDate);
            $employee->performance_predictions = $this->predictFuturePerformance($employee);
        }

        return view('employee-statistics.index', compact(
            'employees',
            'startDate',
            'endDate',
            'departments',
            'allUsers'
        ));
    }

    public function getEmployeeDetails($employee_id)
    {
        $user = Auth::user();
        $employee = User::where('employee_id', $employee_id)->firstOrFail();

        $canViewEmployee = false;

        if ($user->hasRole('hr')) {
            $canViewEmployee = true;
        } elseif ($user->hasRole('department_manager')) {
            $managedTeams = $user->allTeams()->pluck('id');
            $canViewEmployee = $employee->teams()
                ->whereIn('teams.id', $managedTeams)
                ->exists() ||
                $employee->ownedTeams()
                ->whereIn('id', $managedTeams)
                ->exists();
        } elseif ($user->hasRole('team_leader')) {
            $canViewEmployee = $user->currentTeam && $employee->teams()
                ->where('teams.id', $user->currentTeam->id)
                ->exists();
        } elseif ($user->hasRole('company_manager')) {
            $canViewEmployee = !$employee->hasRole('hr');
        } else {
            $canViewEmployee = $user->id === $employee->id;
        }

        if (!$canViewEmployee) {
            abort(403, 'غير مصرح لك بعرض بيانات هذا الموظف');
        }

        $startDate = request('start_date');
        $endDate = request('end_date');

        $statsQuery = AttendanceRecord::where('employee_id', $employee_id)
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        $statistics = [
            'total_working_days' => (clone $statsQuery)
                ->where(function ($query) {
                    $query->where('status', 'حضـور')
                        ->orWhere('status', 'غيــاب');
                })
                ->count(),

            'actual_attendance_days' => (clone $statsQuery)
                ->where('status', 'حضـور')
                ->whereNotNull('entry_time')
                ->count(),

            'absences' => (clone $statsQuery)
                ->where('status', 'غيــاب')
                ->count(),

            'permissions' => PermissionRequest::where('user_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('departure_time', [$startDate, $endDate])
                ->count(),

            'overtimes' => OverTimeRequests::where('user_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('overtime_date', [$startDate, $endDate])
                ->count(),

            'delays' => (clone $statsQuery)
                ->where('delay_minutes', '>', 0)
                ->whereNotNull('entry_time')
                ->sum('delay_minutes'),

            'attendance' => $statsQuery->orderBy('attendance_date', 'desc')->get()
        ];

        $statistics['attendance_percentage'] = $statistics['total_working_days'] > 0
            ? round(($statistics['actual_attendance_days'] / $statistics['total_working_days']) * 100, 1)
            : 0;

        $takenLeaves = AbsenceRequest::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('absence_date', [
                Carbon::parse($startDate)->startOfYear(),
                Carbon::parse($endDate)->endOfYear()
            ])
            ->count();

        $statistics['taken_leaves'] = $takenLeaves;
        $statistics['remaining_leaves'] = $employee->getMaxAllowedAbsenceDays() - $takenLeaves;

        $approvedLeavesDates = AbsenceRequest::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('absence_date', [$startDate, $endDate])
            ->pluck('absence_date')
            ->toArray();

        $statistics['absences'] = (clone $statsQuery)
            ->where('status', 'غيــاب')
            ->whereNotIn('attendance_date', $approvedLeavesDates)
            ->count();

        $statistics['current_month_leaves'] = AbsenceRequest::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('absence_date', [
                Carbon::parse($startDate)->day >= 26
                    ? Carbon::parse($startDate)->startOfDay()
                    : Carbon::parse($startDate)->subMonth()->startOfDay()->setDay(26),
                Carbon::parse($endDate)->day >= 26
                    ? Carbon::parse($endDate)->addMonth()->startOfDay()->setDay(25)->endOfDay()
                    : Carbon::parse($endDate)->startOfDay()->setDay(25)->endOfDay()
            ])
            ->count();

        $employeeData = $employee->only([
            'id', 'name', 'employee_id', 'email', 'phone_number', 'position', 'department',
            'date_of_birth', 'hire_date', 'employment_status', 'profile_photo_url'
        ]);

        // Add max allowed absence days
        $employeeData['max_allowed_absence_days'] = $employee->getMaxAllowedAbsenceDays();

        return response()->json([
            'employee' => $employeeData,
            'statistics' => $statistics
        ]);
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
            ->whereHas('roles', function ($q) use ($allowedRoles) {
                $q->whereIn('name', $allowedRoles);
            })
            ->pluck('users.id')
            ->toArray();
    }

    public function getAbsences($employee_id)
    {
        try {
            $startDate = request('start_date');
            $endDate = request('end_date');

            $approvedLeavesDates = AbsenceRequest::where('user_id', function($query) use ($employee_id) {
                    $query->select('id')
                        ->from('users')
                        ->where('employee_id', $employee_id)
                        ->first();
                })
                ->where('status', 'approved')
                ->whereBetween('absence_date', [$startDate, $endDate])
                ->pluck('absence_date')
                ->toArray();

            $absences = AttendanceRecord::where('employee_id', $employee_id)
                ->where('status', 'غيــاب')
                ->whereNotIn('attendance_date', $approvedLeavesDates)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->orderBy('attendance_date', 'desc')
                ->get();

            return $absences->map(function($record) {
                return [
                    'date' => $record->attendance_date,
                    'reason' => 'غياب',
                    'status' => 'غياب'
                ];
            });
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء جلب البيانات'], 500);
        }
    }

    public function getPermissions($employee_id)
    {
        try {
            $startDate = request('start_date');
            $endDate = request('end_date');

            $user = User::where('employee_id', $employee_id)->firstOrFail();

            $permissions = PermissionRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('departure_time', [$startDate, $endDate])
                ->orderBy('departure_time', 'desc')
                ->get();

            return $permissions->map(function($record) {
                $departureTime = Carbon::parse($record->departure_time);
                $returnTime = Carbon::parse($record->return_time);
                $minutes = abs($returnTime->diffInMinutes($departureTime));

                return [
                    'date' => $departureTime->format('Y-m-d'),
                    'departure_time' => $departureTime->format('H:i'),
                    'return_time' => $returnTime->format('H:i'),
                    'minutes' => $minutes,
                    'reason' => $record->reason,
                    'status' => 'معتمد'
                ];
            });
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء جلب البيانات'], 500);
        }
    }

    public function getOvertimes($employee_id)
    {
        try {
            $startDate = request('start_date');
            $endDate = request('end_date');

            $user = User::where('employee_id', $employee_id)->firstOrFail();

            $overtimes = OverTimeRequests::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('overtime_date', [$startDate, $endDate])
                ->orderBy('overtime_date', 'desc')
                ->get();

            return $overtimes->map(function($record) {
                $startTime = Carbon::parse($record->start_time);
                $endTime = Carbon::parse($record->end_time);
                $minutes = abs($endTime->diffInMinutes($startTime));

                return [
                    'date' => Carbon::parse($record->overtime_date)->format('Y-m-d'),
                    'start_time' => Carbon::parse($record->start_time)->format('H:i'),
                    'end_time' => Carbon::parse($record->end_time)->format('H:i'),
                    'minutes' => $minutes,
                    'reason' => $record->reason,
                    'status' => 'معتمد'
                ];
            });
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء جلب البيانات'], 500);
        }
    }

    public function getLeaves($employee_id)
    {
        try {
            $startDate = request('start_date');
            $endDate = request('end_date');

            $user = User::where('employee_id', $employee_id)->firstOrFail();

            $leaves = AbsenceRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('absence_date', [
                    Carbon::parse($startDate)->startOfYear(),
                    Carbon::parse($endDate)->endOfYear()
                ])
                ->orderBy('absence_date', 'desc')
                ->get();

            $formattedLeaves = $leaves->map(function($record) {
                return [
                    'date' => $record->absence_date,
                    'reason' => $record->reason,
                    'status' => 'معتمد'
                ];
            });

            return response()->json($formattedLeaves);
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء جلب البيانات'], 500);
        }
    }

    public function getCurrentMonthLeaves($employee_id)
    {
        $startDate = request('start_date');
        $endDate = request('end_date');

        $user = User::where('employee_id', $employee_id)->firstOrFail();

        $leaves = AbsenceRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('absence_date', [
                Carbon::parse($startDate)->day >= 26
                    ? Carbon::parse($startDate)->startOfDay()
                    : Carbon::parse($startDate)->subMonth()->startOfDay()->setDay(26),
                Carbon::parse($endDate)->day >= 26
                    ? Carbon::parse($endDate)->addMonth()->startOfDay()->setDay(25)->endOfDay()
                    : Carbon::parse($endDate)->startOfDay()->setDay(25)->endOfDay()
            ])
            ->get();

        $formattedLeaves = $leaves->map(function($record) {
            return [
                'date' => $record->absence_date,
                'reason' => $record->reason,
                'status' => 'معتمد'
            ];
        });

        return response()->json($formattedLeaves);
    }

    private function calculatePerformanceMetrics($employee, $startDate, $endDate)
    {
        // Calculate attendance score (0-100)
        $attendanceScore = min(100, ($employee->attendance_percentage ?? 0));

        // Calculate punctuality score (0-100)
        $maxAcceptableDelays = 180; // 3 hours per month
        $punctualityScore = max(0, 100 - (($employee->delays / $maxAcceptableDelays) * 100));

        // Calculate work consistency score (0-100)
        $workingHoursScore = min(100, (($employee->average_working_hours / 8) * 100));

        // Calculate overall performance score
        $overallScore = round(($attendanceScore * 0.4) + ($punctualityScore * 0.3) + ($workingHoursScore * 0.3), 1);

        // Performance trend (comparing with previous period)
        $previousPeriodScore = $this->calculatePreviousPeriodScore($employee, $startDate);
        $trend = $overallScore - $previousPeriodScore;

        return [
            'attendance_score' => round($attendanceScore, 1),
            'punctuality_score' => round($punctualityScore, 1),
            'working_hours_score' => round($workingHoursScore, 1),
            'overall_score' => $overallScore,
            'trend' => round($trend, 1),
            'performance_level' => $this->getPerformanceLevel($overallScore),
            'areas_for_improvement' => $this->getAreasForImprovement($attendanceScore, $punctualityScore, $workingHoursScore)
        ];
    }

    private function calculatePreviousPeriodScore($employee, $startDate)
    {
        // Get previous period dates
        $previousStart = Carbon::parse($startDate)->subMonth();
        $previousEnd = Carbon::parse($startDate)->subDay();

        // Calculate previous attendance percentage
        $previousStats = AttendanceRecord::where('employee_id', $employee->employee_id)
            ->whereBetween('attendance_date', [$previousStart, $previousEnd])
            ->get();

        $totalDays = $previousStats->count();
        $presentDays = $previousStats->where('status', 'حضـور')->count();

        $prevAttendanceScore = $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 0;
        $prevPunctualityScore = 100 - (($previousStats->sum('delay_minutes') / 180) * 100);
        $prevWorkingHoursScore = $previousStats->avg('working_hours') ? (($previousStats->avg('working_hours') / 8) * 100) : 0;

        return round(($prevAttendanceScore * 0.4) + ($prevPunctualityScore * 0.3) + ($prevWorkingHoursScore * 0.3), 1);
    }

    private function getPerformanceLevel($score)
    {
        if ($score >= 90) return 'ممتاز';
        if ($score >= 80) return 'جيد جداً';
        if ($score >= 70) return 'جيد';
        if ($score >= 60) return 'مقبول';
        return 'يحتاج إلى تحسين';
    }

    private function getAreasForImprovement($attendanceScore, $punctualityScore, $workingHoursScore)
    {
        $areas = [];

        if ($attendanceScore < 80) {
            $areas[] = 'تحسين نسبة الحضور';
        }
        if ($punctualityScore < 80) {
            $areas[] = 'الالتزام بمواعيد الحضور';
        }
        if ($workingHoursScore < 80) {
            $areas[] = 'زيادة ساعات العمل الفعلية';
        }

        return $areas;
    }

    private function predictFuturePerformance($employee)
    {
        // Calculate trend based on last 3 months
        $threeMonthsAgo = now()->subMonths(3);
        $monthlyScores = [];

        for ($i = 0; $i < 3; $i++) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();

            $monthStats = AttendanceRecord::where('employee_id', $employee->employee_id)
                ->whereBetween('attendance_date', [$monthStart, $monthEnd])
                ->get();

            $presentDays = $monthStats->where('status', 'حضـور')->count();
            $totalDays = $monthStats->count() ?: 1;

            $monthlyScores[] = ($presentDays / $totalDays) * 100;
        }

        // Calculate trend
        $trend = 0;
        if (count($monthlyScores) >= 2) {
            $trend = ($monthlyScores[0] - $monthlyScores[count($monthlyScores)-1]) / count($monthlyScores);
        }

        // Predict next month's performance
        $predictedScore = end($monthlyScores) + $trend;
        $predictedScore = max(0, min(100, $predictedScore));

        return [
            'predicted_attendance' => round($predictedScore, 1),
            'trend_direction' => $trend > 0 ? 'تحسن' : ($trend < 0 ? 'تراجع' : 'ثابت'),
            'trend_percentage' => abs(round($trend, 1)),
            'recommendations' => $this->getRecommendations($predictedScore, $trend)
        ];
    }

    private function getRecommendations($predictedScore, $trend)
    {
        $recommendations = [];

        if ($predictedScore < 70) {
            $recommendations[] = 'يحتاج إلى متابعة مباشرة وخطة تحسين عاجلة';
        } elseif ($predictedScore < 85) {
            $recommendations[] = 'يحتاج إلى تحسين في بعض جوانب الأداء';
        }

        if ($trend < 0) {
            $recommendations[] = 'مراجعة أسباب تراجع الأداء ووضع خطة تصحيحية';
        }

        return $recommendations ?: ['الحفاظ على مستوى الأداء الحالي'];
    }
}
