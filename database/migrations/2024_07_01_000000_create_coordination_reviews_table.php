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
        Schema::create('coordination_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('review_month', 7)->comment('شهر التقييم (YYYY-MM)');
            $table->integer('documentation_delivery_score')->default(0)->comment('تقديل الدراسة وتوقيع أوإرسال الدراسة بجميع مرفقاتها');
            $table->integer('daily_delivery_score')->default(0)->comment('تسليم حد أدنى 3 دراسات يوميا او دراستين مع تعديلات في حالة توافق الدراسات من الفريق التنفيذي');
            $table->integer('scheduling_score')->default(0)->comment('أن لا يتخطى وقت تنسيق وتقفيل الدراسة 2:30');
            $table->integer('error_free_delivery_score')->default(0)->comment('تسليم الدراسة بدون أخطاء تشمل (تنسيقات أو الجداول أو الخطوط أو أخطاء إملائية، ورق الشركة الجديدة، العلامة المائية)');
            $table->integer('schedule_follow_up_score')->default(0)->comment('متابعة جدول الأسبوع مع الفريق التنفيذي بشكل يوم (مشروعات - تعديلات - تهائيات)');
            $table->integer('no_previous_drafts_score')->default(0)->comment('التأكد من عدم وجود أي كلمات من مسودات سابقة');
            $table->integer('no_design_errors_score')->default(0)->comment('التأكد من عدم وجود أي كلمات من أخطاء بالتصاميم');
            $table->integer('follow_up_modifications_score')->default(0)->comment('متابعة ( التعديلات - التهائيات)');
            $table->integer('presentations_score')->default(0)->comment('عمل عروض تقديمية بوربوينت للمشاريع التي تم تسليمها وملخصات');
            $table->integer('customer_service_score')->default(0)->comment('متابعة التسليمات اليومية مع خدمة العملاء');
            $table->integer('project_monitoring_score')->default(0)->comment('عمل أرشفة و متابعة لجميع المشاريع');
            $table->integer('feedback_score')->default(0)->comment('ملف ثبيت بالملاحظات على الدراسات + متابعة العملاء+التعديلات و التسليمات بالموعد');
            $table->integer('team_leader_evaluation_score')->default(0)->comment('تقييم التيم ليدر');
            $table->integer('hr_evaluation_score')->default(0)->comment('تقييم hr');
            $table->integer('total_score')->default(0)->comment('المجموع');
            $table->integer('bonus_score')->default(0)->comment('البونص');
            $table->integer('required_deliveries_score')->default(0)->comment('تخطى العدد المحدد لتسليم الدراسات');
            $table->integer('seo_score')->default(0)->comment('الحصول على 10 مواقع خاصة (التصحيح اللغوي - التحرير ال ال PDF - التصميم - البيانات التحليلية وغيرها من القوالب الجاهزة )');
            $table->integer('portfolio_score')->default(0)->comment('عمل داتا بورد بالمشاريع للأعوام السابقة وفرز القطاعات منها (خدمى - تجارى - صناعى - زراعى )');
            $table->integer('proposal_score')->default(0)->comment('تقديم عرض او مقترح جديد في طريقة تطوير عرض واخراج الدراسة');
            $table->integer('company_idea_score')->default(0)->comment('تقديم فكرة تطويرية للشركة بشرط أن تكون الفكرة جديدة قابلة للقياس و التنفيذ (و يتم إرسالها إلى المدير المباشر- مدير المشروعات - المدير التنفيذى- الآدمن )');
            $table->integer('total_after_deductions')->default(0)->comment('الإجمالي بعد الخصم');
            $table->integer('old_draft_penalty')->default(0)->comment('وجود كلمات من مسودة قديمة -10 لكل دراسة');
            $table->integer('design_error_penalty')->default(0)->comment('أخطاء بالتصاميم أو التنسيقات بعد تقفيل الدراسة -5 لكل دراسة');
            $table->integer('daily_commitment_penalty')->default(0)->comment('عدم الالتزام بتسليم المشروعات والتعديلات المطلوبه خلال اليوم');
            $table->integer('review_failure_penalty')->default(0)->comment('عدم تسليم المسودة الصحيحة للأقسام الثلاثة ناتج عن عدم المراجعة بشكل دقيق');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coordination_reviews');
    }
};
