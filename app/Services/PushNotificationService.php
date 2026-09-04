<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    public function send(string $userId, string $title, string $message, string $url = '/dashboard'): void
    {
        if (! config('webpush.public_key') || ! config('webpush.private_key')) {
            return;
        }

        $webPush = new WebPush(['VAPID' => [
            'subject' => config('webpush.subject'),
            'publicKey' => config('webpush.public_key'),
            'privateKey' => config('webpush.private_key'),
        ]]);

        PushSubscription::where('user_id', $userId)->each(function (PushSubscription $stored) use ($webPush, $title, $message, $url) {
            $subscription = Subscription::create([
                'endpoint' => $stored->endpoint,
                'publicKey' => $stored->public_key,
                'authToken' => $stored->auth_token,
                'contentEncoding' => $stored->content_encoding,
            ]);
            $report = $webPush->sendOneNotification($subscription, json_encode(compact('title', 'message', 'url')));
            if ($report->isSubscriptionExpired()) {
                $stored->delete();
            }
        });
    }
}
