<?php

/**
 * Notification System Usage Examples
 *
 * This file demonstrates how to use the notification system in various scenarios
 */

namespace App\Examples;

use App\Models\User;
use App\Models\AbsenceRequest;
use App\Models\PermissionRequest;
use App\Models\OverTimeRequest;
use App\Services\NotificationService;
use App\Services\FirebaseNotificationService;
use App\Services\NotificationPermissionService;
use App\Services\NotificationOvertimeService;

/**
 * Example 1: Creating a new absence request notification
 */
function createAbsenceRequestNotification(AbsenceRequest $request, NotificationService $notificationService)
{
    // This will notify both team managers and HR about the new absence request
    $notificationService->createLeaveRequestNotification($request);
}

/**
 * Example 2: Creating a status update notification for an absence request
 */
function updateAbsenceRequestStatus(AbsenceRequest $request, NotificationService $notificationService)
{
    // Update the request status first
    $request->status = 'approved'; // or 'rejected', 'pending'
    $request->manager_status = 'approved';
    $request->save();

    // This will notify the employee about the status update
    // and relevant stakeholders based on the current user's role
    $notificationService->createStatusUpdateNotification($request);
}

/**
 * Example 3: Sending a Firebase push notification directly
 */
function sendPushNotification(User $user, FirebaseNotificationService $firebaseService)
{
    // Check if the user has a registered FCM token
    if (!empty($user->fcm_token)) {
        $firebaseService->sendNotification(
            $user->fcm_token,
            'Important Notification',
            'This is an important message for you',
            '/dashboard/notifications'
        );
    }
}

/**
 * Example 4: Broadcasting notifications to all employees
 */
function broadcastToAllEmployees(FirebaseNotificationService $firebaseService)
{
    $firebaseService->sendNotificationToEmployees(
        'Company Announcement',
        'There will be a company meeting tomorrow at 10 AM',
        '/announcements'
    );
}

/**
 * Example 5: Creating a permission request notification
 */
function createPermissionRequestNotification(
    PermissionRequest $request,
    NotificationPermissionService $notificationService
)
{
    // This will notify managers about the new permission request
    $notificationService->createPermissionRequestNotification($request);
}

/**
 * Example 6: Creating an overtime request notification
 */
function createOvertimeRequestNotification(
    OverTimeRequest $request,
    NotificationOvertimeService $notificationService
)
{
    // This will notify managers about the new overtime request
    $notificationService->createOvertimeRequestNotification($request);
}

/**
 * Example 7: Acknowledging an administrative decision
 */
function acknowledgeAdministrativeDecision($decisionId, $userId)
{
    // Find the decision
    $decision = \App\Models\AdministrativeDecision::findOrFail($decisionId);

    // Verify this decision belongs to the user
    if ($decision->user_id !== $userId) {
        throw new \Exception('Unauthorized access');
    }

    // Mark as acknowledged
    $decision->update([
        'acknowledged_at' => now()
    ]);

    return 'Decision acknowledged successfully';
}

/**
 * Example 8: Marking notifications as read
 */
function markNotificationsAsRead($notificationIds, $userId)
{
    // Get notifications that belong to the user
    $notifications = \App\Models\Notification::whereIn('id', $notificationIds)
        ->where('user_id', $userId)
        ->get();

    foreach ($notifications as $notification) {
        $notification->markAsRead();
    }

    return count($notifications) . ' notifications marked as read';
}

/**
 * Example 9: Getting unread notifications count
 */
function getUnreadNotificationsCount($userId)
{
    return \App\Models\Notification::where('user_id', $userId)
        ->whereNull('read_at')
        ->count();
}

/**
 * Example 10: Using the notification system in a controller
 *
 * This is how you would typically use the notification service in a controller:
 */

class ExampleController extends \App\Http\Controllers\Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function storeAbsenceRequest()
    {
        // Create and save the request
        $request = new AbsenceRequest();
        $request->user_id = auth()->id();
        $request->absence_date = request('absence_date');
        $request->reason = request('reason');
        $request->save();

        // Send notification
        $this->notificationService->createLeaveRequestNotification($request);

        return response()->json([
            'success' => true,
            'message' => 'Absence request created successfully'
        ]);
    }

    public function respondToAbsenceRequest($id)
    {
        $request = AbsenceRequest::findOrFail($id);
        $request->status = request('status');
        $request->save();

        $this->notificationService->createStatusUpdateNotification($request);

        return response()->json([
            'success' => true,
            'message' => 'Response submitted successfully'
        ]);
    }
}
