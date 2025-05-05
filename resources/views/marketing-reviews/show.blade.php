@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/marketing-reviews.css') }}">
@endpush

@section('content')
<div class="container fade-in">
  <div class="marketing-reviews-container">
    <div class="marketing-reviews-header">
      <h2>عرض تقييم قسم التسويق</h2>
    </div>

    <div class="marketing-reviews-card">
      <div class="marketing-reviews-card-header">
        <span>تفاصيل التقييم</span>
        <div>
          <a href="{{ route('marketing-reviews.index') }}" class="marketing-reviews-btn marketing-reviews-btn-secondary">
            <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
          </a>
          @if(auth()->id() != $review->user_id)
          <a href="{{ route('marketing-reviews.edit', $review) }}" class="marketing-reviews-btn marketing-reviews-btn-primary ms-2">
            <i class="fas fa-edit me-1"></i> تعديل
          </a>
          @endif
        </div>
      </div>

      <div class="marketing-reviews-card-body">
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
                <span class="criteria-name">الانتهاء قبل الموعد النهائي</span>
                <span class="criteria-score">{{ $review->finish_before_deadline_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">التسليم في الوقت المحدد</span>
                <span class="criteria-score">{{ $review->deliver_on_time_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">تسليم مشروع كامل</span>
                <span class="criteria-score">{{ $review->deliver_complete_project_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">تنسيق المشروع</span>
                <span class="criteria-score">{{ $review->project_formatting_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">عدم وجود مراجعات للمشروع</span>
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
                <span class="criteria-name">تحليل المنافسين</span>
                <span class="criteria-score">{{ $review->competitor_analysis_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">تغيير طريقة عرض البيانات</span>
                <span class="criteria-score">{{ $review->data_presentation_change_score }}</span>
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
                <span class="criteria-name">إكمال جدول المواعيد</span>
                <span class="criteria-score">{{ $review->timing_sheet_completion_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">أفكار تجارية جديدة</span>
                <span class="criteria-score">{{ $review->new_business_ideas_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">مصادر جديدة</span>
                <span class="criteria-score">{{ $review->new_sources_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">قياس طلب جديد</span>
                <span class="criteria-score">{{ $review->new_demand_measurement_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">مهام قائد الفريق</span>
                <span class="criteria-score">{{ $review->team_leader_tasks_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">التأثير الاقتصادي</span>
                <span class="criteria-score">{{ $review->economic_impact_score }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">التقرير الاقتصادي</span>
                <span class="criteria-score">{{ $review->economic_report_score }}</span>
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
          <h3 class="form-section-title">الخصومات</h3>

          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">خصم المراجعات الأساسية</span>
                <span class="criteria-score">{{ $review->core_revisions_penalty }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">خصم أخطاء الإملاء</span>
                <span class="criteria-score">{{ $review->spelling_errors_penalty }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">خصم أخطاء المحتوى</span>
                <span class="criteria-score">{{ $review->content_errors_penalty }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">خصم الحد الأدنى للمشاريع</span>
                <span class="criteria-score">{{ $review->minimum_projects_penalty }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">خصم كلمات المسودة القديمة</span>
                <span class="criteria-score">{{ $review->old_draft_words_penalty }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">خصم التزام الأوراق</span>
                <span class="criteria-score">{{ $review->sheets_commitment_penalty }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">خصم سلوك العمل</span>
                <span class="criteria-score">{{ $review->work_behavior_penalty }}</span>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="criteria-item">
                <span class="criteria-name">خصم التزام المراجعات</span>
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
          <form action="{{ route('marketing-reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا التقييم؟');" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="marketing-reviews-btn marketing-reviews-btn-danger">
              <i class="fas fa-trash-alt me-1"></i> حذف التقييم
            </button>
          </form>
          <a href="{{ route('marketing-reviews.edit', $review) }}" class="marketing-reviews-btn marketing-reviews-btn-primary">
            <i class="fas fa-edit me-1"></i> تعديل التقييم
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection