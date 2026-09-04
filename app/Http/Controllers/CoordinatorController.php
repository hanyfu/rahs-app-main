<?php

namespace App\Http\Controllers;

use App\Models\Atoll;
use App\Models\Profile;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoordinatorController extends Controller
{
    public function directoryData(): array
    {
        $profiles = Profile::query()
            ->with('userRole')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $atolls = Atoll::query()->where('status', 'active')->orderBy('name')->get();

        $managers = [];

        foreach ($profiles as $profile) {
            $role = $profile->userRole?->role;
            if (! in_array($role, ['coordinator', 'supervisor'], true)) {
                continue;
            }

            $column = $role === 'coordinator' ? 'coordinator_id' : 'supervisor_id';
            $assignedAtolls = $atolls->where($column, $profile->id)->values();

            $managers[] = [
                'id' => $profile->id,
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'full_name' => $profile->full_name,
                'initials' => $profile->initials,
                'designation' => $profile->designation,
                'email' => $profile->email,
                'contact_no' => $profile->contact_no,
                'avatar_url' => $profile->avatar_url,
                'role' => $role,
                'status' => $profile->status,
                'assigned_atolls' => $assignedAtolls->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values(),
            ];
        }

        return [
            'managers' => $managers,
            'atolls' => $atolls->map(fn ($a) => ['id' => $a->id, 'name' => $a->name]),
        ];
    }

    public function data()
    {
        return response()->json($this->directoryData());
    }

    public function updateAssignments(Request $request)
    {
        if (! in_array(auth()->user()->role, ['admin', 'supervisor'], true)) {
            abort(403, 'Permission denied');
        }

        $data = $request->validate([
            'profileId' => ['required', 'string'],
            'role' => ['required', 'in:coordinator,supervisor'],
            'atollIds' => ['required', 'array'],
            'atollIds.*' => ['string'],
        ]);

        $column = $data['role'] === 'coordinator' ? 'coordinator_id' : 'supervisor_id';

        DB::transaction(function () use ($data, $column) {
            Atoll::query()->where($column, $data['profileId'])->update([$column => null]);

            foreach ($data['atollIds'] as $atollId) {
                Atoll::query()->where('id', $atollId)->update([$column => $data['profileId']]);
            }
        });

        return response()->json(['success' => true]);
    }

    public function deactivate(Request $request)
    {
        if (! in_array(auth()->user()->role, ['admin', 'supervisor'], true)) {
            abort(403, 'Permission denied');
        }

        $data = $request->validate(['profileId' => ['required', 'string']]);

        DB::transaction(function () use ($data) {
            UserRole::query()->where('user_id', $data['profileId'])->update(['role' => 'staff']);
            Atoll::query()->where('coordinator_id', $data['profileId'])->update(['coordinator_id' => null]);
            Atoll::query()->where('supervisor_id', $data['profileId'])->update(['supervisor_id' => null]);
        });

        return response()->json(['success' => true]);
    }
}
