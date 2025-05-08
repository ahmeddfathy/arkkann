<?php

namespace App\Http\Controllers;

use App\Models\CoordinationReview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;

class CoordinationReviewController extends Controller
{
    /**
     * Display a listing of the reviews.
     */
    public function index(Request $request)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('view_coordination_review')) {
            abort(403, 'غير مصرح لك بعرض التقييمات');
        }

        $query = CoordinationReview::with(['user', 'reviewer']);
        $user = Auth::user();

        // If user is a team leader or department manager, only show reviews for their team members
        if ($user->hasRole(['coordination_team_leader', 'coordination_department_manager'])) {
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
        if ($user->hasRole(['coordination_team_leader', 'coordination_department_manager'])) {
            if ($user->currentTeam) {
                $users = $user->currentTeam->users()->orderBy('name')->get();
            } else {
                $users = collect([$user]);
            }
        } else {
            // For admins and others with full access
            $users = User::orderBy('name')->get();
        }

        return view('coordination-reviews.index', compact('reviews', 'users'));
    }

    /**
     * Show the form for creating a new review.
     */
    public function create()
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('create_coordination_review')) {
            abort(403, 'غير مصرح لك بإنشاء تقييم جديد');
        }

        $user = Auth::user();

        // Get users based on permissions
        if ($user->hasRole(['coordination_team_leader', 'coordination_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }
            $users = $user->currentTeam->users()->orderBy('name')->get();
        } else {
            // For admins and others with full access
            $users = User::orderBy('name')->get();
        }

        return view('coordination-reviews.create', compact('users'));
    }

    /**
     * Store a newly created review in storage.
     */
    public function store(Request $request)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('create_coordination_review')) {
            abort(403, 'غير مصرح لك بإنشاء تقييم جديد');
        }

        $user = Auth::user();

        try {
            $validated = $this->validateReview($request);

            // Check if the user being reviewed is in the reviewer's team
            if ($user->hasRole(['coordination_team_leader', 'coordination_department_manager'])) {
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

            CoordinationReview::create($validated);

            return redirect()->route('coordination-reviews.index')
                ->with('success', 'تم إنشاء التقييم بنجاح');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    /**
     * Display the specified review.
     */
    public function show(CoordinationReview $coordinationReview)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('view_coordination_review')) {
            abort(403, 'غير مصرح لك بعرض هذا التقييم');
        }

        $user = Auth::user();

        // Check if the review belongs to the reviewer's team
        if ($user->hasRole(['coordination_team_leader', 'coordination_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($coordinationReview->user_id, $teamMemberIds) && $coordinationReview->user_id != $user->id) {
                abort(403, 'لا يمكنك عرض تقييم لشخص ليس في فريقك');
            }
        }

        return view('coordination-reviews.show', [
            'review' => $coordinationReview->load(['user', 'reviewer'])
        ]);
    }

    /**
     * Show the form for editing the specified review.
     */
    public function edit(CoordinationReview $coordinationReview)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('update_coordination_review')) {
            abort(403, 'غير مصرح لك بتعديل هذا التقييم');
        }

        $user = Auth::user();

        // Prevent users from editing their own reviews
        if ($coordinationReview->user_id == $user->id) {
            abort(403, 'لا يمكنك تعديل تقييمك الخاص');
        }

        // Check if the review belongs to the reviewer's team
        if ($user->hasRole(['coordination_team_leader', 'coordination_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($coordinationReview->user_id, $teamMemberIds) && $coordinationReview->user_id != $user->id) {
                abort(403, 'لا يمكنك تعديل تقييم لشخص ليس في فريقك');
            }
        }

        // Get users based on permissions
        if ($user->hasRole(['coordination_team_leader', 'coordination_department_manager'])) {
            if ($user->currentTeam) {
                $users = $user->currentTeam->users()->orderBy('name')->get();
            } else {
                $users = collect([$user]);
            }
        } else {
            // For admins and others with full access
            $users = User::orderBy('name')->get();
        }

        return view('coordination-reviews.edit', [
            'review' => $coordinationReview,
            'users' => $users
        ]);
    }

    /**
     * Update the specified review in storage.
     */
    public function update(Request $request, CoordinationReview $coordinationReview)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('update_coordination_review')) {
            abort(403, 'غير مصرح لك بتعديل هذا التقييم');
        }

        $user = Auth::user();

        // Prevent users from updating their own reviews
        if ($coordinationReview->user_id == $user->id) {
            abort(403, 'لا يمكنك تعديل تقييمك الخاص');
        }

        $validated = $this->validateReview($request);

        // Check if the user being reviewed is in the reviewer's team
        if ($user->hasRole(['coordination_team_leader', 'coordination_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($validated['user_id'], $teamMemberIds) && $validated['user_id'] != $user->id) {
                abort(403, 'لا يمكنك تعديل تقييم لشخص ليس في فريقك');
            }
        }

        $coordinationReview->update($validated);

        return redirect()->route('coordination-reviews.index')
            ->with('success', 'تم تحديث التقييم بنجاح');
    }

    /**
     * Remove the specified review from storage.
     */
    public function destroy(CoordinationReview $coordinationReview)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('delete_coordination_review')) {
            abort(403, 'غير مصرح لك بحذف هذا التقييم');
        }

        $user = Auth::user();

        // Check if the review belongs to the reviewer's team
        if ($user->hasRole(['coordination_team_leader', 'coordination_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($coordinationReview->user_id, $teamMemberIds) && $coordinationReview->user_id != $user->id) {
                abort(403, 'لا يمكنك حذف تقييم لشخص ليس في فريقك');
            }
        }

        $coordinationReview->delete();

        return redirect()->route('coordination-reviews.index')
            ->with('success', 'تم حذف التقييم بنجاح');
    }

    /**
     * Validate the review data.
     */
    private function validateReview(Request $request)
    {
        $messages = [
            'user_id.unique' => 'هذا الموظف لديه تقييم بالفعل لهذا الشهر، لا يمكن إنشاء تقييم مكرر.',
        ];

        return $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('coordination_reviews')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->user_id)
                                ->where('review_month', $request->review_month)
                                ->whereNull('deleted_at');
                })->ignore($request->route('coordinationReview') ? $request->route('coordinationReview')->id : null)
            ],
            'review_month' => ['required', 'string', 'max:7'],
            'documentation_delivery_score' => ['required', 'integer', 'min:0', 'max:40'],
            'daily_delivery_score' => ['required', 'integer', 'min:0', 'max:26'],
            'scheduling_score' => ['required', 'integer', 'min:0', 'max:40'],
            'error_free_delivery_score' => ['required', 'integer', 'min:0', 'max:40'],
            'schedule_follow_up_score' => ['required', 'integer', 'min:0', 'max:26'],
            'no_previous_drafts_score' => ['required', 'integer', 'min:0', 'max:40'],
            'no_design_errors_score' => ['required', 'integer', 'min:0', 'max:40'],
            'follow_up_modifications_score' => ['required', 'integer', 'min:0', 'max:26'],
            'presentations_score' => ['required', 'integer', 'min:0', 'max:10'],
            'customer_service_score' => ['required', 'integer', 'min:0', 'max:26'],
            'project_monitoring_score' => ['required', 'integer', 'min:0', 'max:10'],
            'feedback_score' => ['required', 'integer', 'min:0', 'max:40'],
            'team_leader_evaluation_score' => ['required', 'integer', 'min:0', 'max:10'],
            'hr_evaluation_score' => ['required', 'integer', 'min:0', 'max:10'],
            'required_deliveries_score' => ['required', 'integer', 'min:0'],
            'seo_score' => ['required', 'integer', 'min:0'],
            'portfolio_score' => ['required', 'integer', 'min:0'],
            'proposal_score' => ['required', 'integer', 'min:0'],
            'company_idea_score' => ['required', 'integer', 'min:0'],
            'old_draft_penalty' => ['required', 'integer', 'min:0'],
            'design_error_penalty' => ['required', 'integer', 'min:0'],
            'daily_commitment_penalty' => ['required', 'integer', 'min:0'],
            'review_failure_penalty' => ['required', 'integer', 'min:0'],
            'total_salary' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ], $messages);
    }
}
