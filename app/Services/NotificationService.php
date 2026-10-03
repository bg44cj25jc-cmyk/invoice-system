<?php
// app/Services/NotificationService.php

namespace App\Services;

use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Validator;
use Exception;

class NotificationService
{
    private FirebaseNotificationService $firebaseService;

    public function __construct(FirebaseNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function sendNotification(array $notificationData): array
    {
        // Validate input
        $validator = Validator::make($notificationData, [
            'device_tokens' => 'required|string',
            'title' => 'required|string',
            'body' => 'required|string',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return [
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()->first(),
                'code' => 422
            ];
        }

        try {
            $result = $this->firebaseService->sendToDevice(
                $notificationData['device_tokens'],
                $notificationData['title'],
                $notificationData['body'],
                $notificationData['data'] ?? []
            );

            if ($result) {
                return [
                    'status' => true,
                    'message' => 'Notification sent successfully',
                    'data' => $result,
                    'code' => 200
                ];
            }

            return [
                'status' => false,
                'message' => 'Failed to send notification',
                'data' => null,
                'code' => 500
            ];

        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }
}