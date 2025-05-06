<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class CustomerServiceReview extends Model implements Auditable
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
        'client_interaction_score',
        'client_contract_score',
        'client_communication_speed_score',
        'final_collection_score',
        'client_data_recording_score',
        'project_archiving_score',
        'after_sales_service_score',
        'team_coordination_score',
        'client_followup_quality_score',
        'customer_service_archiving_score',
        'client_evaluation_score',
        'team_leader_tasks_score',
        'average_sales_score',
        'daily_report_commitment_score',
        'hr_evaluation_score',
        'excess_services_penalty',
        'unauthorized_discount_penalty',
        'contract_mismatch_penalty',
        'team_conflict_penalty',
        'personal_phone_use_penalty',
        'absence_late_penalty',
        'additional_bonus',
        'additional_deduction',
        'total_score',
        'total_after_deductions',
        'total_salary',
        'percentage',
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
        'client_interaction_score',
        'client_contract_score',
        'client_communication_speed_score',
        'final_collection_score',
        'client_data_recording_score',
        'project_archiving_score',
        'after_sales_service_score',
        'team_coordination_score',
        'client_followup_quality_score',
        'customer_service_archiving_score',
        'client_evaluation_score',
        'team_leader_tasks_score',
        'average_sales_score',
        'daily_report_commitment_score',
        'hr_evaluation_score',
        'excess_services_penalty',
        'unauthorized_discount_penalty',
        'contract_mismatch_penalty',
        'team_conflict_penalty',
        'personal_phone_use_penalty',
        'absence_late_penalty',
        'additional_bonus',
        'additional_deduction',
        'total_score',
        'total_after_deductions',
        'total_salary',
        'percentage',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'total_salary' => 'decimal:2',
        'percentage' => 'decimal:2',
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
            'client_interaction_score',
            'client_contract_score',
            'client_communication_speed_score',
            'final_collection_score',
            'client_data_recording_score',
            'project_archiving_score',
            'after_sales_service_score',
            'team_coordination_score',
            'client_followup_quality_score',
            'customer_service_archiving_score',
            'client_evaluation_score',
            'team_leader_tasks_score',
            'average_sales_score',
            'daily_report_commitment_score',
            'hr_evaluation_score',
            'additional_bonus',
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
            'excess_services_penalty',
            'unauthorized_discount_penalty',
            'contract_mismatch_penalty',
            'team_conflict_penalty',
            'personal_phone_use_penalty',
            'absence_late_penalty',
            'additional_deduction',
        ];

        $total = $this->calculateTotalScore();
        $deductions = 0;

        foreach ($penaltyFields as $field) {
            $deductions += $this->$field;
        }

        return max(0, $total - $deductions);
    }

    /**
     * Calculate the percentage based on the total score of 355 points.
     */
    public function calculatePercentage(): float
    {
        $maxScore = 355; // Maximum possible score (330 + 25 for HR evaluation)
        $scoreAfterDeductions = $this->calculateTotalAfterDeductions();

        return ($scoreAfterDeductions / $maxScore) * 100;
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
            $review->percentage = $review->calculatePercentage();
        });
    }
}
