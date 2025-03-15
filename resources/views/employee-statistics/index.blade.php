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

                <!-- قواعد التقييم -->
                <div class="card-body border-bottom pb-3">
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fas fa-book me-2 text-primary"></i> قواعد التقييم وأسس احتساب النقاط
                            </h5>
                            <button class="btn btn-sm btn-outline-secondary ms-3" type="button" id="rulesToggleBtn">
                                <i class="fas fa-chevron-down" id="rulesIcon"></i> عرض/إخفاء القواعد
                            </button>
                        </div>

                        <div id="rulesCollapse" style="display: none;">
                            <div class="card card-body bg-light">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <h6 class="fw-bold text-primary mb-3">كيفية احتساب التقييم النهائي للموظفين</h6>
                                        <p>يتم تقييم أداء الموظفين بناءً على ثلاثة مؤشرات رئيسية ولكل منها وزن نسبي في التقييم النهائي:</p>

                                        <div class="d-flex flex-wrap">
                                            <div class="me-4 mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="badge bg-primary d-inline-block" style="width:30px; height:30px; line-height:30px; border-radius:50%;">45%</span>
                                                    <span class="ms-2 fw-bold">الحضور</span>
                                                </div>
                                            </div>
                                            <div class="me-4 mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="badge bg-success d-inline-block" style="width:30px; height:30px; line-height:30px; border-radius:50%;">20%</span>
                                                    <span class="ms-2 fw-bold">الالتزام بالمواعيد</span>
                                                </div>
                                            </div>
                                            <div class="me-4 mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="badge bg-info d-inline-block" style="width:30px; height:30px; line-height:30px; border-radius:50%;">35%</span>
                                                    <span class="ms-2 fw-bold">ساعات العمل</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>المؤشر</th>
                                                        <th>الوزن النسبي</th>
                                                        <th>القواعد</th>
                                                        <th>حالات خصم النقاط</th>
                                                        <th>مثال</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- الحضور -->
                                                    <tr>
                                                        <td class="text-primary fw-bold">
                                                            <i class="fas fa-user-check me-1"></i> الحضور
                                                        </td>
                                                        <td>45% من التقييم الكلي</td>
                                                        <td>
                                                            <ul class="mb-0 ps-3">
                                                                <li>الدرجة الكاملة (100%) لمن لديه نسبة حضور 100%</li>
                                                                <li>يتم خصم النقاط بنفس نسبة الغياب</li>
                                                                <li>لا يتم احتساب الإجازات المعتمدة كغياب</li>
                                                            </ul>
                                                        </td>
                                                        <td>
                                                            يتم خصم نقاط من درجة الحضور في حالة:
                                                            <ul class="mb-0 ps-3">
                                                                <li>الغياب بدون إجازة معتمدة</li>
                                                            </ul>
                                                        </td>
                                                        <td>
                                                            <small>
                                                                إذا كان لدى الموظف 22 يوم عمل، وحضر 20 يوم منها:<br>
                                                                - نسبة الحضور = 90.9%<br>
                                                                - درجة مؤشر الحضور = 90.9%<br>
                                                                - التأثير على التقييم النهائي = 90.9% × 45% = 40.9%
                                                            </small>
                                                        </td>
                                                    </tr>

                                                    <!-- الالتزام بالمواعيد -->
                                                    <tr>
                                                        <td class="text-success fw-bold">
                                                            <i class="fas fa-clock me-1"></i> الالتزام بالمواعيد
                                                        </td>
                                                        <td>20% من التقييم الكلي</td>
                                                        <td>
                                                            <ul class="mb-0 ps-3">
                                                                <li>الحد المسموح للتأخير: 120 دقيقة شهرياً</li>
                                                                <li>إذا كان التأخير أقل من أو يساوي 120 دقيقة، تكون الدرجة 100%</li>
                                                                <li>إذا زاد التأخير عن 120 دقيقة، يتم خصم نقاط بنسبة التجاوز</li>
                                                            </ul>
                                                        </td>
                                                        <td>
                                                            يتم خصم نقاط من درجة الالتزام بالمواعيد في حالة:
                                                            <ul class="mb-0 ps-3">
                                                                <li>تجاوز الحد المسموح للتأخير (120 دقيقة شهرياً)</li>
                                                                <li>كل 120 دقيقة إضافية تؤدي لخصم 100% من الدرجة</li>
                                                            </ul>
                                                        </td>
                                                        <td>
                                                            <small>
                                                                إذا تأخر الموظف 180 دقيقة خلال الشهر:<br>
                                                                - تجاوز بمقدار 60 دقيقة (180 - 120)<br>
                                                                - نسبة التجاوز = 60 ÷ 120 = 50%<br>
                                                                - درجة مؤشر الالتزام = 100% - 50% = 50%<br>
                                                                - التأثير على التقييم النهائي = 50% × 20% = 10%
                                                            </small>
                                                        </td>
                                                    </tr>

                                                    <!-- ساعات العمل -->
                                                    <tr>
                                                        <td class="text-info fw-bold">
                                                            <i class="fas fa-business-time me-1"></i> ساعات العمل
                                                        </td>
                                                        <td>20% من التقييم الكلي</td>
                                                        <td>
                                                            <ul class="mb-0 ps-3">
                                                                <li>المعيار: 8 ساعات يومياً</li>
                                                                <li>متوسط ساعات العمل يقاس كنسبة من 8 ساعات</li>
                                                                <li>يتم مراعاة نسبة الحضور في الاحتساب</li>
                                                            </ul>
                                                        </td>
                                                        <td>
                                                            يتم خصم نقاط من درجة ساعات العمل في حالة:
                                                            <ul class="mb-0 ps-3">
                                                                <li>إذا كان متوسط ساعات العمل اليومية أقل من 8 ساعات</li>
                                                            </ul>
                                                        </td>
                                                        <td>
                                                            <small>
                                                                إذا كان متوسط ساعات عمل الموظف 7 ساعات يومياً:<br>
                                                                - نسبة ساعات العمل = 7 ÷ 8 = 87.5%<br>
                                                                - درجة مؤشر ساعات العمل = 87.5%<br>
                                                                - التأثير على التقييم النهائي = 87.5% × 20% = 17.5%
                                                            </small>
                                                        </td>
                                                    </tr>

                                                    <!-- الأذونات -->
                                                    <tr>
                                                        <td class="text-warning fw-bold">
                                                            <i class="fas fa-door-open me-1"></i> الأذونات
                                                        </td>
                                                        <td>لا يتم احتسابها حالياً</td>
                                                        <td>
                                                            <ul class="mb-0 ps-3">
                                                                <li>الحد المسموح للأذونات: 180 دقيقة شهرياً</li>
                                                                <li>لا يؤثر تجاوز الحد حالياً على التقييم النهائي</li>
                                                            </ul>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-secondary">لا يتم خصم نقاط حالياً</span>
                                                        </td>
                                                        <td>
                                                            <small>لا يؤثر على التقييم النهائي في الوقت الحالي</small>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="mt-3 p-3 bg-white rounded border">
                                            <h6 class="fw-bold mb-2">كيفية حساب التقييم النهائي:</h6>
                                            <div class="formula p-2 bg-light rounded">
                                                <code dir="ltr">التقييم النهائي = (درجة الحضور × 45%) + (درجة الالتزام بالمواعيد × 20%) + (درجة ساعات العمل × 35%)</code>
                                            </div>

                                            <h6 class="fw-bold mt-3 mb-2">مستويات التقييم:</h6>
                                            <div class="d-flex flex-wrap">
                                                <div class="me-3 mb-2"><span class="badge bg-success me-1">90% - 100%</span> ممتاز</div>
                                                <div class="me-3 mb-2"><span class="badge bg-primary me-1">80% - 89%</span> جيد جداً</div>
                                                <div class="me-3 mb-2"><span class="badge bg-info me-1">70% - 79%</span> جيد</div>
                                                <div class="me-3 mb-2"><span class="badge bg-warning me-1">60% - 69%</span> مقبول</div>
                                                <div class="me-3 mb-2"><span class="badge bg-danger me-1">أقل من 60%</span> يحتاج إلى تحسين</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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

                                                                <!-- تفاصيل النقاط المخصومة -->
                                                                <div class="mt-3">
                                                                    <h5 class="card-title fw-bold">
                                                                        <i class="fas fa-chart-line text-info me-2"></i>تفاصيل النقاط المخصومة
                                                                    </h5>
                                                                    <ul class="list-unstyled mb-0">
                                                                        @if($employee->performance_metrics['attendance_score'] < 100)
                                                                        <li class="mb-2">
                                                                            <i class="fas fa-minus-circle text-danger me-1"></i>
                                                                            <span>
                                                                                خصم
                                                                                <span class="badge bg-danger deduction-badge"
                                                                                      data-bs-toggle="tooltip"
                                                                                      data-bs-html="true"
                                                                                      data-bs-placement="top"
                                                                                      title="
                                                                                      <div class='text-start'>
                                                                                        <strong>تفاصيل الخصم:</strong><br>
                                                                                        - نسبة الغياب: {{ round(100 - $employee->performance_metrics['attendance_score'], 1) }}%<br>
                                                                                        - وزن مؤشر الحضور: 45% من التقييم الكلي<br>
                                                                                        - حساب الخصم: {{ round(100 - $employee->performance_metrics['attendance_score'], 1) }}% × 45% = {{ round((100 - $employee->performance_metrics['attendance_score']) * 0.45, 1) }}%<br>
                                                                                        - أيام الغياب: {{ $employee->absences }} {{ $employee->absences > 1 ? 'أيام' : 'يوم' }}<br>
                                                                                        - الغياب يؤثر سلباً على أدائك الكلي!
                                                                                      </div>
                                                                                      ">
                                                                                    {{ round((100 - $employee->performance_metrics['attendance_score']) * 0.45, 1) }}%
                                                                                </span>
                                                                                من التقييم النهائي بسبب الغياب
                                                                            </span>
                                                                        </li>
                                                                        @endif

                                                                        @if($employee->performance_metrics['punctuality_score'] < 100)
                                                                        <li class="mb-2">
                                                                            <i class="fas fa-minus-circle text-danger me-1"></i>
                                                                            <span>
                                                                                خصم
                                                                                <span class="badge bg-danger deduction-badge"
                                                                                      data-bs-toggle="tooltip"
                                                                                      data-bs-html="true"
                                                                                      data-bs-placement="top"
                                                                                      title="
                                                                                      <div class='text-start'>
                                                                                        <strong>تفاصيل الخصم:</strong><br>
                                                                                        - دقائق التأخير: {{ $employee->delays }} دقيقة<br>
                                                                                        - الحد المسموح: 120 دقيقة شهرياً<br>
                                                                                        - تجاوز الحد بـ: {{ max(0, $employee->delays - 120) }} دقيقة<br>
                                                                                        - نسبة الخصم من مؤشر الالتزام: {{ round(100 - $employee->performance_metrics['punctuality_score'], 1) }}%<br>
                                                                                        - وزن مؤشر الالتزام: 20% من التقييم الكلي<br>
                                                                                        - حساب الخصم: {{ round(100 - $employee->performance_metrics['punctuality_score'], 1) }}% × 20% = {{ round((100 - $employee->performance_metrics['punctuality_score']) * 0.2, 1) }}%
                                                                                      </div>
                                                                                      ">
                                                                                    {{ round((100 - $employee->performance_metrics['punctuality_score']) * 0.2, 1) }}%
                                                                                </span>
                                                                                من التقييم النهائي بسبب التأخير
                                                                            </span>
                                                                        </li>
                                                                        @endif

                                                                        @if($employee->performance_metrics['working_hours_score'] < 100)
                                                                        <li class="mb-2">
                                                                            <i class="fas fa-minus-circle text-danger me-1"></i>
                                                                            <span>
                                                                                خصم
                                                                                <span class="badge bg-danger deduction-badge"
                                                                                      data-bs-toggle="tooltip"
                                                                                      data-bs-html="true"
                                                                                      data-bs-placement="top"
                                                                                      title="
                                                                                      <div class='text-start'>
                                                                                        <strong>تفاصيل الخصم:</strong><br>
                                                                                        - متوسط ساعات العمل: {{ $employee->average_working_hours }} ساعة يومياً<br>
                                                                                        - المطلوب: 8 ساعات يومياً<br>
                                                                                        - نسبة النقص: {{ round((8 - $employee->average_working_hours) / 8 * 100, 1) }}%<br>
                                                                                        - نسبة الخصم من مؤشر ساعات العمل: {{ round(100 - $employee->performance_metrics['working_hours_score'], 1) }}%<br>
                                                                                        - وزن مؤشر ساعات العمل: 35% من التقييم الكلي<br>
                                                                                        - حساب الخصم: {{ round(100 - $employee->performance_metrics['working_hours_score'], 1) }}% × 35% = {{ round((100 - $employee->performance_metrics['working_hours_score']) * 0.35, 1) }}%
                                                                                      </div>
                                                                                      ">
                                                                                    {{ round((100 - $employee->performance_metrics['working_hours_score']) * 0.35, 1) }}%
                                                                                </span>
                                                                                من التقييم النهائي بسبب قلة ساعات العمل
                                                                            </span>
                                                                        </li>
                                                                        @endif

                                                                        @if($employee->performance_metrics['attendance_score'] == 100 &&
                                                                            $employee->performance_metrics['punctuality_score'] == 100 &&
                                                                            $employee->performance_metrics['working_hours_score'] == 100)
                                                                        <li class="mb-1">
                                                                            <i class="fas fa-check-circle text-success me-1"></i>
                                                                            <span class="text-success">لا توجد نقاط مخصومة. مستوى الأداء ممتاز في جميع المؤشرات!</span>
                                                                        </li>
                                                                        @endif
                                                                    </ul>
                                                                </div>

                                                                <!-- معلومات فترات المقارنة -->
                                                                <div class="mt-4">
                                                                    <h5 class="card-title fw-bold">
                                                                        <i class="fas fa-calendar-alt text-primary me-2"></i>فترات المقارنة للتقييم
                                                                    </h5>
                                                                    <div class="alert alert-info py-2">
                                                                        <div class="row">
                                                                            <div class="col-12 mb-2">
                                                                                <span class="fw-bold">الفترة الحالية:</span>
                                                                                {{ $employee->performance_predictions['current_period']['label'] ?? 'غير متوفر' }}
                                                                            </div>
                                                                            <div class="col-12 mb-2">
                                                                                <span class="fw-bold">الفترة السابقة:</span>
                                                                                {{ $employee->performance_predictions['previous_period']['label'] ?? 'غير متوفر' }}
                                                                            </div>
                                                                            <div class="col-12">
                                                                                <span class="fw-bold">نسبة التحسن:</span>
                                                                                @if(isset($employee->performance_predictions['improvement_percentage']))
                                                                                    <span
                                                                                        class="badge {{ $employee->performance_predictions['improvement_percentage'] > 0 ? 'bg-success' : ($employee->performance_predictions['improvement_percentage'] < 0 ? 'bg-danger' : 'bg-secondary') }}"
                                                                                        data-bs-toggle="tooltip"
                                                                                        data-bs-html="true"
                                                                                        data-bs-placement="top"
                                                                                        title="
                                                                                        <div class='text-start'>
                                                                                        <strong>تفاصيل الحساب:</strong><br>
                                                                                        - التقييم الحالي: {{ $employee->performance_predictions['current_score'] }}%<br>
                                                                                        - التقييم السابق: {{ $employee->performance_predictions['previous_score'] }}%<br>
                                                                                        - الفرق: {{ $employee->performance_predictions['current_score'] - $employee->performance_predictions['previous_score'] }}%<br>
                                                                                        - صيغة الحساب: (التقييم الحالي - التقييم السابق) ÷ التقييم السابق × 100<br>
                                                                                        - الحساب: ({{ $employee->performance_predictions['current_score'] }} - {{ $employee->performance_predictions['previous_score'] }}) ÷ {{ $employee->performance_predictions['previous_score'] }} × 100 = {{ $employee->performance_predictions['improvement_percentage'] }}%
                                                                                        </div>
                                                                                        "
                                                                                    >
                                                                                        {{ abs($employee->performance_predictions['improvement_percentage']) }}%
                                                                                        @if($employee->performance_predictions['improvement_percentage'] > 0)
                                                                                            <i class="fas fa-arrow-up"></i> تحسن
                                                                                        @elseif($employee->performance_predictions['improvement_percentage'] < 0)
                                                                                            <i class="fas fa-arrow-down"></i> تراجع
                                                                                        @else
                                                                                            <i class="fas fa-minus"></i> ثابت
                                                                                        @endif
                                                                                    </span>
                                                                                @else
                                                                                    غير متوفر
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- تفاصيل التحسن في كل مؤشر -->
                                                                <div class="mt-3">
                                                                    <h5 class="card-title fw-bold">
                                                                        <i class="fas fa-chart-line text-success me-2"></i>تفاصيل التحسن في كل مؤشر
                                                                    </h5>
                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm table-bordered">
                                                                            <thead class="table-light">
                                                                                <tr>
                                                                                    <th>المؤشر</th>
                                                                                    <th>الفترة السابقة</th>
                                                                                    <th>الفترة الحالية</th>
                                                                                    <th>التغيير</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td>
                                                                                        <i class="fas fa-user-check text-primary me-1"></i>الحضور
                                                                                    </td>
                                                                                    <td>{{ $employee->performance_predictions['metric_predictions']['attendance']['previous'] ?? 0 }}</td>
                                                                                    <td>{{ $employee->performance_predictions['metric_predictions']['attendance']['current'] ?? 0 }}</td>
                                                                                    <td>
                                                                                        @if(isset($employee->performance_predictions['metric_predictions']['attendance']['improvement_percentage']))
                                                                                            <span
                                                                                                class="badge {{ $employee->performance_predictions['metric_predictions']['attendance']['improvement_percentage'] > 0 ? 'bg-success' : ($employee->performance_predictions['metric_predictions']['attendance']['improvement_percentage'] < 0 ? 'bg-danger' : 'bg-secondary') }}"
                                                                                                data-bs-toggle="tooltip"
                                                                                                data-bs-html="true"
                                                                                                data-bs-placement="top"
                                                                                                title="
                                                                                                <div class='text-start'>
                                                                                                <strong>تفاصيل:</strong><br>
                                                                                                - القيمة الحالية: {{ $employee->performance_predictions['metric_predictions']['attendance']['current'] }}%<br>
                                                                                                - القيمة السابقة: {{ $employee->performance_predictions['metric_predictions']['attendance']['previous'] }}%<br>
                                                                                                - الفرق: {{ $employee->performance_predictions['metric_predictions']['attendance']['improvement'] }}%<br>
                                                                                                - نسبة التغيير: {{ $employee->performance_predictions['metric_predictions']['attendance']['improvement_percentage'] }}%
                                                                                                </div>
                                                                                                "
                                                                                            >
                                                                                                {{ abs($employee->performance_predictions['metric_predictions']['attendance']['improvement_percentage']) }}%
                                                                                                @if($employee->performance_predictions['metric_predictions']['attendance']['improvement_percentage'] > 0)
                                                                                                    <i class="fas fa-arrow-up"></i>
                                                                                                @elseif($employee->performance_predictions['metric_predictions']['attendance']['improvement_percentage'] < 0)
                                                                                                    <i class="fas fa-arrow-down"></i>
                                                                                                @endif
                                                                                            </span>
                                                                                        @else
                                                                                            -
                                                                                        @endif
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td>
                                                                                        <i class="fas fa-clock text-warning me-1"></i>الالتزام بالمواعيد
                                                                                    </td>
                                                                                    <td>{{ $employee->performance_predictions['metric_predictions']['punctuality']['previous'] ?? 0 }}</td>
                                                                                    <td>{{ $employee->performance_predictions['metric_predictions']['punctuality']['current'] ?? 0 }}</td>
                                                                                    <td>
                                                                                        @if(isset($employee->performance_predictions['metric_predictions']['punctuality']['improvement_percentage']))
                                                                                            <span
                                                                                                class="badge {{ $employee->performance_predictions['metric_predictions']['punctuality']['improvement_percentage'] > 0 ? 'bg-success' : ($employee->performance_predictions['metric_predictions']['punctuality']['improvement_percentage'] < 0 ? 'bg-danger' : 'bg-secondary') }}"
                                                                                                data-bs-toggle="tooltip"
                                                                                                data-bs-html="true"
                                                                                                data-bs-placement="top"
                                                                                                title="
                                                                                                <div class='text-start'>
                                                                                                <strong>تفاصيل:</strong><br>
                                                                                                - القيمة الحالية: {{ $employee->performance_predictions['metric_predictions']['punctuality']['current'] }}%<br>
                                                                                                - القيمة السابقة: {{ $employee->performance_predictions['metric_predictions']['punctuality']['previous'] }}%<br>
                                                                                                - الفرق: {{ $employee->performance_predictions['metric_predictions']['punctuality']['improvement'] }}%<br>
                                                                                                - نسبة التغيير: {{ $employee->performance_predictions['metric_predictions']['punctuality']['improvement_percentage'] }}%
                                                                                                </div>
                                                                                                "
                                                                                            >
                                                                                                {{ abs($employee->performance_predictions['metric_predictions']['punctuality']['improvement_percentage']) }}%
                                                                                                @if($employee->performance_predictions['metric_predictions']['punctuality']['improvement_percentage'] > 0)
                                                                                                    <i class="fas fa-arrow-up"></i>
                                                                                                @elseif($employee->performance_predictions['metric_predictions']['punctuality']['improvement_percentage'] < 0)
                                                                                                    <i class="fas fa-arrow-down"></i>
                                                                                                @endif
                                                                                            </span>
                                                                                        @else
                                                                                            -
                                                                                        @endif
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td>
                                                                                        <i class="fas fa-business-time text-info me-1"></i>ساعات العمل
                                                                                    </td>
                                                                                    <td>{{ $employee->performance_predictions['metric_predictions']['working_hours']['previous'] ?? 0 }}</td>
                                                                                    <td>{{ $employee->performance_predictions['metric_predictions']['working_hours']['current'] ?? 0 }}</td>
                                                                                    <td>
                                                                                        @if(isset($employee->performance_predictions['metric_predictions']['working_hours']['improvement_percentage']))
                                                                                            <span
                                                                                                class="badge {{ $employee->performance_predictions['metric_predictions']['working_hours']['improvement_percentage'] > 0 ? 'bg-success' : ($employee->performance_predictions['metric_predictions']['working_hours']['improvement_percentage'] < 0 ? 'bg-danger' : 'bg-secondary') }}"
                                                                                                data-bs-toggle="tooltip"
                                                                                                data-bs-html="true"
                                                                                                data-bs-placement="top"
                                                                                                title="
                                                                                                <div class='text-start'>
                                                                                                <strong>تفاصيل:</strong><br>
                                                                                                - القيمة الحالية: {{ $employee->performance_predictions['metric_predictions']['working_hours']['current'] }}%<br>
                                                                                                - القيمة السابقة: {{ $employee->performance_predictions['metric_predictions']['working_hours']['previous'] }}%<br>
                                                                                                - الفرق: {{ $employee->performance_predictions['metric_predictions']['working_hours']['improvement'] }}%<br>
                                                                                                - نسبة التغيير: {{ $employee->performance_predictions['metric_predictions']['working_hours']['improvement_percentage'] }}%
                                                                                                </div>
                                                                                                "
                                                                                            >
                                                                                                {{ abs($employee->performance_predictions['metric_predictions']['working_hours']['improvement_percentage']) }}%
                                                                                                @if($employee->performance_predictions['metric_predictions']['working_hours']['improvement_percentage'] > 0)
                                                                                                    <i class="fas fa-arrow-up"></i>
                                                                                                @elseif($employee->performance_predictions['metric_predictions']['working_hours']['improvement_percentage'] < 0)
                                                                                                    <i class="fas fa-arrow-down"></i>
                                                                                                @endif
                                                                                            </span>
                                                                                        @else
                                                                                            -
                                                                                        @endif
                                                                                    </td>
                                                                                </tr>
                                                                                <tr class="table-active">
                                                                                    <td class="fw-bold">التقييم الإجمالي</td>
                                                                                    <td>{{ $employee->performance_predictions['previous_score'] ?? 0 }}</td>
                                                                                    <td>{{ $employee->performance_predictions['current_score'] ?? 0 }}</td>
                                                                                    <td>
                                                                                        @if(isset($employee->performance_predictions['improvement_percentage']))
                                                                                            <span
                                                                                                class="badge {{ $employee->performance_predictions['improvement_percentage'] > 0 ? 'bg-success' : ($employee->performance_predictions['improvement_percentage'] < 0 ? 'bg-danger' : 'bg-secondary') }}"
                                                                                                data-bs-toggle="tooltip"
                                                                                                data-bs-html="true"
                                                                                                data-bs-placement="top"
                                                                                                title="
                                                                                                <div class='text-start'>
                                                                                                <strong>تفاصيل الحساب:</strong><br>
                                                                                                - التقييم الحالي: {{ $employee->performance_predictions['current_score'] }}%<br>
                                                                                                - التقييم السابق: {{ $employee->performance_predictions['previous_score'] }}%<br>
                                                                                                - الفرق: {{ $employee->performance_predictions['current_score'] - $employee->performance_predictions['previous_score'] }}%<br>
                                                                                                - صيغة الحساب: (التقييم الحالي - التقييم السابق) ÷ التقييم السابق × 100<br>
                                                                                                - الحساب: ({{ $employee->performance_predictions['current_score'] }} - {{ $employee->performance_predictions['previous_score'] }}) ÷ {{ $employee->performance_predictions['previous_score'] }} × 100 = {{ $employee->performance_predictions['improvement_percentage'] }}%
                                                                                                </div>
                                                                                                "
                                                                                            >
                                                                                                {{ abs($employee->performance_predictions['improvement_percentage']) }}%
                                                                                                @if($employee->performance_predictions['improvement_percentage'] > 0)
                                                                                                    <i class="fas fa-arrow-up"></i>
                                                                                                @elseif($employee->performance_predictions['improvement_percentage'] < 0)
                                                                                                    <i class="fas fa-arrow-down"></i>
                                                                                                @endif
                                                                                            </span>
                                                                                        @else
                                                                                            -
                                                                                        @endif
                                                                                    </td>
                                                                                </tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- التنبؤات والتوصيات -->
                                                        <div class="col-md-6">
                                                            <div class="predictions-section p-4 bg-light rounded-3">
                                                                <h6 class="fw-bold mb-4">التنبؤات والتوصيات</h6>

                                                                <!-- الفترات الزمنية للتقييم والتنبؤ -->
                                                                <div class="periods-info mb-4 p-3 border-start border-info border-3 bg-light">
                                                                    <h6 class="mb-3">الفترات الزمنية</h6>
                                                                    <div class="d-flex flex-column gap-2">
                                                                        <div class="period-item">
                                                                            <div class="fw-bold text-muted">الفترة السابقة:</div>
                                                                            <div>{{ $employee->performance_predictions['previous_period']['label'] }}</div>
                                                                        </div>
                                                                        <div class="period-item">
                                                                            <div class="fw-bold text-muted">الفترة الحالية:</div>
                                                                            <div>{{ $employee->performance_predictions['current_period']['label'] }}</div>
                                                                        </div>
                                                                        <div class="period-item">
                                                                            <div class="fw-bold text-primary">التنبؤ للفترة:</div>
                                                                            <div class="text-primary">{{ $employee->performance_predictions['prediction_period']['label'] }}</div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- إجمالي الفترتين -->
                                                                <div class="total-periods mb-4 p-3 border rounded bg-light">
                                                                    <h6 class="mb-3">إجمالي الفترتين (الحالية والسابقة)</h6>

                                                                    <div class="row g-3">
                                                                        <div class="col-6">
                                                                            <div class="text-center">
                                                                                <div class="text-muted mb-2">إجمالي أيام العمل</div>
                                                                                <span class="badge bg-secondary">
                                                                                    {{ $employee->total_periods_stats['total_working_days'] ?? 0 }} يوم
                                                                                </span>
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-6">
                                                                            <div class="text-center">
                                                                                <div class="text-muted mb-2">إجمالي أيام الحضور</div>
                                                                                <span class="badge bg-primary">
                                                                                    {{ $employee->total_periods_stats['total_attendance_days'] ?? 0 }} يوم
                                                                                </span>
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-12">
                                                                            <div class="text-center">
                                                                                <div class="text-muted mb-2">نسبة الحضور الإجمالية</div>
                                                                                <div class="progress" style="height: 25px;">
                                                                                    <div class="progress-bar {{ ($employee->total_periods_stats['total_attendance_percentage'] ?? 0) >= 90 ? 'bg-success' : (($employee->total_periods_stats['total_attendance_percentage'] ?? 0) >= 75 ? 'bg-warning' : 'bg-danger') }}"
                                                                                    role="progressbar"
                                                                                    style="width: {{ $employee->total_periods_stats['total_attendance_percentage'] ?? 0 }}%">
                                                                                        {{ $employee->total_periods_stats['total_attendance_percentage'] ?? 0 }}%
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-6">
                                                                            <div class="text-center">
                                                                                <div class="text-muted mb-2">إجمالي دقائق التأخير</div>
                                                                                <span class="badge {{ ($employee->total_periods_stats['total_delays'] ?? 0) <= 240 ? 'bg-success' : 'bg-danger' }}">
                                                                                    {{ $employee->total_periods_stats['total_delays'] ?? 0 }} دقيقة
                                                                                </span>
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-6">
                                                                            <div class="text-center">
                                                                                <div class="text-muted mb-2">متوسط ساعات العمل</div>
                                                                                <span class="badge {{ ($employee->total_periods_stats['average_working_hours'] ?? 0) >= 7.5 ? 'bg-success' : (($employee->total_periods_stats['average_working_hours'] ?? 0) >= 6.5 ? 'bg-warning' : 'bg-danger') }}">
                                                                                    {{ $employee->total_periods_stats['average_working_hours'] ?? 0 }} ساعة
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

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
                                                                    <div class="prediction-info mt-3 small">
                                                                        <div class="fw-bold mb-2">طريقة حساب التنبؤ:</div>
                                                                        <p class="mb-1">
                                                                            {{ $employee->performance_predictions['metric_predictions']['summary']['calculation_method']['description'] ?? 'تم الحساب بناءً على تحليل أداء الفترة الحالية مقارنة بالفترة السابقة' }}
                                                                        </p>
                                                                        <div class="mt-2">
                                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                                <span>الأداء الحالي:</span>
                                                                                <span class="fw-bold">{{ $employee->performance_predictions['current_score'] }}%</span>
                                                                            </div>
                                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                                <span>الأداء السابق:</span>
                                                                                <span class="fw-bold">{{ $employee->performance_predictions['previous_score'] }}%</span>
                                                                            </div>
                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                <span>نسبة التغيير:</span>
                                                                                <span class="fw-bold {{ $employee->performance_predictions['improvement_percentage'] > 0 ? 'text-success' : ($employee->performance_predictions['improvement_percentage'] < 0 ? 'text-danger' : '') }}">
                                                                                    {{ $employee->performance_predictions['improvement_percentage'] > 0 ? '+' : '' }}{{ $employee->performance_predictions['improvement_percentage'] }}%
                                                                                </span>
                                                                            </div>
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
    // سكريبت خاص بعرض وإخفاء قواعد التقييم
    document.addEventListener('DOMContentLoaded', function() {
        const rulesToggleBtn = document.getElementById('rulesToggleBtn');
        const rulesCollapse = document.getElementById('rulesCollapse');
        const rulesIcon = document.getElementById('rulesIcon');

        if (rulesToggleBtn && rulesCollapse) {
            rulesToggleBtn.addEventListener('click', function() {
                // تبديل حالة العرض
                if (rulesCollapse.style.display === 'none') {
                    // عرض القواعد
                    rulesCollapse.style.display = 'block';
                    // إضافة تأثير حركي للعرض
                    rulesCollapse.style.opacity = '0';
                    rulesCollapse.style.transform = 'translateY(-10px)';
                    setTimeout(function() {
                        rulesCollapse.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        rulesCollapse.style.opacity = '1';
                        rulesCollapse.style.transform = 'translateY(0)';
                    }, 10);
                    // تدوير الأيقونة
                    rulesIcon.classList.remove('fa-chevron-down');
                    rulesIcon.classList.add('fa-chevron-up');
                } else {
                    // إضافة تأثير حركي للإخفاء
                    rulesCollapse.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    rulesCollapse.style.opacity = '0';
                    rulesCollapse.style.transform = 'translateY(-10px)';
                    setTimeout(function() {
                        rulesCollapse.style.display = 'none';
                    }, 300);
                    // تدوير الأيقونة
                    rulesIcon.classList.remove('fa-chevron-up');
                    rulesIcon.classList.add('fa-chevron-down');
                }
            });
        }
    });

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

                    <!-- تفاصيل النقاط المخصومة -->
                    ${data.statistics.attendance_percentage < 100 || data.statistics.delays > 120 ? `
                    <div class="mt-4">
                        <h6 class="border-bottom pb-2">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>تفاصيل النقاط المخصومة
                        </h6>

                        <div class="card mt-3 border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">تفاصيل تقييم الأداء</h6>

                                <div class="row">
                                    <!-- مؤشر الحضور -->
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100 ${data.statistics.attendance_percentage < 80 ? 'border-danger' : (data.statistics.attendance_percentage < 90 ? 'border-warning' : 'border-success')}">
                                            <div class="card-body p-3 text-center">
                                                <h6 class="card-title mb-1">الحضور</h6>
                                                <h3 class="mb-0 ${data.statistics.attendance_percentage < 80 ? 'text-danger' : (data.statistics.attendance_percentage < 90 ? 'text-warning' : 'text-success')}">
                                                    ${data.statistics.attendance_percentage}%
                                                </h3>
                                                <small class="text-muted">الوزن: 40% من التقييم الكلي</small>

                                                ${data.statistics.attendance_percentage < 100 ? `
                                                <div class="alert alert-light mt-2 mb-0 p-2 text-start">
                                                    <small>
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        تم خصم <strong>${100 - data.statistics.attendance_percentage}%</strong> بسبب
                                                        الغياب لمدة <strong>${data.statistics.total_working_days - data.statistics.actual_attendance_days} أيام</strong>
                                                    </small>
                                                </div>
                                                ` : ''}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- مؤشر الانضباط -->
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100 ${data.statistics.delays > 120 ? 'border-danger' : 'border-success'}">
                                            <div class="card-body p-3 text-center">
                                                <h6 class="card-title mb-1">الانضباط</h6>
                                                <h3 class="mb-0 ${data.statistics.delays > 120 ? 'text-danger' : 'text-success'}">
                                                    ${data.statistics.delays <= 120 ? '100%' : Math.max(0, Math.round(100 - ((data.statistics.delays - 120) / 120) * 100)) + '%'}
                                                </h3>
                                                <small class="text-muted">الوزن: 40% من التقييم الكلي</small>

                                                ${data.statistics.delays > 120 ? `
                                                <div class="alert alert-light mt-2 mb-0 p-2 text-start">
                                                    <small>
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        تم خصم <strong>${Math.min(100, Math.round(((data.statistics.delays - 120) / 120) * 100))}%</strong> بسبب
                                                        تجاوز التأخير <strong>${data.statistics.delays - 120} دقيقة</strong> عن الحد المسموح (120 دقيقة)
                                                    </small>
                                                </div>
                                                ` : ''}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- مؤشر ساعات العمل -->
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100 border-info">
                                            <div class="card-body p-3 text-center">
                                                <h6 class="card-title mb-1">ساعات العمل</h6>
                                                <h3 class="mb-0 text-info">
                                                    ${typeof data.statistics.average_working_hours !== 'undefined' ?
                                                    Math.min(100, Math.round((data.statistics.average_working_hours / 8) * 100)) + '%' :
                                                    '-'}
                                                </h3>
                                                <small class="text-muted">الوزن: 20% من التقييم الكلي</small>

                                                ${typeof data.statistics.average_working_hours !== 'undefined' && data.statistics.average_working_hours < 8 ? `
                                                <div class="alert alert-light mt-2 mb-0 p-2 text-start">
                                                    <small>
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        متوسط ساعات العمل <strong>${data.statistics.average_working_hours}</strong> من أصل <strong>8</strong> ساعات
                                                    </small>
                                                </div>
                                                ` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- تأثير النقاط المخصومة -->
                                <div class="mt-3 pt-3 border-top">
                                    <h6 class="mb-3">التأثير على النتيجة النهائية</h6>

                                    <ul class="list-unstyled mb-0">
                                        ${data.statistics.attendance_percentage < 100 ? `
                                        <li class="mb-2">
                                            <i class="fas fa-minus-circle text-danger me-1"></i>
                                            خصم <strong>${Math.round((100 - data.statistics.attendance_percentage) * 0.4, 1)}%</strong>
                                            من التقييم النهائي بسبب الغياب
                                        </li>
                                        ` : ''}

                                        ${data.statistics.delays > 120 ? `
                                        <li class="mb-2">
                                            <i class="fas fa-minus-circle text-danger me-1"></i>
                                            خصم <strong>${Math.round(Math.min(100, ((data.statistics.delays - 120) / 120) * 100) * 0.4, 1)}%</strong>
                                            من التقييم النهائي بسبب التأخير
                                        </li>
                                        ` : ''}

                                        ${typeof data.statistics.average_working_hours !== 'undefined' && data.statistics.average_working_hours < 8 ? `
                                        <li class="mb-2">
                                            <i class="fas fa-minus-circle text-danger me-1"></i>
                                            خصم <strong>${Math.round((100 - Math.min(100, Math.round((data.statistics.average_working_hours / 8) * 100))) * 0.2, 1)}%</strong>
                                            من التقييم النهائي بسبب قلة ساعات العمل
                                        </li>
                                        ` : ''}
                                    </ul>
                                </div>

                                <!-- النتيجة النهائية -->
                                <div class="mt-3 pt-3 border-top">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">النتيجة النهائية</h6>

                                        <div class="text-center">
                                            <span class="badge bg-${
                                                calculateOverallScore(data) >= 90 ? 'success' :
                                                (calculateOverallScore(data) >= 80 ? 'primary' :
                                                (calculateOverallScore(data) >= 70 ? 'info' :
                                                (calculateOverallScore(data) >= 60 ? 'warning' : 'danger')))
                                            } p-2 fs-6">
                                                ${calculateOverallScore(data)}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                `;

                content.innerHTML = html;
                new bootstrap.Modal(document.getElementById('detailsModal')).show();
            });
    }

    // دالة حساب النتيجة النهائية للأداء
    function calculateOverallScore(data) {
        let attendanceScore = data.statistics.attendance_percentage;

        let punctualityScore = 100;
        if (data.statistics.delays > 120) {
            let excessDelays = data.statistics.delays - 120;
            punctualityScore = Math.max(0, 100 - ((excessDelays / 120) * 100));
        }

        let workingHoursScore = 100;
        if (typeof data.statistics.average_working_hours !== 'undefined') {
            workingHoursScore = Math.min(100, Math.round((data.statistics.average_working_hours / 8) * 100));
        }

        // استخدام النسب الجديدة للتقييم (الحضور 45%، الانضباط 20%، ساعات العمل 35%)
        let overallScore = Math.round((attendanceScore * 0.45) + (punctualityScore * 0.2) + (workingHoursScore * 0.35));
        return overallScore;
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
        // تم استبدال كود تهيئة collapse بكود جديد أعلاه في السكريبت

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

    document.addEventListener("DOMContentLoaded", function() {
        // ... existing code ...

        // تفعيل tooltips بشكل عام في الصفحة
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                html: true,
                container: 'body'
            });
        });

        // ... rest of existing code ...
    });
</script>
@endpush
