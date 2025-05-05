@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/customer-service-reviews.css') }}">
@endpush

@section('content')
<div class="container fade-in">
  <div class="customer-service-reviews-container">
    <div class="customer-service-reviews-header">
      <h2>تفاصيل تقييم خدمة العملاء</h2>
      <p>{{ $review->user->name ?? 'غير محدد' }} - {{ $review->review_month }}</p>
    </div>

    <div class="row">
      <div class="col-md-12 mb-4">
        <div class="customer-service-reviews-card">
          <div class="customer-service-reviews-card-header">
            <span>ملخص التقييم</span>
            <div>
              @if(auth()->id() != $review->user_id)
              <a href="{{ route('customer-service-reviews.edit', $review) }}" class="customer-service-reviews-btn customer-service-reviews-btn-success customer-service-reviews-btn-sm">
                <i class="fas fa-edit me-1"></i> تعديل
              </a>
              @endif
              <a href="{{ route('customer-service-reviews.index') }}" class="customer-service-reviews-btn customer-service-reviews-btn-secondary customer-service-reviews-btn-sm">
                <i class="fas fa-arrow-right me-1"></i> العودة
              </a>
            </div>
          </div>

          <div class="customer-service-reviews-card-body">
            <div class="row mb-4">
              <div class="col-md-3">
                <div class="summary-item">
                  <div class="summary-label">المجموع</div>
                  <div class="summary-value">{{ $review->total_score }}</div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="summary-item">
                  <div class="summary-label">المجموع بعد الخصم</div>
                  <div class="summary-value">{{ $review->total_after_deductions }}</div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="summary-item">
                  <div class="summary-label">النسبة المئوية</div>
                  <div class="summary-value">
                    <span class="customer-service-reviews-status {{ $review->percentage >= 80 ? 'customer-service-reviews-status-approved' : 'customer-service-reviews-status-pending' }}">
                      {{ number_format($review->percentage, 2) }}%
                    </span>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="summary-item">
                  <div class="summary-label">إجمالي المرتب</div>
                  <div class="summary-value summary-highlight">{{ number_format($review->total_salary, 2) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-12 mb-4">
        <div class="customer-service-reviews-card">
          <div class="customer-service-reviews-card-header">
            <span>بنود التقييم الإيجابية</span>
          </div>

          <div class="customer-service-reviews-card-body">
            <div class="row">
              @foreach ([
              'client_interaction_score' => ['نسبة التفاعل مع العملاء', 30],
              'client_contract_score' => ['نسبة التعاقد مع العملاء', 20],
              'client_communication_speed_score' => ['سرعة التواصل مع العملاء', 20],
              'final_collection_score' => ['تحصيل النهائي', 30],
              'client_data_recording_score' => ['تسجيل بيانات جميع العملاء بشكل مفصل على جميع الشيتات', 15],
              'project_archiving_score' => ['أرشفة ( التقارير - المكالمات - الاسئلة - الإجابات ) كل ما يخص العميل على سرفر تحليل المشروعات', 15],
              'after_sales_service_score' => ['خدمة ما بعد البيع ( إرسال كافة الخدمات للعميل - إرسال كوبون للعميل الفعلي )', 30],
              'team_coordination_score' => ['حسن التواصل والتنسيق مع الفريق التنفيذي', 15],
              'client_followup_quality_score' => ['جودة متابعة ومراجعة العميل بشكل مستمر وفعال ( متابعة أولى / متابعة تانيه)', 25],
              'customer_service_archiving_score' => ['أرشفة العقود والحوالات والدراسات على سرفر خدمة العملاء', 15],
              'client_evaluation_score' => ['تحصيل تقييم من العملاء (فعلي – محتمل) ومعرفة من منهم نفذ المشروع بشكل فعلي', 30],
              'team_leader_tasks_score' => ['عمل المهام المطلوبة من التيم ليدر بكفاءة عالية', 30],
              'average_sales_score' => ['متوسط المبيعات', 35],
              'daily_report_commitment_score' => ['الإلتزام بكتابة التقرير اليومي بشكل مفصل', 20],
              'hr_evaluation_score' => ['تقييم HR', 25],
              ] as $field => $data)
              <div class="col-md-4 mb-3">
                <div class="criteria-item">
                  <div class="criteria-name">{{ $data[0] }}</div>
                  <div class="criteria-score">{{ $review->$field }} / {{ $data[1] }}</div>
                </div>
              </div>
              @endforeach
            </div>

            @if($review->additional_bonus > 0)
            <div class="row mt-3">
              <div class="col-md-4">
                <div class="criteria-item">
                  <div class="criteria-name">بونص إضافي</div>
                  <div class="criteria-score">{{ $review->additional_bonus }}</div>
                </div>
              </div>
            </div>
            @endif
          </div>
        </div>
      </div>

      <div class="col-md-12 mb-4">
        <div class="customer-service-reviews-card">
          <div class="customer-service-reviews-card-header">
            <span>بنود التقييم السلبية</span>
          </div>

          <div class="customer-service-reviews-card-body">
            <div class="row">
              @foreach ([
              'excess_services_penalty' => ['حصول العميل على خدمات أكثر من المتفق عليه بسبب سوء التنسيق', 15],
              'unauthorized_discount_penalty' => ['التخفيض ( بلا داعي / بدون إذن )مع العملاء', 15],
              'contract_mismatch_penalty' => ['عدم تنفيذ عقد او فاتورة بالخدمة اللي تم الاتفاق بها مع العميل', 10],
              'team_conflict_penalty' => ['الخلافات بين أفراد الفريق أو بين الفريق وأي فريق أخر لأسباب تنسيقية تتعلق بالعمل', 15],
              'personal_phone_use_penalty' => ['استخدام الهاتف الشخصي أثناء العمل', 30],
              'absence_late_penalty' => ['غياب / تأخير بدون إذن', 30],
              ] as $field => $data)
              @if($review->$field > 0)
              <div class="col-md-4 mb-3">
                <div class="criteria-item">
                  <div class="criteria-name">{{ $data[0] }}</div>
                  <div class="criteria-score customer-service-reviews-status-rejected">-{{ $review->$field }}</div>
                </div>
              </div>
              @endif
              @endforeach

              @if($review->additional_deduction > 0)
              <div class="col-md-4 mb-3">
                <div class="criteria-item">
                  <div class="criteria-name">خصم إضافي</div>
                  <div class="criteria-score customer-service-reviews-status-rejected">-{{ $review->additional_deduction }}</div>
                </div>
              </div>
              @endif

              @if(!$review->excess_services_penalty && !$review->unauthorized_discount_penalty && !$review->contract_mismatch_penalty && !$review->team_conflict_penalty && !$review->personal_phone_use_penalty && !$review->absence_late_penalty && !$review->additional_deduction)
              <div class="col-md-12">
                <p class="text-center text-muted">لا توجد خصومات</p>
              </div>
              @endif
            </div>
          </div>
        </div>
      </div>

      @if($review->notes)
      <div class="col-md-12 mb-4">
        <div class="customer-service-reviews-card">
          <div class="customer-service-reviews-card-header">
            <span>ملاحظات</span>
          </div>

          <div class="customer-service-reviews-card-body">
            <p>{{ $review->notes }}</p>
          </div>
        </div>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection