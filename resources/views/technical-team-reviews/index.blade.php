@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/technical-team-reviews.css') }}">
@endpush

@section('content')
<div class="container fade-in">
    <div class="technical-team-reviews-container">
        <div class="technical-team-reviews-header">
            <h2>تقييمات الفريق التقني</h2>
        </div>

        <div class="technical-team-reviews-card">
            <div class="technical-team-reviews-card-header">
                <span>قائمة التقييمات</span>
                <a href="{{ route('technical-team-reviews.create') }}" class="technical-team-reviews-btn technical-team-reviews-btn-success">
                    <i class="fas fa-plus-circle me-1"></i> إضافة تقييم جديد
                </a>
            </div>

            <div class="technical-team-reviews-card-body">
                <!-- Filter Form -->
                <form action="{{ route('technical-team-reviews.index') }}" method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="technical-team-reviews-form-group">
                                <label class="technical-team-reviews-form-label">الموظف</label>
                                <select name="user_id" class="technical-team-reviews-form-control">
                                    <option value="">جميع الموظفين</option>
                                    @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="technical-team-reviews-form-group">
                                <label class="technical-team-reviews-form-label">شهر التقييم</label>
                                <input type="month" name="review_month" value="{{ request('review_month') }}" class="technical-team-reviews-form-control">
                            </div>
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <div class="d-flex gap-2 w-100">
                                <button type="submit" class="technical-team-reviews-btn technical-team-reviews-btn-primary">
                                    <i class="fas fa-filter me-1"></i> تصفية
                                </button>

                                @if(request('user_id') || request('review_month'))
                                <a href="{{ route('technical-team-reviews.index') }}" class="technical-team-reviews-btn technical-team-reviews-btn-secondary">
                                    <i class="fas fa-undo me-1"></i> إعادة ضبط
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>

                @if (session('success'))
                <div class="alert alert-success mb-4" role="alert">
                    {{ session('success') }}
                </div>
                @endif

                <table class="technical-team-reviews-table">
                    <thead>
                        <tr>
                            <th>الموظف</th>
                            <th>شهر التقييم</th>
                            <th>المجموع</th>
                            <th>المجموع بعد الخصم</th>
                            <th>إجمالي المرتب</th>
                            <th>المراجع</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reviews as $review)
                        <tr>
                            <td>{{ $review->user->name ?? 'غير محدد' }}</td>
                            <td>{{ $review->review_month }}</td>
                            <td>{{ $review->total_score }}</td>
                            <td>{{ $review->total_after_deductions }}</td>
                            <td>{{ number_format($review->total_salary, 2) }}</td>
                            <td>{{ $review->reviewer->name ?? 'غير محدد' }}</td>
                            <td>
                                <div class="technical-team-reviews-actions">
                                    <a href="{{ route('technical-team-reviews.show', $review) }}" class="technical-team-reviews-btn technical-team-reviews-btn-primary technical-team-reviews-btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(auth()->id() != $review->user_id)
                                    <a href="{{ route('technical-team-reviews.edit', $review) }}" class="technical-team-reviews-btn technical-team-reviews-btn-success technical-team-reviews-btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('technical-team-reviews.destroy', $review) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا التقييم؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="technical-team-reviews-btn technical-team-reviews-btn-danger technical-team-reviews-btn-sm">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                    @endif
                                    @if(auth()->user()->hasRole('hr'))
                                    <a href="{{ route('audit-log.index', ['request_type' => App\Models\TechnicalTeamReview::class, 'model_id' => $review->id]) }}" class="technical-team-reviews-btn technical-team-reviews-btn-info technical-team-reviews-btn-sm" title="سجل التغييرات">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">لا توجد تقييمات متاحة.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $reviews->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection