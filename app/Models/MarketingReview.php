<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class MarketingReview extends Model implements Auditable
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
     * Get the criteria associated with this review.
     * This returns all the criteria as a relationship for the show view.
     */
    public function criteria()
    {
        // This method returns the review criteria formatted for display
        // We're returning the scores as a collection of objects for consistency with other models
        $scoreFields = [
            'finish_before_deadline_score' => 'إنهاء المشاريع قبل الموعد المحدد',
            'deliver_on_time_score' => 'التسليم في الوقت المحدد',
            'deliver_complete_project_score' => 'تسليم المشروع كاملاً',
            'project_formatting_score' => 'تنسيق المشروع',
            'no_project_revisions_score' => 'عدم وجود مراجعات للمشروع',
            'continuous_update_score' => 'التحديث المستمر',
            'competitor_analysis_score' => 'تحليل المنافسين',
            'data_presentation_change_score' => 'تغيير عرض البيانات',
            'project_sheet_update_score' => 'تحديث ورقة المشروع',
            'timing_sheet_completion_score' => 'إكمال ورقة التوقيت',
            'new_business_ideas_score' => 'أفكار أعمال جديدة',
            'new_sources_score' => 'مصادر جديدة',
            'new_demand_measurement_score' => 'قياس الطلب الجديد',
            'team_leader_tasks_score' => 'مهام قائد الفريق',
            'economic_impact_score' => 'التأثير الاقتصادي',
            'economic_report_score' => 'التقرير الاقتصادي',
            'new_data_sources_score' => 'مصادر بيانات جديدة',
            'client_calls_score' => 'مكالمات العملاء',
            'potential_client_calls_score' => 'مكالمات العملاء المحتملين',
            'project_questions_score' => 'أسئلة المشروع',
            'project_followup_score' => 'متابعة المشروع',
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
