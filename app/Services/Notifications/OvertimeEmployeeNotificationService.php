<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\OverTimeRequests;
use App\Services\Notifications\Traits\HasFirebaseNotification;

class OvertimeEmployeeNotificationService
{
    use HasFirebaseNotification;

    public function notifyEmployee(OverTimeRequests $request, string $type, string $message): void
    {
        try {
            $timestamp = now()->format('Y-m-d H:i:s');

            if (strpos($type, 'response_modified') !== false || strpos($type, 'status_reset') !== false) {
                Notification::where([
                    'user_id' => $request->user_id,
                    'type' => $type,
                    'related_id' => $request->id
                ])->delete();
            }

            $responderName = 'النظام';
            if (\Illuminate\Support\Facades\Auth::check()) {
                $responderName = \Illuminate\Support\Facades\Auth::user()->name;
            }

            Notification::create([
                'user_id' => $request->user_id,
                'type' => $type,
                'data' => [
                    'message' => $message,
                    'request_id' => $request->id,
                    'overtime_date' => $request->overtime_date,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'reason' => $request->reason,
                    'status' => $request->status,
                    'manager_status' => $request->manager_status,
                    'hr_status' => $request->hr_status,
                    'manager_rejection_reason' => $request->manager_rejection_reason,
                    'hr_rejection_reason' => $request->hr_rejection_reason,
                    'responder_name' => $responderName,
                    'notification_time' => $timestamp,
                    'action_type' => $this->determineActionType($type),
                    'action_details' => $this->getActionDetails($request, $type)
                ],
                'related_id' => $request->id
            ]);

            if ($request->user) {
                $this->sendAdditionalFirebaseNotification($request->user, $message);
            }
        } catch (\Exception $e) {
        }
    }

    private function determineActionType(string $type): string
    {
        if (strpos($type, 'created') !== false) {
            return 'إنشاء';
        } elseif (strpos($type, 'updated') !== false) {
            return 'تحديث';
        } elseif (strpos($type, 'reset') !== false) {
            return 'إعادة تعيين';
        } elseif (strpos($type, 'deleted') !== false) {
            return 'حذف';
        } else {
            return 'إجراء';
        }
    }

    private function getActionDetails($request, string $type): array
    {
        $details = [
            'by_who' => \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user()->name : 'النظام'
        ];

        $details['status'] = $request->status;
        $details['manager_status'] = $request->manager_status;
        $details['hr_status'] = $request->hr_status;

        if ($request->status === 'approved') {
            $details['status_text'] = 'موافقة';
        } elseif ($request->status === 'rejected') {
            $details['status_text'] = 'رفض';
        } elseif ($request->status === 'pending') {
            $details['status_text'] = 'قيد الانتظار';
        }

        if ($request->manager_rejection_reason) {
            $details['manager_rejection_reason'] = $request->manager_rejection_reason;
        }

        if ($request->hr_rejection_reason) {
            $details['hr_rejection_reason'] = $request->hr_rejection_reason;
        }

        if (strpos($type, 'overtime_status_updated') !== false) {
            $details['responder_type'] = 'النظام';
            if ($request->manager_status === 'approved' && $request->hr_status === 'approved') {
                $details['combined_status_text'] = 'تمت الموافقة من المدير و HR';
            } elseif ($request->manager_status === 'rejected') {
                $details['combined_status_text'] = 'تم الرفض من المدير';
            } elseif ($request->hr_status === 'rejected') {
                $details['combined_status_text'] = 'تم الرفض من HR';
            } elseif ($request->manager_status === 'pending' && $request->hr_status === 'pending') {
                $details['combined_status_text'] = 'قيد الانتظار';
            } else {
                $details['combined_status_text'] = 'تم تحديث الحالة';
            }
        } elseif (strpos($type, 'overtime_response_modified') !== false) {
            if (strpos($type, 'manager') !== false) {
                $details['responder_type'] = 'مدير';
                $details['status'] = $request->manager_status;
                $details['action'] = 'تعديل الرد';
                if ($request->manager_status === 'approved') {
                    $details['status_text'] = 'موافقة';
                } elseif ($request->manager_status === 'rejected') {
                    $details['status_text'] = 'رفض';
                    $details['reason'] = $request->manager_rejection_reason;
                } elseif ($request->manager_status === 'pending') {
                    $details['status_text'] = 'قيد الانتظار';
                }
            } elseif (strpos($type, 'hr') !== false) {
                $details['responder_type'] = 'HR';
                $details['status'] = $request->hr_status;
                $details['action'] = 'تعديل الرد';
                if ($request->hr_status === 'approved') {
                    $details['status_text'] = 'موافقة';
                } elseif ($request->hr_status === 'rejected') {
                    $details['status_text'] = 'رفض';
                    $details['reason'] = $request->hr_rejection_reason;
                } elseif ($request->hr_status === 'pending') {
                    $details['status_text'] = 'قيد الانتظار';
                }
            } else {
                $details['action'] = 'تعديل الرد';
                $details['modification_time'] = now()->format('Y-m-d H:i:s');
            }
        } elseif (strpos($type, 'manager_status') !== false) {
            $details['responder_type'] = 'مدير';
            $details['status'] = $request->manager_status;
            if ($request->manager_status === 'approved') {
                $details['status_text'] = 'موافقة';
            } elseif ($request->manager_status === 'rejected') {
                $details['status_text'] = 'رفض';
                $details['reason'] = $request->manager_rejection_reason;
            } elseif ($request->manager_status === 'pending') {
                $details['status_text'] = 'قيد الانتظار';
            }
        } elseif (strpos($type, 'hr_status') !== false) {
            $details['responder_type'] = 'HR';
            $details['status'] = $request->hr_status;
            if ($request->hr_status === 'approved') {
                $details['status_text'] = 'موافقة';
            } elseif ($request->hr_status === 'rejected') {
                $details['status_text'] = 'رفض';
                $details['reason'] = $request->hr_rejection_reason;
            } elseif ($request->hr_status === 'pending') {
                $details['status_text'] = 'قيد الانتظار';
            }
        } elseif (strpos($type, 'status_reset') !== false) {
            if (strpos($type, 'hr_status_reset') !== false) {
                $details['responder_type'] = 'HR';
                $details['reset_status'] = 'تم إعادة تعيين حالة HR إلى قيد الانتظار';
            } else {
                $details['responder_type'] = 'مدير';
                $details['reset_status'] = 'تم إعادة تعيين حالة المدير إلى قيد الانتظار';
            }
        }

        return $details;
    }
}
