<?php

namespace App\Http\Controllers;

use App\Models\MarketingReview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class MarketingReviewController extends Controller
{
    /**
     * Display a listing of the reviews.
     */
    public function index(Request $request)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('view_marketing_review')) {
            abort(403, 'غير مصرح لك بعرض التقييمات');
        }

        $query = MarketingReview::with(['user', 'reviewer']);
        $user = Auth::user();

        // If user is a team leader or department manager, only show reviews for their team members
        if ($user->hasRole(['marketing_team_leader', 'marketing_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            // Get team members IDs
            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();

            // Add current user's ID to the list
            $teamMemberIds[] = $user->id;

            // Filter reviews to only include team members
            $query->whereIn('user_id', $teamMemberIds);
        }

        // Filter by user if requested
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by month and year if requested
        if ($request->has('review_month')) {
            $query->where('review_month', $request->review_month);
        }

        $reviews = $query->latest()->paginate(10);

        // Get users based on permissions
        if ($user->hasRole(['marketing_team_leader', 'marketing_department_manager'])) {
            if ($user->currentTeam) {
                $users = $user->currentTeam->users()->orderBy('name')->get();
            } else {
                $users = collect([$user]);
            }
        } else {
            // For admins and others with full access
            $users = User::orderBy('name')->get();
        }

        return view('marketing-reviews.index', compact('reviews', 'users'));
    }

    /**
     * Show the form for creating a new review.
     */
    public function create()
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('create_marketing_review')) {
            abort(403, 'غير مصرح لك بإنشاء تقييم جديد');
        }

        $user = Auth::user();

        // Get users based on permissions
        if ($user->hasRole(['marketing_team_leader', 'marketing_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }
            $users = $user->currentTeam->users()->orderBy('name')->get();
        } else {
            // For admins and others with full access
            $users = User::orderBy('name')->get();
        }

        return view('marketing-reviews.create', compact('users'));
    }

    /**
     * Store a newly created review in storage.
     */
    public function store(Request $request)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('create_marketing_review')) {
            abort(403, 'غير مصرح لك بإنشاء تقييم جديد');
        }

        $user = Auth::user();
        $validated = $this->validateReview($request);

        // Check if the user being reviewed is in the reviewer's team
        if ($user->hasRole(['marketing_team_leader', 'marketing_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($validated['user_id'], $teamMemberIds) && $validated['user_id'] != $user->id) {
                abort(403, 'لا يمكنك إنشاء تقييم لشخص ليس في فريقك');
            }
        }

        $validated['reviewer_id'] = Auth::id();

        // إذا لم يتم تحديد شهر التقييم، استخدم الشهر والسنة الحالية
        $validated['review_month'] = $validated['review_month'] ?? now()->format('Y-m');

        MarketingReview::create($validated);

        return redirect()->route('marketing-reviews.index')
            ->with('success', 'تم إنشاء التقييم بنجاح');
    }

    /**
     * Display the specified review.
     */
    public function show(MarketingReview $marketingReview)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('view_marketing_review')) {
            abort(403, 'غير مصرح لك بعرض هذا التقييم');
        }

        $user = Auth::user();

        // Check if the review belongs to the reviewer's team
        if ($user->hasRole(['marketing_team_leader', 'marketing_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($marketingReview->user_id, $teamMemberIds) && $marketingReview->user_id != $user->id) {
                abort(403, 'لا يمكنك عرض تقييم لشخص ليس في فريقك');
            }
        }

        return view('marketing-reviews.show', [
            'review' => $marketingReview->load(['user', 'reviewer'])
        ]);
    }

    /**
     * Show the form for editing the specified review.
     */
    public function edit(MarketingReview $marketingReview)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('update_marketing_review')) {
            abort(403, 'غير مصرح لك بتعديل هذا التقييم');
        }

        $user = Auth::user();

        // Prevent users from editing their own reviews
        if ($marketingReview->user_id == $user->id) {
            abort(403, 'لا يمكنك تعديل تقييمك الخاص');
        }

        // Check if the review belongs to the reviewer's team
        if ($user->hasRole(['marketing_team_leader', 'marketing_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($marketingReview->user_id, $teamMemberIds) && $marketingReview->user_id != $user->id) {
                abort(403, 'لا يمكنك تعديل تقييم لشخص ليس في فريقك');
            }
        }

        // Get users based on permissions
        if ($user->hasRole(['marketing_team_leader', 'marketing_department_manager'])) {
            if ($user->currentTeam) {
                $users = $user->currentTeam->users()->orderBy('name')->get();
            } else {
                $users = collect([$user]);
            }
        } else {
            // For admins and others with full access
            $users = User::orderBy('name')->get();
        }

        return view('marketing-reviews.edit', [
            'review' => $marketingReview,
            'users' => $users
        ]);
    }

    /**
     * Update the specified review in storage.
     */
    public function update(Request $request, MarketingReview $marketingReview)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('update_marketing_review')) {
            abort(403, 'غير مصرح لك بتعديل هذا التقييم');
        }

        $user = Auth::user();

        // Prevent users from updating their own reviews
        if ($marketingReview->user_id == $user->id) {
            abort(403, 'لا يمكنك تعديل تقييمك الخاص');
        }

        $validated = $this->validateReview($request);

        // Check if the user being reviewed is in the reviewer's team
        if ($user->hasRole(['marketing_team_leader', 'marketing_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($validated['user_id'], $teamMemberIds) && $validated['user_id'] != $user->id) {
                abort(403, 'لا يمكنك تعديل تقييم لشخص ليس في فريقك');
            }
        }

        $marketingReview->update($validated);

        return redirect()->route('marketing-reviews.index')
            ->with('success', 'تم تحديث التقييم بنجاح');
    }

    /**
     * Remove the specified review from storage.
     */
    public function destroy(MarketingReview $marketingReview)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('delete_marketing_review')) {
            abort(403, 'غير مصرح لك بحذف هذا التقييم');
        }

        $user = Auth::user();

        // Check if the review belongs to the reviewer's team
        if ($user->hasRole(['marketing_team_leader', 'marketing_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($marketingReview->user_id, $teamMemberIds) && $marketingReview->user_id != $user->id) {
                abort(403, 'لا يمكنك حذف تقييم لشخص ليس في فريقك');
            }
        }

        $marketingReview->delete();

        return redirect()->route('marketing-reviews.index')
            ->with('success', 'تم حذف التقييم بنجاح');
    }

    /**
     * Validate the review data.
     */
    private function validateReview(Request $request)
    {
        return $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'review_month' => ['required', 'string', 'max:7'],
            'finish_before_deadline_score' => ['required', 'integer', 'min:0'],
            'deliver_on_time_score' => ['required', 'integer', 'min:0'],
            'deliver_complete_project_score' => ['required', 'integer', 'min:0'],
            'project_formatting_score' => ['required', 'integer', 'min:0'],
            'no_project_revisions_score' => ['required', 'integer', 'min:0'],
            'continuous_update_score' => ['required', 'integer', 'min:0'],
            'competitor_analysis_score' => ['required', 'integer', 'min:0'],
            'data_presentation_change_score' => ['required', 'integer', 'min:0'],
            'project_sheet_update_score' => ['required', 'integer', 'min:0'],
            'timing_sheet_completion_score' => ['required', 'integer', 'min:0'],
            'new_business_ideas_score' => ['required', 'integer', 'min:0'],
            'new_sources_score' => ['required', 'integer', 'min:0'],
            'new_demand_measurement_score' => ['required', 'integer', 'min:0'],
            'team_leader_tasks_score' => ['required', 'integer', 'min:0'],
            'economic_impact_score' => ['required', 'integer', 'min:0'],
            'economic_report_score' => ['required', 'integer', 'min:0'],
            'new_data_sources_score' => ['required', 'integer', 'min:0'],
            'client_calls_score' => ['required', 'integer', 'min:0'],
            'potential_client_calls_score' => ['required', 'integer', 'min:0'],
            'project_questions_score' => ['required', 'integer', 'min:0'],
            'project_followup_score' => ['required', 'integer', 'min:0'],
            'team_leader_evaluation_score' => ['required', 'integer', 'min:0'],
            'hr_evaluation_score' => ['required', 'integer', 'min:0'],
            'core_revisions_penalty' => ['required', 'integer', 'min:0'],
            'spelling_errors_penalty' => ['required', 'integer', 'min:0'],
            'content_errors_penalty' => ['required', 'integer', 'min:0'],
            'minimum_projects_penalty' => ['required', 'integer', 'min:0'],
            'old_draft_words_penalty' => ['required', 'integer', 'min:0'],
            'sheets_commitment_penalty' => ['required', 'integer', 'min:0'],
            'work_behavior_penalty' => ['required', 'integer', 'min:0'],
            'revisions_commitment_penalty' => ['required', 'integer', 'min:0'],
            'total_salary' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
