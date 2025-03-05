<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\AbsenceRequest;

use Illuminate\Support\Facades\Log;

class EmployeeNotificationService
{
    public function notifyEmployee(AbsenceRequest $request, string $type, array $data): void
    {
        try {
            $notificationData = array_merge($data, [
                'request_details' => [
                    'absence_date' => $request->absence_date->format('Y-m-d'),
                    'reason' => $request->reason,
                    'status' => $request->status
                ]
            ]);

            Notification::create([
                'user_id' => $request->user_id,
                'type' => $type,
                'data' => $notificationData,
                'related_id' => $request->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error in notifyEmployee: ' . $e->getMessage());
        }
    }

    public function notifyTeamMembers(AbsenceRequest $request, string $type, array $data): void
    {
        try {
            if ($request->user && $request->user->currentTeam) {
                $teamMembers = $request->user->currentTeam->users()
                    ->where('users.id', '!=', $request->user_id)
                    ->get();

                foreach ($teamMembers as $member) {
                    Notification::create([
                        'user_id' => $member->id,
                        'type' => $type,
                        'data' => $data,
                        'related_id' => $request->id
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in notifyTeamMembers: ' . $e->getMessage());
        }
    }

    public function deleteExistingNotifications(AbsenceRequest $request, string $type): void
    {
        try {
            Notification::where('related_id', $request->id)
                ->where('type', $type)
                ->delete();
        } catch (\Exception $e) {
            Log::error('Error in deleteExistingNotifications: ' . $e->getMessage());
        }
    }
}
