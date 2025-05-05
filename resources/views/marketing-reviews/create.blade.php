@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/marketing-reviews.css') }}">
@endpush

@section('content')
<div class="container fade-in">
    <div class="marketing-reviews-container">
        <div class="marketing-reviews-header">
            <h2>إضافة تقييم جديد</h2>
        </div>

        <div class="marketing-reviews-card">
            <div class="marketing-reviews-card-header">
                <span>نموذج التقييم</span>
                <a href="{{ route('marketing-reviews.index') }}" class="marketing-reviews-btn marketing-reviews-btn-secondary">
                    <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
                </a>
            </div>

            <div class="marketing-reviews-card-body">
                <form action="{{ route('marketing-reviews.store') }}" method="POST">
                    @csrf

                    <div class="row mb-4">
                        <!-- User -->
                        <div class="col-md-6">
                            <div class="marketing-reviews-form-group">
                                <label class="marketing-reviews-form-label">الموظف <span class="text-danger">*</span></label>
                                <select id="user_id" name="user_id" required class="marketing-reviews-form-control">
                                    <option value="">اختر الموظف</option>
                                    @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
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
                            <div class="marketing-reviews-form-group">
                                <label class="marketing-reviews-form-label">شهر التقييم <span class="text-danger">*</span></label>
                                <input type="month" name="review_month" id="review_month" value="{{ old('review_month', now()->format('Y-m')) }}" required class="marketing-reviews-form-control">
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
                            <!-- Finish Before Deadline -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">الانتهاء قبل الموعد النهائي</label>
                                    <input type="number" name="finish_before_deadline_score" value="{{ old('finish_before_deadline_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('finish_before_deadline_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Deliver On Time -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">التسليم في الوقت المحدد</label>
                                    <input type="number" name="deliver_on_time_score" value="{{ old('deliver_on_time_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('deliver_on_time_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Deliver Complete Project -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">تسليم مشروع كامل</label>
                                    <input type="number" name="deliver_complete_project_score" value="{{ old('deliver_complete_project_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('deliver_complete_project_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Project Formatting -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">تنسيق المشروع</label>
                                    <input type="number" name="project_formatting_score" value="{{ old('project_formatting_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('project_formatting_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- No Project Revisions -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">عدم وجود مراجعات للمشروع</label>
                                    <input type="number" name="no_project_revisions_score" value="{{ old('no_project_revisions_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('no_project_revisions_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Continuous Update -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">التحديث المستمر</label>
                                    <input type="number" name="continuous_update_score" value="{{ old('continuous_update_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('continuous_update_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Competitor Analysis -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">تحليل المنافسين</label>
                                    <input type="number" name="competitor_analysis_score" value="{{ old('competitor_analysis_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('competitor_analysis_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Data Presentation Change -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">تغيير طريقة عرض البيانات</label>
                                    <input type="number" name="data_presentation_change_score" value="{{ old('data_presentation_change_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('data_presentation_change_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Project Sheet Update -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">تحديث ورقة المشروع</label>
                                    <input type="number" name="project_sheet_update_score" value="{{ old('project_sheet_update_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('project_sheet_update_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Timing Sheet Completion -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">إكمال جدول المواعيد</label>
                                    <input type="number" name="timing_sheet_completion_score" value="{{ old('timing_sheet_completion_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('timing_sheet_completion_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- New Business Ideas -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">أفكار تجارية جديدة</label>
                                    <input type="number" name="new_business_ideas_score" value="{{ old('new_business_ideas_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('new_business_ideas_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- New Sources -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">مصادر جديدة</label>
                                    <input type="number" name="new_sources_score" value="{{ old('new_sources_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('new_sources_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- New Demand Measurement -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">قياس طلب جديد</label>
                                    <input type="number" name="new_demand_measurement_score" value="{{ old('new_demand_measurement_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('new_demand_measurement_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Team Leader Tasks -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">مهام قائد الفريق</label>
                                    <input type="number" name="team_leader_tasks_score" value="{{ old('team_leader_tasks_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('team_leader_tasks_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Economic Impact -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">التأثير الاقتصادي</label>
                                    <input type="number" name="economic_impact_score" value="{{ old('economic_impact_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('economic_impact_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Economic Report -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">التقرير الاقتصادي</label>
                                    <input type="number" name="economic_report_score" value="{{ old('economic_report_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('economic_report_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- New Data Sources -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">مصادر بيانات جديدة</label>
                                    <input type="number" name="new_data_sources_score" value="{{ old('new_data_sources_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('new_data_sources_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Client Calls -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">مكالمات العملاء</label>
                                    <input type="number" name="client_calls_score" value="{{ old('client_calls_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('client_calls_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Potential Client Calls -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">مكالمات العملاء المحتملين</label>
                                    <input type="number" name="potential_client_calls_score" value="{{ old('potential_client_calls_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('potential_client_calls_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Project Questions -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">أسئلة المشروع</label>
                                    <input type="number" name="project_questions_score" value="{{ old('project_questions_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('project_questions_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Project Followup -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">متابعة المشروع</label>
                                    <input type="number" name="project_followup_score" value="{{ old('project_followup_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('project_followup_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Team Leader Evaluation -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">تقييم قائد الفريق</label>
                                    <input type="number" name="team_leader_evaluation_score" value="{{ old('team_leader_evaluation_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('team_leader_evaluation_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- HR Evaluation -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">تقييم الموارد البشرية</label>
                                    <input type="number" name="hr_evaluation_score" value="{{ old('hr_evaluation_score', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('hr_evaluation_score')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Penalties Section -->
                    <div class="form-section mb-4">
                        <h3 class="form-section-title">الخصومات</h3>

                        <div class="row">
                            <!-- Core Revisions Penalty -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">خصم المراجعات الأساسية</label>
                                    <input type="number" name="core_revisions_penalty" value="{{ old('core_revisions_penalty', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('core_revisions_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Spelling Errors Penalty -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">خصم أخطاء الإملاء</label>
                                    <input type="number" name="spelling_errors_penalty" value="{{ old('spelling_errors_penalty', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('spelling_errors_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Content Errors Penalty -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">خصم أخطاء المحتوى</label>
                                    <input type="number" name="content_errors_penalty" value="{{ old('content_errors_penalty', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('content_errors_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Minimum Projects Penalty -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">خصم الحد الأدنى للمشاريع</label>
                                    <input type="number" name="minimum_projects_penalty" value="{{ old('minimum_projects_penalty', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('minimum_projects_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Old Draft Words Penalty -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">خصم كلمات المسودة القديمة</label>
                                    <input type="number" name="old_draft_words_penalty" value="{{ old('old_draft_words_penalty', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('old_draft_words_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Sheets Commitment Penalty -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">خصم التزام الأوراق</label>
                                    <input type="number" name="sheets_commitment_penalty" value="{{ old('sheets_commitment_penalty', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('sheets_commitment_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Work Behavior Penalty -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">خصم سلوك العمل</label>
                                    <input type="number" name="work_behavior_penalty" value="{{ old('work_behavior_penalty', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('work_behavior_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Revisions Commitment Penalty -->
                            <div class="col-md-4">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">خصم التزام المراجعات</label>
                                    <input type="number" name="revisions_commitment_penalty" value="{{ old('revisions_commitment_penalty', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('revisions_commitment_penalty')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Salary Section -->
                    <div class="form-section mb-4">
                        <h3 class="form-section-title">بيانات الراتب</h3>

                        <div class="row">
                            <!-- Total Salary -->
                            <div class="col-md-6">
                                <div class="marketing-reviews-form-group">
                                    <label class="marketing-reviews-form-label">إجمالي الراتب <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="total_salary" value="{{ old('total_salary', 0) }}" min="0" required class="marketing-reviews-form-control">
                                    @error('total_salary')
                                    <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="form-section mb-4">
                        <h3 class="form-section-title">ملاحظات</h3>

                        <div class="marketing-reviews-form-group">
                            <textarea name="notes" rows="4" class="marketing-reviews-form-control">{{ old('notes') }}</textarea>
                            @error('notes')
                            <p class="text-danger mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="marketing-reviews-btn marketing-reviews-btn-primary">
                            <i class="fas fa-save me-1"></i> حفظ التقييم
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection