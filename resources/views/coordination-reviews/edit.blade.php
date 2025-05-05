@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/coordination-reviews.css') }}">
@endpush



@section('content')
<div class="container fade-in">
    <div class="coordination-reviews-container">
        <div class="coordination-reviews-header">
            <h2>تعديل تقييم</h2>
        </div>

        <div class="coordination-reviews-card">
            <div class="coordination-reviews-card-header">
                <span>نموذج التقييم</span>
                <a href="{{ route('coordination-reviews.index') }}" class="coordination-reviews-btn coordination-reviews-btn-secondary">
                    <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
                </a>
            </div>

            <div class="coordination-reviews-card-body">
                <form action="{{ route('coordination-reviews.update', $review) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row mb-4">
                        <!-- User -->
                        <div class="col-md-6">
                            <div class="coordination-reviews-form-group">
                                <label class="coordination-reviews-form-label">الموظف <span class="text-danger">*</span></label>
                                <select id="user_id" name="user_id" required class="coordination-reviews-form-control">
                                    <option value="">اختر الموظف</option>
                                    @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id', $review->user_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('user_id')
                                <p class="text-danger mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Review Month -->
                        <div class="col-md-6">
                            <div class="coordination-reviews-form-group">
                                <label class="coordination-reviews-form-label">شهر التقييم <span class="text-danger">*</span></label>
                                <input type="month" name="review_month" id="review_month" value="{{ old('review_month', $review->review_month) }}" required class="coordination-reviews-form-control">
                                @error('review_month')
                                <p class="text-danger mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Positive Evaluation Items -->
                    <div class="form-section mb-4">
                        <h3 class="form-section-title">بنود التقييم الإيجابية</h3>

                        <div class="row">
                            <!-- Documentation Delivery -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">تقفيل الدراسة وتوقيع أو إرسال الدراسة بجميع مرفقاتها</label>
                                    <input type="number" name="documentation_delivery_score" value="{{ old('documentation_delivery_score', $review->documentation_delivery_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('documentation_delivery_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Daily Delivery -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">تسليم حد أدنى 3 دراسات يوميا أو دراستين مع تعديلات</label>
                                    <input type="number" name="daily_delivery_score" value="{{ old('daily_delivery_score', $review->daily_delivery_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('daily_delivery_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Scheduling -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">أن لا يتخطى وقت تنسيق وتقفيل الدراسة 2:30</label>
                                    <input type="number" name="scheduling_score" value="{{ old('scheduling_score', $review->scheduling_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('scheduling_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Error Free Delivery -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">تسليم الدراسة بدون أخطاء</label>
                                    <input type="number" name="error_free_delivery_score" value="{{ old('error_free_delivery_score', $review->error_free_delivery_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('error_free_delivery_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Schedule Follow Up -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">متابعة جدول الأسبوع مع الفريق التنفيذي</label>
                                    <input type="number" name="schedule_follow_up_score" value="{{ old('schedule_follow_up_score', $review->schedule_follow_up_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('schedule_follow_up_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- No Previous Drafts -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">التأكد من عدم وجود أي كلمات من مسودات سابقة</label>
                                    <input type="number" name="no_previous_drafts_score" value="{{ old('no_previous_drafts_score', $review->no_previous_drafts_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('no_previous_drafts_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- No Design Errors -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">التأكد من عدم وجود أي كلمات من أخطاء بالتصاميم</label>
                                    <input type="number" name="no_design_errors_score" value="{{ old('no_design_errors_score', $review->no_design_errors_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('no_design_errors_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Follow Up Modifications -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">متابعة (التعديلات - التهائيات)</label>
                                    <input type="number" name="follow_up_modifications_score" value="{{ old('follow_up_modifications_score', $review->follow_up_modifications_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('follow_up_modifications_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Presentations -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">عمل عروض تقديمية بوربوينت للمشاريع</label>
                                    <input type="number" name="presentations_score" value="{{ old('presentations_score', $review->presentations_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('presentations_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Customer Service -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">متابعة التسليمات اليومية مع خدمة العملاء</label>
                                    <input type="number" name="customer_service_score" value="{{ old('customer_service_score', $review->customer_service_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('customer_service_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Project Monitoring -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">عمل أرشفة و متابعة لجميع المشاريع</label>
                                    <input type="number" name="project_monitoring_score" value="{{ old('project_monitoring_score', $review->project_monitoring_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('project_monitoring_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Feedback -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">ملف تثبيت بالملاحظات على الدراسات</label>
                                    <input type="number" name="feedback_score" value="{{ old('feedback_score', $review->feedback_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('feedback_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Team Leader Evaluation -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">تقييم التيم ليدر</label>
                                    <input type="number" name="team_leader_evaluation_score" value="{{ old('team_leader_evaluation_score', $review->team_leader_evaluation_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('team_leader_evaluation_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- HR Evaluation -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">تقييم HR</label>
                                    <input type="number" name="hr_evaluation_score" value="{{ old('hr_evaluation_score', $review->hr_evaluation_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('hr_evaluation_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bonus Items -->
                    <div class="form-section mb-4">
                        <h3 class="form-section-title">البونص</h3>

                        <div class="row">
                            <!-- Bonus Score -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">البونص</label>
                                    <input type="number" name="bonus_score" value="{{ old('bonus_score', $review->bonus_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('bonus_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Required Deliveries -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">تخطى العدد المحدد لتسليم الدراسات</label>
                                    <input type="number" name="required_deliveries_score" value="{{ old('required_deliveries_score', $review->required_deliveries_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('required_deliveries_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- SEO -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">الحصول على 10 مواقع خاصة</label>
                                    <input type="number" name="seo_score" value="{{ old('seo_score', $review->seo_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('seo_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Portfolio -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">عمل داتا بورد بالمشاريع للأعوام السابقة</label>
                                    <input type="number" name="portfolio_score" value="{{ old('portfolio_score', $review->portfolio_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('portfolio_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Proposal -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">تقديم عرض او مقترح جديد</label>
                                    <input type="number" name="proposal_score" value="{{ old('proposal_score', $review->proposal_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('proposal_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Company Idea -->
                            <div class="col-md-4">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">تقديم فكرة تطويرية للشركة</label>
                                    <input type="number" name="company_idea_score" value="{{ old('company_idea_score', $review->company_idea_score) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('company_idea_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Penalty Items -->
                    <div class="form-section mb-4">
                        <h3 class="form-section-title">السوالب</h3>

                        <div class="row">
                            <!-- Old Draft Penalty -->
                            <div class="col-md-6">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">وجود كلمات من مسودة قديمة (-10 لكل دراسة)</label>
                                    <input type="number" name="old_draft_penalty" value="{{ old('old_draft_penalty', $review->old_draft_penalty) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('old_draft_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Design Error Penalty -->
                            <div class="col-md-6">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">وجود أخطاء في التصاميم (-10 لكل دراسة)</label>
                                    <input type="number" name="design_error_penalty" value="{{ old('design_error_penalty', $review->design_error_penalty) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('design_error_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Hidden fields for required but not displayed form elements -->
                            <input type="hidden" name="late_delivery_penalty" value="{{ $review->late_delivery_penalty }}">
                            <input type="hidden" name="missing_attachments_penalty" value="{{ $review->missing_attachments_penalty }}">

                            <!-- Daily Commitment Penalty -->
                            <div class="col-md-6">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">عدم الالتزام بتسليم المشروعات والتعديلات المطلوبة خلال اليوم</label>
                                    <input type="number" name="daily_commitment_penalty" value="{{ old('daily_commitment_penalty', $review->daily_commitment_penalty) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('daily_commitment_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Review Failure Penalty -->
                            <div class="col-md-6">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">عدم تسليم المسودة الصحيحة للأقسام الثلاثة</label>
                                    <input type="number" name="review_failure_penalty" value="{{ old('review_failure_penalty', $review->review_failure_penalty) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('review_failure_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Salary Section -->
                    <div class="form-section mb-4">
                        <h3 class="form-section-title">إجمالي المرتب</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="coordination-reviews-form-group">
                                    <label class="coordination-reviews-form-label">إجمالي المرتب</label>
                                    <input type="number" step="0.01" name="total_salary" value="{{ old('total_salary', $review->total_salary) }}" min="0" required class="coordination-reviews-form-control">
                                    @error('total_salary')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="form-section mb-4">
                        <h3 class="form-section-title">ملاحظات</h3>

                        <div class="coordination-reviews-form-group">
                            <textarea name="notes" rows="4" class="coordination-reviews-form-control">{{ old('notes', $review->notes) }}</textarea>
                            @error('notes')
                            <p class="text-danger mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('coordination-reviews.index') }}" class="coordination-reviews-btn coordination-reviews-btn-secondary">إلغاء</a>
                        <button type="submit" class="coordination-reviews-btn coordination-reviews-btn-primary">تحديث التقييم</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection