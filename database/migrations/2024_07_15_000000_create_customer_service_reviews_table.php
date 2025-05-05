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
        Schema::create('customer_service_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('review_month', 7)->comment('شهر التقييم (YYYY-MM)');

            // بنود التقييم الإيجابية
            $table->integer('client_interaction_score')->default(0)->comment('نسبة التفاعل مع العملاء');
            $table->integer('client_contract_score')->default(0)->comment('نسبة التعاقد مع العملاء');
            $table->integer('client_communication_speed_score')->default(0)->comment('سرعة التواصل مع العملاء');
            $table->integer('final_collection_score')->default(0)->comment('تحصيل النهائي');
            $table->integer('client_data_recording_score')->default(0)->comment('تسجيل بيانات جميع العملاء بشكل مفصل على جميع الشيتات');
            $table->integer('project_archiving_score')->default(0)->comment('أرشفة ( التقارير - المكالمات - الاسئلة - الإجابات ) كل ما يخص العميل على سرفر تحليل المشروعات');
            $table->integer('after_sales_service_score')->default(0)->comment('خدمة ما بعد البيع ( إرسال كافة الخدمات للعميل - إرسال كوبون للعميل الفعلي )');
            $table->integer('team_coordination_score')->default(0)->comment('حسن التواصل والتنسيق مع الفريق التنفيذي');
            $table->integer('client_followup_quality_score')->default(0)->comment('جودة متابعة ومراجعة العميل بشكل مستمر وفعال ( متابعة أولى / متابعة تانيه)');
            $table->integer('customer_service_archiving_score')->default(0)->comment('أرشفة العقود والحوالات والدراسات على سرفر خدمة العملاء');
            $table->integer('client_evaluation_score')->default(0)->comment('تحصيل تقييم من العملاء (فعلي – محتمل) ومعرفة من منهم نفذ المشروع بشكل فعلي');
            $table->integer('team_leader_tasks_score')->default(0)->comment('عمل المهام المطلوبة من التيم ليدر بكفاءة عالية');
            $table->integer('average_sales_score')->default(0)->comment('متوسط المبيعات');
            $table->integer('daily_report_commitment_score')->default(0)->comment('الإلتزام بكتابة التقرير اليومي بشكل مفصل');

            // نقاط بالسالب
            $table->integer('excess_services_penalty')->default(0)->comment('حصول العميل على خدمات أكثر من المتفق عليه بسبب سوء التنسيق');
            $table->integer('unauthorized_discount_penalty')->default(0)->comment('التخفيض ( بلا داعي / بدون إذن )مع العملاء');
            $table->integer('contract_mismatch_penalty')->default(0)->comment('عدم تنفيذ عقد او فاتورة بالخدمة اللي تم الاتفاق بها مع العميل');
            $table->integer('team_conflict_penalty')->default(0)->comment('الخلافات بين أفراد الفريق أو بين الفريق وأي فريق أخر لأسباب تنسيقية تتعلق بالعمل');
            $table->integer('personal_phone_use_penalty')->default(0)->comment('استخدام الهاتف الشخصي أثناء العمل');
            $table->integer('absence_late_penalty')->default(0)->comment('غياب / تأخير بدون إذن');

            // بونص إضافي والخصومات
            $table->integer('additional_bonus')->default(0)->comment('بونص إضافي');
            $table->integer('additional_deduction')->default(0)->comment('خصم إضافي');

            // مجاميع
            $table->integer('total_score')->default(0)->comment('المجموع الكلي');
            $table->integer('total_after_deductions')->default(0)->comment('المجموع بعد الخصم');
            $table->decimal('total_salary', 10, 2)->default(0)->comment('إجمالي المرتب');
            $table->decimal('percentage', 5, 2)->default(0)->comment('النسبة المئوية');

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_service_reviews');
    }
};
