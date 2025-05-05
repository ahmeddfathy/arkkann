<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('technical_team_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('review_month', 7)->comment('شهر التقييم (YYYY-MM)');

            // بنود التقييم الإيجابية
            $table->integer('monthly_project_target_score')->default(0)->comment('التارجت الشهري من المشروعات 12 مشروع');
            $table->integer('finish_before_deadline_score')->default(0)->comment('الانتهاء من التاسك أو المشروع قبل الموعد المحدد');
            $table->integer('deliver_on_time_score')->default(0)->comment('تسليم المشروع في الوقت المحدد له');
            $table->integer('deliver_complete_project_score')->default(0)->comment('تسليم المشروع كامل بجميع مرفقاته');
            $table->integer('price_quote_comparison_score')->default(0)->comment('مفاضلة عروض الأسعار');
            $table->integer('operation_plan_delivery_score')->default(0)->comment('تسليم خطة التشغيل لكل المشروعات التي تم العمل عليها');
            $table->integer('project_formatting_score')->default(0)->comment('تنسيقات المشروع كاملة');
            $table->integer('no_project_revisions_score')->default(0)->comment('عدم ارجاع أي تعديلات على المشروع');
            $table->integer('continuous_update_score')->default(0)->comment('تحديث الدراسة باستمرار وعدم ترك أي بيانات قديمة');
            $table->integer('industry_standards_score')->default(0)->comment('الحصول على المواصفات القياسية لكل المشروعات الصناعية');
            $table->integer('project_sheet_update_score')->default(0)->comment('ملئ شيت المشروعات بحالة المشروع والتوقيت وكذلك شيت التعديلات');
            $table->integer('final_product_price_score')->default(0)->comment('الحصول على أسعار المنتج النهائي في السوق');
            $table->integer('legal_risks_score')->default(0)->comment('الحصول على 5 مخاطر قانونية ممكن تواجهة المشروع');
            $table->integer('study_development_proposals_score')->default(0)->comment('وضع مقترحات لتطوير الدراسة');
            $table->integer('company_ideas_score')->default(0)->comment('أفكار لتطوير الشركة');
            $table->integer('other_project_revisions_score')->default(0)->comment('تعديلات بمشروعات ليست لصاحب المشروع');
            $table->integer('non_project_task_score')->default(0)->comment('عمل تاسك ليس بالمشروع');
            $table->integer('new_data_sources_score')->default(0)->comment('تجميع مواقع ومصادر جديدة للبيانات');
            $table->integer('client_calls_score')->default(0)->comment('مكالمة عملاء');
            $table->integer('potential_client_calls_score')->default(0)->comment('مكالمة عملاء المحتملين');
            $table->integer('project_questions_score')->default(0)->comment('وضع أسئلة للمشروع بمجرد النزول');
            $table->integer('project_followup_score')->default(0)->comment('متابعة المشاريع بشكل دوري');
            $table->integer('client_addition_score')->default(0)->comment('تعديل إضافة من العميل');
            $table->integer('urgent_projects_score')->default(0)->comment('عمل مشروعات مستعجلة مع الحفاظ على الجدول الموضوع');
            $table->integer('direct_delivery_projects_score')->default(0)->comment('مشروعات يتم تسليمها للعميل مباشرة');
            $table->integer('no_leave_score')->default(0)->comment('في حالة عدم أخذ أذونات أو أجازات');
            $table->integer('workshop_participation_score')->default(0)->comment('المشاركة في ورش العمل والتدريبات');
            $table->integer('team_leader_evaluation_score')->default(0)->comment('تقييم التيم ليدر');
            $table->integer('hr_evaluation_score')->default(0)->comment('تقييم HR');

            // نقاط بالسالب
            $table->integer('core_revisions_penalty')->default(0)->comment('تعديلات جوهرية من التيم ليدر');
            $table->integer('spelling_errors_penalty')->default(0)->comment('أخطاء املائية وهمزات');
            $table->integer('content_errors_penalty')->default(0)->comment('إخطاء بالمحتوى');
            $table->integer('minimum_projects_penalty')->default(0)->comment('عدم تسليم الحد الأدني للمشروعات');
            $table->integer('old_draft_words_penalty')->default(0)->comment('كلمات من مسودات سابقة');
            $table->integer('sheets_commitment_penalty')->default(0)->comment('عدم الالتزام بملئ الشيتات');
            $table->integer('questions_neglect_penalty')->default(0)->comment('في حال تجاهل ارسال أسئلة ل 4 مشروعات');
            $table->integer('work_behavior_penalty')->default(0)->comment('طريقة التعامل وسلوكيات العمل');
            $table->integer('revisions_commitment_penalty')->default(0)->comment('عدم الالتزام بالتعديلات');

            // بونص خاص (عمولة المبيعات)
            $table->decimal('sales_commission', 10, 2)->default(0)->comment('عمولة مبيعات خطوط الإنتاج والمعدات');
            $table->decimal('sales_commission_percentage', 5, 2)->default(6.00)->comment('نسبة العمولة من قيمة المبيعات');
            $table->decimal('sales_amount', 10, 2)->default(0)->comment('قيمة المبيعات');

            // مجاميع
            $table->integer('total_score')->default(0)->comment('المجموع الكلي');
            $table->integer('total_after_deductions')->default(0)->comment('المجموع بعد الخصم');
            $table->decimal('total_salary', 10, 2)->default(0)->comment('إجمالي المرتب');

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_team_reviews');
    }
};
