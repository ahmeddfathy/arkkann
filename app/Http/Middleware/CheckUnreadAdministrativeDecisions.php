<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AdministrativeDecision;

class CheckUnreadAdministrativeDecisions
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $unreadDecisions = AdministrativeDecision::whereNull('acknowledged_at')
                ->where('user_id', Auth::id())
                ->whereHas('notification', function ($query) {
                    $query->where('data->requires_acknowledgment', true);
                })
                ->exists();

            if ($unreadDecisions && !$request->is('notifications*')) {
                return redirect()->route('notifications.unread')
                    ->with('warning', 'يوجد قرارات إدارية تحتاج إلى قراءتها وتأكيدها');
            }
        }

        return $next($request);
    }
}
