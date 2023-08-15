<?php

namespace App\Services\ThirdPartyAPIs;

use Illuminate\Support\Facades\Http;

class FirebaseApis
{
    public static string $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    protected static function headers(): array
    {
        return [
            'Content-type' => 'application/json',
            'Authorization' => 'key='.env('FCM_KEY'),
        ];
    }

    public static function sendNotificationFCM($notification_id, $title, $message, $id, $type): int
    {
        $data = [
            'to' => $notification_id,
            'data' => [
                'body' => '',
                'title' => $title,
                'type' => $type,
                'id' => $id,
                'message' => $message,
            ],
            'notification' => [
                'body' => $message,
                'title' => $title,
                'type' => $type,
                'id' => $id,
                'message' => $message,
                'icon' => 'new',
                'sound' => 'default',
            ],
        ];

        $response = Http::withHeaders(self::headers())->post(self::$fcmUrl, $data);

        return $response->successful();
    }
}
