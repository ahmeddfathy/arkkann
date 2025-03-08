<?php

namespace App\Services;

use App\Models\OverTimeRequests;
use App\Models\Notification;
use App\Services\Notifications\OvertimeEmployeeNotificationService;
use App\Services\Notifications\OvertimeManagerNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationOvertimeService
{
    protected $employeeNotificationService;
    protected $managerNotificationService;

    public function __construct(
        OvertimeEmployeeNotificationService $employeeNotificationService,
        OvertimeManagerNotificationService $managerNotificationService
    ) {
        $this->employeeNotificationService = $employeeNotificationService;
        $this->managerNotificationService = $managerNotificationService;
    }

    public function createOvertimeRequestNotification(OverTimeRequests $request): void
    {
        try {
            if ($request->user_id !== Auth::id()) {
                $this->managerNotificationService->notifyManagers(
                    $request,
                    'new_overtime_request',
                    'تم تقديم طلب عمل إضافي جديد'
                );

                $this->managerNotificationService->notifyManagers(
                    $request,
                    'new_overtime_request_hr',
                    'تم تقديم طلب عمل إضافي جديد يحتاج لمراجعتك',
                    true
                );
            }
        } catch (\Exception $e) {
        }
    }

    public function notifyStatusUpdate(OverTimeRequests $request): void
    {
        try {
            $this->employeeNotificationService->notifyEmployee(
                $request,
                'overtime_status_updated',
                'تم تحديث حالة طلب العمل الإضافي الخاص بك'
            );

            if ($request->manager_status !== 'pending' && $request->hr_status === 'pending') {
                $this->managerNotificationService->notifyManagers(
                    $request,
                    'overtime_manager_response',
                    'تم الرد على طلب العمل الإضافي من قبل المدير',
                    true
                );
            }

            if ($request->hr_status !== 'pending' && $request->manager_status === 'pending') {
                $this->managerNotificationService->notifyManagers(
                    $request,
                    'overtime_hr_response',
                    'تم الرد على طلب العمل الإضافي من قبل HR',
                    false
                );
            }
        } catch (\Exception $e) {
        }
    }

    public function notifyOvertimeDeleted(OverTimeRequests $request): void
    {
        try {
            $this->employeeNotificationService->notifyEmployee(
                $request,
                'overtime_request_deleted',
                'تم حذف طلب العمل الإضافي الخاص بك'
            );

            $this->managerNotificationService->notifyManagers(
                $request,
                'overtime_request_deleted',
                'تم حذف طلب العمل الإضافي',
                false
            );

            $this->managerNotificationService->notifyManagers(
                $request,
                'overtime_request_deleted_hr',
                'تم حذف طلب العمل الإضافي',
                true
            );
        } catch (\Exception $e) {
        }
    }

    public function deleteExistingStatusNotifications(OverTimeRequests $request): void
    {
        try {
            Notification::where('related_id', $request->id)
                ->whereIn('type', [
                    'overtime_status_updated',
                    'overtime_manager_response',
                    'overtime_hr_response'
                ])
                ->delete();
        } catch (\Exception $e) {
        }
    }

    public function notifyOvertimeModified(OverTimeRequests $request): void
    {
        try {
            // Delete all existing notifications for this request
            Notification::where('related_id', $request->id)
                ->whereIn('type', [
                    'overtime_request_modified',
                    'overtime_request_modified_hr'
                ])
                ->delete();

            // Create new notifications
            if ($request->user_id !== Auth::id()) {
                $this->employeeNotificationService->notifyEmployee(
                    $request,
                    'overtime_request_modified',
                    'تم تعديل طلب العمل الإضافي الخاص بك'
                );
            }

            // Notify managers
            $this->managerNotificationService->notifyManagers(
                $request,
                'overtime_request_modified',
                'تم تعديل طلب العمل الإضافي',
                false
            );

            // Notify HR
            $this->managerNotificationService->notifyManagers(
                $request,
                'overtime_request_modified_hr',
                'تم تعديل طلب العمل الإضافي',
                true
            );

            Log::info('Overtime modification notifications sent successfully for request ID: ' . $request->id);
        } catch (\Exception $e) {
            Log::error('Error sending overtime modification notification: ' . $e->getMessage());
        }
    }

    public function notifyManagerStatusUpdate(OverTimeRequests $request): void
    {
        try {
            if ($request->user_id !== Auth::id()) {
                $this->employeeNotificationService->notifyEmployee(
                    $request,
                    'overtime_manager_status_updated',
                    'تم تحديث حالة طلب العمل الإضافي من قبل المدير'
                );
            }

            $this->managerNotificationService->notifyManagers(
                $request,
                'overtime_manager_status_updated_hr',
                'تم تحديث حالة طلب العمل الإضافي من قبل المدير',
                true
            );
        } catch (\Exception $e) {
            Log::error('Error sending manager status update notification: ' . $e->getMessage());
        }
    }

    public function notifyHRStatusUpdate(OverTimeRequests $request): void
    {
        try {
            if ($request->user_id !== Auth::id()) {
                $this->employeeNotificationService->notifyEmployee(
                    $request,
                    'overtime_hr_status_updated',
                    'تم تحديث حالة طلب العمل الإضافي من قبل HR'
                );
            }

            $this->managerNotificationService->notifyManagers(
                $request,
                'overtime_hr_status_updated_manager',
                'تم تحديث حالة طلب العمل الإضافي من قبل HR',
                false
            );
        } catch (\Exception $e) {
            Log::error('Error sending HR status update notification: ' . $e->getMessage());
        }
    }

    public function notifyStatusReset(OverTimeRequests $request, string $type): void
    {
        try {
            if ($request->user_id !== Auth::id()) {
                $this->employeeNotificationService->notifyEmployee(
                    $request,
                    'overtime_status_reset',
                    'تم إعادة تعيين حالة طلب العمل الإضافي'
                );
            }

            if ($type === 'manager') {
                $this->managerNotificationService->notifyManagers(
                    $request,
                    'overtime_manager_status_reset',
                    'تم إعادة تعيين حالة طلب العمل الإضافي من قبل المدير',
                    false
                );
            } else {
                $this->managerNotificationService->notifyManagers(
                    $request,
                    'overtime_hr_status_reset',
                    'تم إعادة تعيين حالة طلب العمل الإضافي من قبل HR',
                    true
                );
            }
        } catch (\Exception $e) {
            Log::error('Error sending status reset notification: ' . $e->getMessage());
        }
    }
}
