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
    Schema::create('marketing_reviews', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->foreignId('reviewer_id')->nullable()->constrained('users')->onDelete('set null');
      $table->string('review_month', 7)->comment('شهر التقييم (YYYY-MM)');

      // بنود التقييم الإيجابية
      $table->integer('finish_before_deadline_score')->default(0)->comment('الانتهاء من التاسك قبل الموعد المحدد');
      $table->integer('deliver_on_time_score')->default(0)->comment('تسليم المشروع في الوقت المحدد له');
      $table->integer('deliver_complete_project_score')->default(0)->comment('تسليم المشروع كامل بجميع مرفقاته');
      $table->integer('project_formatting_score')->default(0)->comment('تنسيقات المشروع كاملة');
      $table->integer('no_project_revisions_score')->default(0)->comment('عدم ارجاع أي تعديلات على المشروع');
      $table->integer('continuous_update_score')->default(0)->comment('تحديث الدراسة باستمرار وعدم ترك أي بيانات قديمة');
      $table->integer('competitor_analysis_score')->default(0)->comment('تحليل المنافسين');
      $table->integer('data_presentation_change_score')->default(0)->comment('تغيير طريقة عرض البيانات');
      $table->integer('project_sheet_update_score')->default(0)->comment('تعديل على شيت المشروعات');
      $table->integer('timing_sheet_completion_score')->default(0)->comment('ملئ شيت التوقيتات');

      // بنود التطوير
      $table->integer('new_business_ideas_score')->default(0)->comment('إضافة أفكار تجارية جديدة');
      $table->integer('new_sources_score')->default(0)->comment('الحصول على مصادر جديدة');
      $table->integer('new_demand_measurement_score')->default(0)->comment('قياس حجم الطلب بطريقة جديدة');
      $table->integer('team_leader_tasks_score')->default(0)->comment('عمل تاسكات من التيم ليدر');
      $table->integer('economic_impact_score')->default(0)->comment('توضيح تأثير الاقتصاد على المشروع');
      $table->integer('economic_report_score')->default(0)->comment('عمل تقرير أو تحليل اقتصادي');
      $table->integer('new_data_sources_score')->default(0)->comment('تجميع مواقع ومصادر جديدة للبيانات');
      $table->integer('client_calls_score')->default(0)->comment('مكالمة عملاء');
      $table->integer('potential_client_calls_score')->default(0)->comment('مكالمة عملاء محتملين');
      $table->integer('project_questions_score')->default(0)->comment('وضع أسئلة للمشروع بمجرد النزول');
      $table->integer('project_followup_score')->default(0)->comment('متابعة المشاريع بشكل دوري');
      $table->integer('team_leader_evaluation_score')->default(0)->comment('تقييم التيم ليدر');
      $table->integer('hr_evaluation_score')->default(0)->comment('تقييم HR');

      // نقاط بالسالب
      $table->integer('core_revisions_penalty')->default(0)->comment('تعديلات جوهرية من التيم ليدر');
      $table->integer('spelling_errors_penalty')->default(0)->comment('أخطاء املائية وهمزات');
      $table->integer('content_errors_penalty')->default(0)->comment('إخطاء بالمحتوى');
      $table->integer('minimum_projects_penalty')->default(0)->comment('عدم تسليم الحد الأدني للمشروعات');
      $table->integer('old_draft_words_penalty')->default(0)->comment('كلمات من مسودات سابقة');
      $table->integer('sheets_commitment_penalty')->default(0)->comment('عدم الالتزام بملئ الشيتات');
      $table->integer('work_behavior_penalty')->default(0)->comment('طريقة التعامل وسلوكيات العمل');
      $table->integer('revisions_commitment_penalty')->default(0)->comment('عدم الالتزام بالتعديلات');

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
    Schema::dropIfExists('marketing_reviews');
  }
};
