@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/coordination-reviews.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/coordination-reviews.js') }}"></script>
@endpush

@section('content')
<div class="container fade-in">
    <div class="coordination-reviews-container">
        <div class="coordination-reviews-header">
            <h2>عرض تقييم قسم التنسيق والمراجعة</h2>
        </div>

        <div class="coordination-reviews-card">
            <div class="coordination-reviews-card-header">
                <span>تفاصيل التقييم</span>
                <div>
                    <a href="{{ route('coordination-reviews.index') }}" class="coordination-reviews-btn coordination-reviews-btn-secondary">
                        <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
                    </a>
                    @if(auth()->id() != $review->user_id)
                    <a href="{{ route('coordination-reviews.edit', $review) }}" class="coordination-reviews-btn coordination-reviews-btn-primary ms-2">
                        <i class="fas fa-edit me-1"></i> تعديل
                    </a>
                    @endif
                </div>
            </div>

            <div class="coordination-reviews-card-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="summary-item">
                            <span class="summary-label">الموظف</span>
                            <span class="summary-value">{{ $review->user->name ?? 'غير محدد' }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-item">
                            <span class="summary-label">شهر التقييم</span>
                            <span class="summary-value">{{ $review->review_month }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-item">
                            <span class="summary-label">المراجع</span>
                            <span class="summary-value">{{ $review->reviewer->name ?? 'غير محدد' }}</span>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="summary-item">
                            <span class="summary-label">المجموع الكلي</span>
                            <span class="summary-value summary-highlight">{{ $review->total_score }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-item">
                            <span class="summary-label">المجموع بعد الخصم</span>
                            <span class="summary-value summary-highlight">{{ $review->total_after_deductions }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-item">
                            <span class="summary-label">إجمالي المرتب</span>
                            <span class="summary-value summary-highlight">{{ number_format($review->total_salary, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Positive Evaluation Items -->
                <div class="form-section mb-4">
                    <h3 class="form-section-title">بنود التقييم الإيجابية</h3>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">تقفيل الدراسة وتوقيع أو إرسال الدراسة بجميع مرفقاتها</span>
                                <span class="criteria-score">{{ $review->documentation_delivery_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">تسليم حد أدنى 3 دراسات يوميا أو دراستين مع تعديلات</span>
                                <span class="criteria-score">{{ $review->daily_delivery_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">أن لا يتخطى وقت تنسيق وتقفيل الدراسة 2:30</span>
                                <span class="criteria-score">{{ $review->scheduling_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">تسليم الدراسة بدون أخطاء</span>
                                <span class="criteria-score">{{ $review->error_free_delivery_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">متابعة جدول الأسبوع مع الفريق التنفيذي</span>
                                <span class="criteria-score">{{ $review->schedule_follow_up_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">التأكد من عدم وجود أي كلمات من مسودات سابقة</span>
                                <span class="criteria-score">{{ $review->no_previous_drafts_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">التأكد من عدم وجود أي كلمات من أخطاء بالتصاميم</span>
                                <span class="criteria-score">{{ $review->no_design_errors_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">متابعة (التعديلات - التهائيات)</span>
                                <span class="criteria-score">{{ $review->follow_up_modifications_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">عمل عروض تقديمية بوربوينت للمشاريع</span>
                                <span class="criteria-score">{{ $review->presentations_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">متابعة التسليمات اليومية مع خدمة العملاء</span>
                                <span class="criteria-score">{{ $review->customer_service_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">عمل أرشفة و متابعة لجميع المشاريع</span>
                                <span class="criteria-score">{{ $review->project_monitoring_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">ملف تثبيت بالملاحظات على الدراسات</span>
                                <span class="criteria-score">{{ $review->feedback_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">تقييم التيم ليدر</span>
                                <span class="criteria-score">{{ $review->team_leader_evaluation_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">تقييم HR</span>
                                <span class="criteria-score">{{ $review->hr_evaluation_score }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bonus Items -->
                <div class="form-section mb-4">
                    <h3 class="form-section-title">البونص</h3>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">البونص</span>
                                <span class="criteria-score">{{ $review->bonus_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">تخطى العدد المحدد لتسليم الدراسات</span>
                                <span class="criteria-score">{{ $review->required_deliveries_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">الحصول على 10 مواقع خاصة</span>
                                <span class="criteria-score">{{ $review->seo_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">عمل داتا بورد بالمشاريع للأعوام السابقة</span>
                                <span class="criteria-score">{{ $review->portfolio_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">تقديم عرض او مقترح جديد</span>
                                <span class="criteria-score">{{ $review->proposal_score }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">تقديم فكرة تطويرية للشركة</span>
                                <span class="criteria-score">{{ $review->company_idea_score }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Penalty Items -->
                <div class="form-section mb-4">
                    <h3 class="form-section-title">السوالب</h3>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">وجود كلمات من مسودة قديمة (-10 لكل دراسة)</span>
                                <span class="criteria-score">{{ $review->old_draft_penalty }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">وجود أخطاء في التصاميم (-10 لكل دراسة)</span>
                                <span class="criteria-score">{{ $review->design_error_penalty }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">عدم تسليم المشاريع في الوقت المحدد (-10 لكل دراسة)</span>
                                <span class="criteria-score">{{ $review->late_delivery_penalty }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="criteria-item">
                                <span class="criteria-name">عدم تسليم المشروع بجميع المرفقات (-10 لكل دراسة)</span>
                                <span class="criteria-score">{{ $review->missing_attachments_penalty }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                @if($review->notes)
                <div class="form-section mb-4">
                    <h3 class="form-section-title">ملاحظات</h3>
                    <div class="p-3">
                        {{ $review->notes }}
                    </div>
                </div>
                @endif

                <div class="form-actions">
                    <form action="{{ route('coordination-reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا التقييم؟');" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="coordination-reviews-btn coordination-reviews-btn-danger">
                            <i class="fas fa-trash-alt me-1"></i> حذف التقييم
                        </button>
                    </form>
                    <a href="{{ route('coordination-reviews.edit', $review) }}" class="coordination-reviews-btn coordination-reviews-btn-primary">
                        <i class="fas fa-edit me-1"></i> تعديل التقييم
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection