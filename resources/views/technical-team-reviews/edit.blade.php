@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/technical-team-reviews.css') }}">
@endpush

@section('content')
<div class="container fade-in">
    <div class="technical-team-reviews-container">
        <div class="technical-team-reviews-header">
            <h2>تعديل تقييم</h2>
        </div>

        <div class="technical-team-reviews-card">
            <div class="technical-team-reviews-card-header">
                <span>نموذج التقييم</span>
                <a href="{{ route('technical-team-reviews.index') }}" class="technical-team-reviews-btn technical-team-reviews-btn-secondary">
                    <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
                </a>
            </div>

            <div class="technical-team-reviews-card-body">
                <form action="{{ route('technical-team-reviews.update', $review) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row mb-4">
                        <!-- User -->
                        <div class="col-md-6">
                            <div class="technical-team-reviews-form-group">
                                <label class="technical-team-reviews-form-label">الموظف <span class="text-danger">*</span></label>
                                <select id="user_id" name="user_id" required class="technical-team-reviews-form-control">
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
                            <div class="technical-team-reviews-form-group">
                                <label class="technical-team-reviews-form-label">شهر التقييم <span class="text-danger">*</span></label>
                                <input type="month" name="review_month" id="review_month" value="{{ old('review_month', $review->review_month) }}" required class="technical-team-reviews-form-control">
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
                            <!-- Monthly Project Target -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">التارجت الشهري من المشروعات</label>
                                    <input type="number" name="monthly_project_target_score" value="{{ old('monthly_project_target_score', $review->monthly_project_target_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('monthly_project_target_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Finish Before Deadline -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">الانتهاء من التاسك أو المشروع قبل الموعد المحدد</label>
                                    <input type="number" name="finish_before_deadline_score" value="{{ old('finish_before_deadline_score', $review->finish_before_deadline_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('finish_before_deadline_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Deliver On Time -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">تسليم المشروع في الوقت المحدد له</label>
                                    <input type="number" name="deliver_on_time_score" value="{{ old('deliver_on_time_score', $review->deliver_on_time_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('deliver_on_time_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Deliver Complete Project -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">تسليم المشروع كامل بجميع مرفقاته</label>
                                    <input type="number" name="deliver_complete_project_score" value="{{ old('deliver_complete_project_score', $review->deliver_complete_project_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('deliver_complete_project_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Price Quote Comparison -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">مفاضلة عروض الأسعار</label>
                                    <input type="number" name="price_quote_comparison_score" value="{{ old('price_quote_comparison_score', $review->price_quote_comparison_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('price_quote_comparison_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Operation Plan Delivery -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">تسليم خطة التشغيل لكل المشروعات</label>
                                    <input type="number" name="operation_plan_delivery_score" value="{{ old('operation_plan_delivery_score', $review->operation_plan_delivery_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('operation_plan_delivery_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Project Formatting -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">تنسيقات المشروع كاملة</label>
                                    <input type="number" name="project_formatting_score" value="{{ old('project_formatting_score', $review->project_formatting_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('project_formatting_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- No Project Revisions -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">عدم ارجاع أي تعديلات على المشروع</label>
                                    <input type="number" name="no_project_revisions_score" value="{{ old('no_project_revisions_score', $review->no_project_revisions_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('no_project_revisions_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Continuous Update -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">التحديث المستمر</label>
                                    <input type="number" name="continuous_update_score" value="{{ old('continuous_update_score', $review->continuous_update_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('continuous_update_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Industry Standards -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">معايير الصناعة</label>
                                    <input type="number" name="industry_standards_score" value="{{ old('industry_standards_score', $review->industry_standards_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('industry_standards_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Project Sheet Update -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">تحديث ورقة المشروع</label>
                                    <input type="number" name="project_sheet_update_score" value="{{ old('project_sheet_update_score', $review->project_sheet_update_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('project_sheet_update_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Final Product Price -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">سعر المنتج النهائي</label>
                                    <input type="number" name="final_product_price_score" value="{{ old('final_product_price_score', $review->final_product_price_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('final_product_price_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bonus Items -->
                    <div class="form-section mb-4">
                        <h3 class="form-section-title">بنود التقييم الإيجابية (تكملة)</h3>

                        <div class="row">
                            <!-- Legal Risks -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">المخاطر القانونية</label>
                                    <input type="number" name="legal_risks_score" value="{{ old('legal_risks_score', $review->legal_risks_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('legal_risks_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Study Development Proposals -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">دراسة مقترحات التطوير</label>
                                    <input type="number" name="study_development_proposals_score" value="{{ old('study_development_proposals_score', $review->study_development_proposals_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('study_development_proposals_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Company Ideas -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">أفكار الشركة</label>
                                    <input type="number" name="company_ideas_score" value="{{ old('company_ideas_score', $review->company_ideas_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('company_ideas_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Other Project Revisions -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">مراجعات مشاريع أخرى</label>
                                    <input type="number" name="other_project_revisions_score" value="{{ old('other_project_revisions_score', $review->other_project_revisions_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('other_project_revisions_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Non Project Task -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">مهام غير مرتبطة بالمشروع</label>
                                    <input type="number" name="non_project_task_score" value="{{ old('non_project_task_score', $review->non_project_task_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('non_project_task_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- New Data Sources -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">مصادر بيانات جديدة</label>
                                    <input type="number" name="new_data_sources_score" value="{{ old('new_data_sources_score', $review->new_data_sources_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('new_data_sources_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Client Calls -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">مكالمات العملاء</label>
                                    <input type="number" name="client_calls_score" value="{{ old('client_calls_score', $review->client_calls_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('client_calls_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Potential Client Calls -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">مكالمات العملاء المحتملين</label>
                                    <input type="number" name="potential_client_calls_score" value="{{ old('potential_client_calls_score', $review->potential_client_calls_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('potential_client_calls_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Project Questions -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">أسئلة المشروع</label>
                                    <input type="number" name="project_questions_score" value="{{ old('project_questions_score', $review->project_questions_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('project_questions_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Project Followup -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">متابعة المشروع</label>
                                    <input type="number" name="project_followup_score" value="{{ old('project_followup_score', $review->project_followup_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('project_followup_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Client Addition -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">إضافة عميل</label>
                                    <input type="number" name="client_addition_score" value="{{ old('client_addition_score', $review->client_addition_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('client_addition_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Urgent Projects -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">مشاريع عاجلة</label>
                                    <input type="number" name="urgent_projects_score" value="{{ old('urgent_projects_score', $review->urgent_projects_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('urgent_projects_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Direct Delivery Projects -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">مشاريع التسليم المباشر</label>
                                    <input type="number" name="direct_delivery_projects_score" value="{{ old('direct_delivery_projects_score', $review->direct_delivery_projects_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('direct_delivery_projects_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- No Leave -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">بدون إجازة</label>
                                    <input type="number" name="no_leave_score" value="{{ old('no_leave_score', $review->no_leave_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('no_leave_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Workshop Participation -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">المشاركة في ورشة عمل</label>
                                    <input type="number" name="workshop_participation_score" value="{{ old('workshop_participation_score', $review->workshop_participation_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('workshop_participation_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Team Leader Evaluation -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">تقييم قائد الفريق</label>
                                    <input type="number" name="team_leader_evaluation_score" value="{{ old('team_leader_evaluation_score', $review->team_leader_evaluation_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('team_leader_evaluation_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- HR Evaluation -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">تقييم الموارد البشرية</label>
                                    <input type="number" name="hr_evaluation_score" value="{{ old('hr_evaluation_score', $review->hr_evaluation_score) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('hr_evaluation_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Commission -->
                    <div class="form-section mb-4">
                        <h3 class="form-section-title">عمولة المبيعات</h3>

                        <div class="row">
                            <!-- Sales Amount -->
                            <div class="col-md-6">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">قيمة المبيعات</label>
                                    <input type="number" name="sales_amount" value="{{ old('sales_amount', $review->sales_amount) }}" min="0" step="0.01" class="technical-team-reviews-form-control">
                                    @error('sales_amount')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Commission Percentage -->
                            <div class="col-md-6">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">نسبة العمولة (%)</label>
                                    <input type="number" name="sales_commission_percentage" value="{{ old('sales_commission_percentage', $review->sales_commission_percentage) }}" min="0" max="100" step="0.01" class="technical-team-reviews-form-control">
                                    @error('sales_commission_percentage')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Salary -->
                    <div class="form-section mb-4">
                        <h3 class="form-section-title">الراتب الإجمالي</h3>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">الراتب الإجمالي</label>
                                    <input type="number" name="total_salary" value="{{ old('total_salary', $review->total_salary) }}" min="0" step="0.01" required class="technical-team-reviews-form-control">
                                    @error('total_salary')
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
                            <!-- Core Revisions Penalty -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">مراجعات أساسية</label>
                                    <input type="number" name="core_revisions_penalty" value="{{ old('core_revisions_penalty', $review->core_revisions_penalty) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('core_revisions_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Spelling Errors Penalty -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">أخطاء إملائية</label>
                                    <input type="number" name="spelling_errors_penalty" value="{{ old('spelling_errors_penalty', $review->spelling_errors_penalty) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('spelling_errors_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Content Errors Penalty -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">أخطاء في المحتوى</label>
                                    <input type="number" name="content_errors_penalty" value="{{ old('content_errors_penalty', $review->content_errors_penalty) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('content_errors_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Minimum Projects Penalty -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">الحد الأدنى للمشاريع</label>
                                    <input type="number" name="minimum_projects_penalty" value="{{ old('minimum_projects_penalty', $review->minimum_projects_penalty) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('minimum_projects_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Old Draft Words Penalty -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">كلمات المسودة القديمة</label>
                                    <input type="number" name="old_draft_words_penalty" value="{{ old('old_draft_words_penalty', $review->old_draft_words_penalty) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('old_draft_words_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Sheets Commitment Penalty -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">التزام الأوراق</label>
                                    <input type="number" name="sheets_commitment_penalty" value="{{ old('sheets_commitment_penalty', $review->sheets_commitment_penalty) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('sheets_commitment_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Questions Neglect Penalty -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">إهمال الأسئلة</label>
                                    <input type="number" name="questions_neglect_penalty" value="{{ old('questions_neglect_penalty', $review->questions_neglect_penalty) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('questions_neglect_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Work Behavior Penalty -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">سلوك العمل</label>
                                    <input type="number" name="work_behavior_penalty" value="{{ old('work_behavior_penalty', $review->work_behavior_penalty) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('work_behavior_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Revisions Commitment Penalty -->
                            <div class="col-md-4">
                                <div class="technical-team-reviews-form-group">
                                    <label class="technical-team-reviews-form-label">التزام المراجعات</label>
                                    <input type="number" name="revisions_commitment_penalty" value="{{ old('revisions_commitment_penalty', $review->revisions_commitment_penalty) }}" min="0" required class="technical-team-reviews-form-control">
                                    @error('revisions_commitment_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="form-section mb-4">
                        <h3 class="form-section-title">ملاحظات</h3>

                        <div class="technical-team-reviews-form-group">
                            <textarea name="notes" rows="4" class="technical-team-reviews-form-control">{{ old('notes', $review->notes) }}</textarea>
                            @error('notes')
                            <p class="text-danger mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('technical-team-reviews.index') }}" class="technical-team-reviews-btn technical-team-reviews-btn-secondary">إلغاء</a>
                        <button type="submit" class="technical-team-reviews-btn technical-team-reviews-btn-primary">تحديث التقييم</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection