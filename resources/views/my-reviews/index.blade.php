@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/my-reviews.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container fade-in">
    <div class="my-reviews-container">
        <div class="my-reviews-header">
            <h2>تقييماتي</h2>
        </div>

        <div class="my-reviews-card">
            <div class="my-reviews-card-header">
                <span>قائمة التقييمات الخاصة بي</span>
            </div>

            <div class="my-reviews-card-body">
                <!-- Filter Form -->
                <form action="{{ route('my-reviews.index') }}" method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="my-reviews-form-group">
                                <label class="my-reviews-form-label">الشهر</label>
                                <select name="month" class="my-reviews-form-control">
                                    <option value="">اختر الشهر</option>
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                        </option>
                                        @endfor
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="my-reviews-form-group">
                                <label class="my-reviews-form-label">السنة</label>
                                <select name="year" class="my-reviews-form-control">
                                    <option value="">اختر السنة</option>
                                    @for($i = $currentYear - 2; $i <= $currentYear; $i++)
                                        <option value="{{ $i }}" {{ request('year') == $i ? 'selected' : '' }}>
                                        {{ $i }}
                                        </option>
                                        @endfor
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <div class="d-flex gap-2 w-100">
                                <button type="submit" class="my-reviews-btn my-reviews-btn-primary">
                                    <i class="fas fa-filter me-1"></i> تصفية
                                </button>

                                @if(request('month') || request('year'))
                                <a href="{{ route('my-reviews.index') }}" class="my-reviews-btn my-reviews-btn-secondary">
                                    <i class="fas fa-undo me-1"></i> إعادة ضبط
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>

                <table class="my-reviews-table">
                    <thead>
                        <tr>
                            <th>نوع التقييم</th>
                            <th>شهر التقييم</th>
                            <th>المجموع</th>
                            <th>المجموع بعد الخصم</th>
                            <th>النسبة المئوية</th>
                            <th>إجمالي المرتب</th>
                            <th>المراجع</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($allReviews as $review)
                        <tr>
                            <td>
                                @if($review['type'] == 'technical')
                                <span class="badge-technical">الفريق التقني</span>
                                @elseif($review['type'] == 'marketing')
                                <span class="badge-marketing">التسويق</span>
                                @elseif($review['type'] == 'customer_service')
                                <span class="badge-customer_service">خدمة العملاء</span>
                                @elseif($review['type'] == 'coordination')
                                <span class="badge-coordination">التنسيق</span>
                                @endif
                            </td>
                            <td>{{ $review['review_month'] }}</td>
                            <td>{{ $review['total_score'] }}</td>
                            <td>{{ $review['total_after_deductions'] }}</td>
                            <td>
                                @if($review['percentage'])
                                @php
                                $percentClass = $review['percentage'] >= 80 ? 'percentage-high' :
                                ($review['percentage'] >= 60 ? 'percentage-medium' : 'percentage-low');
                                @endphp
                                <span class="percentage-badge {{ $percentClass }}">
                                    {{ number_format($review['percentage'], 2) }}%
                                </span>
                                @else
                                -
                                @endif
                            </td>
                            <td>{{ number_format($review['total_salary'], 2) }}</td>
                            <td>{{ $review['reviewer'] }}</td>
                            <td>
                                <div class="my-reviews-actions">
                                    @if($review['type'] == 'technical')
                                    <a href="{{ route('technical-team-reviews.show', $review['id']) }}" class="my-reviews-btn my-reviews-btn-primary my-reviews-btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @elseif($review['type'] == 'marketing')
                                    <a href="{{ route('marketing-reviews.show', $review['id']) }}" class="my-reviews-btn my-reviews-btn-primary my-reviews-btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @elseif($review['type'] == 'customer_service')
                                    <a href="{{ route('customer-service-reviews.show', $review['id']) }}" class="my-reviews-btn my-reviews-btn-primary my-reviews-btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @elseif($review['type'] == 'coordination')
                                    <a href="{{ route('coordination-reviews.show', $review['id']) }}" class="my-reviews-btn my-reviews-btn-primary my-reviews-btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @endif

                                    @if(auth()->user()->hasRole('hr'))
                                    <a href="{{ route('audit-log.index', ['request_type' => $review['model_type'], 'model_id' => $review['id']]) }}" class="my-reviews-btn my-reviews-btn-info my-reviews-btn-sm" title="سجل التغييرات">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">لا توجد تقييمات متاحة.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection