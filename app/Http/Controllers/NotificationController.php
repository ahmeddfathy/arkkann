<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\AdministrativeDecision;

class NotificationController extends Controller
{
    public function index()
    {
        // جلب الإشعارات الخاصة بالمستخدم والإشعارات العامة
        $notifications = Notification::where(function ($query) {
            $query->where('user_id', Auth::id())
                ->orWhere(function ($q) {
                    $q->where('type', 'admin_broadcast')
                        ->where('data->recipients', 'all');
                });
        })
            ->orderBy('created_at', 'desc')
            ->get();

        if (request()->ajax()) {
            return view('notifications.partials.notification-list', compact('notifications'));
        }

        return view('notifications.index', compact('notifications'));
    }

    public function getUnreadCount()
    {
        $count = Notification::where(function ($query) {
            $query->where('user_id', Auth::id())
                ->orWhere(function ($q) {
                    $q->where('type', 'admin_broadcast')
                        ->where('data->recipients', 'all');
                });
        })
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markAsRead(Notification $notification)
    {
        // إذا كان الإشعار عام، نقوم بإنشاء سجل قراءة جديد للمستخدم الحالي
        if ($notification->type === 'admin_broadcast') {
            Notification::create([
                'user_id' => Auth::id(),
                'type' => 'read_receipt',
                'data' => [
                    'notification_id' => $notification->id,
                    'read_at' => now()
                ]
            ]);
        } else {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    public function unread()
    {
        $unreadDecisions = AdministrativeDecision::with(['notification', 'user'])
            ->whereNull('acknowledged_at')
            ->where('user_id', Auth::id())
            ->whereHas('notification', function ($query) {
                $query->where('data->requires_acknowledgment', true);
            })
            ->latest()
            ->get();

        return view('notifications.unread', [
            'unreadDecisions' => $unreadDecisions,
            'pageTitle' => 'القرارات الإدارية غير المقروءة'
        ]);
    }

    public function acknowledge(AdministrativeDecision $decision)
    {
        // التحقق من أن القرار للمستخدم الحالي
        if ($decision->user_id !== Auth::id()) {
            abort(403);
        }

        $decision->update([
            'acknowledged_at' => now()
        ]);

        return back()->with('success', 'تم تأكيد قراءة القرار الإداري بنجاح');
    }
}
