@extends('layouts.app')

<head>
    <link href="{{ asset('css/overtime-managment.css') }}" rel="stylesheet">
    <!-- Add Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .statistics-card {
            transition: all 0.3s ease;
        }

        .statistics-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .chart-container {
            position: relative;
            height: 200px;
            margin-top: 20px;
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
                                        data-request="{{ json_encode([
                                            'id' => $request->id,
                                            'overtime_date' => $request->overtime_date,
                                            'start_time' => \Carbon\Carbon::parse($request->start_time)->format('H:i'),
                                            'end_time' => \Carbon\Carbon::parse($request->end_time)->format('H:i'),
                                            'reason' => $request->reason
                                        ]) }}">
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
                                    @elseif($canRespondAsHR && $request->hr_status !== 'pending')
                                    <button type="button" class="btn btn-sm btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr"
                                        data-current-status="{{ $request->hr_status }}"
                                        data-current-reason="{{ $request->hr_rejection_reason }}">
                                        <i class="fas fa-edit"></i> تعديل الرد
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary reset-btn"
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
                                        data-request="{{ json_encode([
                                            'id' => $request->id,
                                            'overtime_date' => $request->overtime_date,
                                            'start_time' => \Carbon\Carbon::parse($request->start_time)->format('H:i'),
                                            'end_time' => \Carbon\Carbon::parse($request->end_time)->format('H:i'),
                                            'reason' => $request->reason
                                        ]) }}">
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

    <!-- جدول طلبات موظفي الشركة - لل HR فقط -->
    @if(Auth::user()->hasRole('hr'))
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-building"></i> طلبات العمل الإضافي لموظفي الشركة
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>الموظف</th>
                            <th>التاريخ</th>
                            <th>من</th>
                            <th>إلى</th>
                            <th>المدة</th>
                            <th>السبب</th>
                            <th>رد المدير</th>
                            <th>رد HR</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hrRequests as $request)
                        <tr>
                            <td>{{ $request->user->name }}</td>
                            <td>{{ \Carbon\Carbon::parse($request->overtime_date)->format('Y-m-d') }}</td>
                            <td>{{ $request->start_time }}</td>
                            <td>{{ $request->end_time }}</td>
                            <td>{{ $request->getFormattedDuration() }}</td>
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
                                    {{ $request->status === 'approved' ? 'معتمد' : ($request->status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                </span>
                            </td>
                            <td>
                                @if($canRespondAsHR && $request->hr_status === 'pending')
                                <button class="btn btn-sm btn-primary respond-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#respondOvertimeModal"
                                    data-request-id="{{ $request->id }}"
                                    data-response-type="hr">
                                    <i class="fas fa-reply"></i> رد HR
                                </button>
                                @elseif($canRespondAsHR && $request->hr_status !== 'pending')
                                <button type="button" class="btn btn-sm btn-warning modify-response-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modifyResponseModal"
                                    data-request-id="{{ $request->id }}"
                                    data-response-type="hr"
                                    data-current-status="{{ $request->hr_status }}"
                                    data-current-reason="{{ $request->hr_rejection_reason }}">
                                    <i class="fas fa-edit"></i> تعديل الرد
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary reset-btn"
                                    onclick="resetStatus('{{ $request->id }}', 'hr')">
                                    <i class="fas fa-undo"></i> إعادة تعيين
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center">لا توجد طلبات</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $hrRequests->links() }}
            </div>
        </div>
    </div>
    @endif

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
            <!-- Add personal statistics chart -->
            <div class="chart-section">
                <h6 class="chart-section-title">رسم بياني للإحصائيات الشخصية</h6>
                <div class="chart-container bar-chart-container chart-animate">
                    <canvas id="personalStatsChart"></canvas>
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

            <!-- Add team statistics chart -->
            <div class="chart-section">
                <h6 class="chart-section-title">رسم بياني لتوزيع حالات طلبات الفريق</h6>
                <div class="chart-container pie-chart-container chart-animate">
                    <canvas id="teamStatsChart"></canvas>
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

                <!-- إضافة عناصر جديدة للإحصائيات -->
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">معدل الموافقة</h6>
                            <h2 class="card-title mb-0">{{ number_format($hrStatistics['approval_rate'], 1) }}%</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">الطلبات المرفوضة</h6>
                            <h2 class="card-title mb-0">{{ $hrStatistics['rejected_requests'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">متوسط الساعات لكل طلب</h6>
                            <h2 class="card-title mb-0">{{ number_format($hrStatistics['average_hours_per_request'], 1) }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">مقارنة بالفترة السابقة</h6>
                            @php
                                $percentChange = $hrStatistics['comparative_analysis']['previous_period']['approved_hours'] > 0
                                    ? (($hrStatistics['comparative_analysis']['current_period']['approved_hours'] - $hrStatistics['comparative_analysis']['previous_period']['approved_hours']) / $hrStatistics['comparative_analysis']['previous_period']['approved_hours']) * 100
                                    : 100;
                            @endphp
                            <h2 class="card-title mb-0 d-flex align-items-center">
                                {{ number_format($percentChange, 1) }}%
                                @if($percentChange > 0)
                                    <i class="fas fa-arrow-up text-success ms-2"></i>
                                @elseif($percentChange < 0)
                                    <i class="fas fa-arrow-down text-danger ms-2"></i>
                                @else
                                    <i class="fas fa-minus text-secondary ms-2"></i>
                                @endif
                            </h2>
                        </div>
                    </div>
            </div>

                <!-- قسم الرسوم البيانية -->
                <div class="col-12 mt-4">
                    <h4 class="mb-3 fw-bold">تحليلات الوقت الإضافي</h4>
                </div>

                <!-- رسم بياني لحالة الطلبات -->
                <div class="col-md-6 mb-4">
                    <div class="card chart-card">
                        <div class="card-body">
                            <h5 class="chart-title">توزيع حالات الطلبات</h5>
                            <div class="chart-container">
                    <canvas id="hrStatsChart"></canvas>
                            </div>
                        </div>
                </div>
            </div>

                <!-- رسم بياني للاقسام -->
                <div class="col-md-6 mb-4">
                    <div class="card chart-card">
                        <div class="card-body">
                            <h5 class="chart-title">تحليل الأقسام</h5>
                            <div class="chart-container">
                    <canvas id="departmentsStatsChart"></canvas>
                            </div>
                        </div>
                </div>
            </div>

                <!-- رسم بياني لتوزيع الطلبات على أيام الأسبوع -->
                <div class="col-md-6 mb-4">
                    <div class="card chart-card">
                        <div class="card-body">
                            <h5 class="chart-title">تحليل أيام الأسبوع</h5>
                            <div class="chart-container">
                                <canvas id="dayOfWeekChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- رسم بياني للاتجاهات الشهرية -->
                <div class="col-md-6 mb-4">
                    <div class="card chart-card">
                        <div class="card-body">
                            <h5 class="chart-title">الاتجاهات الشهرية</h5>
                            <div class="chart-container">
                                <canvas id="monthlyTrendsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- قسم كفاءة الأقسام -->
                <div class="col-12 mt-2">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-line"></i> كفاءة الأقسام
                            </h5>
                        </div>
                        <div class="card-body">
                <div class="table-responsive">
                                <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>القسم</th>
                                            <th>الساعات المعتمدة</th>
                                            <th>إجمالي الساعات المطلوبة</th>
                                            <th>معدل الكفاءة</th>
                            </tr>
                        </thead>
                        <tbody>
                                        @foreach($hrStatistics['department_efficiency'] as $dept)
                            <tr>
                                <td>{{ $dept->department }}</td>
                                            <td>{{ number_format($dept->approved_hours, 1) }}</td>
                                            <td>{{ number_format($dept->total_requested_hours, 1) }}</td>
                                <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1" style="height: 10px;">
                                                        <div class="progress-bar bg-success" role="progressbar"
                                                            style="width: {{ $dept->efficiency_rate }}%;"
                                                            aria-valuenow="{{ $dept->efficiency_rate }}"
                                                            aria-valuemin="0"
                                                            aria-valuemax="100"></div>
                                                    </div>
                                                    <span class="ms-2">{{ number_format($dept->efficiency_rate, 1) }}%</span>
                                                </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- أكثر الموظفين استخداما للعمل الإضافي -->
                <div class="col-12 mt-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user-clock"></i> أكثر الموظفين استخداما للعمل الإضافي
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>اسم الموظف</th>
                                            <th>القسم</th>
                                            <th>عدد الطلبات</th>
                                            <th>إجمالي الساعات المعتمدة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($hrStatistics['top_employees'] as $key => $employee)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $employee->name }}</td>
                                            <td>{{ $employee->department }}</td>
                                            <td>{{ $employee->total_requests }}</td>
                                            <td>{{ number_format($employee->approved_hours, 1) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif


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
                        <label for="edit_overtime_date" class="form-label">تاريخ العمل الإضافي</label>
                        <input type="date" class="form-control" id="edit_overtime_date" name="overtime_date" required>
                        <div class="invalid-feedback">الرجاء اختيار تاريخ صحيح.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_start_time" class="form-label">وقت البداية</label>
                                <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                                <div class="invalid-feedback">الرجاء اختيار وقت البداية.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_end_time" class="form-label">وقت النهاية</label>
                                <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                                <div class="invalid-feedback">الرجاء اختيار وقت نهاية بعد وقت البداية.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_reason" class="form-label">السبب</label>
                        <textarea class="form-control" id="edit_reason" name="reason" rows="3" required maxlength="255"></textarea>
                        <div class="invalid-feedback">الرجاء كتابة سبب العمل الإضافي.</div>
                        <div class="form-text">
                            <span id="reasonCharCount">0</span>/255 حرف
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
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
<div id="departmentDetailsModal" class="modal fade" tabindex="-1" aria-labelledby="departmentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="departmentDetailsModalLabel">تفاصيل القسم: <span id="departmentName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <input type="hidden" id="departmentData" data-employees='@json($hrStatistics['department_employees'])'>
                    <table class="table table-bordered">
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
        // تأكد من أن Bootstrap Modal متاح
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap is not loaded!');
            return;
        }

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

                // Format the date to YYYY-MM-DD
                const overtimeDate = new Date(request.overtime_date);
                const formattedDate = overtimeDate.toISOString().split('T')[0];
                document.getElementById('edit_overtime_date').value = formattedDate;

                // Get the original time values (assuming they are in HH:mm format)
                const startTime = request.start_time;
                const endTime = request.end_time;

                document.getElementById('edit_start_time').value = startTime;
                document.getElementById('edit_end_time').value = endTime;
                document.getElementById('edit_reason').value = request.reason;

                // Update character count for reason
                const reasonCharCount = document.getElementById('reasonCharCount');
                if (reasonCharCount) {
                    reasonCharCount.textContent = request.reason.length;
                }

                // Add validation for end time being after start time
                document.getElementById('edit_end_time').min = startTime;
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
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw response;
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message || 'حدث خطأ أثناء حذف الطلب');
                        }
                    })
                    .catch(async error => {
                        let errorMessage = 'حدث خطأ أثناء حذف الطلب';
                        if (error instanceof Response) {
                            try {
                                const errorData = await error.json();
                                errorMessage = errorData.message || errorMessage;
                            } catch (e) {}
                        }
                        alert(errorMessage);
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
        const departmentEmployees = JSON.parse(document.getElementById('departmentData').getAttribute('data-employees'));
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

        // Character count for reason textarea
        document.getElementById('edit_reason').addEventListener('input', function() {
            const charCount = this.value.length;
            document.getElementById('reasonCharCount').textContent = charCount;
        });

        // Form validation
        document.getElementById('editOvertimeForm').addEventListener('submit', function(event) {
            const startTime = document.getElementById('edit_start_time').value;
            const endTime = document.getElementById('edit_end_time').value;

            if (endTime <= startTime) {
                event.preventDefault();
                document.getElementById('edit_end_time').setCustomValidity('يجب أن يكون وقت النهاية بعد وقت البداية');
                document.getElementById('edit_end_time').reportValidity();
            } else {
                document.getElementById('edit_end_time').setCustomValidity('');
            }
        });

        // Reset validation on input
        document.getElementById('edit_end_time').addEventListener('input', function() {
            this.setCustomValidity('');
        });

        // معالج خاص لأزرار رد HR للتأكد من فتح المودال
        document.querySelectorAll('.respond-btn[data-response-type="hr"]').forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.dataset.requestId;
                const responseType = this.dataset.responseType;
                const form = document.getElementById('respondOvertimeForm');

                // تعيين مسار النموذج
                form.action = `/overtime-requests/${requestId}/hr-status`;
                document.getElementById('response_type').value = responseType;
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

    // Initialize charts
    document.addEventListener('DOMContentLoaded', function() {
        // Define common chart colors and options
        const chartColors = [
            'rgba(54, 162, 235, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(255, 99, 132, 0.7)',
            'rgba(255, 205, 86, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(201, 203, 207, 0.7)'
        ];

        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.7)',
                    padding: 10,
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 13
                    }
                }
            }
        };

        // Personal Statistics Chart
        const personalStatsCtx = document.getElementById('personalStatsChart');
        if (personalStatsCtx) {
            new Chart(personalStatsCtx, {
                type: 'bar',
                data: {
                    labels: ['إجمالي الطلبات', 'الطلبات المعتمدة', 'الطلبات المعلقة', 'الطلبات المرفوضة'],
                    datasets: [{
                        label: 'عدد الطلبات',
                        data: [
                            {{ $personalStatistics['total_requests'] }},
                            {{ $personalStatistics['approved_requests'] }},
                            {{ $personalStatistics['pending_requests'] }},
                            {{ $personalStatistics['total_requests'] - $personalStatistics['approved_requests'] - $personalStatistics['pending_requests'] }}
                        ],
                        backgroundColor: [
                            chartColors[0],
                            chartColors[1],
                            chartColors[3],
                            chartColors[2]
                        ],
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    ...chartOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        ...chartOptions.plugins,
                        title: {
                            display: true,
                            text: 'توزيع حالات طلباتي',
                            font: {
                                size: 16
                            }
                        }
                    }
                }
            });
        }

        // Team Statistics Chart
        @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager']) && !empty($teamStatistics))
        const teamStatsCtx = document.getElementById('teamStatsChart');
        if (teamStatsCtx) {
            new Chart(teamStatsCtx, {
                type: 'pie',
                data: {
                    labels: ['معتمد', 'معلق', 'مرفوض'],
                    datasets: [{
                        data: [
                            {{ $teamStatistics['approved_requests'] }},
                            {{ $teamStatistics['pending_requests'] }},
                            {{ $teamStatistics['total_requests'] - $teamStatistics['approved_requests'] - $teamStatistics['pending_requests'] }}
                        ],
                        backgroundColor: [
                            chartColors[1],
                            chartColors[3],
                            chartColors[2]
                        ],
                        borderWidth: 1,
                        hoverOffset: 15
                    }]
                },
                options: {
                    ...chartOptions,
                    plugins: {
                        ...chartOptions.plugins,
                        title: {
                            display: true,
                            text: 'توزيع حالات طلبات الفريق',
                            font: {
                                size: 16
                            }
                        }
                    }
                }
            });
        }
        @endif

        // HR Statistics Chart - Distribution of request statuses
        @if(Auth::user()->hasRole('hr') && !empty($hrStatistics))
        const hrStatsCtx = document.getElementById('hrStatsChart');
        if (hrStatsCtx) {
            new Chart(hrStatsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['معتمد', 'معلق', 'مرفوض'],
                    datasets: [{
                        data: [
                            {{ $hrStatistics['total_company_requests'] - $hrStatistics['pending_requests'] - $hrStatistics['rejected_requests'] }},
                            {{ $hrStatistics['pending_requests'] }},
                            {{ $hrStatistics['rejected_requests'] }}
                        ],
                        backgroundColor: chartColors.slice(0, 3),
                        borderWidth: 1,
                        hoverOffset: 15
                    }]
                },
                options: {
                    ...chartOptions,
                    cutout: '50%',
                    plugins: {
                        ...chartOptions.plugins,
                        title: {
                            display: true,
                            text: 'توزيع حالات طلبات العمل الإضافي',
                            font: {
                                size: 16
                            }
                        }
                    }
                }
            });
        }

        // Day of Week Chart
        const dayOfWeekCtx = document.getElementById('dayOfWeekChart');
        if (dayOfWeekCtx) {
            // Map numeric day of week to day name
            const dayNames = {
                1: 'الأحد',
                2: 'الاثنين',
                3: 'الثلاثاء',
                4: 'الأربعاء',
                5: 'الخميس',
                6: 'الجمعة',
                7: 'السبت'
            };

            // Prepare data
            const labels = [];
            const requestData = [];
            const hoursData = [];

            @foreach($hrStatistics['day_of_week_stats'] as $day)
            labels.push(dayNames[{{ $day->day_of_week }}]);
            requestData.push({{ $day->total_requests }});
            hoursData.push({{ $day->total_hours }});
            @endforeach

            new Chart(dayOfWeekCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'عدد الطلبات',
                        data: requestData,
                        backgroundColor: chartColors[0],
                        borderColor: chartColors[0].replace('0.7', '1'),
                        borderWidth: 1,
                        borderRadius: 4
                    }, {
                        label: 'إجمالي الساعات',
                        data: hoursData,
                        backgroundColor: chartColors[1],
                        borderColor: chartColors[1].replace('0.7', '1'),
                        borderWidth: 1,
                        borderRadius: 4,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    ...chartOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'عدد الطلبات'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: 'عدد الساعات'
                            }
                        }
                    },
                    plugins: {
                        ...chartOptions.plugins,
                        title: {
                            display: true,
                            text: 'توزيع طلبات العمل الإضافي على أيام الأسبوع',
                            font: {
                                size: 16
                            }
                        }
                    }
                }
            });
        }

        // Monthly Trends Chart
        const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart');
        if (monthlyTrendsCtx) {
            // Map numeric month to month name
            const monthNames = {
                1: 'يناير', 2: 'فبراير', 3: 'مارس', 4: 'أبريل',
                5: 'مايو', 6: 'يونيو', 7: 'يوليو', 8: 'أغسطس',
                9: 'سبتمبر', 10: 'أكتوبر', 11: 'نوفمبر', 12: 'ديسمبر'
            };

            // Prepare data
            const labels = [];
            const approvedData = [];
            const rejectedData = [];
            const pendingData = [];

            @foreach($hrStatistics['monthly_trends'] as $trend)
            labels.push(monthNames[{{ $trend->month }}] + ' ' + {{ $trend->year }});
            approvedData.push({{ $trend->approved_requests }});
            rejectedData.push({{ $trend->rejected_requests }});
            pendingData.push({{ $trend->pending_requests }});
            @endforeach

            new Chart(monthlyTrendsCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'طلبات معتمدة',
                        data: approvedData,
                        backgroundColor: chartColors[1],
                        borderColor: chartColors[1].replace('0.7', '1'),
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 4
                    }, {
                        label: 'طلبات مرفوضة',
                        data: rejectedData,
                        backgroundColor: chartColors[2],
                        borderColor: chartColors[2].replace('0.7', '1'),
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 4
                    }, {
                        label: 'طلبات معلقة',
                        data: pendingData,
                        backgroundColor: chartColors[3],
                        borderColor: chartColors[3].replace('0.7', '1'),
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    ...chartOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'عدد الطلبات'
                            }
                        }
                    },
                    plugins: {
                        ...chartOptions.plugins,
                        title: {
                            display: true,
                            text: 'اتجاهات طلبات العمل الإضافي عبر الأشهر',
                            font: {
                                size: 16
                            }
                        }
                    }
                }
            });
        }

        // Departments Chart
        const deptStatsCtx = document.getElementById('departmentsStatsChart');
        if (deptStatsCtx) {
            // Prepare data
            const labels = [];
            const hoursData = [];
            const requestsData = [];

            @foreach($hrStatistics['departments_stats'] as $dept)
            labels.push('{{ $dept->department }}');
            hoursData.push({{ $dept->total_hours }});
            requestsData.push({{ $dept->total_requests }});
            @endforeach

            new Chart(deptStatsCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'إجمالي الساعات',
                        data: hoursData,
                        backgroundColor: chartColors[0],
                        borderColor: chartColors[0].replace('0.7', '1'),
                        borderWidth: 1,
                        borderRadius: 4
                    }, {
                        label: 'عدد الطلبات',
                        data: requestsData,
                        backgroundColor: chartColors[2],
                        borderColor: chartColors[2].replace('0.7', '1'),
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    ...chartOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'العدد'
                            }
                        }
                    },
                    plugins: {
                        ...chartOptions.plugins,
                        title: {
                            display: true,
                            text: 'تحليل أقسام الشركة',
                            font: {
                                size: 16
                            }
                        }
                    }
                }
            });
        }
        @endif
    });
</script>
@endpush

@endsection