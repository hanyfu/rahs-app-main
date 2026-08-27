<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\TaskService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $profile = (new TaskService)->currentProfile();
        if (! $profile) {
            $profile = Profile::create([
                'id' => auth()->id(),
                'email' => auth()->user()->email,
                'first_name' => '',
                'last_name' => '',
            ]);
        }

        return view('settings.index', compact('profile'));
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'contact_no' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\s+\-()]*$/'],
            'designation' => ['nullable', 'string', 'max:100'],
            'avatar_url' => ['nullable', 'string'],
        ]);

        $profile = Profile::query()->findOrFail(auth()->id());
        $profile->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'contact_no' => $data['contact_no'] ?: null,
            'designation' => $data['designation'] ?: null,
            'avatar_url' => $data['avatar_url'] ?? $profile->avatar_url,
        ]);

        return response()->json(['success' => true]);
    }

    public function updateAvatar(Request $request)
    {
        $data = $request->validate([
            'avatar_url' => ['required', 'string'],
        ]);

        $profile = Profile::query()->findOrFail(auth()->id());
        $profile->update(['avatar_url' => $data['avatar_url']]);

        return response()->json(['success' => true]);
    }
}
