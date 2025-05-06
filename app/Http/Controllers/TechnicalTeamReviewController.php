<?php

namespace App\Http\Controllers;

use App\Models\TechnicalTeamReview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;


class TechnicalTeamReviewController extends Controller
{
    /**
     * Display a listing of the reviews.
     */
    public function index(Request $request)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('view_technical_team_review')) {
            abort(403, 'غير مصرح لك بعرض التقييمات');
        }

        $query = TechnicalTeamReview::with(['user', 'reviewer']);
        $user = Auth::user();

        // If user is a team leader or department manager, only show reviews for their team members
        if ($user->hasRole(['technical_team_leader', 'technical_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            // Get team members IDs
            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();

            // Filter reviews to only include team members and exclude the current user
            $query->whereIn('user_id', $teamMemberIds)
                  ->where('user_id', '!=', $user->id);
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
        if ($user->hasRole(['technical_team_leader', 'technical_department_manager'])) {
            if ($user->currentTeam) {
                $users = $user->currentTeam->users()->orderBy('name')->get();
            } else {
                $users = collect([$user]);
            }
        } else {
            // For admins and others with full access
            $users = User::orderBy('name')->get();
        }

        return view('technical-team-reviews.index', compact('reviews', 'users'));
    }

    /**
     * Show the form for creating a new review.
     */
    public function create()
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('create_technical_team_review')) {
            abort(403, 'غير مصرح لك بإنشاء تقييم جديد');
        }

        $user = Auth::user();

        // Get users based on permissions
        if ($user->hasRole(['technical_team_leader', 'technical_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }
            $users = $user->currentTeam->users()->orderBy('name')->get();
        } else {
            // For admins and others with full access
            $users = User::orderBy('name')->get();
        }

        return view('technical-team-reviews.create', compact('users'));
    }

    /**
     * Store a newly created review in storage.
     */
    public function store(Request $request)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('create_technical_team_review')) {
            abort(403, 'غير مصرح لك بإنشاء تقييم جديد');
        }

        $user = Auth::user();
        $validated = $this->validateReview($request);

        // Check if the user being reviewed is in the reviewer's team
        if ($user->hasRole(['technical_team_leader', 'technical_department_manager'])) {
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

        // حساب العمولة بناءً على المبلغ ونسبة العمولة
        if (isset($validated['sales_amount']) && $validated['sales_amount'] > 0) {
            $validated['sales_commission'] = ($validated['sales_amount'] * $validated['sales_commission_percentage']) / 100;
        }

        TechnicalTeamReview::create($validated);

        return redirect()->route('technical-team-reviews.index')
            ->with('success', 'تم إنشاء التقييم بنجاح');
    }

    /**
     * Display the specified review.
     */
    public function show(TechnicalTeamReview $technicalTeamReview)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('view_technical_team_review')) {
            abort(403, 'غير مصرح لك بعرض هذا التقييم');
        }

        $user = Auth::user();

        // Check if the review belongs to the reviewer's team
        if ($user->hasRole(['technical_team_leader', 'technical_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($technicalTeamReview->user_id, $teamMemberIds) && $technicalTeamReview->user_id != $user->id) {
                abort(403, 'لا يمكنك عرض تقييم لشخص ليس في فريقك');
            }
        }

        return view('technical-team-reviews.show', [
            'review' => $technicalTeamReview->load(['user', 'reviewer'])
        ]);
    }

    /**
     * Show the form for editing the specified review.
     */
    public function edit(TechnicalTeamReview $technicalTeamReview)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('update_technical_team_review')) {
            abort(403, 'غير مصرح لك بتعديل هذا التقييم');
        }

        $user = Auth::user();

        // Prevent users from editing their own reviews
        if ($technicalTeamReview->user_id == $user->id) {
            abort(403, 'لا يمكنك تعديل تقييمك الخاص');
        }

        // Check if the review belongs to the reviewer's team
        if ($user->hasRole(['technical_team_leader', 'technical_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($technicalTeamReview->user_id, $teamMemberIds) && $technicalTeamReview->user_id != $user->id) {
                abort(403, 'لا يمكنك تعديل تقييم لشخص ليس في فريقك');
            }
        }

        // Get users based on permissions
        if ($user->hasRole(['technical_team_leader', 'technical_department_manager'])) {
            if ($user->currentTeam) {
                $users = $user->currentTeam->users()->orderBy('name')->get();
            } else {
                $users = collect([$user]);
            }
        } else {
            // For admins and others with full access
            $users = User::orderBy('name')->get();
        }

        return view('technical-team-reviews.edit', [
            'review' => $technicalTeamReview,
            'users' => $users
        ]);
    }

    /**
     * Update the specified review in storage.
     */
    public function update(Request $request, TechnicalTeamReview $technicalTeamReview)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('update_technical_team_review')) {
            abort(403, 'غير مصرح لك بتعديل هذا التقييم');
        }

        $user = Auth::user();

        // Prevent users from updating their own reviews
        if ($technicalTeamReview->user_id == $user->id) {
            abort(403, 'لا يمكنك تعديل تقييمك الخاص');
        }

        $validated = $this->validateReview($request);

        // Check if the user being reviewed is in the reviewer's team
        if ($user->hasRole(['technical_team_leader', 'technical_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($validated['user_id'], $teamMemberIds) && $validated['user_id'] != $user->id) {
                abort(403, 'لا يمكنك تعديل تقييم لشخص ليس في فريقك');
            }
        }

        // حساب العمولة بناءً على المبلغ ونسبة العمولة
        if (isset($validated['sales_amount']) && $validated['sales_amount'] > 0) {
            $validated['sales_commission'] = ($validated['sales_amount'] * $validated['sales_commission_percentage']) / 100;
        }

        $technicalTeamReview->update($validated);

        return redirect()->route('technical-team-reviews.index')
            ->with('success', 'تم تحديث التقييم بنجاح');
    }

    /**
     * Remove the specified review from storage.
     */
    public function destroy(TechnicalTeamReview $technicalTeamReview)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('delete_technical_team_review')) {
            abort(403, 'غير مصرح لك بحذف هذا التقييم');
        }

        $user = Auth::user();

        // Check if the review belongs to the reviewer's team
        if ($user->hasRole(['technical_team_leader', 'technical_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($technicalTeamReview->user_id, $teamMemberIds) && $technicalTeamReview->user_id != $user->id) {
                abort(403, 'لا يمكنك حذف تقييم لشخص ليس في فريقك');
            }
        }

        $technicalTeamReview->delete();

        return redirect()->route('technical-team-reviews.index')
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
            'monthly_project_target_score' => ['required', 'integer', 'min:0'],
            'finish_before_deadline_score' => ['required', 'integer', 'min:0'],
            'deliver_on_time_score' => ['required', 'integer', 'min:0', 'max:10'],
            'deliver_complete_project_score' => ['required', 'integer', 'min:0', 'max:10'],
            'price_quote_comparison_score' => ['required', 'integer', 'min:0'],
            'operation_plan_delivery_score' => ['required', 'integer', 'min:0', 'max:0'],
            'project_formatting_score' => ['required', 'integer', 'min:0', 'max:10'],
            'no_project_revisions_score' => ['required', 'integer', 'min:0', 'max:10'],
            'continuous_update_score' => ['required', 'integer', 'min:0', 'max:10'],
            'industry_standards_score' => ['required', 'integer', 'min:0', 'max:25'],
            'project_sheet_update_score' => ['required', 'integer', 'min:0', 'max:10'],
            'final_product_price_score' => ['required', 'integer', 'min:0', 'max:24'],
            'legal_risks_score' => ['required', 'integer', 'min:0', 'max:10'],
            'study_development_proposals_score' => ['required', 'integer', 'min:0'],
            'company_ideas_score' => ['required', 'integer', 'min:0'],
            'other_project_revisions_score' => ['required', 'integer', 'min:0'],
            'non_project_task_score' => ['required', 'integer', 'min:0'],
            'new_data_sources_score' => ['required', 'integer', 'min:0', 'max:15'],
            'client_calls_score' => ['required', 'integer', 'min:0', 'max:10'],
            'potential_client_calls_score' => ['required', 'integer', 'min:0'],
            'project_questions_score' => ['required', 'integer', 'min:0', 'max:10'],
            'project_followup_score' => ['required', 'integer', 'min:0', 'max:10'],
            'client_addition_score' => ['required', 'integer', 'min:0'],
            'urgent_projects_score' => ['required', 'integer', 'min:0'],
            'direct_delivery_projects_score' => ['required', 'integer', 'min:0'],
            'no_leave_score' => ['required', 'integer', 'min:0'],
            'workshop_participation_score' => ['required', 'integer', 'min:0'],
            'team_leader_evaluation_score' => ['required', 'integer', 'min:0', 'max:10'],
            'hr_evaluation_score' => ['required', 'integer', 'min:0', 'max:10'],
            'core_revisions_penalty' => ['required', 'integer', 'min:0', 'max:14'],
            'spelling_errors_penalty' => ['required', 'integer', 'min:0', 'max:14'],
            'content_errors_penalty' => ['required', 'integer', 'min:0', 'max:8'],
            'minimum_projects_penalty' => ['required', 'integer', 'min:0'],
            'old_draft_words_penalty' => ['required', 'integer', 'min:0', 'max:15'],
            'sheets_commitment_penalty' => ['required', 'integer', 'min:0', 'max:20'],
            'questions_neglect_penalty' => ['required', 'integer', 'min:0', 'max:20'],
            'work_behavior_penalty' => ['required', 'integer', 'min:0', 'max:10'],
            'revisions_commitment_penalty' => ['required', 'integer', 'min:0', 'max:12'],
            'sales_amount' => ['nullable', 'numeric', 'min:0'],
            'sales_commission_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'total_salary' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
