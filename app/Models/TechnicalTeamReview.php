<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class TechnicalTeamReview extends Model implements Auditable
{
    use HasFactory, AuditableTrait, SoftDeletes;

    /**
     * The audit events to record
     *
     * @var array
     */
    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

    /**
     * Attributes to include in the Audit.
     *
     * @var array
     */
    protected $auditInclude = [
        'user_id',
        'reviewer_id',
        'review_month',
        'monthly_project_target_score',
        'finish_before_deadline_score',
        'deliver_on_time_score',
        'deliver_complete_project_score',
        'price_quote_comparison_score',
        'operation_plan_delivery_score',
        'project_formatting_score',
        'no_project_revisions_score',
        'continuous_update_score',
        'industry_standards_score',
        'project_sheet_update_score',
        'final_product_price_score',
        'legal_risks_score',
        'study_development_proposals_score',
        'company_ideas_score',
        'other_project_revisions_score',
        'non_project_task_score',
        'new_data_sources_score',
        'client_calls_score',
        'potential_client_calls_score',
        'project_questions_score',
        'project_followup_score',
        'client_addition_score',
        'urgent_projects_score',
        'direct_delivery_projects_score',
        'no_leave_score',
        'workshop_participation_score',
        'team_leader_evaluation_score',
        'hr_evaluation_score',
        'core_revisions_penalty',
        'spelling_errors_penalty',
        'content_errors_penalty',
        'minimum_projects_penalty',
        'old_draft_words_penalty',
        'sheets_commitment_penalty',
        'questions_neglect_penalty',
        'work_behavior_penalty',
        'revisions_commitment_penalty',
        'sales_commission',
        'sales_commission_percentage',
        'sales_amount',
        'total_score',
        'total_after_deductions',
        'total_salary',
        'notes',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'reviewer_id',
        'review_month',
        'monthly_project_target_score',
        'finish_before_deadline_score',
        'deliver_on_time_score',
        'deliver_complete_project_score',
        'price_quote_comparison_score',
        'operation_plan_delivery_score',
        'project_formatting_score',
        'no_project_revisions_score',
        'continuous_update_score',
        'industry_standards_score',
        'project_sheet_update_score',
        'final_product_price_score',
        'legal_risks_score',
        'study_development_proposals_score',
        'company_ideas_score',
        'other_project_revisions_score',
        'non_project_task_score',
        'new_data_sources_score',
        'client_calls_score',
        'potential_client_calls_score',
        'project_questions_score',
        'project_followup_score',
        'client_addition_score',
        'urgent_projects_score',
        'direct_delivery_projects_score',
        'no_leave_score',
        'workshop_participation_score',
        'team_leader_evaluation_score',
        'hr_evaluation_score',
        'core_revisions_penalty',
        'spelling_errors_penalty',
        'content_errors_penalty',
        'minimum_projects_penalty',
        'old_draft_words_penalty',
        'sheets_commitment_penalty',
        'questions_neglect_penalty',
        'work_behavior_penalty',
        'revisions_commitment_penalty',
        'sales_commission',
        'sales_commission_percentage',
        'sales_amount',
        'total_score',
        'total_after_deductions',
        'total_salary',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'total_salary' => 'decimal:2',
        'sales_commission' => 'decimal:2',
        'sales_commission_percentage' => 'decimal:2',
        'sales_amount' => 'decimal:2',
    ];

    /**
     * Get the user that was reviewed.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who performed the review.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the criteria associated with this review.
     * This returns all the criteria as a relationship for the show view.
     */
    public function criteria()
    {
        // This method returns the review criteria formatted for display
        // We're returning the scores as a collection of objects for consistency with other models
        $scoreFields = [
            'monthly_project_target_score' => 'مستهدف المشاريع الشهري',
            'finish_before_deadline_score' => 'إنهاء المشاريع قبل الموعد المحدد',
            'deliver_on_time_score' => 'التسليم في الوقت المحدد',
            'deliver_complete_project_score' => 'تسليم المشروع كاملاً',
            'price_quote_comparison_score' => 'مقارنة عروض الأسعار',
            'operation_plan_delivery_score' => 'تسليم خطة العمليات',
            'project_formatting_score' => 'تنسيق المشروع',
            'no_project_revisions_score' => 'عدم وجود مراجعات للمشروع',
            'continuous_update_score' => 'التحديث المستمر',
            'industry_standards_score' => 'معايير الصناعة',
            'project_sheet_update_score' => 'تحديث ورقة المشروع',
            'final_product_price_score' => 'سعر المنتج النهائي',
            'legal_risks_score' => 'المخاطر القانونية',
            'study_development_proposals_score' => 'مقترحات تطوير الدراسة',
            'company_ideas_score' => 'أفكار الشركة',
            'other_project_revisions_score' => 'مراجعات المشروع الأخرى',
            'non_project_task_score' => 'المهام غير المتعلقة بالمشروع',
            'new_data_sources_score' => 'مصادر بيانات جديدة',
            'client_calls_score' => 'مكالمات العملاء',
            'potential_client_calls_score' => 'مكالمات العملاء المحتملين',
            'project_questions_score' => 'أسئلة المشروع',
            'project_followup_score' => 'متابعة المشروع',
            'client_addition_score' => 'إضافة العملاء',
            'urgent_projects_score' => 'المشاريع العاجلة',
            'direct_delivery_projects_score' => 'مشاريع التسليم المباشر',
            'no_leave_score' => 'عدم طلب إجازة',
            'workshop_participation_score' => 'المشاركة في ورش العمل',
            'team_leader_evaluation_score' => 'تقييم قائد الفريق',
            'hr_evaluation_score' => 'تقييم الموارد البشرية',
        ];

        $penaltyFields = [
            'core_revisions_penalty' => 'غرامة المراجعات الأساسية',
            'spelling_errors_penalty' => 'غرامة أخطاء الإملاء',
            'content_errors_penalty' => 'غرامة أخطاء المحتوى',
            'minimum_projects_penalty' => 'غرامة الحد الأدنى للمشاريع',
            'old_draft_words_penalty' => 'غرامة كلمات المسودة القديمة',
            'sheets_commitment_penalty' => 'غرامة الالتزام بالأوراق',
            'questions_neglect_penalty' => 'غرامة إهمال الأسئلة',
            'work_behavior_penalty' => 'غرامة سلوك العمل',
            'revisions_commitment_penalty' => 'غرامة الالتزام بالمراجعات',
        ];

        $criteria = collect();

        // Add positive score criteria
        foreach ($scoreFields as $field => $name) {
            $criteria->push((object)[
                'id' => $field,
                'name' => $name,
                'score' => $this->$field,
                'type' => 'score'
            ]);
        }

        // Add penalty criteria
        foreach ($penaltyFields as $field => $name) {
            $criteria->push((object)[
                'id' => $field,
                'name' => $name,
                'score' => $this->$field,
                'type' => 'penalty'
            ]);
        }

        return $criteria;
    }

    /**
     * Calculate the total score by summing up all evaluation criteria scores.
     */
    public function calculateTotalScore(): int
    {
        $positiveScoreFields = [
            'monthly_project_target_score',
            'finish_before_deadline_score',
            'deliver_on_time_score',
            'deliver_complete_project_score',
            'price_quote_comparison_score',
            'operation_plan_delivery_score',
            'project_formatting_score',
            'no_project_revisions_score',
            'continuous_update_score',
            'industry_standards_score',
            'project_sheet_update_score',
            'final_product_price_score',
            'legal_risks_score',
            'study_development_proposals_score',
            'company_ideas_score',
            'other_project_revisions_score',
            'non_project_task_score',
            'new_data_sources_score',
            'client_calls_score',
            'potential_client_calls_score',
            'project_questions_score',
            'project_followup_score',
            'client_addition_score',
            'urgent_projects_score',
            'direct_delivery_projects_score',
            'no_leave_score',
            'workshop_participation_score',
            'team_leader_evaluation_score',
            'hr_evaluation_score',
        ];

        $total = 0;
        foreach ($positiveScoreFields as $field) {
            $total += $this->$field;
        }

        return $total;
    }

    /**
     * Calculate the total score after deductions.
     */
    public function calculateTotalAfterDeductions(): int
    {
        $penaltyFields = [
            'core_revisions_penalty',
            'spelling_errors_penalty',
            'content_errors_penalty',
            'minimum_projects_penalty',
            'old_draft_words_penalty',
            'sheets_commitment_penalty',
            'questions_neglect_penalty',
            'work_behavior_penalty',
            'revisions_commitment_penalty',
        ];

        $total = $this->calculateTotalScore();
        $deductions = 0;

        foreach ($penaltyFields as $field) {
            $deductions += $this->$field;
        }

        return max(0, $total - $deductions);
    }

    /**
     * Calculate the sales commission based on the sales amount and commission percentage.
     */
    public function calculateSalesCommission(): float
    {
        return ($this->sales_amount * $this->sales_commission_percentage) / 100;
    }

    /**
     * Boot method to automatically calculate and set total_score and total_after_deductions
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($review) {
            $review->total_score = $review->calculateTotalScore();
            $review->total_after_deductions = $review->calculateTotalAfterDeductions();
            $review->sales_commission = $review->calculateSalesCommission();
        });
    }
}
