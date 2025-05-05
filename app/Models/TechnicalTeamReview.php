<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class TechnicalTeamReview extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

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
