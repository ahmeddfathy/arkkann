<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TechnicalTeamReview;
use App\Models\MarketingReview;
use App\Models\CustomerServiceReview;
use App\Models\CoordinationReview;
use Illuminate\Support\Facades\Auth;

class MyReviewsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $month = $request->input('month');
        $year = $request->input('year');

        // Get technical team reviews
        $technicalReviews = TechnicalTeamReview::where('user_id', $user->id);

        // Get marketing reviews
        $marketingReviews = MarketingReview::where('user_id', $user->id);

        // Get customer service reviews
        $customerServiceReviews = CustomerServiceReview::where('user_id', $user->id);

        // Get coordination reviews
        $coordinationReviews = CoordinationReview::where('user_id', $user->id);

        // Apply month and year filters if provided
        if ($month && $year) {
            $reviewMonth = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

            $technicalReviews->where('review_month', $reviewMonth);
            $marketingReviews->where('review_month', $reviewMonth);
            $customerServiceReviews->where('review_month', $reviewMonth);
            $coordinationReviews->where('review_month', $reviewMonth);
        }

        // Get the reviews
        $technicalReviews = $technicalReviews->get();
        $marketingReviews = $marketingReviews->get();
        $customerServiceReviews = $customerServiceReviews->get();
        $coordinationReviews = $coordinationReviews->get();

        // Prepare the combined collection with review type
        $allReviews = collect();

        foreach ($technicalReviews as $review) {
            $allReviews->push([
                'id' => $review->id,
                'type' => 'technical',
                'model_type' => TechnicalTeamReview::class,
                'review_month' => $review->review_month,
                'total_score' => $review->total_score,
                'total_after_deductions' => $review->total_after_deductions,
                'total_salary' => $review->total_salary,
                'percentage' => null,
                'reviewer' => $review->reviewer->name ?? 'غير محدد',
                'created_at' => $review->created_at,
            ]);
        }

        foreach ($marketingReviews as $review) {
            $allReviews->push([
                'id' => $review->id,
                'type' => 'marketing',
                'model_type' => MarketingReview::class,
                'review_month' => $review->review_month,
                'total_score' => $review->total_score,
                'total_after_deductions' => $review->total_after_deductions,
                'total_salary' => $review->total_salary,
                'percentage' => null,
                'reviewer' => $review->reviewer->name ?? 'غير محدد',
                'created_at' => $review->created_at,
            ]);
        }

        foreach ($customerServiceReviews as $review) {
            $allReviews->push([
                'id' => $review->id,
                'type' => 'customer_service',
                'model_type' => CustomerServiceReview::class,
                'review_month' => $review->review_month,
                'total_score' => $review->total_score,
                'total_after_deductions' => $review->total_after_deductions,
                'total_salary' => $review->total_salary,
                'percentage' => $review->percentage,
                'reviewer' => $review->reviewer->name ?? 'غير محدد',
                'created_at' => $review->created_at,
            ]);
        }

        foreach ($coordinationReviews as $review) {
            $allReviews->push([
                'id' => $review->id,
                'type' => 'coordination',
                'model_type' => CoordinationReview::class,
                'review_month' => $review->review_month,
                'total_score' => $review->total_score,
                'total_after_deductions' => $review->total_after_deductions,
                'total_salary' => $review->total_salary,
                'percentage' => null,
                'reviewer' => $review->reviewer->name ?? 'غير محدد',
                'created_at' => $review->created_at,
            ]);
        }

        // Sort all reviews by month (newest first)
        $allReviews = $allReviews->sortByDesc('review_month');

        // Current month and year for filter
        $currentMonth = date('m');
        $currentYear = date('Y');

        return view('my-reviews.index', compact('allReviews', 'currentMonth', 'currentYear'));
    }
}
