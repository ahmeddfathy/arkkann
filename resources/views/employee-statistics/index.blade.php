@extends('layouts.app')

@php
use Carbon\Carbon;
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/employee-statistics.css') }}">
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i> إحصائيات الموظفين
                    </h5>
                </div>

                <!-- Filters -->
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <!-- فلتر القسم للمدراء و HR -->
                        @if(Auth::user()->hasRole(['hr', 'company_manager', 'department_manager']))
                        <div class="col-md-3">
                            <label for="department" class="form-label">القسم</label>
                            <select class="form-select" id="department" name="department">
                                <option value="">كل الأقسام</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>
                                    {{ $dept }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <!-- فلتر الموظفين -->
                        @if(Auth::user()->hasRole(['hr', 'company_manager', 'department_manager', 'team_leader']))
                        <div class="col-md-3">
                            <label for="search" class="form-label">اختر الموظف</label>
                            <select class="form-select" id="search" name="search">
                                <option value="">كل الموظفين</option>
                                @foreach($allUsers as $emp)
                                <option value="{{ $emp->employee_id }}"
                                    {{ request('search') == $emp->employee_id ? 'selected' : '' }}>
                                    {{ $emp->employee_id }} - {{ $emp->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="col-md-3">
                            <label for="start_date" class="form-label">من تاريخ</label>
                            <input type="date"
                                class="form-control"
                                id="start_date"
                                name="start_date"
                                value="{{ $startDate }}">
                        </div>

                        <div class="col-md-3">
                            <label for="end_date" class="form-label">إلى تاريخ</label>
                            <input type="date"
                                class="form-control"
                                id="end_date"
                                name="end_date"
                                value="{{ $endDate }}">
                        </div>

                        <div class="col-md-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i> بحث
                            </button>
                            <a href="{{ route('employee-statistics.index') }}" class="btn btn-secondary">
                                <i class="fas fa-undo me-1"></i> إعادة تعيين
                            </a>
                        </div>
                    </form>

                    <!-- عرض الفترة الحالية -->
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-calendar-alt me-2"></i>
                        الفترة: {{ Carbon::parse($startDate)->format('Y-m-d') }} إلى {{ Carbon::parse($endDate)->format('Y-m-d') }}
                    </div>

                    <!-- Charts Section -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0 text-center">
                                        <i class="fas fa-chart-pie me-2"></i> نسب الحضور والغياب
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="attendanceChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0 text-center">
                                        <i class="fas fa-chart-bar me-2"></i> الإجازات والأذونات
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="leavesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0 text-center">
                                        <i class="fas fa-clock me-2"></i> إحصائيات التأخير والوقت الإضافي
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="timeChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>الموظف</th>
                                    <th>القسم</th>
                                    <th>الفريق</th>
                                    <th>أيام العمل</th>
                                    <th>أيام الحضور</th>
                                    <th>نسبة الحضور</th>
                                    <th>الغياب</th>
                                    <th>الأذونات</th>
                                    <th>الوقت الإضافي</th>
                                    <th>التأخير</th>
                                    <th>الإجازات المأخوذة</th>
                                    <th>الإجازات المتبقية</th>
                                    <th>إجازات الشهر الحالي</th>
                                    <th>التفاصيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employees as $employee)
                                <tr class="request-row">
                                    <td>
                                        <div>{{ $employee->name }}</div>
                                        <small class="text-muted">{{ $employee->employee_id }}</small>
                                    </td>
                                    <td>{{ $employee->department ?? 'غير محدد' }}</td>
                                    <td>{{ $employee->currentTeam ? $employee->currentTeam->name : 'بدون فريق' }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $employee->total_working_days }} يوم</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $employee->actual_attendance_days }} يوم</span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar
                                                {{ $employee->attendance_percentage >= 90 ? 'bg-success' :
                                                   ($employee->attendance_percentage >= 75 ? 'bg-warning' : 'bg-danger') }}"
                                                role="progressbar"
                                                style="width: {{ $employee->attendance_percentage }}%;"
                                                aria-valuenow="{{ $employee->attendance_percentage }}"
                                                aria-valuemin="0"
                                                aria-valuemax="100">
                                                {{ $employee->attendance_percentage }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $employee->absences > 0 ? 'danger' : 'success' }}"
                                            style="cursor: pointer;"
                                            onclick="showAbsenceDetails('{{ $employee->employee_id }}', '{{ $startDate }}', '{{ $endDate }}')">
                                            {{ $employee->absences }} أيام
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"
                                            style="cursor: pointer;"
                                            onclick="showPermissionDetails('{{ $employee->employee_id }}', '{{ $startDate }}', '{{ $endDate }}')">
                                            {{ $employee->permissions }} مرات
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"
                                            style="cursor: pointer;"
                                            onclick="showOvertimeDetails('{{ $employee->employee_id }}', '{{ $startDate }}', '{{ $endDate }}')">
                                            {{ $employee->overtimes }} ساعات
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $employee->delays > 0 ? 'warning' : 'success' }}">
                                            {{ $employee->delays }} دقيقة
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="badge bg-info"
                                                style="cursor: pointer;"
                                                onclick="showLeaveDetails('{{ $employee->employee_id }}', '{{ $startDate }}', '{{ $endDate }}')">
                                                {{ $employee->taken_leaves }} يوم
                                            </span>
                                            <small class="text-muted mt-1">من أصل {{ $employee->getMaxAllowedAbsenceDays() }} يوم</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="badge {{ $employee->remaining_leaves > 0 ? 'bg-success' : 'bg-danger' }}">
                                                {{ $employee->remaining_leaves }} يوم
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="badge bg-purple"
                                                style="cursor: pointer;"
                                                onclick="showCurrentMonthLeaves('{{ $employee->employee_id }}', '{{ $startDate }}', '{{ $endDate }}')">
                                                {{ $employee->current_month_leaves }} يوم
                                            </span>
                                            <small class="text-muted mt-1">
                                                {{ Carbon::parse($startDate)->format('d/m') }} - {{ Carbon::parse($endDate)->format('d/m') }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info"
                                            onclick="showDetails('{{ $employee->employee_id }}')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>

                                    <!-- تحليل الأداء والتنبؤات -->
                                    <tr class="performance-analysis">
                                        <td colspan="14">
                                            <div class="card border-0 shadow-sm mt-3">
                                                <div class="card-header bg-white py-3">
                                                    <h5 class="card-title mb-0">
                                                        <i class="fas fa-chart-line me-2"></i>
                                                        تحليل الأداء والتنبؤات المستقبلية
                                                    </h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row g-4">
                                                        <!-- مؤشرات الأداء -->
                                                        <div class="col-md-6">
                                                            <div class="performance-metrics p-4 bg-light rounded-3">
                                                                <h6 class="fw-bold mb-4">مؤشرات الأداء الحالية</h6>

                                                                <div class="metric-item mb-4">
                                                                    <label class="d-flex justify-content-between mb-2">
                                                                        <span>الدرجة الكلية</span>
                                                                        <span class="badge bg-{{ $employee->performance_metrics['overall_score'] >= 80 ? 'success' : ($employee->performance_metrics['overall_score'] >= 60 ? 'warning' : 'danger') }}">
                                                                            {{ $employee->performance_metrics['overall_score'] }}%
                                                                        </span>
                                                                    </label>
                                                                    <div class="progress" style="height: 8px;">
                                                                        <div class="progress-bar bg-{{ $employee->performance_metrics['overall_score'] >= 80 ? 'success' : ($employee->performance_metrics['overall_score'] >= 60 ? 'warning' : 'danger') }}"
                                                                            role="progressbar"
                                                                            style="width: {{ $employee->performance_metrics['overall_score'] }}%">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- حالة التأخير -->
                                                                <div class="metric-item mb-4">
                                                                    <label class="d-flex justify-content-between mb-2">
                                                                        <span>دقائق التأخير</span>
                                                                        <span class="badge bg-{{ $employee->performance_metrics['delay_status']['is_good'] ? 'success' : 'danger' }}">
                                                                            {{ $employee->performance_metrics['delay_status']['minutes'] }} دقيقة
                                                                        </span>
                                                                    </label>
                                                                    <div class="progress" style="height: 8px;">
                                                                        <div class="progress-bar bg-{{ $employee->performance_metrics['delay_status']['is_good'] ? 'success' : 'danger' }}"
                                                                            role="progressbar"
                                                                            style="width: {{ $employee->performance_metrics['delay_status']['percentage'] }}%">
                                                                        </div>
                                                                    </div>
                                                                    <small class="text-muted">الحد المسموح: 120 دقيقة</small>
                                                                </div>

                                                                <!-- حالة الأذونات -->
                                                                <div class="metric-item mb-4">
                                                                    <label class="d-flex justify-content-between mb-2">
                                                                        <span>دقائق الأذونات</span>
                                                                        <span class="badge bg-{{ $employee->performance_metrics['permissions_status']['is_good'] ? 'success' : 'danger' }}">
                                                                            {{ $employee->performance_metrics['permissions_status']['minutes'] }} دقيقة
                                                                        </span>
                                                                    </label>
                                                                    <div class="progress" style="height: 8px;">
                                                                        <div class="progress-bar bg-{{ $employee->performance_metrics['permissions_status']['is_good'] ? 'success' : 'danger' }}"
                                                                            role="progressbar"
                                                                            style="width: {{ $employee->performance_metrics['permissions_status']['percentage'] }}%">
                                                                        </div>
                                                                    </div>
                                                                    <small class="text-muted">الحد المسموح: 180 دقيقة</small>
                                                                </div>

                                                                <!-- مؤشرات الأداء التفصيلية -->
                                                                <div class="metric-details">
                                                                    <div class="row g-3">
                                                                        <div class="col-md-4">
                                                                            <div class="metric-card text-center p-3 border rounded">
                                                                                <div class="metric-value text-primary h4 mb-1">
                                                                                    {{ $employee->performance_metrics['attendance_score'] }}%
                                                                                </div>
                                                                                <div class="metric-label small">الحضور</div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="metric-card text-center p-3 border rounded">
                                                                                <div class="metric-value text-success h4 mb-1">
                                                                                    {{ $employee->performance_metrics['punctuality_score'] }}%
                                                                                </div>
                                                                                <div class="metric-label small">الانضباط</div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="metric-card text-center p-3 border rounded">
                                                                                <div class="metric-value text-info h4 mb-1">
                                                                                    {{ $employee->performance_metrics['working_hours_score'] }}%
                                                                                </div>
                                                                                <div class="metric-label small">ساعات العمل</div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                @if($employee->performance_metrics['trend'] != 0)
                                                                <div class="trend-indicator mt-3">
                                                                    <i class="fas fa-{{ $employee->performance_metrics['trend'] > 0 ? 'arrow-up text-success' : 'arrow-down text-danger' }} me-1"></i>
                                                                    <span class="{{ $employee->performance_metrics['trend'] > 0 ? 'text-success' : 'text-danger' }}">
                                                                        {{ abs($employee->performance_metrics['trend']) }}%
                                                                        {{ $employee->performance_metrics['trend'] > 0 ? 'تحسن' : 'تراجع' }}
                                                                        عن الفترة السابقة
                                                                    </span>
                                                                </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <!-- التنبؤات والتوصيات -->
                                                        <div class="col-md-6">
                                                            <div class="predictions-section p-4 bg-light rounded-3">
                                                                <h6 class="fw-bold mb-4">التنبؤات والتوصيات</h6>

                                                                <div class="prediction-card mb-4 p-3 border rounded">
                                                                    <h6 class="mb-3">التنبؤ بالأداء للشهر القادم</h6>
                                                                    <div class="d-flex align-items-center mb-3">
                                                                        <div class="prediction-score me-3">
                                                                            <span class="h4 mb-0">{{ $employee->performance_predictions['predicted_attendance'] }}%</span>
                                                                        </div>
                                                                        <div class="prediction-trend">
                                                                            <span class="badge bg-{{ $employee->performance_predictions['trend_direction'] == 'تحسن' ? 'success' : ($employee->performance_predictions['trend_direction'] == 'ثابت' ? 'info' : 'warning') }}">
                                                                                <i class="fas fa-{{ $employee->performance_predictions['trend_direction'] == 'تحسن' ? 'arrow-up' : ($employee->performance_predictions['trend_direction'] == 'ثابت' ? 'equals' : 'arrow-down') }} me-1"></i>
                                                                                {{ $employee->performance_predictions['trend_direction'] }}
                                                                                @if($employee->performance_predictions['trend_percentage'] > 0)
                                                                                    ({{ $employee->performance_predictions['trend_percentage'] }}%)
                                                                                @endif
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- مجالات التحسين -->
                                                                @if(count($employee->performance_metrics['areas_for_improvement']) > 0)
                                                                <div class="improvement-areas mb-4">
                                                                    <h6 class="mb-3">مجالات تحتاج إلى تحسين</h6>
                                                                    <ul class="list-unstyled">
                                                                        @foreach($employee->performance_metrics['areas_for_improvement'] as $area)
                                                                        <li class="mb-2">
                                                                            <i class="fas fa-exclamation-circle text-warning me-2"></i>
                                                                            {{ $area }}
                                                                        </li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                                @endif

                                                                <!-- التوصيات -->
                                                                <div class="recommendations">
                                                                    <h6 class="mb-3">التوصيات</h6>
                                                                    <ul class="list-unstyled">
                                                                        @foreach($employee->performance_predictions['recommendations'] as $recommendation)
                                                                        <li class="mb-2">
                                                                            <i class="fas fa-lightbulb text-primary me-2"></i>
                                                                            {{ $recommendation }}
                                                                        </li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="14" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>لا توجد بيانات متاحة</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $employees->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal التفاصيل -->
@include('employee-statistics.partials.details-modal')

<div class="modal fade" id="detailsDataModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsDataModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detailsDataContent"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function showDetails(employeeId) {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        fetch(`/employee-statistics/${employeeId}?start_date=${startDate}&end_date=${endDate}`)
            .then(response => response.json())
            .then(data => {
                const content = document.getElementById('modalContent');
                let html = `
                    <div class="text-center mb-4">
                        <h4>${data.employee.name}</h4>
                        <small class="text-muted">${data.employee.employee_id}</small>
                        <div class="mt-2">${data.employee.department || 'غير محدد'}</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-muted mb-2">أيام العمل</div>
                                <span class="badge bg-secondary">
                                    ${data.statistics.total_working_days} يوم
                                </span>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-muted mb-2">أيام الحضور</div>
                                <span class="badge bg-primary">
                                    ${data.statistics.actual_attendance_days} يوم
                                </span>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="text-center">
                                <div class="text-muted mb-2">نسبة الحضور</div>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar ${
                                        data.statistics.attendance_percentage >= 90 ? 'bg-success' :
                                        (data.statistics.attendance_percentage >= 75 ? 'bg-warning' : 'bg-danger')
                                    }"
                                    role="progressbar"
                                    style="width: ${data.statistics.attendance_percentage}%">
                                        ${data.statistics.attendance_percentage}%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="text-center">
                                <div class="text-muted mb-2">الغياب</div>
                                <span class="badge bg-${data.statistics.absences > 0 ? 'danger' : 'success'}"
                                    style="cursor: pointer;"
                                    onclick="showAbsenceDetails('${data.employee.employee_id}', '${startDate}', '${endDate}')">
                                    ${data.statistics.absences} أيام
                                </span>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="text-center">
                                <div class="text-muted mb-2">الأذونات</div>
                                <span class="badge bg-info"
                                    style="cursor: pointer;"
                                    onclick="showPermissionDetails('${data.employee.employee_id}', '${startDate}', '${endDate}')">
                                    ${data.statistics.permissions} مرات
                                </span>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="text-center">
                                <div class="text-muted mb-2">الوقت الإضافي</div>
                                <span class="badge bg-primary"
                                    style="cursor: pointer;"
                                    onclick="showOvertimeDetails('${data.employee.employee_id}', '${startDate}', '${endDate}')">
                                    ${data.statistics.overtimes} ساعات
                                </span>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-muted mb-2">إجمالي التأخير</div>
                                <span class="badge bg-${data.statistics.delays > 0 ? 'warning' : 'success'}">
                                    ${data.statistics.delays} دقيقة
                                </span>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-muted mb-2">الإجازات المأخوذة</div>
                                <div>
                                    <span class="badge bg-info"
                                        style="cursor: pointer;"
                                        onclick="showLeaveDetails('${data.employee.employee_id}', '${startDate}', '${endDate}')">
                                        ${data.statistics.taken_leaves} يوم
                                    </span>
                                    <small class="text-muted d-block mt-1">من أصل ${data.employee.max_allowed_absence_days} يوم</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-muted mb-2">الإجازات المتبقية</div>
                                <span class="badge ${data.statistics.remaining_leaves > 0 ? 'bg-success' : 'bg-danger'}">
                                    ${data.statistics.remaining_leaves} يوم
                                </span>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-muted mb-2">إجازات الشهر الحالي</div>
                                <div>
                                    <span class="badge bg-purple"
                                        style="cursor: pointer;"
                                        onclick="showCurrentMonthLeaves('${data.employee.employee_id}', '${startDate}', '${endDate}')">
                                        ${data.statistics.current_month_leaves} يوم
                                    </span>
                                    <small class="text-muted d-block mt-1">
                                        ${new Date(startDate).toLocaleDateString('ar', { day: '2-digit', month: '2-digit' })} -
                                        ${new Date(endDate).toLocaleDateString('ar', { day: '2-digit', month: '2-digit' })}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- سجل الحضور التفصيلي -->
                    <div class="mt-4">
                        <h6 class="border-bottom pb-2">
                            <i class="fas fa-calendar-check me-2"></i>سجل الحضور التفصيلي
                        </h6>
                        <div class="list-group mt-3">
                            ${data.statistics.attendance.map(record => `
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>${record.attendance_date}</span>
                                        <span class="badge ${
                                            record.status === 'حضـور' ? 'bg-success' :
                                            record.status === 'غيــاب' ? 'bg-danger' :
                                            record.status === 'عطله إسبوعية' ? 'bg-info' : 'bg-secondary'
                                        }">${record.status}</span>
                                    </div>
                                    ${record.entry_time ? `
                                        <div class="small mt-1">
                                            <span>الدخول: ${record.entry_time}</span>
                                            ${record.exit_time ? `<span class="ms-2">الخروج: ${record.exit_time}</span>` : ''}
                                            ${record.delay_minutes > 0 ? `
                                                <span class="text-warning ms-2">
                                                    <i class="fas fa-clock"></i> تأخير: ${record.delay_minutes} دقيقة
                                                </span>
                                            ` : ''}
                                        </div>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;

                content.innerHTML = html;
                new bootstrap.Modal(document.getElementById('detailsModal')).show();
            });
    }

    // Animation for new rows
    document.addEventListener('DOMContentLoaded', function() {
        gsap.from(".request-row", {
            duration: 0.5,
            opacity: 0,
            y: 20,
            stagger: 0.1
        });
    });

    // إزالة قيود التاريخ
    document.addEventListener('DOMContentLoaded', function() {
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            // إزالة أي قيود
            input.removeAttribute('min');
            input.removeAttribute('max');

            // منع أي أحداث JavaScript تقيد اختيار التاريخ
            input.addEventListener('mousedown', function(e) {
                e.stopPropagation();
            }, true);
        });
    });

    // إضافة دالة لتعيين التواريخ الافتراضية
    function setDefaultDates() {
        const now = new Date();
        const saturday = new Date(now);
        saturday.setDate(now.getDate() - now.getDay() + 6); // السبت الماضي

        const thursday = new Date(saturday);
        thursday.setDate(saturday.getDate() + 5); // الخميس القادم

        document.getElementById('start_date').value = saturday.toISOString().split('T')[0];
        document.getElementById('end_date').value = thursday.toISOString().split('T')[0];
    }

    // تعيين التواريخ الافتراضية عند تحميل الصفحة إذا لم يتم تحديد تواريخ
    document.addEventListener('DOMContentLoaded', function() {
        if (!document.getElementById('start_date').value || !document.getElementById('end_date').value) {
            setDefaultDates();
        }
    });

    // دالة عرض تفاصيل الغياب
    function showAbsenceDetails(employeeId, startDate, endDate) {
        console.log('Fetching absences:', { employeeId, startDate, endDate });

        fetch(`/employee-statistics/absences/${employeeId}?start_date=${startDate}&end_date=${endDate}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received absences data:', data);
                const modalTitle = document.getElementById('detailsDataModalTitle');
                const content = document.getElementById('detailsDataContent');

                modalTitle.textContent = 'تفاصيل الغياب';

                if (!data || data.length === 0) {
                    content.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            لا يوجد غياب في هذه الفترة
                        </div>
                    `;
                } else {
                    let html = `
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>السبب</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.map(record => `
                                        <tr>
                                            <td>${record.date}</td>
                                            <td>${record.reason}</td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    ${record.status}
                                                </span>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                    content.innerHTML = html;
                }

                new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
            })
            .catch(error => {
                console.error('Error:', error);
                const content = document.getElementById('detailsDataContent');
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        حدث خطأ أثناء جلب البيانات
                    </div>
                `;
                new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
            });
    }

    // دالة عرض تفاصيل الإذونات
    function showPermissionDetails(employeeId, startDate, endDate) {
        console.log('Fetching permissions:', { employeeId, startDate, endDate });

        fetch(`/employee-statistics/permissions/${employeeId}?start_date=${startDate}&end_date=${endDate}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received permissions data:', data);
                const modalTitle = document.getElementById('detailsDataModalTitle');
                const content = document.getElementById('detailsDataContent');

                modalTitle.textContent = 'تفاصيل الأذونات';

                if (!data || data.length === 0) {
                    content.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            لا توجد إذونات في هذه الفترة
                        </div>
                    `;
                } else {
                    let html = `
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>وقت المغادرة</th>
                                        <th>وقت العودة</th>
                                        <th>عدد الساعات</th>
                                        <th>السبب</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.map(record => `
                                        <tr>
                                            <td>${record.date}</td>
                                            <td>${record.departure_time}</td>
                                            <td>${record.return_time}</td>
                                            <td>${record.minutes} دقيقة</td>
                                            <td>${record.reason || 'غير محدد'}</td>
                                            <td>
                                                <span class="badge bg-success">
                                                    ${record.status}
                                                </span>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                    content.innerHTML = html;
                }

                new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
            })
            .catch(error => {
                console.error('Error:', error);
                const content = document.getElementById('detailsDataContent');
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        حدث خطأ أثناء جلب البيانات
                    </div>
                `;
                new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
            });
    }

    // دالة عرض تفاصيل الوقت الإضافي
    function showOvertimeDetails(employeeId, startDate, endDate) {
        console.log('Fetching overtimes:', { employeeId, startDate, endDate });

        fetch(`/employee-statistics/overtimes/${employeeId}?start_date=${startDate}&end_date=${endDate}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received overtimes data:', data);
                const modalTitle = document.getElementById('detailsDataModalTitle');
                const content = document.getElementById('detailsDataContent');

                modalTitle.textContent = 'تفاصيل الوقت الإضافي';

                if (!data || data.length === 0) {
                    content.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            لا يوجد وقت إضافي في هذه الفترة
                        </div>
                    `;
                } else {
                    let html = `
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>وقت البداية</th>
                                        <th>وقت النهاية</th>
                                        <th>عدد الساعات</th>
                                        <th>السبب</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.map(record => `
                                        <tr>
                                            <td>${record.date}</td>
                                            <td>${record.start_time}</td>
                                            <td>${record.end_time}</td>
                                            <td>${record.minutes} دقيقة</td>
                                            <td>${record.reason || 'غير محدد'}</td>
                                            <td>
                                                <span class="badge bg-success">
                                                    ${record.status}
                                                </span>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                    content.innerHTML = html;
                }

                new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
            })
            .catch(error => {
                console.error('Error:', error);
                const content = document.getElementById('detailsDataContent');
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        حدث خطأ أثناء جلب البيانات
                    </div>
                `;
                new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
            });
    }

    // دالة عرض تفاصيل الإجازات
    function showLeaveDetails(employeeId, startDate, endDate) {
        fetch(`/employee-statistics/leaves/${employeeId}?start_date=${startDate}&end_date=${endDate}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const modalTitle = document.getElementById('detailsDataModalTitle');
                const content = document.getElementById('detailsDataContent');

                const year = new Date(startDate).getFullYear();
                modalTitle.textContent = `تفاصيل الإجازات لسنة ${year}`;

                if (!data || data.length === 0) {
                    content.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            لا توجد إجازات في هذه السنة
                        </div>
                    `;
                } else {
                    let html = `
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>السبب</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.map(record => `
                                        <tr>
                                            <td>${record.date}</td>
                                            <td>${record.reason || 'غير محدد'}</td>
                                            <td>
                                                <span class="badge bg-success">
                                                    ${record.status}
                                                </span>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                    content.innerHTML = html;
                }

                new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
            })
            .catch(error => {
                console.error('Error:', error);
                const content = document.getElementById('detailsDataContent');
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        حدث خطأ أثناء جلب البيانات
                    </div>
                `;
                new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
            });
    }

    // دالة عرض تفاصيل إجازات الشهر الحالي
    function showCurrentMonthLeaves(employeeId, startDate, endDate) {
        console.log('Fetching leaves for:', { employeeId, startDate, endDate });

        fetch(`/employee-statistics/current-month-leaves/${employeeId}?start_date=${startDate}&end_date=${endDate}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                const modalTitle = document.getElementById('detailsDataModalTitle');
                const content = document.getElementById('detailsDataContent');

                modalTitle.textContent = 'تفاصيل إجازات الشهر الحالي';

                if (!data || data.length === 0) {
                    content.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            لا توجد إجازات في هذه الفترة
                        </div>
                    `;
                } else {
                    let html = `
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>السبب</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.map(record => `
                                        <tr>
                                            <td>${record.date}</td>
                                            <td>${record.reason || 'غير محدد'}</td>
                                            <td>
                                                <span class="badge bg-success">
                                                    ${record.status}
                                                </span>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                    content.innerHTML = html;
                }

                new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
            })
            .catch(error => {
                console.error('Error:', error);
                const content = document.getElementById('detailsDataContent');
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        حدث خطأ أثناء جلب البيانات
                    </div>
                `;
                new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
            });
    }

    // Charts initialization
    document.addEventListener('DOMContentLoaded', function() {
        const employeesData = {!! json_encode($employees->items()) !!};

        if (employeesData.length === 0) return;

        // Attendance Chart
        const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(attendanceCtx, {
            type: 'doughnut',
            data: {
                labels: ['أيام الحضور', 'أيام الغياب'],
                datasets: [{
                    data: [
                        employeesData.reduce((sum, emp) => sum + emp.actual_attendance_days, 0),
                        employeesData.reduce((sum, emp) => sum + emp.absences, 0)
                    ],
                    backgroundColor: ['#28a745', '#dc3545'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.raw;
                                const percentage = Math.round((value / total) * 100);
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Leaves Chart
        const leavesCtx = document.getElementById('leavesChart').getContext('2d');
        new Chart(leavesCtx, {
            type: 'bar',
            data: {
                labels: ['الإجازات المأخوذة', 'الإجازات المتبقية', 'الأذونات'],
                datasets: [{
                    label: 'عدد الأيام',
                    data: [
                        employeesData.reduce((sum, emp) => sum + emp.taken_leaves, 0),
                        employeesData.reduce((sum, emp) => sum + emp.remaining_leaves, 0),
                        employeesData.reduce((sum, emp) => sum + emp.permissions, 0)
                    ],
                    backgroundColor: ['#17a2b8', '#28a745', '#ffc107'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Time Chart (Delays and Overtime)
        const timeCtx = document.getElementById('timeChart').getContext('2d');
        new Chart(timeCtx, {
            type: 'bar',
            data: {
                labels: employeesData.map(emp => emp.name),
                datasets: [
                    {
                        label: 'دقائق التأخير',
                        data: employeesData.map(emp => emp.delays),
                        backgroundColor: '#ffc107',
                        borderWidth: 1
                    },
                    {
                        label: 'ساعات العمل الإضافي',
                        data: employeesData.map(emp => emp.overtimes),
                        backgroundColor: '#007bff',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: {
                            autoSkip: true,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
