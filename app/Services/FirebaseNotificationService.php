<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    private $credentials;
    private $accessToken;

    public function __construct()
    {
        $firebaseKey = file_get_contents(storage_path('app/firebase/hr-system-46dda-firebase-adminsdk-fbsvc-4465c46c3e.json'));
        $this->credentials = json_decode($firebaseKey, true);
    }

    public function sendNotification(string $fcmToken, string $title, string $body, string $link = '/test')
    {
        try {
            Log::info('Starting to send notification', [
                'token' => $fcmToken,
                'title' => $title,
                'body' => $body,
                'link' => $link
            ]);

            if (!$this->accessToken) {
                Log::info('Getting new access token');
                $this->accessToken = $this->getAccessToken();
            }

            $payload = [
                'message' => [
                    'token' => $fcmToken,
                    'data' => [
                        'url' => $link,
                        'title' => $title,
                        'body' => $body
                    ],
                    'webpush' => [
                        'headers' => [
                            'Urgency' => 'high'
                        ],
                        'fcm_options' => [
                            'link' => $link
                        ]
                    ]
                ]
            ];

            Log::info('Sending FCM request with payload', [
                'payload' => $payload
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/v1/projects/hr-system-46dda/messages:send', $payload);

            Log::info('FCM response received', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            return [
                'success' => $response->successful(),
                'message' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('Error sending notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function sendNotificationToAdmins(string $title, string $body, string $link = '/admin/dashboard')
    {
        try {
            Log::info('Starting to send notifications to admins');

            // Get all admin users who have FCM tokens
            $admins = User::where('role', 'admin')
                         ->whereNotNull('fcm_token')
                         ->get();

            // Add this debug line
            Log::info('Admin users query', [
                'sql' => User::where('role', 'admin')->whereNotNull('fcm_token')->toSql(),
                'count' => $admins->count(),
                'admins' => $admins->toArray()
            ]);

            Log::info('Found admins with FCM tokens', [
                'count' => $admins->count(),
                'admin_ids' => $admins->pluck('id')->toArray()
            ]);

            $results = [];
            foreach ($admins as $admin) {
                try {
                    Log::info('Sending notification to admin', [
                        'admin_id' => $admin->id,
                        'fcm_token' => $admin->fcm_token
                    ]);

                    $result = $this->sendNotification(
                        $admin->fcm_token,
                        $title,
                        $body,
                        $link
                    );

                    Log::info('Notification sent to admin successfully', [
                        'admin_id' => $admin->id,
                        'result' => $result
                    ]);

                    $results[$admin->id] = $result;
                } catch (\Exception $e) {
                    Log::error("Failed to send notification to admin {$admin->id}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'fcm_token' => $admin->fcm_token
                    ]);
                    $results[$admin->id] = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('Error sending notifications to admins', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function sendNotificationToEmployees(string $title, string $body, string $link = '/employee/dashboard')
    {
        try {
            Log::info('Starting to send notifications to employees');

            // Get all employee users who have FCM tokens
            $employees = User::where('role', 'employee')
                         ->whereNotNull('fcm_token')
                         ->get();

            Log::info('Found employees with FCM tokens', [
                'count' => $employees->count(),
                'employee_ids' => $employees->pluck('id')->toArray()
            ]);

            $results = [];
            foreach ($employees as $employee) {
                try {
                    Log::info('Sending notification to employee', [
                        'employee_id' => $employee->id,
                        'fcm_token' => $employee->fcm_token
                    ]);

                    $result = $this->sendNotification(
                        $employee->fcm_token,
                        $title,
                        $body,
                        $link
                    );

                    Log::info('Notification sent to employee successfully', [
                        'employee_id' => $employee->id,
                        'result' => $result
                    ]);

                    $results[$employee->id] = $result;
                } catch (\Exception $e) {
                    Log::error("Failed to send notification to employee {$employee->id}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'fcm_token' => $employee->fcm_token
                    ]);
                    $results[$employee->id] = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('Error sending notifications to employees', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    private function getAccessToken()
    {
        try {
            $now = time();
            $payload = [
                'iss' => $this->credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $this->credentials['token_uri'],
                'exp' => $now + 3600,
                'iat' => $now
            ];

            $jwt = $this->generateJWT($payload, $this->credentials['private_key']);

            $response = Http::asForm()->post($this->credentials['token_uri'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]);

            if (!$response->successful()) {
                Log::error('Failed to get access token', [
                    'response' => $response->json()
                ]);
                throw new \Exception('Failed to get access token: ' . $response->body());
            }

            return $response->json()['access_token'];

        } catch (\Exception $e) {
            Log::error('Error getting access token', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function generateJWT($payload, $privateKey)
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
        $payload = json_encode($payload);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;
        openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
}
