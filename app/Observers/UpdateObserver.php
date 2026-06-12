<?php

namespace App\Observers;

use App\Models\Update;
use Exception;
use Illuminate\Support\Facades\Log;

class UpdateObserver
{
    /**
     * Handle the Update "created" event - Send FCM notifications to donors
     */
    public function created(Update $update): void
    {
        try {
            $this->sendFcmNotifications($update);
        } catch (Exception $exception) {
            Log::error('Failed to send FCM notifications for update', [
                'update_id' => $update->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Send FCM notifications to all donors of the campaign
     */
    protected function sendFcmNotifications(Update $update): void
    {
        // Get unique donors who have donated to this campaign
        $donors = $update->campaign
            ->donations()
            ->with('user')
            ->whereHas('user', function ($query) {
                $query->whereNotNull('fcm_token');
            })
            ->get()
            ->pluck('user')
            ->unique('id');

        if ($donors->isEmpty()) {
            return;
        }

        $fcmTokens = $donors
            ->pluck('fcm_token')
            ->filter()
            ->toArray();

        if (empty($fcmTokens)) {
            return;
        }

        // Send FCM notification
        $this->sendToFirebase(
            $fcmTokens,
            $update->campaign->title,
            $update->title,
            $update->content,
            [
                'campaign_id' => (string) $update->campaign->id,
                'update_id' => (string) $update->id,
            ]
        );
    }

    /**
     * Send notification via Firebase Cloud Messaging
     */
    protected function sendToFirebase(
        array $tokens,
        string $campaignTitle,
        string $updateTitle,
        string $updateContent,
        array $metadata = []
    ): void {
        try {
            $firebaseServerKey = config('services.firebase.server_key');

            if (! $firebaseServerKey) {
                Log::warning('Firebase Server Key not configured');
                return;
            }

            $notificationPayload = [
                'notification' => [
                    'title' => $updateTitle,
                    'body' => $updateContent,
                    'sound' => 'default',
                ],
                'data' => array_merge([
                    'campaign_title' => $campaignTitle,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ], $metadata),
            ];

            // Send to multiple tokens (Firebase requires batching for multiple tokens)
            foreach ($tokens as $token) {
                $payload = array_merge($notificationPayload, [
                    'to' => $token,
                ]);

                $this->sendHttpRequest($firebaseServerKey, $payload);
            }

            Log::info('FCM notifications sent successfully', [
                'token_count' => count($tokens),
                'campaign_id' => $metadata['campaign_id'] ?? null,
            ]);
        } catch (Exception $exception) {
            Log::error('Firebase notification error: '.$exception->getMessage());
            throw $exception;
        }
    }

    /**
     * Send HTTP request to Firebase
     */
    protected function sendHttpRequest(string $serverKey, array $payload): void
    {
        $url = 'https://fcm.googleapis.com/fcm/send';

        $headers = [
            'Authorization: key='.$serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode !== 200) {
            Log::warning('FCM HTTP response code: '.$httpCode, [
                'response' => $response,
            ]);
        }
    }
}
