<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Atoll;
use App\Models\Island;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AtollController extends Controller
{
    public function index()
    {
        return response()->json(
            Atoll::query()->with(['coordinator:id,first_name,last_name', 'supervisor:id,first_name,last_name'])->orderBy('name')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
            'coordinator_id' => ['nullable', 'string'],
        ]);

        $atoll = Atoll::create([
            'name' => trim($data['name']),
            'status' => $data['status'] ?? 'active',
            'coordinator_id' => $data['coordinator_id'] ?: null,
        ]);

        return response()->json($atoll->load('coordinator:id,first_name,last_name'), 201);
    }

    public function bulkImport(Request $request)
    {
        $data = $request->validate([
            'atolls' => ['required', 'array', 'min:1'],
            'atolls.*.name' => ['required', 'string', 'max:255'],
        ]);

        $created = [];
        foreach ($data['atolls'] as $row) {
            $name = trim($row['name']);
            if (! $name) {
                continue;
            }
            $created[] = Atoll::create(['name' => $name, 'status' => 'active']);
        }

        return response()->json(['success' => true, 'created' => count($created)]);
    }

    public function update(Request $request, string $id)
    {
        $atoll = Atoll::query()->findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
            'coordinator_id' => ['nullable', 'string'],
        ]);

        $atoll->update([
            'name' => trim($data['name']),
            'status' => $data['status'] ?? $atoll->status,
            'coordinator_id' => $data['coordinator_id'] ?: null,
        ]);

        return response()->json($atoll->load('coordinator:id,first_name,last_name'));
    }

    public function destroy(string $id)
    {
        $atoll = Atoll::query()->findOrFail($id);

        DB::transaction(function () use ($atoll) {
            $islandIds = Island::query()->where('atoll_id', $atoll->id)->pluck('id');

            if ($islandIds->isNotEmpty()) {
                DB::table('hospital_profiles')->whereIn('island_id', $islandIds)->delete();
                DB::table('hospital_contacts')->whereIn('island_id', $islandIds)->update(['island_id' => null]);
                DB::table('tasks')->whereIn('island_id', $islandIds)->update(['island_id' => null]);
            }

            $atoll->delete();
        });

        return response()->json(['success' => true]);
    }
}
