<?php

namespace App\Http\Controllers;

use App\Models\CustomerServiceReview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CustomerServiceReviewController extends Controller
{
    /**
     * Display a listing of the reviews.
     */
    public function index(Request $request)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('view_customer_service_review')) {
            abort(403, 'غير مصرح لك بعرض التقييمات');
        }

        $query = CustomerServiceReview::with(['user', 'reviewer']);
        $user = Auth::user();

        // If user is a team leader or department manager, only show reviews for their team members
        if ($user->hasRole(['customer_service_team_leader', 'customer_service_department_manager'])) {
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
        if ($user->hasRole(['customer_service_team_leader', 'customer_service_department_manager'])) {
            if ($user->currentTeam) {
                $users = $user->currentTeam->users()->orderBy('name')->get();
            } else {
                $users = collect([$user]);
            }
        } else {
            // For admins and others with full access
            $users = User::orderBy('name')->get();
        }

        return view('customer-service-reviews.index', compact('reviews', 'users'));
    }

    /**
     * Show the form for creating a new review.
     */
    public function create()
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('create_customer_service_review')) {
            abort(403, 'غير مصرح لك بإنشاء تقييم جديد');
        }

        $user = Auth::user();

        // Get users based on permissions
        if ($user->hasRole(['customer_service_team_leader', 'customer_service_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }
            $users = $user->currentTeam->users()->orderBy('name')->get();
        } else {
            // For admins and others with full access
            $users = User::orderBy('name')->get();
        }

        return view('customer-service-reviews.create', compact('users'));
    }

    /**
     * Store a newly created review in storage.
     */
    public function store(Request $request)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('create_customer_service_review')) {
            abort(403, 'غير مصرح لك بإنشاء تقييم جديد');
        }

        $user = Auth::user();
        $validated = $this->validateReview($request);

        // Check if the user being reviewed is in the reviewer's team
        if ($user->hasRole(['customer_service_team_leader', 'customer_service_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($validated['user_id'], $teamMemberIds) && $validated['user_id'] != $user->id) {
                abort(403, 'لا يمكنك إنشاء تقييم لشخص ليس في فريقك');
            }
        }

        // Check if a review already exists for this user in the same month
        $existingReview = CustomerServiceReview::where('user_id', $validated['user_id'])
                            ->where('review_month', $validated['review_month'])
                            ->first();

        if ($existingReview) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['user_id' => 'يوجد بالفعل تقييم لهذا الموظف في نفس الشهر']);
        }

        $validated['reviewer_id'] = Auth::id();

        // إذا لم يتم تحديد شهر التقييم، استخدم الشهر والسنة الحالية
        $validated['review_month'] = $validated['review_month'] ?? now()->format('Y-m');

        CustomerServiceReview::create($validated);

        return redirect()->route('customer-service-reviews.index')
            ->with('success', 'تم إنشاء التقييم بنجاح');
    }

    /**
     * Display the specified review.
     */
    public function show(CustomerServiceReview $customerServiceReview)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('view_customer_service_review')) {
            abort(403, 'غير مصرح لك بعرض هذا التقييم');
        }

        $user = Auth::user();

        // Check if the review belongs to the reviewer's team
        if ($user->hasRole(['customer_service_team_leader', 'customer_service_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($customerServiceReview->user_id, $teamMemberIds) && $customerServiceReview->user_id != $user->id) {
                abort(403, 'لا يمكنك عرض تقييم لشخص ليس في فريقك');
            }
        }

        return view('customer-service-reviews.show', [
            'review' => $customerServiceReview->load(['user', 'reviewer'])
        ]);
    }

    /**
     * Show the form for editing the specified review.
     */
    public function edit(CustomerServiceReview $customerServiceReview)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('update_customer_service_review')) {
            abort(403, 'غير مصرح لك بتعديل هذا التقييم');
        }

        $user = Auth::user();

        // Prevent users from editing their own reviews
        if ($customerServiceReview->user_id == $user->id) {
            abort(403, 'لا يمكنك تعديل تقييمك الخاص');
        }

        // Check if the review belongs to the reviewer's team
        if ($user->hasRole(['customer_service_team_leader', 'customer_service_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($customerServiceReview->user_id, $teamMemberIds) && $customerServiceReview->user_id != $user->id) {
                abort(403, 'لا يمكنك تعديل تقييم لشخص ليس في فريقك');
            }
        }

        // Get users based on permissions
        if ($user->hasRole(['customer_service_team_leader', 'customer_service_department_manager'])) {
            if ($user->currentTeam) {
                $users = $user->currentTeam->users()->orderBy('name')->get();
            } else {
                $users = collect([$user]);
            }
        } else {
            // For admins and others with full access
            $users = User::orderBy('name')->get();
        }

        return view('customer-service-reviews.edit', [
            'review' => $customerServiceReview,
            'users' => $users
        ]);
    }

    /**
     * Update the specified review in storage.
     */
    public function update(Request $request, CustomerServiceReview $customerServiceReview)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('update_customer_service_review')) {
            abort(403, 'غير مصرح لك بتعديل هذا التقييم');
        }

        $user = Auth::user();

        // Prevent users from updating their own reviews
        if ($customerServiceReview->user_id == $user->id) {
            abort(403, 'لا يمكنك تعديل تقييمك الخاص');
        }

        $validated = $this->validateReview($request);

        // Check if the user being reviewed is in the reviewer's team
        if ($user->hasRole(['customer_service_team_leader', 'customer_service_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($validated['user_id'], $teamMemberIds) && $validated['user_id'] != $user->id) {
                abort(403, 'لا يمكنك تعديل تقييم لشخص ليس في فريقك');
            }
        }

        // Check if a review already exists for this user in the same month (excluding the current review)
        $existingReview = CustomerServiceReview::where('user_id', $validated['user_id'])
                        ->where('review_month', $validated['review_month'])
                        ->where('id', '!=', $customerServiceReview->id)
                        ->first();

        if ($existingReview) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['review_month' => 'يوجد بالفعل تقييم لهذا الموظف في نفس الشهر']);
        }

        $customerServiceReview->update($validated);

        return redirect()->route('customer-service-reviews.index')
            ->with('success', 'تم تحديث التقييم بنجاح');
    }

    /**
     * Remove the specified review from storage.
     */
    public function destroy(CustomerServiceReview $customerServiceReview)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('delete_customer_service_review')) {
            abort(403, 'غير مصرح لك بحذف هذا التقييم');
        }

        $user = Auth::user();

        // Check if the review belongs to the reviewer's team
        if ($user->hasRole(['customer_service_team_leader', 'customer_service_department_manager'])) {
            if (!$user->currentTeam) {
                abort(403, 'ليس لديك فريق حالي لإدارته');
            }

            $teamMemberIds = $user->currentTeam->users()->pluck('users.id')->toArray();
            if (!in_array($customerServiceReview->user_id, $teamMemberIds) && $customerServiceReview->user_id != $user->id) {
                abort(403, 'لا يمكنك حذف تقييم لشخص ليس في فريقك');
            }
        }

        $customerServiceReview->delete();

        return redirect()->route('customer-service-reviews.index')
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
            'client_interaction_score' => ['required', 'integer', 'min:0'],
            'client_contract_score' => ['required', 'integer', 'min:0'],
            'client_communication_speed_score' => ['required', 'integer', 'min:0'],
            'final_collection_score' => ['required', 'integer', 'min:0'],
            'client_data_recording_score' => ['required', 'integer', 'min:0'],
            'project_archiving_score' => ['required', 'integer', 'min:0'],
            'after_sales_service_score' => ['required', 'integer', 'min:0'],
            'team_coordination_score' => ['required', 'integer', 'min:0'],
            'client_followup_quality_score' => ['required', 'integer', 'min:0'],
            'customer_service_archiving_score' => ['required', 'integer', 'min:0'],
            'client_evaluation_score' => ['required', 'integer', 'min:0'],
            'team_leader_tasks_score' => ['required', 'integer', 'min:0'],
            'average_sales_score' => ['required', 'integer', 'min:0'],
            'daily_report_commitment_score' => ['required', 'integer', 'min:0'],
            'hr_evaluation_score' => ['required', 'integer', 'min:0'],
            'excess_services_penalty' => ['required', 'integer', 'min:0'],
            'unauthorized_discount_penalty' => ['required', 'integer', 'min:0'],
            'contract_mismatch_penalty' => ['required', 'integer', 'min:0'],
            'team_conflict_penalty' => ['required', 'integer', 'min:0'],
            'personal_phone_use_penalty' => ['required', 'integer', 'min:0'],
            'absence_late_penalty' => ['required', 'integer', 'min:0'],
            'additional_bonus' => ['required', 'integer', 'min:0'],
            'additional_deduction' => ['required', 'integer', 'min:0'],
            'total_salary' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
