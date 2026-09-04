<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function config()
    {
        return response()->json(['publicKey' => config('webpush.public_key')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:4096'],
            'keys.p256dh' => ['required', 'string', 'max:1024'],
            'keys.auth' => ['required', 'string', 'max:1024'],
        ]);

        $subscription = PushSubscription::updateOrCreate(
            ['user_id' => auth()->id(), 'endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => 'aes128gcm',
                'device_name' => substr((string) $request->userAgent(), 0, 255),
            ]
        );

        return response()->json($subscription, 201);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['endpoint' => ['required', 'url', 'max:4096']]);
        PushSubscription::where('user_id', auth()->id())->where('endpoint_hash', hash('sha256', $data['endpoint']))->delete();

        return response()->json(['success' => true]);
    }
}
