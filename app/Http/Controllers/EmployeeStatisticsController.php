<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AbsenceRequest;
use App\Models\PermissionRequest;
use App\Models\OverTimeRequests;
use App\Models\AttendanceRecord;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeStatisticsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $employeeQuery = User::query();

        // تحديد المستخدمين حسب الصلاحيات
        if ($user->hasRole('hr')) {
            // HR يرى كل الموظفين ما عدا HR والمدير العام
            $employeeQuery->whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['hr', 'company_manager']);
            });

            // جلب قائمة الموظفين للفلتر
            $allUsers = User::whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['hr', 'company_manager']);
            })->get();
        } elseif ($user->hasRole('department_manager')) {
            // مدير القسم يرى كل الموظفين في الفرق التي هو أدمن فيها
            $managedTeams = $user->allTeams()->pluck('id');

            // جلب الموظفين + مالكي الفرق
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

            // جلب قائمة الموظفين للفلتر (بما فيهم مالكي الفرق)
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
                // قائد الفريق يرى فقط موظفي فريقه
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
            // المدير العام يرى الجميع ما عدا HR
            $employeeQuery->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'hr');
            });

            $allUsers = User::whereDoesntHave('roles', function ($q) {
                $q->where('name', 'hr');
            })->get();
        } else {
            // الموظف العادي يرى نفسه فقط
            $employeeQuery->where('id', $user->id);
            $allUsers = collect([$user]);
        }

        // تطبيق الفلاتر
        if ($request->has('department') && $request->department != '') {
            $employeeQuery->where('department', $request->department);
        }

        if ($request->has('search') && $request->search != '') {
            $employeeQuery->where(function ($q) use ($request) {
                $q->where('employee_id', 'like', "%{$request->search}%")
                    ->orWhere('name', 'like', "%{$request->search}%");
            });
        }

        // جلب قائمة الأقسام للفلتر (للمدراء و HR فقط)
        $departments = [];
        if ($user->hasRole(['hr', 'company_manager', 'department_manager'])) {
            $departments = User::select('department')
                ->distinct()
                ->whereNotNull('department')
                ->pluck('department');
        }

        // تعيين التواريخ الافتراضية
        $now = now();
        $startDate = $request->start_date ?? ($now->day >= 26
            ? $now->copy()->startOfDay()->setDay(26)
            : $now->copy()->subMonth()->startOfDay()->setDay(26))->format('Y-m-d');

        $endDate = $request->end_date ?? ($now->day >= 26
            ? $now->copy()->addMonth()->startOfDay()->setDay(25)->endOfDay()
            : $now->copy()->startOfDay()->setDay(25)->endOfDay())->format('Y-m-d');

        // جلب المستخدمين مع الترقيم
        $employees = $employeeQuery->orderBy('department')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        // حساب الإحصائيات لكل موظف
        foreach ($employees as $employee) {
            if ($startDate && $endDate) {
                // نجيب الإجازات المعتمدة للسنة الحالية
                $approvedLeaves = AbsenceRequest::where('user_id', $employee->id)
                    ->where('status', 'approved')
                    ->whereBetween('absence_date', [
                        Carbon::parse($startDate)->startOfYear(),
                        Carbon::parse($endDate)->endOfYear()
                    ])
                    ->get();

                // نحسب إجمالي الإجازات المعتمدة
                $totalApprovedLeaves = $approvedLeaves->count();

                // نجيب تواريخ الإجازات المعتمدة (في حدود 21 يوم فقط)
                $approvedLeavesDates = [];
                if ($totalApprovedLeaves <= 21) {
                    $approvedLeavesDates = $approvedLeaves->pluck('absence_date')->toArray();
                } else {
                    // إذا تجاوزت 21 يوم، نأخذ أول 21 إجازة فقط
                    $approvedLeavesDates = $approvedLeaves->take(21)->pluck('absence_date')->toArray();
                }

                $statsQuery = AttendanceRecord::where('employee_id', $employee->employee_id)
                    ->whereBetween('attendance_date', [$startDate, $endDate]);

                // حساب أجمالي أيام العمل (فقط أيام الحضور والغياب)
                $totalWorkDays = (clone $statsQuery)
                    ->where(function ($query) {
                        $query->where('status', 'حضـور')
                            ->orWhere('status', 'غيــاب');
                    })
                    ->count();
                $employee->total_working_days = $totalWorkDays;

                // حساب أيام الحضور الفعلية + الإجازات المعتمدة (في حدود 21 يوم)
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

                // حساب أيام الغياب (بعد استبعاد الإجازات المعتمدة)
                $employee->absences = (clone $statsQuery)
                    ->where('status', 'غيــاب')
                    ->whereNotIn('attendance_date', $approvedLeavesDates)
                    ->count();

                // حساب نسبة الحضور الجديدة (تشمل الحضور الفعلي + الإجازات المعتمدة)
                $employee->attendance_percentage = $totalWorkDays > 0
                    ? round(($actualAttendanceDays / $totalWorkDays) * 100, 1)
                    : 0;

                // حساب أيام العطل الأسبوعية
                $employee->weekend_days = (clone $statsQuery)
                    ->where('status', 'عطله إسبوعية')
                    ->count();

                // حساب التأخير
                $lateRecords = (clone $statsQuery)
                    ->where('delay_minutes', '>', 0)
                    ->whereNotNull('entry_time')
                    ->get();

                $employee->delays = $lateRecords->sum('delay_minutes');

                // حساب متوسط ساعات العمل
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

            // الأذونات
            $permissionQuery = PermissionRequest::where('user_id', $employee->id)
                ->where('status', 'approved');
            if ($startDate && $endDate) {
                $permissionQuery->whereBetween('departure_time', [$startDate, $endDate]);
            }
            $employee->permissions = $permissionQuery->count();

            // الوقت الإضافي
            $overtimeQuery = OverTimeRequests::where('user_id', $employee->id)
                ->where('status', 'approved');
            if ($startDate && $endDate) {
                $overtimeQuery->whereBetween('overtime_date', [$startDate, $endDate]);
            }
            $employee->overtimes = $overtimeQuery->count();

            // حساب الإجازات المأخوذة والمتبقية
            $takenLeaves = AbsenceRequest::where('user_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('absence_date', [
                    Carbon::parse($startDate)->startOfYear(),
                    Carbon::parse($endDate)->endOfYear()
                ])
                ->count();

            $employee->taken_leaves = $takenLeaves;
            $employee->remaining_leaves = 21 - $takenLeaves;

            // إضافة حساب الإجازات الشهرية
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

        // التحقق من الصلاحيات
        $canViewEmployee = false;

        if ($user->hasRole('hr')) {
            $canViewEmployee = true;
        } elseif ($user->hasRole('department_manager')) {
            // التحقق مما إذا كان الموظف في أحد الفرق التي هو أدمن فيها أو مالك لها
            $managedTeams = $user->allTeams()->pluck('id');
            $canViewEmployee = $employee->teams()
                ->whereIn('teams.id', $managedTeams)
                ->exists() ||
                $employee->ownedTeams()
                ->whereIn('id', $managedTeams)
                ->exists();
        } elseif ($user->hasRole('team_leader')) {
            // التحقق مما إذا كان الموظف في فريقه
            $canViewEmployee = $user->currentTeam && $employee->teams()
                ->where('teams.id', $user->currentTeam->id)
                ->exists();
        } elseif ($user->hasRole('company_manager')) {
            // المدير العام يرى الجميع ما عدا HR
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
        $statistics['remaining_leaves'] = 21 - $takenLeaves;

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

        return response()->json([
            'employee' => $employee,
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

            \Log::info('Fetching absences', [
                'employee_id' => $employee_id,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            // نجيب الغجازات المعتمدة
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

            // نجيب الغياب من جدول الحضور ما عدا أيام الإجازات المعتمدة
            $absences = AttendanceRecord::where('employee_id', $employee_id)
                ->where('status', 'غيــاب')
                ->whereNotIn('attendance_date', $approvedLeavesDates)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->orderBy('attendance_date', 'desc')
                ->get();

            \Log::info('Found absences', ['count' => $absences->count()]);

            return $absences->map(function($record) {
                return [
                    'date' => $record->attendance_date,
                    'reason' => 'غياب',
                    'status' => 'غياب'
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Error in getAbsences', [
                'error' => $e->getMessage(),
                'employee_id' => $employee_id
            ]);
            return response()->json(['error' => 'حدث خطأ أثناء جلب البيانات'], 500);
        }
    }

    public function getPermissions($employee_id)
    {
        try {
            $startDate = request('start_date');
            $endDate = request('end_date');

            \Log::info('Fetching permissions', [
                'employee_id' => $employee_id,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            $user = User::where('employee_id', $employee_id)->firstOrFail();

            $permissions = PermissionRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('departure_time', [$startDate, $endDate])
                ->orderBy('departure_time', 'desc')
                ->get();

            \Log::info('Found permissions', ['count' => $permissions->count()]);

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
            \Log::error('Error in getPermissions', [
                'error' => $e->getMessage(),
                'employee_id' => $employee_id
            ]);
            return response()->json(['error' => 'حدث خطأ أثناء جلب البيانات'], 500);
        }
    }

    public function getOvertimes($employee_id)
    {
        try {
            $startDate = request('start_date');
            $endDate = request('end_date');

            \Log::info('Fetching overtimes', [
                'employee_id' => $employee_id,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            $user = User::where('employee_id', $employee_id)->firstOrFail();

            $overtimes = OverTimeRequests::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('overtime_date', [$startDate, $endDate])
                ->orderBy('overtime_date', 'desc')
                ->get();

            \Log::info('Found overtimes', ['count' => $overtimes->count()]);

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
            \Log::error('Error in getOvertimes', [
                'error' => $e->getMessage(),
                'employee_id' => $employee_id
            ]);
            return response()->json(['error' => 'حدث خطأ أثناء جلب البيانات'], 500);
        }
    }

    public function getLeaves($employee_id)
    {
        try {
            $startDate = request('start_date');
            $endDate = request('end_date');

            \Log::info('Fetching leaves', [
                'employee_id' => $employee_id,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            $user = User::where('employee_id', $employee_id)->firstOrFail();

            // نجيب الإجازات المعتمدة للسنة المحددة
            $leaves = AbsenceRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('absence_date', [
                    Carbon::parse($startDate)->startOfYear(),
                    Carbon::parse($endDate)->endOfYear()
                ])
                ->orderBy('absence_date', 'desc')
                ->get();

            \Log::info('Found leaves', ['count' => $leaves->count()]);

            $formattedLeaves = $leaves->map(function($record) {
                return [
                    'date' => $record->absence_date,
                    'reason' => $record->reason,
                    'status' => 'معتمد'
                ];
            });

            return response()->json($formattedLeaves);
        } catch (\Exception $e) {
            \Log::error('Error in getLeaves', [
                'error' => $e->getMessage(),
                'employee_id' => $employee_id
            ]);
            return response()->json(['error' => 'حدث خطأ أثناء جلب البيانات'], 500);
        }
    }

    public function getCurrentMonthLeaves($employee_id)
    {
        $startDate = request('start_date');
        $endDate = request('end_date');

        \Log::info('Fetching leaves for employee', [
            'employee_id' => $employee_id,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        // جلب الإجازات المعتمدة فقط
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

        \Log::info('Found leaves', ['count' => $leaves->count()]);

        $formattedLeaves = $leaves->map(function($record) {
            return [
                'date' => $record->absence_date,
                'reason' => $record->reason,
                'status' => 'معتمد'
            ];
        });

        return response()->json($formattedLeaves);
    }
}
