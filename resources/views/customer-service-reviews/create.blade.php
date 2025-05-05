@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/customer-service-reviews.css') }}">
@endpush



@section('content')
<div class="container fade-in">
    <div class="customer-service-reviews-container">
        <div class="customer-service-reviews-header">
            <h2>إضافة تقييم خدمة عملاء جديد</h2>
            <p>استمارة تقييم أداء موظفي خدمة العملاء بشركة أركان للإستشارات الإقتصادية</p>
        </div>

        <div class="customer-service-reviews-card">
            <div class="customer-service-reviews-card-header">
                <span>نموذج التقييم</span>
            </div>

            <div class="customer-service-reviews-card-body">
                <form action="{{ route('customer-service-reviews.store') }}" method="POST">
                    @csrf

                    <div class="form-section">
                        <h4 class="form-section-title">بيانات أساسية</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="customer-service-reviews-form-group">
                                    <label class="customer-service-reviews-form-label">الموظف <span class="text-danger">*</span></label>
                                    <select name="user_id" class="customer-service-reviews-form-control" required>
                                        <option value="">اختر الموظف</option>
                                        @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="customer-service-reviews-form-group">
                                    <label class="customer-service-reviews-form-label">شهر التقييم <span class="text-danger">*</span></label>
                                    <input type="month" name="review_month" value="{{ old('review_month', now()->format('Y-m')) }}" class="customer-service-reviews-form-control" required>
                                    @error('review_month')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4 class="form-section-title">بنود التقييم الإيجابية</h4>
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
                            <div class="col-md-4">
                                <div class="customer-service-reviews-form-group">
                                    <label class="customer-service-reviews-form-label">
                                        {{ $data[0] }}
                                        <small class="text-muted">({{ $data[1] }} نقطة)</small>
                                    </label>
                                    <input type="number" name="{{ $field }}" value="{{ old($field, 0) }}" min="0" max="{{ $data[1] }}" class="customer-service-reviews-form-control" required placeholder="الحد الأقصى {{ $data[1] }}">
                                    @error($field)
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-section">
                        <h4 class="form-section-title">بونص إضافي</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="customer-service-reviews-form-group">
                                    <label class="customer-service-reviews-form-label">بونص إضافي</label>
                                    <input type="number" name="additional_bonus" value="{{ old('additional_bonus', 0) }}" min="0" class="customer-service-reviews-form-control" required>
                                    @error('additional_bonus')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4 class="form-section-title">نقاط أساسية (بالسالب)</h4>
                        <div class="row">
                            @foreach ([
                            'excess_services_penalty' => ['حصول العميل على خدمات أكثر من المتفق عليه بسبب سوء التنسيق', 15],
                            'unauthorized_discount_penalty' => ['التخفيض ( بلا داعي / بدون إذن )مع العملاء', 15],
                            'contract_mismatch_penalty' => ['عدم تنفيذ عقد او فاتورة بالخدمة اللي تم الاتفاق بها مع العميل', 10],
                            'team_conflict_penalty' => ['الخلافات بين أفراد الفريق أو بين الفريق وأي فريق أخر لأسباب تنسيقية تتعلق بالعمل', 15],
                            'personal_phone_use_penalty' => ['استخدام الهاتف الشخصي أثناء العمل', 30],
                            'absence_late_penalty' => ['غياب / تأخير بدون إذن', 30],
                            ] as $field => $data)
                            <div class="col-md-6">
                                <div class="customer-service-reviews-form-group">
                                    <label class="customer-service-reviews-form-label">
                                        {{ $data[0] }}
                                        <small class="text-muted">({{ $data[1] }} نقطة)</small>
                                    </label>
                                    <input type="number" name="{{ $field }}" value="{{ old($field, 0) }}" min="0" max="{{ $data[1] }}" class="customer-service-reviews-form-control" required placeholder="الحد الأقصى {{ $data[1] }}">
                                    @error($field)
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-section">
                        <h4 class="form-section-title">خصم إضافي</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="customer-service-reviews-form-group">
                                    <label class="customer-service-reviews-form-label">خصم إضافي</label>
                                    <input type="number" name="additional_deduction" value="{{ old('additional_deduction', 0) }}" min="0" class="customer-service-reviews-form-control" required>
                                    @error('additional_deduction')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4 class="form-section-title">الإجمالي</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="customer-service-reviews-form-group">
                                    <label class="customer-service-reviews-form-label">إجمالي المرتب</label>
                                    <input type="number" step="0.01" name="total_salary" value="{{ old('total_salary', 0) }}" min="0" class="customer-service-reviews-form-control" required>
                                    @error('total_salary')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4 class="form-section-title">ملاحظات</h4>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="customer-service-reviews-form-group">
                                    <label class="customer-service-reviews-form-label">ملاحظات</label>
                                    <textarea name="notes" rows="3" class="customer-service-reviews-form-control">{{ old('notes') }}</textarea>
                                    @error('notes')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" onclick="window.history.back()" class="customer-service-reviews-btn customer-service-reviews-btn-secondary">
                            <i class="fas fa-arrow-right me-1"></i> إلغاء
                        </button>
                        <button type="submit" class="customer-service-reviews-btn customer-service-reviews-btn-primary">
                            <i class="fas fa-save me-1"></i> حفظ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection