@extends('layouts.app')

@section('content')

<head>
  <link rel="stylesheet" href="{{asset('css/dashboard-hr.css')}}">
</head>

<div class="dashboard-container">
  <div class="dashboard-header">
    <div class="dashboard-header-content">
      <h1 class="text-3xl font-bold text-white mb-2">مرحباً، {{ Auth::user()->name }}</h1>
      <p class="text-white/80">
        @if(Auth::user()->hasRole('hr'))
        إدارة حضور وأداء الفريق
        @else
        متابعة الحضور والانصراف
        @endif
      </p>
    </div>
  </div>

  @if(Auth::user()->hasRole('hr'))
  <!-- إحصائيات اليوم للمدير -->
  <div class="action-cards mt-5">
    <div class="action-card">
      <div class="action-icon bg-primary">
        <i class="fas fa-chart-line text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">إحصائيات اليوم</h3>
      <p class="text-gray-600 mb-4">عرض إحصائيات الحضور اليومية</p>
      <div class="row g-2">
        <div class="col-6">
          <div class="stat-card">
            <div class="stat-icon bg-primary">
              <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
              <h3>{{ $todayStats['totalEmployees'] }}</h3>
              <p>إجمالي الموظفين</p>
            </div>
          </div>
        </div>
        <div class="col-6">
          <div class="stat-card">
            <div class="stat-icon bg-success">
              <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-details">
              <h3>{{ $todayStats['presentToday'] }}</h3>
              <p>الحضور اليوم</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- طلبات اليوم -->
  <div class="action-cards mt-5">
    <div class="action-card">
      <div class="action-icon" style="background-color: #FF9500;">
        <i class="fas fa-clipboard-list text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">طلبات اليوم</h3>
      <p class="text-gray-600 mb-4">عرض جميع الطلبات المقدمة اليوم</p>
      <div class="row g-2">
        <div class="col-6">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-calendar-times"></i>
            </div>
            <div class="stat-value">{{ $todayRequests['absenceRequests'] }}</div>
            <div class="stat-label">طلبات الغياب</div>
          </div>
        </div>
        <div class="col-6">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-door-open"></i>
            </div>
            <div class="stat-value">{{ $todayRequests['permissionRequests'] }}</div>
            <div class="stat-label">طلبات الاستئذان</div>
          </div>
        </div>
        <div class="col-6">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-business-time"></i>
            </div>
            <div class="stat-value">{{ $todayRequests['overtimeRequests'] }}</div>
            <div class="stat-label">طلبات العمل الإضافي</div>
          </div>
        </div>
        <div class="col-6">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-value">{{ $todayRequests['violations'] }}</div>
            <div class="stat-label">المخالفات</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- إحصائيات الشهر للمدير -->
  <div class="action-cards mt-5">
    <div class="action-card">
      <div class="action-icon" style="background-color: #4CAF50;">
        <i class="fas fa-calendar-check text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">إحصائياتي الشهرية</h3>
      <p class="text-gray-600 mb-4">ملخص أداء الحضور والانصراف الشهري</p>
      <div class="row g-2">
        <div class="col-6">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-check text-white text-xl"></i>
            </div>
            <div class="stat-value">{{ $attendanceStats['present_days'] }}</div>
            <div class="stat-label">أيام الحضور</div>
            <div class="stat-sublabel">من أصل {{ $attendanceStats['total_work_days'] }} يوم</div>
          </div>
        </div>
        <div class="col-6">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-times text-white text-xl"></i>
            </div>
            <div class="stat-value">{{ $attendanceStats['absent_days'] }}</div>
            <div class="stat-label">أيام الغياب</div>
          </div>
        </div>
        <div class="col-6">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-clock text-white text-xl"></i>
            </div>
            <div class="stat-value">{{ $attendanceStats['late_days'] }}</div>
            <div class="stat-label">مرات التأخير</div>
            <div class="stat-sublabel">{{ $attendanceStats['total_delay_minutes'] }} دقيقة</div>
          </div>
        </div>
        <div class="col-6">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-exclamation-triangle text-white text-xl"></i>
            </div>
            <div class="stat-value">{{ $attendanceStats['violation_days'] }}</div>
            <div class="stat-label">المخالفات</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif

  <!-- الإجراءات السريعة -->
  <div class="action-cards mt-5">
    <div class="action-card">
      <div class="action-icon" style="background-color: #3F51B5;">
        <i class="fas fa-tasks text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">الإجراءات السريعة</h3>
      <p class="text-gray-600 mb-4">إجراءات سريعة للوصول إلى الوظائف المتكررة</p>
      <div class="row g-2">
        @if(Auth::user()->hasRole('hr'))
        <div class="col-6">
          <a href="{{ route('users.index') }}" class="btn-dashboard">
            <i class="fas fa-users"></i>
            إدارة الموظفين
          </a>
        </div>
        <div class="col-6">
          <a href="{{ route('salary-sheets.index') }}" class="btn-dashboard">
            <i class="fas fa-file-invoice-dollar"></i>
            كشوف المرتبات
          </a>
        </div>
        <div class="col-6">
          <a href="{{ route('work-shifts.index') }}" class="btn-dashboard">
            <i class="fas fa-clock"></i>
            إدارة الورديات
          </a>
        </div>
        <div class="col-6">
          <a href="{{ route('users.assign-work-shifts') }}" class="btn-dashboard">
            <i class="fas fa-user-clock"></i>
            تعيين الورديات للموظفين
          </a>
        </div>
        <div class="col-6">
          <a href="{{ route('admin.notifications.index') }}" class="btn-dashboard">
            <i class="fas fa-file-contract"></i>
            القرارات الإدارية
          </a>
        </div>
        @endif

        @if(Auth::user()->role === 'employee')
        <div class="col-6">
          <a href="{{ route('attendances.create') }}" class="btn-dashboard">
            <i class="fas fa-clock"></i>
            تسجيل الحضور
          </a>
        </div>
        <div class="col-6">
          <a href="{{ route('leaves.create') }}" class="btn-dashboard">
            <i class="fas fa-calendar-plus"></i>
            طلب إجازة
          </a>
        </div>
        @endif
      </div>
    </div>
  </div>

  <!-- الطلبات المعلقة -->
  <div class="action-cards mt-5">
    <div class="action-card">
      <div class="action-icon" style="background-color: #E91E63;">
        <i class="fas fa-bell text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">الطلبات المعلقة</h3>
      <p class="text-gray-600 mb-4">عرض الطلبات التي تنتظر الموافقة</p>
      <div class="row g-2">
        <div class="col-6">
          <a href="{{ route('overtime-requests.index') }}" class="btn-dashboard">
            <i class="fas fa-clock"></i>
            طلبات العمل الإضافي
          </a>
        </div>
        <div class="col-6">
          <a href="{{ route('absence-requests.index') }}" class="btn-dashboard">
            <i class="fas fa-user-clock"></i>
            طلبات الغياب
          </a>
        </div>
        <div class="col-6">
          <a href="{{ route('permission-requests.index') }}" class="btn-dashboard">
            <i class="fas fa-door-open"></i>
            طلبات الاستئذان
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="action-cards mt-5">
    <div class="action-card">
      <div class="action-icon">
        <i class="fas fa-file-alt text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">التقارير والمستندات</h3>
      <p class="text-gray-600 mb-4">الوصول وإدارة المستندات المهمة</p>
      <div class="space-y-2">
        <div class="row">
          <div class="col-6">
            <a href="{{ route('attendance.index') }}" class="btn-dashboard btn-block">سجلات الحضور</a>
          </div>
          <div class="col-6">
            <a href="{{ route('salary-sheets.index') }}" class="btn-dashboard btn-block">كشوف المرتبات</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- قسم إحصائيات الموظفين -->
  <div class="action-cards mt-5">
    <div class="action-card">
      <div class="action-icon bg-success">
        <i class="fas fa-chart-bar text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">إحصائيات الموظفين</h3>
      <p class="text-gray-600 mb-4">عرض تقارير وإحصائيات مفصلة عن أداء الموظفين</p>
      <div class="row g-2">
        <div class="col-6">
          <a href="{{ route('employee-statistics.index') }}" class="btn-dashboard">
            <i class="fas fa-chart-line"></i>
            عرض الإحصائيات الشاملة
          </a>
        </div>

        <div class="col-6">
          <a href="{{ route('employee-competition.index') }}" class="btn-dashboard">
            <i class="fas fa-trophy"></i>
            المسابقة
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- قسم أعياد الميلاد -->
  <div class="action-cards mt-5">
    <div class="action-card">
      <div class="action-icon" style="background-color: #FF69B4;">
        <i class="fas fa-birthday-cake text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">أعياد الميلاد</h3>
      <p class="text-gray-600 mb-4">عرض وإدارة أعياد ميلاد الموظفين</p>
      <div class="row g-2">
        <div class="col-6">
          <a href="{{ route('employee-birthdays.index') }}" class="btn-dashboard">
            <i class="fas fa-calendar-day"></i>
            عرض أعياد الميلاد
          </a>
        </div>

      </div>
    </div>
  </div>

  <!-- قسم الحالات الخاصة -->
  <div class="action-cards mt-5">
    <div class="action-card">
      <div class="action-icon" style="background-color: #9370DB;">
        <i class="fas fa-user-shield text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">الحالات الخاصة</h3>
      <p class="text-gray-600 mb-4">عرض وإدارة الحالات الخاصة للموظفين</p>
      <div class="row g-2">
        <div class="col-6">
          <a href="{{ route('special-cases.index') }}" class="btn-dashboard">
            <i class="fas fa-users-cog"></i>
            إدارة الحالات الخاصة
          </a>
        </div>
        <div class="col-6">
          <a href="{{ route('special-cases.create') }}" class="btn-dashboard">
            <i class="fas fa-plus-circle"></i>
            إضافة حالة خاصة
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- قسم سجلات التدقيق -->
  <div class="action-cards mt-5">
    <div class="action-card">
      <div class="action-icon" style="background-color: #2F4F4F;">
        <i class="fas fa-history text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">سجلات التدقيق</h3>
      <p class="text-gray-600 mb-4">عرض سجلات التدقيق والتغييرات في النظام</p>
      <div class="row g-2">
        <div class="col-6">
          <a href="{{ route('audit-log.index') }}" class="btn-dashboard">
            <i class="fas fa-clipboard-list"></i>
            سجلات النظام
          </a>
        </div>
        <div class="col-6">
          <div class="dropdown">
            <button class="btn-dashboard dropdown-toggle" type="button" id="auditsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-file-alt"></i>
              سجلات الطلبات
            </button>
            <ul class="dropdown-menu" aria-labelledby="auditsDropdown">
              <li><a class="dropdown-item" href="{{ route('absence-requests.audits', 0) }}">طلبات الغياب</a></li>
              <li><a class="dropdown-item" href="{{ route('permission-requests.audits', 0) }}">طلبات الاستئذان</a></li>
              <li><a class="dropdown-item" href="{{ route('overtime-requests.audits', 0) }}">طلبات العمل الإضافي</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('styles')
<style>
  .stat-sublabel {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
  }
</style>
@endpush
@endsection
