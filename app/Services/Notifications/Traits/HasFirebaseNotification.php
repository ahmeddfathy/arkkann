<?php

namespace App\Services\Notifications\Traits;

use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Log;

trait HasFirebaseNotification
{
    protected function sendAdditionalFirebaseNotification(User $user, string $message, string $title = 'إشعار جديد', ?string $link = null, ?string $type = null): ?array
    {
        try {
            if ($user && !empty($user->fcm_token)) {
                $firebaseService = app(FirebaseNotificationService::class);

                if (!$link) {
                    $link = '/dashboard';
                    if ($user->role === 'employee') {
                        $link = '/employee/dashboard';
                    } elseif ($user->role === 'admin' || $user->role === 'manager') {
                        $link = '/admin/dashboard';
                    }

                    if ($type) {
                        $link .= "?notification_type={$type}";
                    }
                }

                $result = $firebaseService->sendNotification(
                    $user->fcm_token,
                    $title,
                    $message,
                    $link
                );

                if (method_exists($this, 'storeFirebaseNotificationLog')) {
                    $this->storeFirebaseNotificationLog($user, $title, $message, $result, $type);
                }

                return $result;
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }

    protected function sendTypedFirebaseNotification(User $user, string $requestType, string $actionType, string $message, int $requestId): ?array
    {
        try {
            $title = 'إشعار جديد';

            switch ($actionType) {
                case 'created': $title = 'طلب جديد'; break;
                case 'updated': $title = 'تم تحديث الطلب'; break;
                case 'approved': $title = 'تمت الموافقة'; break;
                case 'rejected': $title = 'تم الرفض'; break;
                case 'deleted': $title = 'تم حذف الطلب'; break;
                case 'reset': $title = 'تم إعادة تعيين الطلب'; break;
            }

            $link = '/dashboard';

            if ($user->role === 'employee') {
                $link = "/employee/{$requestType}/{$requestId}";
            } elseif ($user->role === 'admin' || $user->role === 'manager') {
                $link = "/admin/{$requestType}/{$requestId}";
            } elseif ($user->role === 'hr') {
                $link = "/hr/{$requestType}/{$requestId}";
            }

            $type = "{$requestType}_{$actionType}";

            return $this->sendAdditionalFirebaseNotification($user, $message, $title, $link, $type);
        } catch (\Exception $e) {
            return null;
        }
    }
}
