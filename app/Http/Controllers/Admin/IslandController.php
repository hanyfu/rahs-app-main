<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Atoll;
use App\Models\Island;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IslandController extends Controller
{
    public function index()
    {
        return response()->json(
            Island::query()->with(['atoll:id,name', 'assignedStaff:id,first_name,last_name'])->orderBy('name')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'atoll_id' => ['required', 'string'],
            'assigned_staff_id' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        if (! Atoll::query()->where('id', $data['atoll_id'])->exists()) {
            throw ValidationException::withMessages(['atoll_id' => 'Please select an atoll']);
        }

        $this->validateAssignedStaff($data['assigned_staff_id'] ?? null);

        $island = Island::create([
            'name' => trim($data['name']),
            'atoll_id' => $data['atoll_id'],
            'assigned_staff_id' => $data['assigned_staff_id'] ?: null,
            'status' => $data['status'] ?? 'active',
        ]);

        return response()->json($island->load(['atoll:id,name', 'assignedStaff:id,first_name,last_name']), 201);
    }

    public function bulkAdd(Request $request)
    {
        $data = $request->validate([
            'atoll_id' => ['required', 'string'],
            'names' => ['required', 'string'],
        ]);

        $names = collect(preg_split('/\r?\n/', $data['names']))
            ->map(fn ($n) => trim($n))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            throw ValidationException::withMessages(['names' => 'Enter at least one island name']);
        }

        $created = 0;
        foreach ($names as $name) {
            Island::create(['name' => $name, 'atoll_id' => $data['atoll_id'], 'status' => 'active']);
            $created++;
        }

        return response()->json(['success' => true, 'created' => $created]);
    }

    public function update(Request $request, string $id)
    {
        $island = Island::query()->findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'atoll_id' => ['required', 'string'],
            'assigned_staff_id' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $this->validateAssignedStaff($data['assigned_staff_id'] ?? null);

        $island->update([
            'name' => trim($data['name']),
            'atoll_id' => $data['atoll_id'],
            'assigned_staff_id' => $data['assigned_staff_id'] ?: null,
            'status' => $data['status'] ?? $island->status,
        ]);

        return response()->json($island->load(['atoll:id,name', 'assignedStaff:id,first_name,last_name']));
    }

    public function destroy(string $id)
    {
        $island = Island::query()->findOrFail($id);

        DB::transaction(function () use ($island) {
            DB::table('hospital_profiles')->where('island_id', $island->id)->delete();
            DB::table('hospital_contacts')->where('island_id', $island->id)->update(['island_id' => null]);
            DB::table('tasks')->where('island_id', $island->id)->update(['island_id' => null]);
            $island->delete();
        });

        return response()->json(['success' => true]);
    }

    private function validateAssignedStaff(?string $profileId): void
    {
        if (! $profileId) {
            return;
        }

        $eligible = Profile::query()
            ->whereKey($profileId)
            ->where('status', 'active')
            ->whereHas('userRole', fn ($query) => $query->where('role', 'staff'))
            ->exists();

        if (! $eligible) {
            throw ValidationException::withMessages([
                'assigned_staff_id' => 'Select an active user with the Staff role.',
            ]);
        }
    }
}
