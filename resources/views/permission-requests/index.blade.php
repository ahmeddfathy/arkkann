@extends('layouts.app')

@php
use App\Models\PermissionRequest;
use Carbon\Carbon;
@endphp

@push('styles')
<link href="{{ asset('css/permission-managment.css') }}" rel="stylesheet">
<style>
    .countdown-timer {
        font-size: 1.5rem;
        font-weight: bold;
        padding: 15px;
        border-radius: 50%;
        width: 120px;
        height: 120px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border: 3px solid #dee2e6;
        margin: 10px 0;
        transition: all 0.3s ease;
    }

    .countdown-timer.warning {
        border-color: #ffc107;
        color: #856404;
        background-color: #fff3cd;
    }

    .countdown-timer.danger {
        border-color: #dc3545;
        color: #721c24;
        background-color: #f8d7da;
    }

    .timer-label {
        font-size: 0.875rem;
        color: #6c757d;
        margin-bottom: 5px;
    }

    .timer-value {
        font-size: 1.75rem;
        line-height: 1;
        font-family: monospace;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js Configuration
    Chart.defaults.font.family = 'Cairo, sans-serif';
    Chart.defaults.color = '#4a5568';
    Chart.defaults.plugins.tooltip.rtl = true;
    Chart.defaults.plugins.tooltip.titleAlign = 'right';
    Chart.defaults.plugins.tooltip.bodyAlign = 'right';

    // Common Colors
    const colors = {
        approved: {
            bg: 'rgba(40, 167, 69, 0.7)',
            border: 'rgba(40, 167, 69, 1)'
        },
        pending: {
            bg: 'rgba(255, 193, 7, 0.7)',
            border: 'rgba(255, 193, 7, 1)'
        },
        rejected: {
            bg: 'rgba(220, 53, 69, 0.7)',
            border: 'rgba(220, 53, 69, 1)'
        },
        onTime: {
            bg: 'rgba(13, 110, 253, 0.7)',
            border: 'rgba(13, 110, 253, 1)'
        },
        late: {
            bg: 'rgba(111, 66, 193, 0.7)',
            border: 'rgba(111, 66, 193, 1)'
        }
    };

    // Common Chart Options
    const commonPieOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                rtl: true,
                labels: {
                    font: {
                        family: 'Cairo, sans-serif'
                    },
                    padding: 20
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value * 100) / total).toFixed(1) : 0;
                        return `${label}: ${value} (${percentage}%)`;
                    }
                }
            }
        }
    };

    const commonBarOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            },
            x: {
                ticks: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            }
        }
    };

    // Personal Statistics Charts
    @if(isset($statistics) && isset($statistics['personal']))
    const personalRequestsCtx = document.getElementById('personalRequestsChart')?.getContext('2d');
    if (personalRequestsCtx) {
        const approvedRequests = {{ $statistics['personal']['approved_requests'] }};
        const pendingRequests = {{ $statistics['personal']['pending_requests'] }};
        const rejectedRequests = {{ $statistics['personal']['rejected_requests'] }};

        new Chart(personalRequestsCtx, {
            type: 'pie',
            data: {
                labels: ['تمت الموافقة', 'معلقة', 'مرفوضة'],
                datasets: [{
                    data: [approvedRequests, pendingRequests, rejectedRequests],
                    backgroundColor: [colors.approved.bg, colors.pending.bg, colors.rejected.bg],
                    borderColor: [colors.approved.border, colors.pending.border, colors.rejected.border],
                    borderWidth: 1
                }]
            },
            options: commonPieOptions
        });
    }

    const personalMinutesCtx = document.getElementById('personalMinutesChart')?.getContext('2d');
    if (personalMinutesCtx) {
        const onTimeReturns = {{ $statistics['personal']['on_time_returns'] }};
        const lateReturns = {{ $statistics['personal']['late_returns'] }};
        const totalMinutes = {{ $statistics['personal']['total_minutes'] }};

        new Chart(personalMinutesCtx, {
            type: 'bar',
            data: {
                labels: ['الدقائق المستخدمة', 'عودة في الوقت', 'عودة متأخرة'],
                datasets: [{
                    label: 'العدد',
                    data: [totalMinutes, onTimeReturns, lateReturns],
                    backgroundColor: [colors.approved.bg, colors.onTime.bg, colors.late.bg],
                    borderColor: [colors.approved.border, colors.onTime.border, colors.late.border],
                    borderWidth: 1
                }]
            },
            options: {
                ...commonBarOptions,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const index = context.dataIndex;
                                if (index === 0) {
                                    return `الدقائق المستخدمة: ${context.parsed.y} دقيقة`;
                                } else {
                                    return `العدد: ${context.parsed.y}`;
                                }
                            }
                        }
                    }
                }
            }
        });
    }
    @endif

    // Team Statistics Charts
    @if(isset($statistics) && isset($statistics['team']))
    const teamRequestsCtx = document.getElementById('teamRequestsChart')?.getContext('2d');
    if (teamRequestsCtx) {
        const totalRequests = {{ $statistics['team']['total_requests'] }};
        const exceededLimit = {{ $statistics['team']['employees_exceeded_limit'] }};
        const withinLimit = totalRequests - exceededLimit;

        new Chart(teamRequestsCtx, {
            type: 'pie',
            data: {
                labels: ['ضمن الحد المسموح', 'تجاوزوا الحد'],
                datasets: [{
                    data: [withinLimit, exceededLimit],
                    backgroundColor: [colors.approved.bg, colors.rejected.bg],
                    borderColor: [colors.approved.border, colors.rejected.border],
                    borderWidth: 1
                }]
            },
            options: commonPieOptions
        });
    }

    const teamMinutesCtx = document.getElementById('teamMinutesChart')?.getContext('2d');
    if (teamMinutesCtx) {
        // Get top 5 employees by minutes if available
        @if(isset($statistics['team']['team_employees']) && count($statistics['team']['team_employees']) > 0)
        const teamEmployees = @json($statistics['team']['team_employees']);
        const sortedEmployees = teamEmployees.sort((a, b) => b.minutes - a.minutes).slice(0, 5);
        const labels = sortedEmployees.map(emp => emp.name);
        const minutes = sortedEmployees.map(emp => emp.minutes);

        new Chart(teamMinutesCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'الدقائق المستخدمة',
                    data: minutes,
                    backgroundColor: colors.approved.bg,
                    borderColor: colors.approved.border,
                    borderWidth: 1
                }]
            },
            options: {
                ...commonBarOptions,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.parsed.y} دقيقة`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        @else
        new Chart(teamMinutesCtx, {
            type: 'bar',
            data: {
                labels: ['إجمالي دقائق الفريق'],
                datasets: [{
                    label: 'الدقائق',
                    data: [{{ $statistics['team']['total_minutes'] }}],
                    backgroundColor: colors.approved.bg,
                    borderColor: colors.approved.border,
                    borderWidth: 1
                }]
            },
            options: commonBarOptions
        });
        @endif
    }
    @endif

    // HR Statistics Charts
    @if(isset($statistics) && isset($statistics['hr']))
    const hrRequestsCtx = document.getElementById('hrRequestsChart')?.getContext('2d');
    if (hrRequestsCtx) {
        const totalRequests = {{ $statistics['hr']['total_requests'] }};
        const pendingRequests = {{ $statistics['hr']['pending_requests'] }};
        const exceededLimit = {{ $statistics['hr']['employees_exceeded_limit'] }};
        const normalRequests = totalRequests - pendingRequests - exceededLimit;

        new Chart(hrRequestsCtx, {
            type: 'pie',
            data: {
                labels: ['طلبات عادية', 'طلبات معلقة', 'تجاوزوا الحد'],
                datasets: [{
                    data: [normalRequests, pendingRequests, exceededLimit],
                    backgroundColor: [colors.approved.bg, colors.pending.bg, colors.rejected.bg],
                    borderColor: [colors.approved.border, colors.pending.border, colors.rejected.border],
                    borderWidth: 1
                }]
            },
            options: commonPieOptions
        });
    }

    const hrDepartmentMinutesCtx = document.getElementById('hrDepartmentMinutesChart')?.getContext('2d');
    if (hrDepartmentMinutesCtx) {
        // Get department statistics if available
        @if(isset($statistics['hr']['departments']) && count($statistics['hr']['departments']) > 0)
        const departments = @json($statistics['hr']['departments']);
        const labels = departments.map(dept => dept.name);
        const minutes = departments.map(dept => dept.total_minutes);
        const avgMinutes = departments.map(dept => dept.avg_minutes);

        new Chart(hrDepartmentMinutesCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'إجمالي الدقائق',
                        data: minutes,
                        backgroundColor: colors.approved.bg,
                        borderColor: colors.approved.border,
                        borderWidth: 1
                    },
                    {
                        label: 'متوسط الدقائق للموظف',
                        data: avgMinutes,
                        backgroundColor: colors.onTime.bg,
                        borderColor: colors.onTime.border,
                        borderWidth: 1
                    }
                ]
            },
            options: {
                ...commonBarOptions,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        rtl: true
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const datasetIndex = context.datasetIndex;
                                if (datasetIndex === 0) {
                                    return `إجمالي الدقائق: ${context.parsed.y} دقيقة`;
                                } else {
                                    return `متوسط الدقائق للموظف: ${context.parsed.y} دقيقة`;
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        @else
        new Chart(hrDepartmentMinutesCtx, {
            type: 'bar',
            data: {
                labels: ['إجمالي دقائق الشركة'],
                datasets: [{
                    label: 'الدقائق',
                    data: [{{ $statistics['hr']['total_minutes'] }}],
                    backgroundColor: colors.approved.bg,
                    borderColor: colors.approved.border,
                    borderWidth: 1
                }]
            },
            options: commonBarOptions
        });
        @endif
    }
    @endif
});
</script>
@endpush

@section('content')
<div class="container">
    @if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif

    <!-- قسم البحث والفلترة -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('permission-requests.index') }}" class="row g-3">
                @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
                <div class="col-md-3">
                    <label for="employee_name" class="form-label">بحث عن موظف</label>
                    <input type="text" class="form-control" id="employee_name" name="employee_name"
                        value="{{ request('employee_name') }}" placeholder="ادخل اسم الموظف" list="employee_names">
                    <datalist id="employee_names">
                        @foreach($users as $user)
                        <option value="{{ $user->name }}">
                            @endforeach
                    </datalist>
                </div>
                @endif

                <div class="col-md-2">
                    <label for="status" class="form-label">تصفية حسب الحالة</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">كل الحالات</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>معلق</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>موافق عليه</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>مرفوض</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="from_date" class="form-label">من تاريخ</label>
                    <input type="date" class="form-control" id="from_date" name="from_date"
                        value="{{ request('from_date') }}">
                </div>

                <div class="col-md-2">
                    <label for="to_date" class="form-label">إلى تاريخ</label>
                    <input type="date" class="form-control" id="to_date" name="to_date"
                        value="{{ request('to_date') }}">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>تطبيق الفلتر
                    </button>
                    <a href="{{ route('permission-requests.index') }}" class="btn btn-secondary ms-2">
                        <i class="fas fa-undo me-2"></i>إعادة تعيين
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- قسم الإحصائيات -->
    @if(isset($statistics))
    <div class="row mb-4">
        <!-- الإحصائيات الشخصية -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-clock"></i> إحصائيات طلباتي</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">إجمالي الطلبات</h6>
                                <h4 class="mb-0">{{ $statistics['personal']['total_requests'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">الدقائق المستخدمة</h6>
                                <h4 class="mb-0">{{ $statistics['personal']['total_minutes'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-success">
                                <h6 class="mb-2">تمت الموافقة</h6>
                                <h4 class="mb-0">{{ $statistics['personal']['approved_requests'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-danger">
                                <h6 class="mb-2">مرفوضة</h6>
                                <h4 class="mb-0">{{ $statistics['personal']['rejected_requests'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-warning">
                                <h6 class="mb-2">معلقة</h6>
                                <h4 class="mb-0">{{ $statistics['personal']['pending_requests'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 text-success">
                                <h6 class="mb-2">عودة في الوقت</h6>
                                <h4 class="mb-0">{{ $statistics['personal']['on_time_returns'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 text-danger">
                                <h6 class="mb-2">عودة متأخرة</h6>
                                <h4 class="mb-0">{{ $statistics['personal']['late_returns'] }}</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Charts for Personal Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="chart-card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> توزيع حالات الطلبات</h5>
                                </div>
                                <div class="card-body p-3">
                                    <div class="chart-container">
                                        <canvas id="personalRequestsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> توزيع الدقائق</h5>
                                </div>
                                <div class="card-body p-3">
                                    <div class="chart-container">
                                        <canvas id="personalMinutesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات الفريق -->
        @if((Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager']) ||
        (Auth::user()->hasRole('hr') && (Auth::user()->ownedTeams->count() > 0 || Auth::user()->teams()->wherePivot('role', 'admin')->exists())))
        && isset($statistics['team']))
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> إحصائيات الفريق
                        @if(isset($statistics['team']['team_name']))
                        <small>({{ $statistics['team']['team_name'] }})</small>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">إجمالي طلبات الفريق</h6>
                                <h4 class="mb-0">{{ $statistics['team']['total_requests'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">إجمالي الدقائق</h6>
                                <h4 class="mb-0">{{ $statistics['team']['total_minutes'] }}</h4>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">تجاوزوا الحد المسموح</h6>
                                <h4 class="mb-0 text-danger">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#teamExceededLimitModal" class="text-danger text-decoration-none">
                                        {{ $statistics['team']['employees_exceeded_limit'] }} موظفين
                                    </a>
                                </h4>
                            </div>
                        </div>
                        @if($statistics['team']['most_requested_employee'])
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">الأكثر طلباً للاستئذان</h6>
                                <h5 class="mb-1">{{ $statistics['team']['most_requested_employee']['name'] }}</h5>
                                <small class="text-muted">{{ $statistics['team']['most_requested_employee']['count'] }} طلبات</small>
                            </div>
                        </div>
                        @endif
                        @if($statistics['team']['highest_minutes_employee'])
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">الأكثر استخداماً للدقائق</h6>
                                <h5 class="mb-1">{{ $statistics['team']['highest_minutes_employee']['name'] }}</h5>
                                <small class="text-muted">{{ $statistics['team']['highest_minutes_employee']['minutes'] }} دقيقة</small>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Charts for Team Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="chart-card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> توزيع طلبات الفريق</h5>
                                </div>
                                <div class="card-body p-3">
                                    <div class="chart-container">
                                        <canvas id="teamRequestsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> توزيع دقائق الفريق</h5>
                                </div>
                                <div class="card-body p-3">
                                    <div class="chart-container">
                                        <canvas id="teamMinutesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- قسم الإحصائيات HR -->
    @if(Auth::user()->hasRole('hr') && isset($statistics['hr']))
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> إحصائيات جميع الموظفين</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">إجمالي الطلبات</h6>
                                <h4 class="mb-0">{{ $statistics['hr']['total_requests'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">إجمالي الدقائق</h6>
                                <h4 class="mb-0">{{ $statistics['hr']['total_minutes'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">تجاوزوا الحد المسموح</h6>
                                <h4 class="mb-0 text-danger">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#exceededLimitModal" class="text-danger text-decoration-none">
                                        {{ $statistics['hr']['employees_exceeded_limit'] }} موظفين
                                    </a>
                                </h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">الطلبات المعلقة</h6>
                                <h4 class="mb-0 text-warning">{{ $statistics['hr']['pending_requests'] }}</h4>
                            </div>
                        </div>
                        @if($statistics['hr']['most_requested_employee'])
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">الأكثر طلباً للاستئذان</h6>
                                <h5 class="mb-1">{{ $statistics['hr']['most_requested_employee']['name'] }}</h5>
                                <small class="text-muted">{{ $statistics['hr']['most_requested_employee']['count'] }} طلبات</small>
                            </div>
                        </div>
                        @endif
                        @if($statistics['hr']['highest_minutes_employee'])
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">الأكثر استخداماً للدقائق</h6>
                                <h5 class="mb-1">{{ $statistics['hr']['highest_minutes_employee']['name'] }}</h5>
                                <small class="text-muted">{{ $statistics['hr']['highest_minutes_employee']['minutes'] }} دقيقة</small>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Charts for HR Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="chart-card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> توزيع حالات الطلبات</h5>
                                </div>
                                <div class="card-body p-3">
                                    <div class="chart-container">
                                        <canvas id="hrRequestsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> توزيع الدقائق حسب الأقسام</h5>
                                </div>
                                <div class="card-body p-3">
                                    <div class="chart-container">
                                        <canvas id="hrDepartmentMinutesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal للموظفين المتجاوزين -->
    <div class="modal fade" id="exceededLimitModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">الموظفين المتجاوزين للحد المسموح</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>اسم الموظف</th>
                                    <th>الدقائق المستخدمة</th>
                                    <th>تجاوز الحد بـ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($statistics['hr']['exceeded_employees'] ?? [] as $index => $employee)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $employee['name'] }}</td>
                                    <td>{{ $employee['total_minutes'] }} دقيقة</td>
                                    <td class="text-danger">{{ $employee['total_minutes'] - 180 }} دقيقة</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @endif

    <!-- عرض الدقائق المستخدمة في الفترة المحددة -->
    <div class="alert alert-info mb-4">
        <i class="fas fa-calendar-alt me-2"></i>
        الفترة: {{ $dateStart->format('Y-m-d') }} إلى {{ $dateEnd->format('Y-m-d') }}
        <br>
        @php
        $periodUsedMinutes = PermissionRequest::where('user_id', Auth::id())
        ->where('status', 'approved')
        ->whereBetween('departure_time', [$dateStart, $dateEnd])
        ->sum('minutes_used');
        @endphp
        <i class="fas fa-clock me-2"></i>
        استخدمت {{ $periodUsedMinutes }} دقيقة في هذه الفترة
        @if($periodUsedMinutes > 180)
        <span class="text-danger">
            (تجاوزت الحد الشهري بـ {{ $periodUsedMinutes - 180 }} دقيقة)
        </span>
        @endif
    </div>

    <!-- جدول طلباتي -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clock"></i> طلبات الاستئذان
                        <small class="ms-2">
                            @php
                            $totalUsedMinutes = PermissionRequest::where('user_id', Auth::id())
                            ->where('status', 'approved')
                            ->whereBetween('departure_time', [$dateStart, $dateEnd])
                            ->sum('minutes_used');
                            @endphp
                            <span title="الحد الشهري المسموح: 180 دقيقة">
                                استخدمت {{ $totalUsedMinutes }} دقيقة في الفترة من {{ $dateStart->format('Y-m-d') }} إلى {{ $dateEnd->format('Y-m-d') }}
                                @if($totalUsedMinutes > 180)
                                <span class="text-danger">
                                    (تجاوزت الحد الشهري بـ {{ $totalUsedMinutes - 180 }} دقيقة)
                                </span>
                                @endif
                            </span>
                        </small>
                    </h5>
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
                        <i class="fas fa-plus"></i> طلب جديد
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>وقت المغادرة</th>
                                <th>وقت العودة</th>
                                <th>المدة</th>
                                <th>السبب</th>
                                <th>رد المدير</th>
                                <th>رد HR</th>
                                <th>الحالة النهائية</th>
                                <th>حالة العودة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($myRequests as $request)
                            <tr class="request-row">
                                <td>{{ \Carbon\Carbon::parse($request->departure_time)->format('Y-m-d H:i') }}</td>
                                <td>{{ \Carbon\Carbon::parse($request->return_time)->format('Y-m-d H:i') }}</td>
                                <td>{{ $request->minutes_used }} دقيقة</td>
                                <td>{{ $request->reason }}</td>
                                <td>
                                    <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->manager_status === 'approved' ? 'موافق' : ($request->manager_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->hr_status === 'approved' ? 'موافق' : ($request->hr_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->status === 'approved' ? 'موافق' : ($request->status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>{{ $request->getReturnStatusLabel() }}</td>
                                <td>
                                    <div class="action-buttons">
                                        @if($request->status === 'approved')
                                        <div class="btn-group" role="group">
                                            @php
                                            $returnTime = \Carbon\Carbon::parse($request->return_time);
                                            $now = \Carbon\Carbon::now()->setTimezone('Africa/Cairo');
                                            $endOfWorkDay = \Carbon\Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0);
                                            $isBeforeEndOfDay = $now->lt($endOfWorkDay);
                                            $isSameDay = $now->isSameDay($returnTime);
                                            $departureTime = \Carbon\Carbon::parse($request->departure_time);
                                            $isAfterDeparture = $now->gte($departureTime);
                                            @endphp

                                            <div class="d-flex align-items-center">
                                                @if($request->shouldShowCountdown())
                                                <div class="countdown-timer countdown" data-return-time="{{ $returnTime->format('Y-m-d H:i:s') }}">
                                                    <div class="timer-label">الوقت المتبقي</div>
                                                    <div class="timer-value"></div>
                                                </div>
                                                @else
                                                <!-- عداد بديل للتحقق من المشكلة -->
                                                <div class="p-2 bg-light border rounded mb-2">
                                                    <p class="m-0"><small>العداد غير ظاهر لأن:</small></p>
                                                    <p class="m-0"><small>قيمة returned_on_time: [{{ $request->returned_on_time === null ? 'null' : $request->returned_on_time }}]</small></p>
                                                    <p class="m-0"><small>{{ $request->returned_on_time == 1 ? 'العودة مسجلة (عاد)' : ($request->returned_on_time == 2 ? 'العودة مسجلة (لم يعد)' : '') }}</small></p>
                                                    <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->isSameDay($returnTime) ? 'ليس في نفس اليوم' : '' }}</small></p>
                                                    <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->lt(\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0)) ? 'تجاوز نهاية يوم العمل' : '' }}</small></p>
                                                    <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->gte(\Carbon\Carbon::parse($request->departure_time)) ? 'لم يبدأ وقت المغادرة بعد' : '' }}</small></p>
                                                </div>
                                                @endif

                                                <!-- زر الرجوع - يظهر فقط لصاحب الطلب ويعمل فقط في الوقت المناسب -->
                                                @if($request->canMarkAsReturned(Auth::user()))
                                                <button type="button"
                                                    class="btn btn-success btn-sm return-btn me-2"
                                                    data-request-id="{{ $request->id }}"
                                                    data-status="1">
                                                    <i class="fas fa-check me-1"></i>رجع
                                                </button>
                                                @endif

                                                <!-- زر "لم يرجع" - يظهر فقط للمدراء وHR -->
                                                @if(Auth::user()->hasRole(['hr', 'team_leader', 'department_manager', 'company_manager']))
                                                <button type="button"
                                                    class="btn btn-danger btn-sm return-btn me-2"
                                                    data-request-id="{{ $request->id }}"
                                                    data-status="2"
                                                    {{ $request->returned_on_time === true || $request->returned_on_time == 2 ? 'disabled' : '' }}>
                                                    <i class="fas fa-times me-1"></i>لم يرجع
                                                </button>
                                                @endif

                                                <!-- زر إعادة تعيين - يظهر لصاحب الطلب فقط إذا لم يكن وقت العودة قد انتهى -->
                                                @if($request->canResetReturnStatus(Auth::user()))
                                                <button type="button"
                                                    class="btn btn-secondary btn-sm reset-btn"
                                                    data-request-id="{{ $request->id }}"
                                                    data-status="0">
                                                    <i class="fas fa-undo me-1"></i>إعادة تعيين
                                                </button>
                                                @endif
                                            </div>
                                        </div>
                                        @endif

                                        @if($request->manager_status === 'pending' && $request->hr_status === 'pending')
                                        <button class="btn btn-sm btn-warning edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editPermissionModal"
                                            data-id="{{ $request->id }}"
                                            data-departure="{{ $request->departure_time }}"
                                            data-return="{{ $request->return_time }}"
                                            data-reason="{{ $request->reason }}">
                                            <i class="fas fa-edit"></i> تعديل
                                        </button>

                                        <form action="{{ route('permission-requests.destroy', $request) }}"
                                            method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('هل أنت متأكد من الحذف؟')">
                                                <i class="fas fa-trash"></i> حذف
                                            </button>
                                        </form>
                                        @endif


                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">لا توجد طلبات استئذان</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $myRequests->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- جدول طلبات الفريق -->
    @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> طلبات الفريق
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>الموظف</th>
                                <th>وقت المغادرة</th>
                                <th>وقت العودة</th>
                                <th>المدة</th>
                                <th>الدقائق المتبقية</th>
                                <th>السبب</th>
                                <th>رد المدير</th>
                                <th>سبب رفض المدير</th>
                                <th>رد HR</th>
                                <th>سبب رفض HR</th>
                                <th>الحالة النهائية</th>
                                <th>حالة العودة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($teamRequests as $request)
                            <tr class="request-row">
                                <!-- بيانات الطلب -->
                                <td>{{ $request->user->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($request->departure_time)->format('Y-m-d H:i') }}</td>
                                <td>{{ \Carbon\Carbon::parse($request->return_time)->format('Y-m-d H:i') }}</td>
                                <td>{{ $request->minutes_used }} دقيقة</td>
                                <td>
                                    @php
                                    $userTotalMinutes = PermissionRequest::where('user_id', $request->user_id)
                                    ->where('status', 'approved')
                                    ->whereBetween('departure_time', [$dateStart, $dateEnd])
                                    ->sum('minutes_used');
                                    @endphp
                                    <span title="الحد الشهري المسموح: 180 دقيقة">
                                        استخدم {{ $userTotalMinutes }} دقيقة في الفترة المحددة
                                        @if($userTotalMinutes > 180)
                                        <br>
                                        <small class="text-danger">
                                            (تجاوز الحد بـ {{ $userTotalMinutes - 180 }} دقيقة)
                                        </small>
                                        @endif
                                    </span>
                                </td>
                                <td>{{ $request->reason }}</td>
                                <td>
                                    <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->manager_status === 'approved' ? 'موافق' : ($request->manager_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>{{ $request->manager_rejection_reason ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->hr_status === 'approved' ? 'موافق' : ($request->hr_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>{{ $request->hr_rejection_reason ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->status === 'approved' ? 'موافق' : ($request->status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>{{ $request->getReturnStatusLabel() }}</td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- أزرار الرد للمدراء و HR -->
                                        @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager']) && $request->user->teams()->exists())
                                        @if($request->manager_status === 'pending')
                                        <button class="btn btn-sm btn-info respond-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#respondModal"
                                            data-request-id="{{ $request->id }}"
                                            data-response-type="manager">
                                            <i class="fas fa-reply"></i> رد المدير
                                        </button>
                                        @else
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-warning modify-response-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modifyResponseModal"
                                                data-request-id="{{ $request->id }}"
                                                data-response-type="manager"
                                                data-status="{{ $request->manager_status }}"
                                                data-reason="{{ $request->manager_rejection_reason }}">
                                                <i class="fas fa-edit"></i> تعديل الرد
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary"
                                                onclick="resetStatus('{{ $request->id }}', 'manager')">
                                                <i class="fas fa-undo"></i> إعادة تعيين
                                            </button>
                                        </div>
                                        @endif
                                        @endif

                                        @if(Auth::user()->hasRole('hr'))
                                        @if($request->hr_status === 'pending')
                                        <button class="btn btn-sm btn-info respond-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#respondModal"
                                            data-request-id="{{ $request->id }}"
                                            data-response-type="hr">
                                            <i class="fas fa-reply"></i> رد HR
                                        </button>
                                        @else
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-warning modify-response-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modifyResponseModal"
                                                data-request-id="{{ $request->id }}"
                                                data-response-type="hr"
                                                data-status="{{ $request->hr_status }}"
                                                data-reason="{{ $request->hr_rejection_reason }}">
                                                <i class="fas fa-edit"></i> تعديل رد HR
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary"
                                                onclick="resetStatus('{{ $request->id }}', 'hr')">
                                                <i class="fas fa-undo"></i> إعادة تعيين
                                            </button>
                                        </div>
                                        @endif
                                        @endif

                                        <!-- أزرار حالة العودة -->
                                        @if($request->status === 'approved')
                                        <div class="btn-group" role="group">
                                            @php
                                            $returnTime = \Carbon\Carbon::parse($request->return_time);
                                            $now = \Carbon\Carbon::now()->setTimezone('Africa/Cairo');
                                            $endOfWorkDay = \Carbon\Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0);
                                            $isBeforeEndOfDay = $now->lt($endOfWorkDay);
                                            $isSameDay = $now->isSameDay($returnTime);
                                            $departureTime = \Carbon\Carbon::parse($request->departure_time);
                                            $isAfterDeparture = $now->gte($departureTime);
                                            @endphp

                                            <div class="d-flex align-items-center">
                                                @if($request->shouldShowCountdown())
                                                <div class="countdown-timer countdown" data-return-time="{{ $returnTime->format('Y-m-d H:i:s') }}">
                                                    <div class="timer-label">الوقت المتبقي</div>
                                                    <div class="timer-value"></div>
                                                </div>
                                                @else
                                                <!-- عداد بديل للتحقق من المشكلة -->
                                                <div class="p-2 bg-light border rounded mb-2">
                                                    <p class="m-0"><small>العداد غير ظاهر لأن:</small></p>
                                                    <p class="m-0"><small>قيمة returned_on_time: [{{ $request->returned_on_time === null ? 'null' : $request->returned_on_time }}]</small></p>
                                                    <p class="m-0"><small>{{ $request->returned_on_time == 1 ? 'العودة مسجلة (عاد)' : ($request->returned_on_time == 2 ? 'العودة مسجلة (لم يعد)' : '') }}</small></p>
                                                    <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->isSameDay($returnTime) ? 'ليس في نفس اليوم' : '' }}</small></p>
                                                    <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->lt(\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0)) ? 'تجاوز نهاية يوم العمل' : '' }}</small></p>
                                                    <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->gte(\Carbon\Carbon::parse($request->departure_time)) ? 'لم يبدأ وقت المغادرة بعد' : '' }}</small></p>
                                                </div>
                                                @endif

                                                <button type="button"
                                                    class="btn btn-success btn-sm return-btn me-2"
                                                    data-request-id="{{ $request->id }}"
                                                    data-status="1"
                                                    {{ $request->returned_on_time === true || $request->returned_on_time == 2 ? 'disabled' : '' }}>
                                                    <i class="fas fa-check me-1"></i>رجع
                                                </button>

                                                <!-- تعديل زر "لم يرجع" -->
                                                @if(Auth::user()->hasRole(['hr', 'team_leader', 'department_manager', 'company_manager']))
                                                <button type="button"
                                                    class="btn btn-danger btn-sm return-btn me-2"
                                                    data-request-id="{{ $request->id }}"
                                                    data-status="2"
                                                    {{ $request->returned_on_time === true || $request->returned_on_time == 2 ? 'disabled' : '' }}>
                                                    <i class="fas fa-times me-1"></i>لم يرجع
                                                </button>
                                                @endif

                                                <button type="button"
                                                    class="btn btn-secondary btn-sm reset-btn"
                                                    data-request-id="{{ $request->id }}"
                                                    data-status="0"
                                                    {{ $request->returned_on_time === false ? 'disabled' : '' }}>
                                                    <i class="fas fa-undo me-1"></i>إعادة تعيين
                                                </button>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </td>

                            </tr>
                            @empty
                            <tr>
                                <td colspan="13" class="text-center">لا توجد طلبات استئذان</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $teamRequests->links() }}
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- جدول طلبات موظفي الشركه (لل HR فقط) -->
    @if(Auth::user()->hasRole('hr'))
    <div class="row justify-content-center mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-building"></i> طلبات موظفي الشركه
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>الموظف</th>
                                <th>وقت المغادرة</th>
                                <th>وقت العودة</th>
                                <th>المدة</th>
                                <th>الدقائق المتبقية</th>
                                <th>السبب</th>
                                <th>رد المدير</th>
                                <th>سبب رفض المدير</th>
                                <th>رد HR</th>
                                <th>سبب رفض HR</th>
                                <th>الحالة النهائية</th>
                                <th>حالة العودة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($hrRequests as $request)
                            <tr class="request-row">
                                <td>{{ $request->user->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($request->departure_time)->format('Y-m-d H:i') }}</td>
                                <td>{{ \Carbon\Carbon::parse($request->return_time)->format('Y-m-d H:i') }}</td>
                                <td>{{ $request->minutes_used }} دقيقة</td>
                                <td>
                                    @php
                                    $userTotalMinutes = PermissionRequest::where('user_id', $request->user_id)
                                    ->where('status', 'approved')
                                    ->whereBetween('departure_time', [$dateStart, $dateEnd])
                                    ->sum('minutes_used');
                                    @endphp
                                    <span title="الحد الشهري المسموح: 180 دقيقة">
                                        استخدم {{ $userTotalMinutes }} دقيقة في الفترة المحددة
                                        @if($userTotalMinutes > 180)
                                        <br>
                                        <small class="text-danger">
                                            (تجاوز الحد بـ {{ $userTotalMinutes - 180 }} دقيقة)
                                        </small>
                                        @endif
                                    </span>
                                </td>
                                <td>{{ $request->reason }}</td>
                                <td>
                                    <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->manager_status === 'approved' ? 'موافق' : ($request->manager_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>{{ $request->manager_rejection_reason ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->hr_status === 'approved' ? 'موافق' : ($request->hr_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>{{ $request->hr_rejection_reason ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->status === 'approved' ? 'موافق' : ($request->status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>{{ $request->getReturnStatusLabel() }}</td>
                                <td>
                                    @if($request->hr_status === 'pending')
                                    <button class="btn btn-sm btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr">
                                        <i class="fas fa-reply"></i> رد HR
                                    </button>
                                    @else
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-warning modify-response-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modifyResponseModal"
                                            data-request-id="{{ $request->id }}"
                                            data-response-type="hr"
                                            data-status="{{ $request->hr_status }}"
                                            data-reason="{{ $request->hr_rejection_reason }}">
                                            <i class="fas fa-edit"></i> تعديل رد HR
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary"
                                            onclick="resetStatus('{{ $request->id }}', 'hr')">
                                            <i class="fas fa-undo"></i> إعادة تعيين
                                        </button>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="13" class="text-center">لا توجد طلبات استئذان</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $hrRequests->links() }}
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- جدول طلبات الموظفين بدون فريق (لل HR فقط) -->
    @if(Auth::user()->hasRole('hr'))
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-users"></i> طلبات الموظفين (بدون فريق)
            </h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>الموظف</th>
                        <th>وقت المغادرة</th>
                        <th>وقت العودة</th>
                        <th>المدة</th>
                        <th>الدقائق المتبقية</th>
                        <th>السبب</th>
                        <th>رد HR</th>
                        <th>سبب رفض HR</th>
                        <th>الحالة النهائية</th>
                        <th>حالة العودة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($noTeamRequests ?? [] as $request)
                    <tr class="request-row">
                        <td>{{ $request->user->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($request->departure_time)->format('Y-m-d H:i') }}</td>
                        <td>{{ \Carbon\Carbon::parse($request->return_time)->format('Y-m-d H:i') }}</td>
                        <td>{{ $request->minutes_used }} دقيقة</td>
                        <td>
                            @php
                            $userTotalMinutes = PermissionRequest::where('user_id', $request->user_id)
                            ->where('status', 'approved')
                            ->whereBetween('departure_time', [$dateStart, $dateEnd])
                            ->sum('minutes_used');
                            @endphp
                            <span title="الحد الشهري المسموح: 180 دقيقة">
                                استخدم {{ $userTotalMinutes }} دقيقة في الفترة المحددة
                                @if($userTotalMinutes > 180)
                                <br>
                                <small class="text-danger">
                                    (تجاوز الحد بـ {{ $userTotalMinutes - 180 }} دقيقة)
                                </small>
                                @endif
                            </span>
                        </td>
                        <td>{{ $request->reason }}</td>
                        <td>
                            <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ $request->hr_status === 'approved' ? 'موافق' : ($request->hr_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                            </span>
                        </td>
                        <td>{{ $request->hr_rejection_reason ?? '-' }}</td>
                        <td>
                            <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ $request->status === 'approved' ? 'موافق' : ($request->status === 'rejected' ? 'مرفوض' : 'معلق') }}
                            </span>
                        </td>
                        <td>{{ $request->getReturnStatusLabel() }}</td>
                        <td>
                            <div class="action-buttons">
                                @if($request->hr_status === 'pending')
                                <button class="btn btn-sm btn-info respond-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#respondModal"
                                    data-request-id="{{ $request->id }}"
                                    data-response-type="hr">
                                    <i class="fas fa-reply"></i> رد HR
                                </button>
                                @else
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr"
                                        data-status="{{ $request->hr_status }}"
                                        data-reason="{{ $request->hr_rejection_reason }}">
                                        <i class="fas fa-edit"></i> تعديل رد HR
                                    </button>

                                    <form action="{{ route('permission-requests.reset-hr-status', $request) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        <button type="submit"
                                            class="btn btn-sm btn-secondary"
                                            onclick="return confirm('هل أنت متأكد من إعادة تعيين الرد؟')">
                                            <i class="fas fa-undo"></i> إعادة تعيين
                                        </button>
                                    </form>
                                </div>
                                @endif

                                <!-- أزرار حالة العودة -->
                                @if($request->status === 'approved')
                                <div class="btn-group" role="group">
                                    @php
                                    $returnTime = \Carbon\Carbon::parse($request->return_time);
                                    $now = \Carbon\Carbon::now()->setTimezone('Africa/Cairo');
                                    $endOfWorkDay = \Carbon\Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0);
                                    $isBeforeEndOfDay = $now->lt($endOfWorkDay);
                                    $isSameDay = $now->isSameDay($returnTime);
                                    $departureTime = \Carbon\Carbon::parse($request->departure_time);
                                    $isAfterDeparture = $now->gte($departureTime);
                                    @endphp

                                    <div class="d-flex align-items-center">
                                        @if($request->shouldShowCountdown())
                                        <div class="countdown-timer countdown" data-return-time="{{ $returnTime->format('Y-m-d H:i:s') }}">
                                            <div class="timer-label">الوقت المتبقي</div>
                                            <div class="timer-value"></div>
                                        </div>
                                        @else
                                        <!-- عداد بديل للتحقق من المشكلة -->
                                        <div class="p-2 bg-light border rounded mb-2">
                                            <p class="m-0"><small>العداد غير ظاهر لأن:</small></p>
                                            <p class="m-0"><small>قيمة returned_on_time: [{{ $request->returned_on_time === null ? 'null' : $request->returned_on_time }}]</small></p>
                                            <p class="m-0"><small>{{ $request->returned_on_time == 1 ? 'العودة مسجلة (عاد)' : ($request->returned_on_time == 2 ? 'العودة مسجلة (لم يعد)' : '') }}</small></p>
                                            <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->isSameDay($returnTime) ? 'ليس في نفس اليوم' : '' }}</small></p>
                                            <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->lt(\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0)) ? 'تجاوز نهاية يوم العمل' : '' }}</small></p>
                                            <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->gte(\Carbon\Carbon::parse($request->departure_time)) ? 'لم يبدأ وقت المغادرة بعد' : '' }}</small></p>
                                        </div>
                                        @endif

                                        <button type="button"
                                            class="btn btn-success btn-sm return-btn me-2"
                                            data-request-id="{{ $request->id }}"
                                            data-status="1"
                                            {{ $request->returned_on_time === true || $request->returned_on_time == 2 ? 'disabled' : '' }}>
                                            <i class="fas fa-check me-1"></i>رجع
                                        </button>

                                        <!-- تعديل زر "لم يرجع" -->
                                        @if(Auth::user()->hasRole(['hr', 'team_leader', 'department_manager', 'company_manager']))
                                        <button type="button"
                                            class="btn btn-danger btn-sm return-btn me-2"
                                            data-request-id="{{ $request->id }}"
                                            data-status="2"
                                            {{ $request->returned_on_time === true || $request->returned_on_time == 2 ? 'disabled' : '' }}>
                                            <i class="fas fa-times me-1"></i>لم يرجع
                                        </button>
                                        @endif

                                        <button type="button"
                                            class="btn btn-secondary btn-sm reset-btn"
                                            data-request-id="{{ $request->id }}"
                                            data-status="0"
                                            {{ $request->returned_on_time === false ? 'disabled' : '' }}>
                                            <i class="fas fa-undo me-1"></i>إعادة تعيين
                                        </button>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center">لا توجد طلبات استئذان</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @if($noTeamRequests instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $noTeamRequests->links() }}
            @endif
        </div>
    </div>
</div>
@endif



<!-- Create Modal -->
<div class="modal fade" id="createPermissionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('permission-requests.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title">طلب استئذان جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
                    <div class="mb-4">
                        <label class="form-label fw-bold">نوع الطلب</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="registration_type" id="self_registration" value="self" checked>
                            <label class="btn btn-outline-primary" for="self_registration">
                                <i class="fas fa-user me-2"></i>لنفسي
                            </label>

                            <input type="radio" class="btn-check" name="registration_type" id="other_registration" value="other">
                            <label class="btn btn-outline-primary" for="other_registration">
                                <i class="fas fa-users me-2"></i>لموظف آخر
                            </label>
                        </div>
                    </div>

                    <div class="mb-4" id="employee_select_container" style="display: none;">
                        <label for="user_id" class="form-label">اختر الموظف</label>
                        <select name="user_id" id="user_id" class="form-select">
                            <option value="" disabled selected>اختر موظف...</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="departure_time" class="form-label">وقت المغادرة</label>
                        <input type="datetime-local"
                            class="form-control"
                            id="departure_time"
                            name="departure_time"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="return_time" class="form-label">وقت العودة</label>
                        <input type="datetime-local"
                            class="form-control"
                            id="return_time"
                            name="return_time"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">السبب</label>
                        <textarea class="form-control"
                            id="reason"
                            name="reason"
                            required
                            rows="3"
                            maxlength="255"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إرسال الطلب</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editPermissionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editPermissionForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-0">
                    <h5 class="modal-title">تعديل طلب الاستئذان</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_departure_time" class="form-label">وقت المغادرة</label>
                        <input type="datetime-local"
                            class="form-control"
                            id="edit_departure_time"
                            name="departure_time"
                            required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_return_time" class="form-label">وقت العودة</label>
                        <input type="datetime-local"
                            class="form-control"
                            id="edit_return_time"
                            name="return_time"
                            required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_reason" class="form-label">السبب</label>
                        <textarea class="form-control"
                            id="edit_reason"
                            name="reason"
                            required
                            rows="3"
                            maxlength="255"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Respond Modal -->
<div class="modal fade" id="respondModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="respondForm" method="POST">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title">الرد على الطلب</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="response_type" id="response_type">

                    <div class="mb-3">
                        <label class="form-label">الحالة</label>
                        <select class="form-select" id="response_status" name="status" required>
                            <option value="approved">موافق</option>
                            <option value="rejected">مرفوض</option>
                        </select>
                    </div>

                    <div class="mb-3" id="rejection_reason_container" style="display: none;">
                        <label class="form-label">سبب الرفض</label>
                        <textarea class="form-control"
                            id="rejection_reason"
                            name="rejection_reason"
                            maxlength="255"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ الرد</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modify Response Modal -->
<div class="modal fade" id="modifyResponseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="modifyResponseForm" method="POST">
                @csrf
                <input type="hidden" name="response_type" id="modify_response_type">

                <div class="modal-header border-0">
                    <h5 class="modal-title">تعديل الرد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الحالة</label>
                        <select class="form-select" id="modify_status" name="status" required>
                            <option value="approved">موافق</option>
                            <option value="rejected">مرفوض</option>
                        </select>
                    </div>

                    <div class="mb-3" id="modify_reason_container" style="display: none;">
                        <label class="form-label">سبب الرفض</label>
                        <textarea class="form-control"
                            id="modify_reason"
                            name="rejection_reason"
                            maxlength="255"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<!-- Modal للموظفين المتجاوزين في الفريق -->
<div class="modal fade" id="teamExceededLimitModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">الموظفين المتجاوزين للحد المسموح في الفريق</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم الموظف</th>
                                <th>الدقائق المستخدمة</th>
                                <th>تجاوز الحد بـ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statistics['team']['exceeded_employees'] ?? [] as $index => $employee)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $employee['name'] }}</td>
                                <td>{{ $employee['total_minutes'] }} دقيقة</td>
                                <td class="text-danger">{{ $employee['total_minutes'] - 180 }} دقيقة</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // دالة تحديث التايمر
        function startCountdown(element) {
            const returnTime = new Date(element.dataset.returnTime).getTime();
            let timerLabel = element.querySelector('.timer-label');

            function updateTimer() {
                const now = new Date().getTime();
                const distance = returnTime - now;

                // إذا تخطى وقت العودة، نعرض الوقت الإضافي المستهلك
                if (distance < 0) {
                    // تغيير النص من "الوقت المتبقي" إلى "متأخر بـ"
                    timerLabel.textContent = "متأخر بـ";
                    element.classList.add('danger');

                    // حساب الوقت المتأخر (مقلوب distance لأنه سالب)
                    const overtime = Math.abs(distance);
                    const hours = Math.floor((overtime % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((overtime % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((overtime % (1000 * 60)) / 1000);

                    let timeDisplay = '';
                    if (hours > 0) {
                        timeDisplay += `${hours}:`;
                    }
                    timeDisplay += `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

                    element.querySelector('.timer-value').innerHTML = timeDisplay;

                    // لا نقوم بتعطيل زر العودة حتى يتمكن الموظف من تسجيل عودته
                    // const returnBtn = element.closest('.d-flex').querySelector('.return-btn');
                    // if (returnBtn) returnBtn.disabled = true;

                    return true; // استمر في تحديث العداد
                }

                // وقت العودة لم ينته بعد - عرض الوقت المتبقي
                timerLabel.textContent = "الوقت المتبقي";
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                // تحديث شكل التايمر حسب الوقت المتبقي
                element.classList.remove('warning', 'danger');
                if (minutes < 5) {
                    element.classList.add('danger');
                } else if (minutes < 10) {
                    element.classList.add('warning');
                }

                let timeDisplay = '';
                if (hours > 0) {
                    timeDisplay += `${hours}:`;
                }
                timeDisplay += `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

                element.querySelector('.timer-value').innerHTML = timeDisplay;
                return true;
            }

            // تحديث التايمر كل ثانية
            if (updateTimer()) {
                return setInterval(updateTimer, 1000);
            }
        }

        // تهيئة كل التايمرات
        const timers = [];
        console.log("محاولة تهيئة العدادات، عدد العناصر:", document.querySelectorAll('.countdown').length);
        document.querySelectorAll('.countdown').forEach(element => {
            const returnTime = new Date(element.dataset.returnTime);
            const now = new Date();
            console.log("معلومات العداد:", {
                elementExists: !!element,
                returnTime: element.dataset.returnTime,
                parsedReturnTime: returnTime,
                currentTime: now,
                hasDiffInDays: returnTime.toDateString() !== now.toDateString(),
                timerLabelExists: !!element.querySelector('.timer-label'),
                timerValueExists: !!element.querySelector('.timer-value')
            });

            // دائماً نبدأ العداد بغض النظر عن اليوم
            const timer = startCountdown(element);
            if (timer) {
                timers.push(timer);
                console.log("تمت إضافة المؤقت بنجاح");
            } else {
                console.log("فشل إضافة المؤقت");
            }
        });

        // معالجة أزرار العودة
        document.querySelectorAll('.return-btn, .reset-btn').forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.dataset.requestId;
                const status = this.dataset.status;

                // إضافة تأكيد فقط لزر "لم يرجع"
                if (status === '2' && !confirm('هل أنت متأكد من تسجيل عدم عودة الموظف؟')) {
                    return;
                }

                fetch(`/permission-requests/${requestId}/return-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ return_status: status })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'حدث خطأ أثناء تحديث حالة العودة');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ أثناء تحديث حالة العودة');
                });
            });
        });

        // تنظيف التايمرات عند مغادرة الصفحة
        window.addEventListener('beforeunload', () => {
            timers.forEach(timer => clearInterval(timer));
        });

        // معالجة تبديل نوع الطلب (لنفسي/لموظف آخر)
        const registrationTypeInputs = document.querySelectorAll('input[name="registration_type"]');
        const employeeSelectContainer = document.getElementById('employee_select_container');
        const userIdSelect = document.getElementById('user_id');

        if (registrationTypeInputs && employeeSelectContainer) {
            registrationTypeInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.value === 'other') {
                        employeeSelectContainer.style.display = 'block';
                        if (userIdSelect) {
                            userIdSelect.required = true;
                        }
                    } else {
                        employeeSelectContainer.style.display = 'none';
                        if (userIdSelect) {
                            userIdSelect.required = false;
                            userIdSelect.value = '';
                        }
                    }
                });
            });
        }

        // معالجة أزرار الرد (للمدير و HR)
        document.querySelectorAll('.respond-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const requestId = this.dataset.requestId;
                const responseType = this.dataset.responseType;
                const form = document.getElementById('respondForm');
                const responseTypeInput = document.getElementById('response_type');

                // تعيين نوع الرد (مدير أو HR)
                responseTypeInput.value = responseType;

                // تعيين عنوان النموذج بناءً على نوع الرد
                form.action = responseType === 'hr'
                    ? "{{ url('/permission-requests') }}/" + requestId + "/hr-status"
                    : "{{ url('/permission-requests') }}/" + requestId + "/manager-status";
            });
        });

        // معالجة أزرار التعديل
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.dataset.id;
                const departureTime = this.dataset.departure;
                const returnTime = this.dataset.return;
                const reason = this.dataset.reason;

                // تعيين عنوان النموذج
                const form = document.getElementById('editPermissionForm');
                form.action = `/permission-requests/${requestId}`;

                // تعيين القيم في حقول النموذج
                document.getElementById('edit_departure_time').value = departureTime.replace(' ', 'T');
                document.getElementById('edit_return_time').value = returnTime.replace(' ', 'T');
                document.getElementById('edit_reason').value = reason;
            });
        });

        // معالجة أزرار تعديل الرد
        document.querySelectorAll('.modify-response-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const requestId = this.dataset.requestId;
                const responseType = this.dataset.responseType;
                const status = this.dataset.status;
                const reason = this.dataset.reason || '';

                const form = document.getElementById('modifyResponseForm');
                const responseTypeInput = document.getElementById('modify_response_type');
                const statusInput = document.getElementById('modify_status');
                const reasonInput = document.getElementById('modify_reason');

                // تعيين قيم النموذج
                responseTypeInput.value = responseType;
                statusInput.value = status;
                reasonInput.value = reason;

                // تعيين عنوان النموذج بناءً على نوع الرد
                form.action = `/permission-requests/${requestId}/${responseType === 'hr' ? 'modify-hr-status' : 'modify-manager-status'}`;

                // إظهار/إخفاء حقل سبب الرفض
                const reasonContainer = document.getElementById('modify_reason_container');
                reasonContainer.style.display = status === 'rejected' ? 'block' : 'none';
            });
        });

        // عند تغيير حالة الرد، يتم إظهار/إخفاء حقل سبب الرفض
        document.getElementById('response_status').addEventListener('change', function() {
            const reasonContainer = document.getElementById('rejection_reason_container');
            reasonContainer.style.display = this.value === 'rejected' ? 'block' : 'none';
        });

        document.getElementById('modify_status').addEventListener('change', function() {
            const reasonContainer = document.getElementById('modify_reason_container');
            reasonContainer.style.display = this.value === 'rejected' ? 'block' : 'none';
        });

        // دالة إعادة تعيين حالة الطلب
        window.resetStatus = function(requestId, type) {
            if (confirm('هل أنت متأكد من إعادة تعيين هذا الرد؟')) {
                // استخدام fetch API للتعامل مع الاستجابة JSON
                fetch("{{ url('/permission-requests') }}/" + requestId + "/reset-" + type + "-status", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        _token: document.querySelector('meta[name="csrf-token"]').content
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // إعادة تحميل الصفحة عند النجاح
                        window.location.reload();
                    } else {
                        // عرض رسالة الخطأ
                        alert(data.message || 'حدث خطأ أثناء إعادة تعيين الرد');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ أثناء إعادة تعيين الرد');
                });
            }
        };
    });
</script>
@endpush

@endsection
