@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/audit-log.css') }}">
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card audit-filter-card">
                <div class="card-header pb-0">
                    <h6 class="text-lg fw-bold mb-0">
                        <i class="fas fa-filter me-2"></i>تصفية النتائج
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('audit-log.index') }}" class="row g-4">
                        <!-- Date Filter -->
                        <div class="col-md-3">
                            <label class="filter-label">تاريخ محدد</label>
                            <div class="date-filter">
                                <input type="date" name="date" class="form-control shadow-sm" value="{{ request('date') }}">
                            </div>
                        </div>

                        <!-- Month and Year Filter -->
                        <div class="col-md-2">
                            <label class="filter-label">الشهر</label>
                            <select name="month" class="form-select shadow-sm">
                                <option value="">اختر الشهر</option>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="filter-label">السنة</label>
                            <select name="year" class="form-select shadow-sm">
                                <option value="">اختر السنة</option>
                                @for($i = $currentYear - 2; $i <= $currentYear; $i++)
                                    <option value="{{ $i }}" {{ request('year') == $i ? 'selected' : '' }}>
                                        {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <!-- Employee Filter -->
                        <div class="col-md-3">
                            <label class="filter-label">الموظف</label>
                            <select name="user_id" class="form-select shadow-sm">
                                <option value="">اختر الموظف</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Action Type Filter -->
                        <div class="col-md-2">
                            <label class="filter-label">نوع الإجراء</label>
                            <select name="action" class="form-select shadow-sm">
                                <option value="">الكل</option>
                                <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>إنشاء</option>
                                <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>تحديث</option>
                                <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>حذف</option>
                            </select>
                        </div>

                        <!-- Request Type Filter -->
                        <div class="col-md-3">
                            <label class="filter-label">نوع الطلب</label>
                            <select name="request_type" class="form-select shadow-sm">
                                <option value="">الكل</option>
                                <option value="{{ App\Models\AbsenceRequest::class }}" {{ request('request_type') == App\Models\AbsenceRequest::class ? 'selected' : '' }}>طلب غياب</option>
                                <option value="{{ App\Models\PermissionRequest::class }}" {{ request('request_type') == App\Models\PermissionRequest::class ? 'selected' : '' }}>طلب إذن</option>
                                <option value="{{ App\Models\OverTimeRequests::class }}" {{ request('request_type') == App\Models\OverTimeRequests::class ? 'selected' : '' }}>طلب وقت إضافي</option>
                            </select>
                        </div>

                        <!-- Submit and Reset Buttons -->
                        <div class="col-md-3">
                            <label class="filter-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-filter">
                                    <i class="fas fa-search me-2"></i>تطبيق الفلتر
                                </button>
                                <a href="{{ route('audit-log.index') }}" class="btn btn-secondary btn-filter">
                                    <i class="fas fa-redo me-2"></i>إعادة تعيين
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card audit-table-card">
                <div class="card-header pb-0">
                    <h6 class="text-lg fw-bold mb-0">
                        <i class="fas fa-history me-2"></i>سجل التغييرات
                    </h6>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">التاريخ والوقت</th>
                                    <th class="text-center">صاحب الطلب</th>
                                    <th class="text-center">من قام بالإجراء</th>
                                    <th class="text-center">نوع الطلب</th>
                                    <th class="text-center">الإجراء</th>
                                    <th class="text-center">وصف الإجراء</th>
                                    <th class="text-center">عنوان IP</th>
                                    <th class="text-center">التفاصيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($audits as $audit)
                                <tr>
                                    <td class="text-center">
                                        <p class="text-xs font-weight-bold mb-0">{{ $audit['created_at'] }}</p>
                                    </td>
                                    <td class="text-center">
                                        <p class="text-xs font-weight-bold mb-0">{{ $audit['request_owner'] }}</p>
                                    </td>
                                    <td class="text-center">
                                        <p class="text-xs font-weight-bold mb-0">{{ $audit['user'] }}</p>
                                    </td>
                                    <td class="text-center">
                                        <p class="text-xs font-weight-bold mb-0">{{ $audit['model_type'] }}</p>
                                    </td>
                                    <td class="text-center">
                                        <span class="audit-badge status-badge-{{ strtolower($audit['action']) }}">
                                            {{ $audit['action'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <p class="text-xs font-weight-bold mb-0">{{ $audit['action_description'] }}</p>
                                    </td>
                                    <td class="text-center">
                                        <p class="text-xs font-weight-bold mb-0">{{ $audit['ip_address'] ?? 'غير متوفر' }}</p>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-info btn-details" data-bs-toggle="modal" data-bs-target="#changesModal{{ $audit['id'] }}">
                                            <i class="fas fa-eye me-1"></i>عرض التفاصيل
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
    </div>
</div>

<!-- Modals for Changes -->
@foreach($audits as $audit)
<div class="modal fade" id="changesModal{{ $audit['id'] }}" tabindex="-1" aria-labelledby="changesModalLabel{{ $audit['id'] }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="changesModalLabel{{ $audit['id'] }}">
                    <i class="fas fa-info-circle me-2"></i>تفاصيل التغييرات
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4 p-3 bg-light rounded">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>صاحب الطلب:</strong> {{ $audit['request_owner'] }}</p>
                            <p class="mb-2"><strong>قام بالإجراء:</strong> {{ $audit['user'] }}</p>
                            <p class="mb-2"><strong>نوع الطلب:</strong> {{ $audit['model_type'] }}</p>
                            <p class="mb-2"><strong>الإجراء:</strong> {{ $audit['action_description'] }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>التاريخ:</strong> {{ $audit['created_at'] }}</p>
                            <p class="mb-2"><strong>عنوان IP:</strong> {{ $audit['ip_address'] ?? 'غير متوفر' }}</p>
                            <p class="mb-2"><strong>المتصفح:</strong> {{ $audit['user_agent'] ?? 'غير متوفر' }}</p>
                            <p class="mb-2"><strong>الرابط:</strong> {{ $audit['url'] ?? 'غير متوفر' }}</p>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table change-table">
                        <thead>
                            <tr>
                                <th class="text-center">الحقل</th>
                                <th class="text-center">القيمة القديمة</th>
                                <th class="text-center">القيمة الجديدة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($audit['changes'] as $change)
                            <tr>
                                <td class="text-center">{{ $change['field'] }}</td>
                                <td class="text-center">{{ $change['old'] ?? '-' }}</td>
                                <td class="text-center">{{ $change['new'] ?? '-' }}</td>
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
@endforeach
@endsection

@push('scripts')
<script>
    // Add Font Awesome if not already included
    if (!document.querySelector('link[href*="font-awesome"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
        document.head.appendChild(link);
    }
</script>
@endpush
