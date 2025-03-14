@extends('admin.notifications.layouts.notification-layout')

@section('page-title', 'إدارة الإشعارات')

@section('page-actions')
<a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i>
    إنشاء إشعار جديد
</a>
@endsection

@section('notification-content')
<style>
/* Stats Cards */
.stats-card {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.stats-card h3 {
    font-size: 2rem;
    margin-bottom: 10px;
    color: #333;
}

.stats-card p {
    color: #666;
    margin: 0;
}

.stats-card.success h3 {
    color: #28a745;
}

.stats-card.warning h3 {
    color: #ffc107;
}

/* Filter Section */
.filter-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
}

/* Notifications List */
.notifications-list {
    margin-top: 30px;
}

.notification-card {
    background: #fff;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #eee;
}

.notification-card.administrative {
    border-left: 4px solid #dc3545;
}

.notification-card .card-body {
    padding: 20px;
}

.notification-card .card-title {
    color: #333;
    margin-bottom: 15px;
}

.notification-card .card-text {
    color: #666;
}

.notification-card .badges {
    margin-top: 15px;
}

.notification-card .badge {
    margin-right: 10px;
    padding: 8px 12px;
}

/* Progress Bar */
.read-stats .progress {
    height: 10px;
    border-radius: 5px;
    background: #e9ecef;
}

.read-stats .progress-bar {
    background: #0d6efd;
    border-radius: 5px;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.action-buttons .btn {
    padding: 8px 12px;
}

/* Card Footer */
.notification-card .card-footer {
    background: #f8f9fa;
    padding: 15px 20px;
    border-top: 1px solid #eee;
}

/* Responsive */
@media (max-width: 768px) {
    .col-lg-3, .col-lg-6 {
        margin-bottom: 15px;
    }

    .action-buttons {
        justify-content: center;
    }
}
</style>

<!-- إحصائيات عامة -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stats-card">
            <h3>{{ $totalNotifications }}</h3>
            <p>إجمالي الإشعارات</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card success">
            <h3>{{ $readNotifications }}</h3>
            <p>تمت القراءة</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card warning">
            <h3>{{ $unreadNotifications }}</h3>
            <p>في انتظار القراءة</p>
        </div>
    </div>
</div>

<!-- فلتر البحث -->
<div class="filter-section">
    <form method="GET" action="{{ route('admin.notifications.index') }}" class="row g-3">
        <div class="col-md-4">
            <div class="form-floating">
                <select class="form-select" id="read_status" name="read_status">
                    <option value="">الكل</option>
                    <option value="read" {{ request('read_status') == 'read' ? 'selected' : '' }}>تمت القراءة</option>
                    <option value="unread" {{ request('read_status') == 'unread' ? 'selected' : '' }}>لم تتم القراءة</option>
                </select>
                <label for="read_status">حالة القراءة</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-floating">
                <select class="form-select" id="type" name="type">
                    <option value="">الكل</option>
                    <option value="administrative_decision" {{ request('type') == 'administrative_decision' ? 'selected' : '' }}>
                        قرار إداري
                    </option>
                    <option value="admin_broadcast" {{ request('type') == 'admin_broadcast' ? 'selected' : '' }}>
                        إشعار عادي
                    </option>
                </select>
                <label for="type">نوع الإشعار</label>
            </div>
        </div>
        <div class="col-md-4 d-flex align-items-center">
            <button type="submit" class="btn btn-primary me-2">
                <i class="fas fa-filter"></i>
                تصفية
            </button>
            <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">
                <i class="fas fa-redo"></i>
                إعادة تعيين
            </a>
        </div>
    </form>
</div>

<!-- قائمة الإشعارات -->
<div class="notifications-list">
    @foreach($notifications as $notification)
    <div class="notification-card {{ $notification->type === 'administrative_decision' ? 'administrative' : '' }}">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h5 class="card-title">
                        <i class="fas {{ $notification->type === 'administrative_decision' ? 'fa-gavel' : 'fa-bell' }} me-2"></i>
                        {{ $notification->data['title'] ?? '' }}
                    </h5>
                    <p class="card-text">{{ Str::limit($notification->data['message'] ?? '', 100) }}</p>
                    <div class="badges">
                        @if($notification->type === 'administrative_decision')
                        <span class="badge bg-danger">
                            <i class="fas fa-gavel me-1"></i>
                            قرار إداري
                        </span>
                        @else
                        <span class="badge bg-info">
                            <i class="fas fa-bell me-1"></i>
                            إشعار عادي
                        </span>
                        @endif
                        @if($notification->data['requires_acknowledgment'] ?? false)
                        <span class="badge bg-warning">
                            <i class="fas fa-check-double me-1"></i>
                            يتطلب تأكيد
                        </span>
                        @endif
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="read-stats">
                        <div class="progress">
                            @php
                            $percentage = $notification->total_recipients > 0
                            ? ($notification->read_count / $notification->total_recipients) * 100
                            : 0;
                            @endphp
                            <div class="progress-bar" style="width: {{ $percentage }}%"></div>
                        </div>
                        <div class="text-center mt-2">
                            <strong class="text-primary">{{ $notification->read_count }}</strong>
                            <span class="text-muted">من</span>
                            <strong class="text-primary">{{ $notification->total_recipients }}</strong>
                            <small class="d-block text-muted">قرأوا الإشعار</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="action-buttons">
                        <a href="{{ route('admin.notifications.show', $notification) }}"
                            class="btn btn-info"
                            title="عرض التفاصيل">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.notifications.edit', $notification) }}"
                            class="btn btn-primary"
                            title="تعديل">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.notifications.destroy', $notification) }}"
                            method="POST"
                            class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="btn btn-danger"
                                title="حذف"
                                onclick="return confirm('هل أنت متأكد من الحذف؟')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <small class="text-muted">
                <i class="far fa-clock"></i>
                {{ $notification->created_at->format('Y-m-d H:i') }}
            </small>
        </div>
    </div>
    @endforeach
</div>
@endsection
<div class="d-flex justify-content-center mt-4">
    {{ $notifications->links() }}
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/notifications.css') }}">
@endpush

@push('page-scripts')
<script>
    // أي سكريبتات إضافية خاصة بالصفحة
</script>
@endpush
