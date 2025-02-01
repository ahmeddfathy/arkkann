@extends('layouts.app')

<head>
    <link href="{{ asset('css/overtime-managment.css') }}" rel="stylesheet">
    <style>
        .statistics-card {
            transition: all 0.3s ease;
        }

        .statistics-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
@section('content')
<div class="container">
    @include('shared.alerts')

    <!-- فورم البحث والفلترة -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('overtime-requests.index') }}" class="row g-3">
                @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
                <div class="col-md-3">
                    <label for="employee_name" class="form-label">بحث عن موظف</label>
                    <input type="text" class="form-control" id="employee_name" name="employee_name"
                        value="{{ $filters['employeeName'] }}" placeholder="ادخل اسم الموظف" list="employee_names">
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
                        <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>معلق</option>
                        <option value="approved" {{ $filters['status'] === 'approved' ? 'selected' : '' }}>موافق عليه</option>
                        <option value="rejected" {{ $filters['status'] === 'rejected' ? 'selected' : '' }}>مرفوض</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="from_date" class="form-label">من تاريخ</label>
                    <input type="date" class="form-control" id="from_date" name="from_date"
                        value="{{ $dateStart->format('Y-m-d') }}">
                </div>

                <div class="col-md-2">
                    <label for="to_date" class="form-label">إلى تاريخ</label>
                    <input type="date" class="form-control" id="to_date" name="to_date"
                        value="{{ $dateEnd->format('Y-m-d') }}">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> تطبيق الفلتر
                    </button>
                    <a href="{{ route('overtime-requests.index') }}" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> إعادة تعيين
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- إحصائيات شخصية -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-user-clock"></i> إحصائياتي
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">إجمالي طلباتي</h6>
                            <h2 class="card-title mb-0">{{ $personalStatistics['total_requests'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">الطلبات المعتمدة</h6>
                            <h2 class="card-title mb-0">{{ $personalStatistics['approved_requests'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">الطلبات المعلقة</h6>
                            <h2 class="card-title mb-0">{{ $personalStatistics['pending_requests'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">إجمالي ساعات العمل الإضافي</h6>
                            <h2 class="card-title mb-0">{{ number_format($personalStatistics['total_hours'], 1) }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات الفريق للمدراء -->
    @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager']) && !empty($teamStatistics))
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-users"></i> إحصائيات الفريق
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">إجمالي طلبات الفريق</h6>
                            <h2 class="card-title mb-0">{{ $teamStatistics['total_requests'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">الطلبات المعتمدة</h6>
                            <h2 class="card-title mb-0">{{ $teamStatistics['approved_requests'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">الطلبات المعلقة</h6>
                            <h2 class="card-title mb-0">{{ $teamStatistics['pending_requests'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">إجمالي ساعات الفريق</h6>
                            <h2 class="card-title mb-0">{{ number_format($teamStatistics['total_hours'], 1) }}</h2>
                        </div>
                    </div>
                </div>
            </div>
            @if($teamStatistics['most_active_employee'])
            <div class="alert alert-info mt-3">
                <h6 class="alert-heading">الموظف الأكثر نشاطاً</h6>
                <p class="mb-0">
                    {{ $teamStatistics['most_active_employee']->name }}
                    ({{ $teamStatistics['most_active_employee']->overtime_requests_count }} طلب)
                </p>
            </div>
            @endif

            <!-- تفاصيل موظفي الفريق -->
            <div class="mt-4">
                <button type="button" class="btn btn-info mb-3"
                    data-bs-toggle="modal"
                    data-bs-target="#teamDetailsModal">
                    <i class="fas fa-users"></i> عرض تفاصيل موظفي الفريق
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- مودل تفاصيل الفريق -->
    @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager']) && !empty($teamStatistics))
    <div class="modal fade" id="teamDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل موظفي الفريق</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>الموظف</th>
                                    <th>إجمالي الطلبات</th>
                                    <th>الطلبات المعتمدة</th>
                                    <th>الطلبات المرفوضة</th>
                                    <th>الطلبات المعلقة</th>
                                    <th>الساعات المطلوبة</th>
                                    <th>الساعات المعتمدة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teamStatistics['team_employees'] as $employee)
                                <tr>
                                    <td>{{ $employee->name }}</td>
                                    <td>{{ $employee->total_requests }}</td>
                                    <td>{{ $employee->approved_requests }}</td>
                                    <td>{{ $employee->rejected_requests }}</td>
                                    <td>{{ $employee->pending_requests }}</td>
                                    <td>{{ number_format($employee->total_requested_hours, 1) }}</td>
                                    <td>{{ number_format($employee->approved_hours, 1) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- إحصائيات HR -->
    @if(Auth::user()->hasRole('hr') && !empty($hrStatistics))
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-chart-pie"></i> إحصائيات الشركة
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">إجمالي طلبات الشركة</h6>
                            <h2 class="card-title mb-0">{{ $hrStatistics['total_company_requests'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">إجمالي الساعات المعتمدة</h6>
                            <h2 class="card-title mb-0">{{ number_format($hrStatistics['total_approved_hours'], 1) }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">الطلبات المعلقة</h6>
                            <h2 class="card-title mb-0">{{ $hrStatistics['pending_requests'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إحصائيات الأقسام -->
            <div class="mt-4">
                <h6>إحصائيات الأقسام</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>القسم</th>
                                <th>عدد الموظفين</th>
                                <th>عدد الطلبات</th>
                                <th>إجمالي الساعات</th>
                                <th>متوسط الساعات/موظف</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hrStatistics['departments_stats'] as $dept)
                            <tr>
                                <td>{{ $dept->department }}</td>
                                <td>{{ $dept->total_employees }}</td>
                                <td>{{ $dept->total_requests }}</td>
                                <td>{{ number_format($dept->total_hours, 1) }}</td>
                                <td>{{ $dept->total_employees > 0 ? number_format($dept->total_hours / $dept->total_employees, 1) : 0 }}</td>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#departmentDetailsModal"
                                        data-department="{{ $dept->department }}">
                                        <i class="fas fa-users"></i> تفاصيل الموظفين
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- جدول طلبات الفريق -->
    @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-users"></i> طلبات العمل الإضافي للفريق
            </h5>
            <span class="badge bg-primary ms-2">{{ $myOvertimeHours }} ساعة معتمدة</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
                            <th>الموظف</th>
                            @endif
                            <th>التاريخ</th>
                            <th>الوقت</th>
                            <th>المدة</th>
                            <th>السبب</th>
                            <th>رد المدير</th>
                            <th>سبب رفض المدير</th>
                            <th>رد HR</th>
                            <th>سبب رفض HR</th>
                            <th>الحالة النهائية</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teamRequests as $request)
                        <tr>
                            @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
                            <td>{{ $request->user->name }}</td>
                            @endif
                            <td>{{ $request->overtime_date->format('Y-m-d') }}</td>
                            <td>
                                {{ Carbon\Carbon::parse($request->start_time)->format('H:i') }} -
                                {{ Carbon\Carbon::parse($request->end_time)->format('H:i') }}
                            </td>
                            <td>{{ $request->getFormattedDuration() }}</td>
                            <td>{{ Str::limit($request->reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->manager_status) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($request->manager_rejection_reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->hr_status) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($request->hr_rejection_reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @if($request->canUpdate(Auth::user()))
                                    <button type="button" class="btn btn-primary edit-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editOvertimeModal"
                                        data-request="{{ json_encode($request) }}">
                                        <i class="fas fa-edit"></i> تعديل
                                    </button>
                                    @endif

                                    @if($request->canDelete(Auth::user()))
                                    <form action="{{ route('overtime-requests.destroy', $request->id) }}"
                                        method="POST"
                                        class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> حذف
                                        </button>
                                    </form>
                                    @endif

                                    @if($canRespondAsManager && !Auth::user()->hasRole('hr'))
                                    @if($request->manager_status === 'pending')
                                    <button type="button" class="btn btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondOvertimeModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="manager">
                                        <i class="fas fa-reply"></i> رد المدير
                                    </button>
                                    @else
                                    <button type="button" class="btn btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="manager"
                                        data-current-status="{{ $request->manager_status }}"
                                        data-current-reason="{{ $request->manager_rejection_reason }}">
                                        <i class="fas fa-edit"></i> تعديل الرد
                                    </button>
                                    <button type="button" class="btn btn-secondary reset-btn"
                                        onclick="resetStatus('{{ $request->id }}', 'manager')">
                                        <i class="fas fa-undo"></i> إعادة تعيين
                                    </button>
                                    @endif
                                    @endif

                                    @if($canRespondAsHR)
                                    @if($request->hr_status === 'pending')
                                    <button type="button" class="btn btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondOvertimeModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr">
                                        <i class="fas fa-reply"></i> رد HR
                                    </button>
                                    @else
                                    <button type="button" class="btn btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr"
                                        data-current-status="{{ $request->hr_status }}"
                                        data-current-reason="{{ $request->hr_rejection_reason }}">
                                        <i class="fas fa-edit"></i> تعديل الرد
                                    </button>
                                    <button type="button" class="btn btn-secondary reset-btn"
                                        onclick="resetStatus('{{ $request->id }}', 'hr')">
                                        <i class="fas fa-undo"></i> إعادة تعيين
                                    </button>
                                    @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']) ? '11' : '10' }}"
                                class="text-center">
                                لا توجد طلبات عمل إضافي
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-4">
                {{ $teamRequests->links() }}
            </div>
        </div>
    </div>
    @endif
    <!-- جدول طلباتي -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-clock"></i> طلبات العمل الإضافي الخاصة بي
            </h5>
            @if($canCreateOvertime)
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createOvertimeModal">
                <i class="fas fa-plus"></i> طلب جديد
            </button>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>الوقت</th>
                            <th>المدة</th>
                            <th>السبب</th>
                            <th>رد المدير</th>
                            <th>سبب رفض المدير</th>
                            <th>رد HR</th>
                            <th>سبب رفض HR</th>
                            <th>الحالة النهائية</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($myRequests as $request)
                        <tr>
                            <td>{{ $request->overtime_date->format('Y-m-d') }}</td>
                            <td>
                                {{ Carbon\Carbon::parse($request->start_time)->format('H:i') }} -
                                {{ Carbon\Carbon::parse($request->end_time)->format('H:i') }}
                            </td>
                            <td>{{ $request->getFormattedDuration() }}</td>
                            <td>{{ Str::limit($request->reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->manager_status) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($request->manager_rejection_reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->hr_status) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($request->hr_rejection_reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @if($request->canUpdate(Auth::user()) && $request->hr_status === 'pending' && $request -> manager_status == 'pending')
                                    <button type="button" class="btn btn-primary edit-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editOvertimeModal"
                                        data-request="{{ json_encode($request) }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @endif

                                    @if($request->canDelete(Auth::user()) && $request->hr_status === 'pending' && $request -> manager_status == 'pending'))
                                    <form action="{{ route('overtime-requests.destroy', $request->id) }}"
                                        method="POST"
                                        class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> حذف
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center">No overtime requests found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-4">
                {{ $myRequests->links() }}
            </div>
        </div>
    </div>

    <!-- جدول الموظفين بدون فريق - لل HR -->
    @if(Auth::user()->hasRole('hr') && $noTeamRequests->count() > 0)
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Requests from Employees Without Team</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Duration</th>
                            <th>Reason</th>
                            <th>Manager Status</th>
                            <th>Manager Rejection</th>
                            <th>HR Status</th>
                            <th>HR Rejection</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($noTeamRequests as $request)
                        <tr>
                            <td>{{ $request->user->name }}</td>
                            <td>{{ $request->overtime_date->format('Y-m-d') }}</td>
                            <td>
                                {{ Carbon\Carbon::parse($request->start_time)->format('H:i') }} -
                                {{ Carbon\Carbon::parse($request->end_time)->format('H:i') }}
                            </td>
                            <td>{{ $request->getFormattedDuration() }}</td>
                            <td>{{ Str::limit($request->reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->manager_status) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($request->manager_rejection_reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->hr_status) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($request->hr_rejection_reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @if($canRespondAsHR)
                                    @if($request->hr_status === 'pending')
                                    <button type="button" class="btn btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondOvertimeModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    @else
                                    <button type="button" class="btn btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr"
                                        data-current-status="{{ $request->hr_status }}"
                                        data-current-reason="{{ $request->hr_rejection_reason }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-secondary reset-btn"
                                        onclick="resetStatus('{{ $request->id }}', 'hr')">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="d-flex justify-content-center mt-4">
                    {{ $noTeamRequests->links() }}
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Create Modal -->
<div class="modal fade" id="createOvertimeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">طلب عمل إضافي جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('overtime-requests.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="registration_type" id="self_registration" value="self" checked>
                            <label class="form-check-label" for="self_registration">لنفسي</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="registration_type" id="other_registration" value="other">
                            <label class="form-check-label" for="other_registration">لموظف آخر</label>
                        </div>
                    </div>

                    <div class="mb-3 d-none" id="employee_select_container">
                        <label for="employee_id" class="form-label">اختر الموظف</label>
                        <select class="form-select" id="employee_id" name="employee_id">
                            <option value="">اختر موظف...</option>
                            @foreach($users as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">الرجاء اختيار موظف.</div>
                    </div>
                </div>
                @endif

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="overtime_date" class="form-label">تاريخ العمل الإضافي</label>
                        <input type="date" class="form-control" id="overtime_date" name="overtime_date" required>
                        <div class="invalid-feedback">الرجاء اختيار تاريخ صحيح.</div>
                    </div>

                    <div class="mb-3">
                        <label for="start_time" class="form-label">وقت البداية</label>
                        <input type="time" class="form-control" id="start_time" name="start_time" required>
                        <div class="invalid-feedback">الرجاء اختيار وقت البداية.</div>
                    </div>

                    <div class="mb-3">
                        <label for="end_time" class="form-label">وقت النهاية</label>
                        <input type="time" class="form-control" id="end_time" name="end_time" required>
                        <div class="invalid-feedback">الرجاء اختيار وقت النهاية.</div>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">السبب</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                        <div class="invalid-feedback">الرجاء كتابة السبب.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="submit" class="btn btn-primary">إرسال الطلب</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editOvertimeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل طلب العمل الإضافي</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editOvertimeForm" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_overtime_date" class="form-label">Overtime Date</label>
                        <input type="date" class="form-control" id="edit_overtime_date" name="overtime_date" required>
                        <div class="invalid-feedback">Please select a valid date.</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_start_time" class="form-label">Start Time</label>
                        <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                        <div class="invalid-feedback">Please select a start time.</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_end_time" class="form-label">End Time</label>
                        <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                        <div class="invalid-feedback">Please select an end time that is after the start time.</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_reason" class="form-label">Reason</label>
                        <textarea class="form-control" id="edit_reason" name="reason" rows="3" required></textarea>
                        <div class="invalid-feedback">Please provide a reason.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Response Modal -->
<div class="modal fade" id="respondOvertimeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">الرد على الطلب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="respondOvertimeForm" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="response_type" name="response_type">

                    <div class="mb-3">
                        <label class="form-label d-block">الرد</label>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="status" id="approve" value="approved" required>
                            <label class="btn btn-outline-success" for="approve">
                                <i class="fas fa-check"></i> موافقة
                            </label>

                            <input type="radio" class="btn-check" name="status" id="reject" value="rejected" required>
                            <label class="btn btn-outline-danger" for="reject">
                                <i class="fas fa-times"></i> رفض
                            </label>
                        </div>
                        <div class="invalid-feedback">الرجاء اختيار الرد.</div>
                    </div>

                    <div class="mb-3 d-none" id="rejection_reason_container">
                        <label for="rejection_reason" class="form-label">سبب الرفض</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3"></textarea>
                        <div class="invalid-feedback">الرجاء كتابة سبب الرفض.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="submit" class="btn btn-primary">إرسال الرد</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modify Response Modal -->
<div class="modal fade" id="modifyResponseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل الرد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="modifyResponseForm" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="modify_response_type" name="response_type">

                    <div class="mb-3">
                        <label class="form-label d-block">الرد الجديد</label>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="status" id="modify_approve" value="approved" required>
                            <label class="btn btn-outline-success" for="modify_approve">
                                <i class="fas fa-check"></i> موافقة
                            </label>

                            <input type="radio" class="btn-check" name="status" id="modify_reject" value="rejected" required>
                            <label class="btn btn-outline-danger" for="modify_reject">
                                <i class="fas fa-times"></i> رفض
                            </label>
                        </div>
                        <div class="invalid-feedback">الرجاء اختيار الرد.</div>
                    </div>

                    <div class="mb-3 d-none" id="modify_rejection_reason_container">
                        <label for="modify_rejection_reason" class="form-label">سبب الرفض</label>
                        <textarea class="form-control" id="modify_rejection_reason" name="rejection_reason" rows="3"></textarea>
                        <div class="invalid-feedback">الرجاء كتابة سبب الرفض.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="submit" class="btn btn-primary">تحديث الرد</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- إضافة المودل للـ HR -->
@if(Auth::user()->hasRole('hr'))
<div class="modal fade" id="departmentDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تفاصيل موظفي القسم: <span id="departmentName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>الموظف</th>
                                <th>إجمالي الطلبات</th>
                                <th>الطلبات المعتمدة</th>
                                <th>الطلبات المرفوضة</th>
                                <th>الطلبات المعلقة</th>
                                <th>الساعات المطلوبة</th>
                                <th>الساعات المعتمدة</th>
                            </tr>
                        </thead>
                        <tbody id="departmentEmployees">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form Validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        // Create Modal Handler
        const registrationTypeInputs = document.querySelectorAll('input[name="registration_type"]');
        const employeeSelectContainer = document.getElementById('employee_select_container');
        const employeeIdSelect = document.getElementById('employee_id');
        const userIdInput = document.querySelector('input[name="user_id"]');

        if (registrationTypeInputs) {
            registrationTypeInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.value === 'other') {
                        employeeSelectContainer.classList.remove('d-none');
                        employeeIdSelect.required = true;
                        userIdInput.value = '';
                    } else {
                        employeeSelectContainer.classList.add('d-none');
                        employeeIdSelect.required = false;
                        employeeIdSelect.value = '';
                        userIdInput.value = '{{ Auth::id() }}';
                    }
                });
            });
        }

        // عند تغيير الموظف المختار
        if (employeeIdSelect) {
            employeeIdSelect.addEventListener('change', function() {
                userIdInput.value = this.value;
            });
        }

        // Edit Modal Handler
        const editButtons = document.querySelectorAll('.edit-btn');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const request = JSON.parse(this.dataset.request);
                const form = document.getElementById('editOvertimeForm');
                form.action = `/overtime-requests/${request.id}`;

                document.getElementById('edit_overtime_date').value = request.overtime_date;
                document.getElementById('edit_start_time').value = request.start_time;
                document.getElementById('edit_end_time').value = request.end_time;
                document.getElementById('edit_reason').value = request.reason;
            });
        });

        // Response Modal Handler
        const responseButtons = document.querySelectorAll('.respond-btn');
        responseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.dataset.requestId;
                const responseType = this.dataset.responseType;
                const form = document.getElementById('respondOvertimeForm');

                form.action = `/overtime-requests/${requestId}/${responseType}-status`;
                document.getElementById('response_type').value = responseType;
            });
        });

        // Status Change Handler
        const statusInputs = document.querySelectorAll('input[name="status"]');
        const rejectionContainer = document.getElementById('rejection_reason_container');
        const rejectionInput = document.getElementById('rejection_reason');

        if (statusInputs) {
            statusInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.value === 'rejected') {
                        rejectionContainer.classList.remove('d-none');
                        rejectionInput.required = true;
                    } else {
                        rejectionContainer.classList.add('d-none');
                        rejectionInput.required = false;
                    }
                });
            });
        }

        // Delete Confirmation
        const deleteForms = document.querySelectorAll('.delete-form');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (confirm('هل أنت متأكد من حذف هذا الطلب؟')) {
                    fetch(this.action, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert('حدث خطأ أثناء حذف الطلب');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('حدث خطأ أثناء حذف الطلب');
                        });
                }
            });
        });

        // Modify Response Handler
        const modifyResponseButtons = document.querySelectorAll('.modify-response-btn');
        modifyResponseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.dataset.requestId;
                const responseType = this.dataset.responseType;
                const currentStatus = this.dataset.currentStatus;
                const currentReason = this.dataset.currentReason;
                const form = document.getElementById('modifyResponseForm');

                form.action = `/overtime-requests/${requestId}/modify-${responseType}-status`;
                document.getElementById('modify_response_type').value = responseType;

                if (currentStatus === 'approved') {
                    document.getElementById('modify_approve').checked = true;
                    document.getElementById('modify_rejection_reason_container').classList.add('d-none');
                    document.getElementById('modify_rejection_reason').required = false;
                } else {
                    document.getElementById('modify_reject').checked = true;
                    document.getElementById('modify_rejection_reason_container').classList.remove('d-none');
                    document.getElementById('modify_rejection_reason').value = currentReason;
                    document.getElementById('modify_rejection_reason').required = true;
                }
            });
        });

        // Modify Status Change Handler
        const modifyStatusInputs = document.querySelectorAll('#modifyResponseForm input[name="status"]');
        const modifyRejectionContainer = document.getElementById('modify_rejection_reason_container');
        const modifyRejectionInput = document.getElementById('modify_rejection_reason');

        if (modifyStatusInputs) {
            modifyStatusInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.value === 'rejected') {
                        modifyRejectionContainer.classList.remove('d-none');
                        modifyRejectionInput.required = true;
                    } else {
                        modifyRejectionContainer.classList.add('d-none');
                        modifyRejectionInput.required = false;
                        modifyRejectionInput.value = '';
                    }
                });
            });
        }

        // تحميل بيانات القسم
        const departmentEmployees = @json($hrStatistics['department_employees'] ?? []);
        const modal = document.getElementById('departmentDetailsModal');

        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const department = button.dataset.department;
            const employees = departmentEmployees[department] || [];

            document.getElementById('departmentName').textContent = department;
            const tbody = document.getElementById('departmentEmployees');
            tbody.innerHTML = '';

            employees.forEach(employee => {
                tbody.innerHTML += `
                    <tr>
                        <td>${employee.name}</td>
                        <td>${employee.total_requests}</td>
                        <td>${employee.approved_requests}</td>
                        <td>${employee.rejected_requests}</td>
                        <td>${employee.pending_requests}</td>
                        <td>${Number(employee.total_requested_hours).toFixed(1)}</td>
                        <td>${Number(employee.approved_hours).toFixed(1)}</td>
                    </tr>
                `;
            });
        });
    });

    function resetStatus(requestId, type) {
        if (confirm('هل أنت متأكد من إعادة تعيين هذا الرد؟')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/overtime-requests/${requestId}/reset-${type}-status`;

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
@endpush

@endsection