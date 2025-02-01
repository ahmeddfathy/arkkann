<?php

namespace App\Services;

use App\Models\OverTimeRequests;
use App\Models\Notification;
use App\Services\Notifications\OvertimeEmployeeNotificationService;
use App\Services\Notifications\OvertimeManagerNotificationService;
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
            // إشعار للمدراء
            $this->managerNotificationService->notifyManagers(
                $request,
                'new_overtime_request',
                'تم تقديم طلب عمل إضافي جديد'
            );

            // إشعار لـ HR
            $this->managerNotificationService->notifyManagers(
                $request,
                'new_overtime_request_hr',
                'تم تقديم طلب عمل إضافي جديد يحتاج لمراجعتك',
                true
            );

            Log::info('Overtime request notifications created successfully', ['request_id' => $request->id]);
        } catch (\Exception $e) {
            Log::error('Error creating overtime request notifications: ' . $e->getMessage(), [
                'request_id' => $request->id
            ]);
        }
    }

    public function notifyStatusUpdate(OverTimeRequests $request): void
    {
        try {
            // إشعار للموظف
            $this->employeeNotificationService->notifyEmployee(
                $request,
                'overtime_status_updated',
                'تم تحديث حالة طلب العمل الإضافي الخاص بك'
            );

            // إذا كان الرد من المدير، نرسل إشعار لـ HR
            if ($request->manager_status !== 'pending' && $request->hr_status === 'pending') {
                $this->managerNotificationService->notifyManagers(
                    $request,
                    'overtime_manager_response',
                    'تم الرد على طلب العمل الإضافي من قبل المدير',
                    true
                );
            }

            // إذا كان الرد من HR، نرسل إشعار للمدير
            if ($request->hr_status !== 'pending' && $request->manager_status === 'pending') {
                $this->managerNotificationService->notifyManagers(
                    $request,
                    'overtime_hr_response',
                    'تم الرد على طلب العمل الإضافي من قبل HR',
                    false
                );
            }

            Log::info('Status update notifications sent successfully', ['request_id' => $request->id]);
        } catch (\Exception $e) {
            Log::error('Error sending status update notifications: ' . $e->getMessage(), [
                'request_id' => $request->id
            ]);
        }
    }

    public function notifyOvertimeDeleted(OverTimeRequests $request): void
    {
        try {
            // إشعار للموظف
            $this->employeeNotificationService->notifyEmployee(
                $request,
                'overtime_request_deleted',
                'تم حذف طلب العمل الإضافي الخاص بك'
            );

            // إشعار للمدير
            $this->managerNotificationService->notifyManagers(
                $request,
                'overtime_request_deleted',
                'تم حذف طلب العمل الإضافي',
                false
            );

            // إشعار لـ HR
            $this->managerNotificationService->notifyManagers(
                $request,
                'overtime_request_deleted_hr',
                'تم حذف طلب العمل الإضافي',
                true
            );

            Log::info('Overtime deletion notifications sent successfully', ['request_id' => $request->id]);
        } catch (\Exception $e) {
            Log::error('Error sending overtime deletion notifications: ' . $e->getMessage(), [
                'request_id' => $request->id
            ]);
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

            Log::info('Existing status notifications deleted successfully', ['request_id' => $request->id]);
        } catch (\Exception $e) {
            Log::error('Error deleting existing status notifications: ' . $e->getMessage(), [
                'request_id' => $request->id
            ]);
        }
    }
}
