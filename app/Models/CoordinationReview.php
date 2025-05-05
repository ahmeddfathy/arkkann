<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class CoordinationReview extends Model implements Auditable
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
        'documentation_delivery_score',
        'daily_delivery_score',
        'scheduling_score',
        'error_free_delivery_score',
        'schedule_follow_up_score',
        'no_previous_drafts_score',
        'no_design_errors_score',
        'follow_up_modifications_score',
        'presentations_score',
        'customer_service_score',
        'project_monitoring_score',
        'feedback_score',
        'team_leader_evaluation_score',
        'hr_evaluation_score',
        'total_score',
        'bonus_score',
        'required_deliveries_score',
        'seo_score',
        'portfolio_score',
        'proposal_score',
        'company_idea_score',
        'total_after_deductions',
        'old_draft_penalty',
        'design_error_penalty',
        'daily_commitment_penalty',
        'review_failure_penalty',
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
        'documentation_delivery_score',
        'daily_delivery_score',
        'scheduling_score',
        'error_free_delivery_score',
        'schedule_follow_up_score',
        'no_previous_drafts_score',
        'no_design_errors_score',
        'follow_up_modifications_score',
        'presentations_score',
        'customer_service_score',
        'project_monitoring_score',
        'feedback_score',
        'team_leader_evaluation_score',
        'hr_evaluation_score',
        'total_score',
        'bonus_score',
        'required_deliveries_score',
        'seo_score',
        'portfolio_score',
        'proposal_score',
        'company_idea_score',
        'total_after_deductions',
        'old_draft_penalty',
        'design_error_penalty',
        'daily_commitment_penalty',
        'review_failure_penalty',
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
        $scoreFields = [
            'documentation_delivery_score',
            'daily_delivery_score',
            'scheduling_score',
            'error_free_delivery_score',
            'schedule_follow_up_score',
            'no_previous_drafts_score',
            'no_design_errors_score',
            'follow_up_modifications_score',
            'presentations_score',
            'customer_service_score',
            'project_monitoring_score',
            'feedback_score',
            'team_leader_evaluation_score',
            'hr_evaluation_score',
            'bonus_score',
            'required_deliveries_score',
            'seo_score',
            'portfolio_score',
            'proposal_score',
            'company_idea_score',
        ];

        $total = 0;
        foreach ($scoreFields as $field) {
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
            'old_draft_penalty',
            'design_error_penalty',
            'daily_commitment_penalty',
            'review_failure_penalty',
        ];

        $totalPenalties = 0;
        foreach ($penaltyFields as $field) {
            $totalPenalties += $this->$field;
        }

        return $this->calculateTotalScore() - $totalPenalties;
    }

    /**
     * Auto update the total scores before saving.
     */
    protected static function booted()
    {
        static::saving(function ($review) {
            $review->total_score = $review->calculateTotalScore();
            $review->total_after_deductions = $review->calculateTotalAfterDeductions();
        });
    }
}
