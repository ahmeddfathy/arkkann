<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class MarketingReview extends Model implements Auditable
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
        'finish_before_deadline_score',
        'deliver_on_time_score',
        'deliver_complete_project_score',
        'project_formatting_score',
        'no_project_revisions_score',
        'continuous_update_score',
        'competitor_analysis_score',
        'data_presentation_change_score',
        'project_sheet_update_score',
        'timing_sheet_completion_score',
        'new_business_ideas_score',
        'new_sources_score',
        'new_demand_measurement_score',
        'team_leader_tasks_score',
        'economic_impact_score',
        'economic_report_score',
        'new_data_sources_score',
        'client_calls_score',
        'potential_client_calls_score',
        'project_questions_score',
        'project_followup_score',
        'team_leader_evaluation_score',
        'hr_evaluation_score',
        'core_revisions_penalty',
        'spelling_errors_penalty',
        'content_errors_penalty',
        'minimum_projects_penalty',
        'old_draft_words_penalty',
        'sheets_commitment_penalty',
        'work_behavior_penalty',
        'revisions_commitment_penalty',
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
        'finish_before_deadline_score',
        'deliver_on_time_score',
        'deliver_complete_project_score',
        'project_formatting_score',
        'no_project_revisions_score',
        'continuous_update_score',
        'competitor_analysis_score',
        'data_presentation_change_score',
        'project_sheet_update_score',
        'timing_sheet_completion_score',
        'new_business_ideas_score',
        'new_sources_score',
        'new_demand_measurement_score',
        'team_leader_tasks_score',
        'economic_impact_score',
        'economic_report_score',
        'new_data_sources_score',
        'client_calls_score',
        'potential_client_calls_score',
        'project_questions_score',
        'project_followup_score',
        'team_leader_evaluation_score',
        'hr_evaluation_score',
        'core_revisions_penalty',
        'spelling_errors_penalty',
        'content_errors_penalty',
        'minimum_projects_penalty',
        'old_draft_words_penalty',
        'sheets_commitment_penalty',
        'work_behavior_penalty',
        'revisions_commitment_penalty',
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
            'finish_before_deadline_score',
            'deliver_on_time_score',
            'deliver_complete_project_score',
            'project_formatting_score',
            'no_project_revisions_score',
            'continuous_update_score',
            'competitor_analysis_score',
            'data_presentation_change_score',
            'project_sheet_update_score',
            'timing_sheet_completion_score',
            'new_business_ideas_score',
            'new_sources_score',
            'new_demand_measurement_score',
            'team_leader_tasks_score',
            'economic_impact_score',
            'economic_report_score',
            'new_data_sources_score',
            'client_calls_score',
            'potential_client_calls_score',
            'project_questions_score',
            'project_followup_score',
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
            'work_behavior_penalty',
            'revisions_commitment_penalty',
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
