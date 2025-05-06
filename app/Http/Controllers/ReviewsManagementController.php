<?php

namespace App\Http\Controllers;

use App\Models\TechnicalTeamReview;
use App\Models\MarketingReview;
use App\Models\CustomerServiceReview;
use App\Models\CoordinationReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Laravel\Fortify\TwoFactorAuthenticationProvider;

class ReviewsManagementController extends Controller
{
    /**
     * Display a listing of soft deleted reviews only.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        // Get only trashed reviews
        $technicalReviews = TechnicalTeamReview::onlyTrashed()->with(['user:id,name', 'reviewer:id,name'])->get();
        $marketingReviews = MarketingReview::onlyTrashed()->with(['user:id,name', 'reviewer:id,name'])->get();
        $customerServiceReviews = CustomerServiceReview::onlyTrashed()->with(['user:id,name', 'reviewer:id,name'])->get();
        $coordinationReviews = CoordinationReview::onlyTrashed()->with(['user:id,name', 'reviewer:id,name'])->get();

        return view('reviews.management.index', compact(
            'technicalReviews',
            'marketingReviews',
            'customerServiceReviews',
            'coordinationReviews'
        ));
    }

    /**
     * Verify two-factor authentication code.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function verifyTwoFactorCode(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'code' => 'required',
        ]);

        if (! app(TwoFactorAuthenticationProvider::class)
                ->verify(decrypt($user->two_factor_secret), $validated['code'])) {
            return false;
        }

        return true;
    }

    /**
     * Soft delete a technical team review.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteTechnicalReview($id, Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        if (!$this->verifyTwoFactorCode($request)) {
            return back()->withErrors(['code' => 'الكود غير صحيح']);
        }

        $review = TechnicalTeamReview::findOrFail($id);
        $review->delete();

        return redirect()->back()->with('success', 'تم حذف المراجعة بنجاح');
    }

    /**
     * Soft delete a marketing review.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteMarketingReview($id, Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        if (!$this->verifyTwoFactorCode($request)) {
            return back()->withErrors(['code' => 'الكود غير صحيح']);
        }

        $review = MarketingReview::findOrFail($id);
        $review->delete();

        return redirect()->back()->with('success', 'تم حذف المراجعة بنجاح');
    }

    /**
     * Soft delete a customer service review.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteCustomerServiceReview($id, Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        if (!$this->verifyTwoFactorCode($request)) {
            return back()->withErrors(['code' => 'الكود غير صحيح']);
        }

        $review = CustomerServiceReview::findOrFail($id);
        $review->delete();

        return redirect()->back()->with('success', 'تم حذف المراجعة بنجاح');
    }

    /**
     * Soft delete a coordination review.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteCoordinationReview($id, Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        if (!$this->verifyTwoFactorCode($request)) {
            return back()->withErrors(['code' => 'الكود غير صحيح']);
        }

        $review = CoordinationReview::findOrFail($id);
        $review->delete();

        return redirect()->back()->with('success', 'تم حذف المراجعة بنجاح');
    }

    /**
     * Restore a soft deleted technical team review.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restoreTechnicalReview($id)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        $review = TechnicalTeamReview::withTrashed()->findOrFail($id);
        $review->restore();

        return redirect()->back()->with('success', 'تم استعادة المراجعة بنجاح');
    }

    /**
     * Restore a soft deleted marketing review.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restoreMarketingReview($id)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        $review = MarketingReview::withTrashed()->findOrFail($id);
        $review->restore();

        return redirect()->back()->with('success', 'تم استعادة المراجعة بنجاح');
    }

    /**
     * Restore a soft deleted customer service review.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restoreCustomerServiceReview($id)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        $review = CustomerServiceReview::withTrashed()->findOrFail($id);
        $review->restore();

        return redirect()->back()->with('success', 'تم استعادة المراجعة بنجاح');
    }

    /**
     * Restore a soft deleted coordination review.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restoreCoordinationReview($id)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        $review = CoordinationReview::withTrashed()->findOrFail($id);
        $review->restore();

        return redirect()->back()->with('success', 'تم استعادة المراجعة بنجاح');
    }

    /**
     * Display the specified trashed technical team review.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showTechnicalReview($id)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        $review = TechnicalTeamReview::withTrashed()->with(['user', 'reviewer'])->findOrFail($id);

        return view('technical-team-reviews.show', compact('review'));
    }

    /**
     * Display the specified trashed marketing review.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showMarketingReview($id)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        $review = MarketingReview::withTrashed()->with(['user', 'reviewer'])->findOrFail($id);

        return view('marketing-reviews.show', compact('review'));
    }

    /**
     * Display the specified trashed customer service review.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showCustomerServiceReview($id)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        $review = CustomerServiceReview::withTrashed()->with(['user', 'reviewer'])->findOrFail($id);

        return view('customer-service-reviews.show', compact('review'));
    }

    /**
     * Display the specified trashed coordination review.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showCoordinationReview($id)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        $review = CoordinationReview::withTrashed()->with(['user', 'reviewer'])->findOrFail($id);

        return view('coordination-reviews.show', compact('review'));
    }

    /**
     * Bulk delete technical team reviews.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDeleteTechnicalReviews(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        if (!$this->verifyTwoFactorCode($request)) {
            return back()->withErrors(['code' => 'الكود غير صحيح']);
        }

        $validated = $request->validate([
            'reviews' => 'required|array',
            'reviews.*' => 'exists:technical_team_reviews,id'
        ]);

        TechnicalTeamReview::whereIn('id', $validated['reviews'])->delete();

        return redirect()->back()->with('success', 'تم حذف المراجعات المحددة بنجاح');
    }

    /**
     * Bulk delete marketing reviews.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDeleteMarketingReviews(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        if (!$this->verifyTwoFactorCode($request)) {
            return back()->withErrors(['code' => 'الكود غير صحيح']);
        }

        $validated = $request->validate([
            'reviews' => 'required|array',
            'reviews.*' => 'exists:marketing_reviews,id'
        ]);

        MarketingReview::whereIn('id', $validated['reviews'])->delete();

        return redirect()->back()->with('success', 'تم حذف المراجعات المحددة بنجاح');
    }

    /**
     * Bulk delete customer service reviews.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDeleteCustomerServiceReviews(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        if (!$this->verifyTwoFactorCode($request)) {
            return back()->withErrors(['code' => 'الكود غير صحيح']);
        }

        $validated = $request->validate([
            'reviews' => 'required|array',
            'reviews.*' => 'exists:customer_service_reviews,id'
        ]);

        CustomerServiceReview::whereIn('id', $validated['reviews'])->delete();

        return redirect()->back()->with('success', 'تم حذف المراجعات المحددة بنجاح');
    }

    /**
     * Bulk delete coordination reviews.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDeleteCoordinationReviews(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        if (!$this->verifyTwoFactorCode($request)) {
            return back()->withErrors(['code' => 'الكود غير صحيح']);
        }

        $validated = $request->validate([
            'reviews' => 'required|array',
            'reviews.*' => 'exists:coordination_reviews,id'
        ]);

        CoordinationReview::whereIn('id', $validated['reviews'])->delete();

        return redirect()->back()->with('success', 'تم حذف المراجعات المحددة بنجاح');
    }

    /**
     * Bulk restore technical team reviews.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkRestoreTechnicalReviews(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        $validated = $request->validate([
            'reviews' => 'required|array',
            'reviews.*' => 'exists:technical_team_reviews,id'
        ]);

        TechnicalTeamReview::withTrashed()->whereIn('id', $validated['reviews'])->restore();

        return redirect()->back()->with('success', 'تم استعادة المراجعات المحددة بنجاح');
    }

    /**
     * Bulk restore marketing reviews.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkRestoreMarketingReviews(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        $validated = $request->validate([
            'reviews' => 'required|array',
            'reviews.*' => 'exists:marketing_reviews,id'
        ]);

        MarketingReview::withTrashed()->whereIn('id', $validated['reviews'])->restore();

        return redirect()->back()->with('success', 'تم استعادة المراجعات المحددة بنجاح');
    }

    /**
     * Bulk restore customer service reviews.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkRestoreCustomerServiceReviews(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        $validated = $request->validate([
            'reviews' => 'required|array',
            'reviews.*' => 'exists:customer_service_reviews,id'
        ]);

        CustomerServiceReview::withTrashed()->whereIn('id', $validated['reviews'])->restore();

        return redirect()->back()->with('success', 'تم استعادة المراجعات المحددة بنجاح');
    }

    /**
     * Bulk restore coordination reviews.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkRestoreCoordinationReviews(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('manage_reviews')) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة');
        }

        $validated = $request->validate([
            'reviews' => 'required|array',
            'reviews.*' => 'exists:coordination_reviews,id'
        ]);

        CoordinationReview::withTrashed()->whereIn('id', $validated['reviews'])->restore();

        return redirect()->back()->with('success', 'تم استعادة المراجعات المحددة بنجاح');
    }
}
