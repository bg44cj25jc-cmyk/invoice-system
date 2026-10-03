<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $serviceAccountPath = storage_path('app/firebase/doctolik-26752-06bb85ca36c2.json');

        $this->messaging = (new Factory)
            ->withServiceAccount($serviceAccountPath)
            ->createMessaging();
    }

    public function sendToDevice($deviceToken, string $title, string $body, array $data = [])
    {
        try {
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification([
                    'title' => $title,
                    'body' => $body
                ])
                ->withData($data);

            $this->messaging->send($message);
            return true;

        } catch (\Exception $e) {
            logger()->error('FCM Error: '.$e->getMessage());

            // TEMPORARY: return the error message to the client for debugging
            return $e->getMessage(); // <-- add this
        }

    }
}