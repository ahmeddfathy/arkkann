@extends('layouts.app')

@section('content')
<link href="{{ asset('css/absence-management.css') }}" rel="stylesheet">
<div class="container">




    <!-- قسم البحث والفلترة -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('absence-requests.index') }}" class="row g-3">
                @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
                <div class="col-md-3">
                    <label for="employee_name" class="form-label">بحث عن موظف</label>
                    <input type="text" class="form-control" id="employee_name" name="employee_name"
                        value="{{ request('employee_name') }}" placeholder="ادخل اسم الموظف"
                        list="employee_names">
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
                        value="{{ request('from_date', $currentMonthStart->format('Y-m-d')) }}">
                </div>

                <div class="col-md-2">
                    <label for="to_date" class="form-label">إلى تاريخ</label>
                    <input type="date" class="form-control" id="to_date" name="to_date"
                        value="{{ request('to_date', $currentMonthEnd->format('Y-m-d')) }}">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>تطبيق الفلتر
                    </button>
                    <a href="{{ route('absence-requests.index') }}" class="btn btn-secondary ms-2">
                        <i class="fas fa-undo me-2"></i>إعادة تعيين
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- عرض الفترة الحالية -->
    <div class="alert alert-info mb-4">
        <i class="fas fa-calendar-alt me-2"></i>
        الفترة: {{ $dateStart->format('Y-m-d') }} إلى {{ $dateEnd->format('Y-m-d') }}
    </div>

    <!-- جدول طلبات المستخدم الشخصية (للجميع) -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt"></i> طلباتي
                        @php
                            $maxAllowedDays = Auth::user()->getMaxAllowedAbsenceDays();
                        @endphp
                        <small class="ms-2">(عدد أيام الغياب المعتمدة: {{ $myAbsenceDays }} من أصل {{ $maxAllowedDays }} يوم)</small>
                    </h5>
                    @if($canCreateAbsence)
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createAbsenceModal">
                        <i class="fas fa-plus"></i> طلب جديد
                    </button>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>تاريخ الغياب</th>
                                <th>السبب</th>
                                <th>رد المدير</th>
                                <th>سبب رفض المدير</th>
                                <th>رد HR</th>
                                <th>سبب رفض HR</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($myRequests as $request)
                            <tr class="request-row">
                                <td>{{ \Carbon\Carbon::parse($request->absence_date)->format('Y-m-d') }}</td>
                                <td>{{ $request->reason }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->manager_status === 'approved' ? 'success' :
                                        ($request->manager_status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->manager_status === 'approved' ? 'موافق عليه' :
                                            ($request->manager_status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>{{ $request->manager_rejection_reason ?: '-' }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->hr_status === 'approved' ? 'success' :
                                        ($request->hr_status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->hr_status === 'approved' ? 'موافق عليه' :
                                            ($request->hr_status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>{{ $request->hr_rejection_reason ?: '-' }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->status === 'approved' ? 'success' :
                                        ($request->status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->status === 'approved' ? 'موافق عليه' :
                                            ($request->status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>
                                    @if(Auth::id() === $request->user_id && $request->manager_status === 'pending' && $request->hr_status === 'pending' && $canUpdateAbsence)
                                    <button class="btn btn-sm btn-warning edit-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAbsenceModal"
                                        data-request="{{ json_encode([
                                            'id' => $request->id,
                                            'absence_date' => \Carbon\Carbon::parse($request->absence_date)->format('Y-m-d'),
                                            'reason' => $request->reason
                                        ]) }}">
                                        <i class="fas fa-edit"></i> تعديل
                                    </button>
                                    @endif

                                    @if(Auth::id() === $request->user_id && $request->manager_status === 'pending' && $request->hr_status === 'pending' && $canDeleteAbsence)
                                    <form action="{{ route('absence-requests.destroy', $request) }}"
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
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">لا توجد طلبات غياب</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول طلبات الفريق (للمدراء و HR فقط) -->
    @if(Auth::user()->hasRole(['hr', 'team_leader', 'department_manager', 'company_manager']))
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt"></i> طلبات غياب التيم
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>اسم الموظف</th>
                                <th>أيام الغياب المعتمدة</th>
                                <th>تاريخ الغياب</th>
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
                            <tr class="request-row">
                                <td>{{ $request->user->name }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        @php
                                            $maxDays = $request->user->getMaxAllowedAbsenceDays();
                                            $approvedDays = $absenceDaysCount[$request->user_id] ?? 0;
                                        @endphp
                                        {{ $approvedDays }} من أصل {{ $maxDays }} يوم
                                    </span>
                                </td>
                                <td>{{ $request->absence_date }}</td>
                                <td>{{ $request->reason }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->manager_status === 'approved' ? 'success' :
                                        ($request->manager_status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->manager_status === 'approved' ? 'موافق' :
                                            ($request->manager_status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>{{ $request->manager_rejection_reason ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->hr_status === 'approved' ? 'success' :
                                        ($request->hr_status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->hr_status === 'approved' ? 'موافق' :
                                            ($request->hr_status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>{{ $request->hr_rejection_reason ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->status === 'approved' ? 'success' :
                                        ($request->status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                                $request->status === 'approved' ? 'موافق' :
                                            ($request->status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>
                                    @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager']))
                                    @if($request->manager_status === 'pending')
                                    @if($canRespondAsManager)
                                    <button class="btn btn-sm btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="manager">
                                        <i class="fas fa-reply"></i> رد المدير
                                    </button>
                                    @endif
                                    @else
                                    <button class="btn btn-sm btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="manager"
                                        data-status="{{ $request->manager_status }}"
                                        data-reason="{{ $request->manager_rejection_reason }}">
                                        <i class="fas fa-edit"></i> تعديل رد المدير
                                    </button>

                                    <form action="{{ route('absence-requests.reset-status', $request) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="response_type" value="manager">
                                        <button type="submit"
                                            class="btn btn-sm btn-secondary"
                                            onclick="return confirm('هل أنت متأكد من إعادة تعيين الحالة؟')">
                                            <i class="fas fa-undo"></i> إعادة تعيين
                                        </button>
                                    </form>
                                    @endif
                                    @endif

                                    @if(Auth::user()->hasRole('hr'))
                                    @if($request->hr_status === 'pending')
                                    @if($canRespondAsHR)
                                    <button class="btn btn-sm btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr">
                                        <i class="fas fa-reply"></i> رد HR
                                    </button>
                                    @endif
                                    @else
                                    <button class="btn btn-sm btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr"
                                        data-status="{{ $request->hr_status }}"
                                        data-reason="{{ $request->hr_rejection_reason }}">
                                        <i class="fas fa-edit"></i> تعديل رد HR
                                    </button>

                                    <form action="{{ route('absence-requests.reset-status', $request) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="response_type" value="hr">
                                        <button type="submit"
                                            class="btn btn-sm btn-secondary"
                                            onclick="return confirm('هل أنت متأكد من إعادة تعيين الحالة؟')">
                                            <i class="fas fa-undo"></i> إعادة تعيين
                                        </button>
                                    </form>
                                    @endif
                                    @endif

                                    @if(Auth::id() === $request->user_id && $request->manager_status === 'pending' && $request->hr_status === 'pending' && $canUpdateAbsence)
                                    <button class="btn btn-sm btn-warning edit-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAbsenceModal"
                                        data-request="{{ json_encode([
                                            'id' => $request->id,
                                            'absence_date' => \Carbon\Carbon::parse($request->absence_date)->format('Y-m-d'),
                                            'reason' => $request->reason
                                        ]) }}">
                                        <i class="fas fa-edit"></i> تعديل
                                    </button>
                                    @endif

                                    @if(Auth::id() === $request->user_id && $request->manager_status === 'pending' && $request->hr_status === 'pending' && $canDeleteAbsence)
                                    <form action="{{ route('absence-requests.destroy', $request) }}"
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
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center">لا توجد طلبات غياب</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if($teamRequests instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    {{ $teamRequests->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- جدول طلبات HR للشركة -->
    @if(Auth::user()->hasRole('hr'))
    <div class="row justify-content-center mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-building"></i> طلبات موظفي الشركة</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>اسم الموظف</th>
                                <th>الفريق</th>
                                <th>تاريخ الغياب</th>
                                <th>السبب</th>
                                <th>أيام الغياب المعتمدة</th>
                                <th>رد المدير</th>
                                <th>سبب رفض المدير</th>
                                <th>رد HR</th>
                                <th>سبب رفض HR</th>
                                <th>الحالة النهائية</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($hrRequests as $request)
                            <tr>
                                <td>
                                    <div>{{ $request->user->name }}</div>
                                    <small class="text-muted">{{ $request->user->employee_id }}</small>
                                </td>
                                <td>{{ $request->user->currentTeam ? $request->user->currentTeam->name : 'بدون فريق' }}</td>
                                <td>{{ $request->absence_date }}</td>
                                <td>{{ $request->reason }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        @php
                                            $maxDays = $request->user->getMaxAllowedAbsenceDays();
                                            $approvedDays = $hrAbsenceDaysCount[$request->user_id] ?? 0;
                                        @endphp
                                        {{ $approvedDays }} من أصل {{ $maxDays }} يوم
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->manager_status === 'approved' ? 'success' :
                                        ($request->manager_status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->manager_status === 'approved' ? 'موافق' :
                                            ($request->manager_status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>{{ $request->manager_rejection_reason ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->hr_status === 'approved' ? 'success' :
                                        ($request->hr_status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->hr_status === 'approved' ? 'موافق' :
                                            ($request->hr_status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>{{ $request->hr_rejection_reason ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->status === 'approved' ? 'success' :
                                        ($request->status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                                $request->status === 'approved' ? 'موافق' :
                                            ($request->status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>
                                    @if($canRespondAsHR)
                                    @if($request->hr_status === 'pending')
                                    <button class="btn btn-sm btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr">
                                        <i class="fas fa-reply"></i> رد HR
                                    </button>
                                    @else
                                    <button class="btn btn-sm btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr"
                                        data-status="{{ $request->hr_status }}"
                                        data-reason="{{ $request->hr_rejection_reason }}">
                                        <i class="fas fa-edit"></i> تعديل رد HR
                                    </button>

                                    <form action="{{ route('absence-requests.reset-status', $request) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="response_type" value="hr">
                                        <button type="submit"
                                            class="btn btn-sm btn-secondary"
                                            onclick="return confirm('هل أنت متأكد من إعادة تعيين الحالة؟')">
                                            <i class="fas fa-undo"></i> إعادة تعيين
                                        </button>
                                    </form>
                                    @endif
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="text-center">لا توجد طلبات غياب</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $hrRequests->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
    @endif

    <!-- جدول طلبات الموظفين بدون فريق (يظهر فقط لل HR) -->
    @if(Auth::user()->hasRole('hr') && $noTeamRequests->count() > 0)
    <div class="row justify-content-center mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> طلبات الموظفين بدون فريق
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>اسم الموظف</th>
                                <th>أيام الغياب المعتمدة</th>
                                <th>تاريخ الغياب</th>
                                <th>السبب</th>
                                <th>رد HR</th>
                                <th>سبب رفض HR</th>
                                <th>الحالة النهائية</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($noTeamRequests as $request)
                            <tr class="request-row">
                                <td>{{ $request->user->name }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        @php
                                            $maxDays = $request->user->getMaxAllowedAbsenceDays();
                                            $approvedDays = $noTeamAbsenceDaysCount[$request->user_id] ?? 0;
                                        @endphp
                                        {{ $approvedDays }} من أصل {{ $maxDays }} يوم
                                    </span>
                                </td>
                                <td>{{ $request->absence_date }}</td>
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
                                <td>
                                    @if($request->hr_status === 'pending')
                                    @if($canRespondAsHR)
                                    <button class="btn btn-sm btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr">
                                        <i class="fas fa-reply"></i> رد HR
                                    </button>
                                    @endif
                                    @else
                                    <button class="btn btn-sm btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr"
                                        data-status="{{ $request->hr_status }}"
                                        data-reason="{{ $request->hr_rejection_reason }}">
                                        <i class="fas fa-edit"></i> تعديل رد HR
                                    </button>

                                    <form action="{{ route('absence-requests.reset-status', $request) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="response_type" value="hr">
                                        <button type="submit"
                                            class="btn btn-sm btn-secondary"
                                            onclick="return confirm('هل أنت متأكد من إعادة تعيين الحالة؟')">
                                            <i class="fas fa-undo"></i> إعادة تعيين
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $noTeamRequests->appends(['employee_name' => request('employee_name'), 'status' => request('status')])->links() }}
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Create Modal -->
    <div class="modal fade" id="createAbsenceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إنشاء طلب غياب جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('absence-requests.store') }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    <div class="modal-body">
                        <!-- اختيار الموظف (يظهر فقط للمدراء) -->
                        @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
                        <div class="mb-3">
                            <label for="user_id" class="form-label">الموظف</label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">اختر الموظف...</option>
                                <option value="{{ Auth::id() }}">أنا ({{ Auth::user()->name }})</option>
                                @foreach($users->where('id', '!=', Auth::id()) as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">
                                يرجى اختيار الموظف
                            </div>
                        </div>
                        @endif

                        <!-- تاريخ الغياب -->
                        <div class="mb-3">
                            <label for="absence_date" class="form-label">تاريخ الغياب</label>
                            <input type="date" class="form-control" id="absence_date" name="absence_date"
                                required>
                            <div class="invalid-feedback">
                                يرجى اختيار تاريخ الغياب
                            </div>
                        </div>

                        <!-- سبب الغياب -->
                        <div class="mb-3">
                            <label for="reason" class="form-label">سبب الغياب</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                            <div class="invalid-feedback">
                                يرجى كتابة سبب الغياب
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">إنشاء الطلب</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editAbsenceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editAbsenceForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Absence Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_absence_date" class="form-label">Absence Date</label>
                            <input type="date"
                                class="form-control"
                                id="edit_absence_date"
                                name="absence_date"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_reason" class="form-label">Reason</label>
                            <textarea class="form-control"
                                id="edit_reason"
                                name="reason"
                                required
                                maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Request</button>
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
                    <div class="modal-header">
                        <h5 class="modal-title" id="responseTitle">الرد على الطلب</h5>
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

                        <div class="mb-3" id="response_reason_container" style="display: none;">
                            <label class="form-label">سبب الرفض</label>
                            <textarea class="form-control"
                                id="response_reason"
                                name="rejection_reason"
                                maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
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
                    @method('PATCH')
                    <input type="hidden" name="response_type" id="modify_response_type">

                    <div class="modal-header">
                        <h5 class="modal-title">تعديل الرد</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="modify_status" class="form-label">الحالة</label>
                            <select class="form-select" id="modify_status" name="status" required>
                                <option value="approved">موافق</option>
                                <option value="rejected">مرفوض</option>
                            </select>
                        </div>

                        <div class="mb-3" id="modify_reason_container" style="display: none;">
                            <label for="modify_reason" class="form-label">سبب الرفض</label>
                            <textarea class="form-control"
                                id="modify_reason"
                                name="rejection_reason"
                                maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
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
                                <h6 class="text-muted mb-2">أيام الغياب</h6>
                                <h4 class="mb-0">{{ $statistics['personal']['total_days'] }}</h4>
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
                        @if($statistics['personal']['most_common_reason'])
                        <div class="col-12">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">السبب الأكثر تكراراً</h6>
                                <p class="mb-0">{{ $statistics['personal']['most_common_reason']['reason'] }}
                                    ({{ $statistics['personal']['most_common_reason']['count'] }} مرات)</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات الفريق -->
        @if(
        (
        Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager']) ||
        Auth::user()->hasRole('hr')
        ) &&
        (
        Auth::user()->ownedTeams()
        ->withCount('users')
        ->having('users_count', '>', 1)
        ->exists() ||
        Auth::user()->teams()
        ->wherePivot('role', 'admin')
        ->withCount('users')
        ->having('users_count', '>', 1)
        ->exists()
        ) &&
        isset($statistics['team'])
        )
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
                                <h6 class="text-muted mb-2">إجمالي الطلبات</h6>
                                <h4 class="mb-0">{{ $statistics['team']['total_requests'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">أيام الغياب</h6>
                                <h4 class="mb-0">{{ $statistics['team']['total_days'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-success">
                                <h6 class="mb-2">تمت الموافقة</h6>
                                <h4 class="mb-0">{{ $statistics['team']['approved_requests'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-danger">
                                <h6 class="mb-2">مرفوضة</h6>
                                <h4 class="mb-0">{{ $statistics['team']['rejected_requests'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-warning">
                                <h6 class="mb-2">معلقة</h6>
                                <h4 class="mb-0">{{ $statistics['team']['pending_requests'] }}</h4>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">تجاوزوا الحد المسموح</h6>
                                <h4 class="mb-0 text-danger">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#exceededLimitModal" class="text-danger text-decoration-none">
                                        {{ $statistics['team']['employees_exceeded_limit'] }} موظفين
                                    </a>
                                </h4>
                            </div>
                        </div>
                        @if($statistics['team']['most_absent_employee'])
                        <div class="col-md-12">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">الموظف الأكثر غياباً</h6>
                                <h5 class="mb-1">{{ $statistics['team']['most_absent_employee']['name'] }}</h5>
                                <small class="text-muted">{{ $statistics['team']['most_absent_employee']['count'] }} أيام</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- إحصائيات HR -->
        @if(Auth::user()->hasRole('hr'))
        <div class="col-md-12 mt-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> إحصائيات الشركة</h5>
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
                                <h6 class="text-muted mb-2">أيام الغياب</h6>
                                <h4 class="mb-0">{{ $statistics['hr']['total_days'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">تجاوزوا الحد المسموح</h6>
                                <h4 class="mb-0 text-danger">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#hrExceededLimitModal" class="text-danger text-decoration-none">
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
                        @if($statistics['hr']['most_absent_employee'])
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">الموظف الأكثر غياباً</h6>
                                <h5 class="mb-1">{{ $statistics['hr']['most_absent_employee']['name'] }}</h5>
                                <small class="text-muted">{{ $statistics['hr']['most_absent_employee']['count'] }} أيام</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

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
                                        <th>الحد المسموح</th>
                                        <th>أيام الغياب</th>
                                        <th>تجاوز الحد بـ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($statistics['team']['exceeded_employees'] ?? [] as $index => $employee)
                                    @php
                                        $maxDays = $employee->date_of_birth && \Carbon\Carbon::parse($employee->date_of_birth)->age >= 50 ? 45 : 21;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $employee->name }}</td>
                                        <td>{{ $maxDays }} يوم</td>
                                        <td>{{ $employee->total_days }} يوم</td>
                                        <td class="text-danger">{{ $employee->total_days - $maxDays }} يوم</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- جدول طلبات HR -->
        @if(Auth::user()->hasRole('hr'))


        <!-- Modal للموظفين المتجاوزين (HR) -->
        <div class="modal fade" id="hrExceededLimitModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">الموظفين المتجاوزين للحد المسموح (الشركة)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>اسم الموظف</th>
                                        <th>أيام الغياب</th>
                                        <th>تجاوز الحد بـ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($statistics['hr']['exceeded_employees'] ?? [] as $index => $employee)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $employee->name }}</td>
                                        <td>{{ $employee->total_days }} يوم</td>
                                        <td class="text-danger">{{ $employee->total_days - 21 }} يوم</td>
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
    </div>
    @endif

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animation for new rows
        gsap.from(".request-row", {
            duration: 0.5,
            opacity: 0,
            y: 20,
            stagger: 0.1
        });

        // Edit request handling
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                try {
                    const request = JSON.parse(this.dataset.request);
                    const form = document.getElementById('editAbsenceForm');
                    form.action = `/absence-requests/${request.id}`;

                    // تنسيق التاريخ بدون أصفار
                    const date = new Date(request.absence_date);
                    const formattedDate = date.toISOString().split('T')[0];
                    document.getElementById('edit_absence_date').value = formattedDate;

                    document.getElementById('edit_reason').value = request.reason;
                } catch (error) {
                    console.error('Error:', error);
                }
            });
        });

        // Response handling
        document.querySelectorAll('.respond-btn').forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.dataset.requestId;
                const responseType = this.dataset.responseType;
                const form = document.getElementById('respondForm');

                // تحديث عنوان المودال
                document.getElementById('responseTitle').textContent =
                    responseType === 'manager' ? 'رد المدير' : 'رد HR';

                // تحديث نوع الرد
                document.getElementById('response_type').value = responseType;

                // تحديث مسار الفورم
                form.action = `/absence-requests/${requestId}/status`;
            });
        });

        // Show/hide rejection reason field
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const rejectionContainer = document.getElementById('response_reason_container');
                const rejectionTextarea = document.getElementById('response_reason');

                if (this.value === 'rejected') {
                    rejectionContainer.style.display = 'block';
                    rejectionTextarea.required = true;
                } else {
                    rejectionContainer.style.display = 'none';
                    rejectionTextarea.required = false;
                }
            });
        });

        // Form validation
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    toastr.error('Please fill in all required fields.');
                }
                form.classList.add('was-validated');
            });
        });
    });

    document.querySelectorAll('.modify-response-btn').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            const responseType = this.dataset.responseType;
            const form = document.getElementById('modifyResponseForm');

            form.action = `/absence-requests/${requestId}/modify`;

            // تحديث نوع الرد في النموذج
            document.getElementById('modify_response_type').value = responseType;

            // تحديث الحالة وسبب الرفض
            const requestStatus = this.dataset.status;
            const requestReason = this.dataset.reason;

            document.getElementById('modify_status').value = requestStatus;
            document.getElementById('modify_reason').value = requestReason || '';

            // عرض/إخفاء حقل سبب الرفض
            const reasonContainer = document.getElementById('modify_reason_container');
            if (requestStatus === 'rejected') {
                reasonContainer.style.display = 'block';
                document.getElementById('modify_reason').required = true;
            } else {
                reasonContainer.style.display = 'none';
                document.getElementById('modify_reason').required = false;
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {

        document.getElementById('response_status').addEventListener('change', function() {
            const rejectionContainer = document.getElementById('response_reason_container');
            const rejectionTextarea = document.getElementById('response_reason');

            if (this.value === 'rejected') {
                rejectionContainer.style.display = 'block';
                rejectionTextarea.required = true;
            } else {
                rejectionContainer.style.display = 'none';
                rejectionTextarea.required = false;
            }
        });


        document.getElementById('modify_status').addEventListener('change', function() {
            const rejectionContainer = document.getElementById('modify_reason_container');
            const rejectionTextarea = document.getElementById('modify_reason');

            if (this.value === 'rejected') {
                rejectionContainer.style.display = 'block';
                rejectionTextarea.required = true;
            } else {
                rejectionContainer.style.display = 'none';
                rejectionTextarea.required = false;
            }
        });


        if (document.getElementById('response_status').value === 'rejected') {
            document.getElementById('response_reason_container').style.display = 'block';
        }

        if (document.getElementById('modify_status').value === 'rejected') {
            document.getElementById('modify_reason_container').style.display = 'block';
        }
    });
</script>

@endpush

@endsection
