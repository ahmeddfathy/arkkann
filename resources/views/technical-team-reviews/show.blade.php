@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/technical-team-reviews.css') }}">
@endpush

@section('content')
<div class="container fade-in">
  <div class="technical-team-reviews-container">
    <div class="technical-team-reviews-header">
      <h2>عرض تقييم الفريق التقني</h2>
    </div>

    <div class="technical-team-reviews-card">
      <div class="technical-team-reviews-card-header">
        <span>تفاصيل التقييم</span>
        <div>
          <a href="{{ route('technical-team-reviews.index') }}" class="technical-team-reviews-btn technical-team-reviews-btn-secondary">
            <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
          </a>
          @if(auth()->id() != $review->user_id && auth()->id() != $review->reviewer_id)
          <a href="{{ route('technical-team-reviews.edit', $review) }}" class="technical-team-reviews-btn technical-team-reviews-btn-primary ms-2">
            <i class="fas fa-edit me-1"></i> تعديل
          </a>
          @endif
        </div>
      </div>

      <div class="technical-team-reviews-card-body">
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
              <span class="summary-label">عمولة المبيعات</span>
              <span class="summary-value summary-highlight">{{ number_format($review->sales_commission, 2) }}</span>
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
                <span class="criteria-name">التارجت الشهري من المشروعات</span>
                <span class="criteria-score">{{ $review->monthly_project_target_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">الانتهاء من التاسك أو المشروع قبل الموعد المحدد</span>
                <span class="criteria-score">{{ $review->finish_before_deadline_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">تسليم المشروع في الوقت المحدد له</span>
                <span class="criteria-score">{{ $review->deliver_on_time_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">تسليم المشروع كامل بجميع مرفقاته</span>
                <span class="criteria-score">{{ $review->deliver_complete_project_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">مفاضلة عروض الأسعار</span>
                <span class="criteria-score">{{ $review->price_quote_comparison_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">تسليم خطة التشغيل لكل المشروعات</span>
                <span class="criteria-score">{{ $review->operation_plan_delivery_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">تنسيقات المشروع كاملة</span>
                <span class="criteria-score">{{ $review->project_formatting_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">عدم ارجاع أي تعديلات على المشروع</span>
                <span class="criteria-score">{{ $review->no_project_revisions_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">التحديث المستمر</span>
                <span class="criteria-score">{{ $review->continuous_update_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">معايير الصناعة</span>
                <span class="criteria-score">{{ $review->industry_standards_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">تحديث ورقة المشروع</span>
                <span class="criteria-score">{{ $review->project_sheet_update_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">سعر المنتج النهائي</span>
                <span class="criteria-score">{{ $review->final_product_price_score }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- More Positive Items -->
        <div class="form-section mb-4">
          <h3 class="form-section-title">بنود التقييم الإيجابية (تكملة)</h3>

          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">المخاطر القانونية</span>
                <span class="criteria-score">{{ $review->legal_risks_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">دراسة مقترحات التطوير</span>
                <span class="criteria-score">{{ $review->study_development_proposals_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">أفكار الشركة</span>
                <span class="criteria-score">{{ $review->company_ideas_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">مراجعات مشاريع أخرى</span>
                <span class="criteria-score">{{ $review->other_project_revisions_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">مهام غير مرتبطة بالمشروع</span>
                <span class="criteria-score">{{ $review->non_project_task_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">مصادر بيانات جديدة</span>
                <span class="criteria-score">{{ $review->new_data_sources_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">مكالمات العملاء</span>
                <span class="criteria-score">{{ $review->client_calls_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">مكالمات العملاء المحتملين</span>
                <span class="criteria-score">{{ $review->potential_client_calls_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">أسئلة المشروع</span>
                <span class="criteria-score">{{ $review->project_questions_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">متابعة المشروع</span>
                <span class="criteria-score">{{ $review->project_followup_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">إضافة عميل</span>
                <span class="criteria-score">{{ $review->client_addition_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">مشاريع عاجلة</span>
                <span class="criteria-score">{{ $review->urgent_projects_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">مشاريع التسليم المباشر</span>
                <span class="criteria-score">{{ $review->direct_delivery_projects_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">بدون إجازة</span>
                <span class="criteria-score">{{ $review->no_leave_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">المشاركة في ورشة عمل</span>
                <span class="criteria-score">{{ $review->workshop_participation_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">تقييم قائد الفريق</span>
                <span class="criteria-score">{{ $review->team_leader_evaluation_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">تقييم الموارد البشرية</span>
                <span class="criteria-score">{{ $review->hr_evaluation_score }}</span>
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
                <span class="criteria-name">مراجعات أساسية</span>
                <span class="criteria-score">{{ $review->core_revisions_penalty }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">أخطاء إملائية</span>
                <span class="criteria-score">{{ $review->spelling_errors_penalty }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">أخطاء في المحتوى</span>
                <span class="criteria-score">{{ $review->content_errors_penalty }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">الحد الأدنى للمشاريع</span>
                <span class="criteria-score">{{ $review->minimum_projects_penalty }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">كلمات المسودة القديمة</span>
                <span class="criteria-score">{{ $review->old_draft_words_penalty }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">التزام الأوراق</span>
                <span class="criteria-score">{{ $review->sheets_commitment_penalty }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">إهمال الأسئلة</span>
                <span class="criteria-score">{{ $review->questions_neglect_penalty }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">سلوك العمل</span>
                <span class="criteria-score">{{ $review->work_behavior_penalty }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">التزام المراجعات</span>
                <span class="criteria-score">{{ $review->revisions_commitment_penalty }}</span>
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
          @if(auth()->id() != $review->user_id && auth()->id() != $review->reviewer_id)
          <a href="{{ route('technical-team-reviews.edit', $review) }}" class="technical-team-reviews-btn technical-team-reviews-btn-primary">
            <i class="fas fa-edit me-1"></i> تعديل التقييم
          </a>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
