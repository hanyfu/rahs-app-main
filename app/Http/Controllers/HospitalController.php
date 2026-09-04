<?php

namespace App\Http\Controllers;

use App\Models\Atoll;
use App\Models\HospitalContact;
use App\Models\HospitalProfile;
use App\Models\Island;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HospitalController extends Controller
{
    public function index()
    {
        $role = auth()->user()->role;
        $me = auth()->id();

        $contactsQuery = HospitalContact::query()->with(['island.atoll'])->where('status', 'active');

        // The hospital directory is shared operational information and is
        // visible to every authenticated role. Editing remains scoped below
        // and is also enforced by assertProfileAccess() when saving.
        if ($role === 'coordinator') {
            $editableIslandIds = Island::query()
                ->whereIn('atoll_id', Atoll::query()->where('coordinator_id', $me)->pluck('id'))
                ->pluck('id');
        } elseif ($role === 'staff') {
            $editableIslandIds = Island::query()->where('assigned_staff_id', $me)->pluck('id');
        } elseif ($role === 'supervisor') {
            $editableIslandIds = Island::query()
                ->whereIn('atoll_id', Atoll::query()->where('supervisor_id', $me)->pluck('id'))
                ->pluck('id');
        } elseif ($role === 'admin') {
            $editableIslandIds = Island::query()->pluck('id');
        } else {
            $editableIslandIds = collect();
        }

        $contacts = $contactsQuery->orderBy('hospital_name')->get();

        // Latest profile per contact and per island so the directory can show a
        // preview of the staff-maintained data without an extra round-trip.
        $profileByContact = [];
        $profileByIsland = [];
        foreach (HospitalProfile::query()->orderBy('updated_at', 'desc')->get() as $profile) {
            if ($profile->hospital_contact_id && ! isset($profileByContact[$profile->hospital_contact_id])) {
                $profileByContact[$profile->hospital_contact_id] = $profile;
            }
            if ($profile->island_id && ! isset($profileByIsland[$profile->island_id])) {
                $profileByIsland[$profile->island_id] = $profile;
            }
        }

        $preview = function (?HospitalProfile $profile): ?array {
            if (! $profile) {
                return null;
            }

            return [
                'beds' => $profile->no_of_beds ?? 0,
                'grade' => $profile->grade,
                'population' => $profile->population,
                'updated_at' => $profile->updated_at?->toIso8601String(),
            ];
        };

        $contacts->each(function ($contact) use ($profileByContact, $profileByIsland, $preview) {
            $contact->profile_preview = $preview(
                $profileByContact[$contact->id] ?? $profileByIsland[$contact->island_id] ?? null
            );
        });

        // Every island is a facility. Islands that have no registered hospital
        // contact still appear in the directory so the profile staff maintain
        // on their dashboard is always visible to other users.
        $contactIslandIds = $contacts->pluck('island_id');
        $extraIslands = Island::query()
            ->where('status', 'active')
            ->whereNotIn('id', $contactIslandIds)
            ->with('atoll')
            ->orderBy('name')
            ->get()
            ->map(function ($island) use ($profileByIsland, $preview) {
                return [
                    'id' => $island->id,
                    'island_facility' => true,
                    'hospital_name' => $island->name.' Health Facility',
                    'island_id' => $island->id,
                    'manager_name' => null,
                    'contact_number' => null,
                    'contact_designation' => null,
                    'notes' => null,
                    'status' => 'active',
                    'island' => [
                        'id' => $island->id,
                        'name' => $island->name,
                        'atoll_id' => $island->atoll_id,
                        'atoll' => $island->atoll,
                    ],
                    'profile_preview' => $preview($profileByIsland[$island->id] ?? null),
                ];
            });

        $contacts = $contacts->concat($extraIslands)->sortBy('hospital_name')->values();

        // Coverage metric for leadership: how many islands updated their
        // profile within the last 30 days.
        $coverage = null;
        if (in_array($role, ['admin', 'supervisor'], true)) {
            $staleAfter = now()->subDays(30);
            $activeIslands = Island::query()->where('status', 'active')->get();
            $updatedIslandIds = collect($profileByIsland)->filter(
                fn ($profile) => $profile->updated_at && $profile->updated_at->gte($staleAfter)
            )->keys();
            $coverage = [
                'updated' => $activeIslands->whereIn('id', $updatedIslandIds)->count(),
                'total' => $activeIslands->count(),
                'missing' => $activeIslands
                    ->reject(fn ($island) => $updatedIslandIds->contains($island->id))
                    ->pluck('name')
                    ->sort()
                    ->values(),
            ];
        }

        $atolls = Atoll::query()->where('status', 'active')->orderBy('name')->get();
        $islands = Island::query()->where('status', 'active')->orderBy('name')->get();

        return view('hospitals.index', compact('contacts', 'atolls', 'islands', 'role', 'editableIslandIds', 'coverage'));
    }

    public function contacts()
    {
        $contacts = HospitalContact::query()
            ->with(['island.atoll'])
            ->where('status', 'active')
            ->orderBy('hospital_name')
            ->get();

        return response()->json($contacts);
    }

    public function storeContact(Request $request)
    {
        $this->requirePermission('manage_hospitals');
        $data = $request->validate([
            'hospital_name' => ['required', 'string', 'max:255'],
            'island_id' => ['nullable', 'string'],
            'manager_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:50'],
            'contact_designation' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $contact = HospitalContact::create([
            'hospital_name' => $data['hospital_name'],
            'island_id' => $data['island_id'] ?: null,
            'manager_name' => $data['manager_name'],
            'contact_number' => $data['contact_number'],
            'contact_designation' => $data['contact_designation'] ?: null,
            'notes' => $data['notes'] ?: null,
            'status' => 'active',
        ]);

        return response()->json($contact->load('island.atoll'), 201);
    }

    public function updateContact(Request $request, string $id)
    {
        $this->requirePermission('manage_hospitals');
        $contact = HospitalContact::query()->findOrFail($id);

        $data = $request->validate([
            'hospital_name' => ['nullable', 'string', 'max:255'],
            'island_id' => ['nullable', 'string'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'contact_designation' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $contact->update([
            'hospital_name' => $data['hospital_name'] ?? $contact->hospital_name,
            'island_id' => array_key_exists('island_id', $data) ? ($data['island_id'] ?: null) : $contact->island_id,
            'manager_name' => $data['manager_name'] ?? $contact->manager_name,
            'contact_number' => $data['contact_number'] ?? $contact->contact_number,
            'contact_designation' => $data['contact_designation'] ?? $contact->contact_designation,
            'notes' => $data['notes'] ?? $contact->notes,
        ]);

        return response()->json($contact->load('island.atoll'));
    }

    public function deactivateContact(Request $request)
    {
        $ids = $request->validate(['ids' => ['required', 'array']])['ids'];

        HospitalContact::query()->whereIn('id', $ids)->update(['status' => 'inactive']);

        return response()->json(['success' => true]);
    }

    public function importCsv(Request $request)
    {
        $data = $request->validate([
            'contacts' => ['required', 'array', 'min:1', 'max:1000'],
            'contacts.*.hospital_name' => ['required', 'string'],
            'contacts.*.manager_name' => ['required', 'string'],
            'contacts.*.contact_number' => ['required', 'string'],
            'contacts.*.island_id' => ['nullable', 'string'],
            'contacts.*.contact_designation' => ['nullable', 'string'],
        ]);

        $imported = 0;
        DB::transaction(function () use ($data, &$imported) {
            foreach ($data['contacts'] as $row) {
                HospitalContact::create([
                    'hospital_name' => $row['hospital_name'],
                    'island_id' => $row['island_id'] ?: null,
                    'manager_name' => $row['manager_name'],
                    'contact_number' => $row['contact_number'],
                    'contact_designation' => $row['contact_designation'] ?: null,
                    'status' => 'active',
                ]);
                $imported++;
            }
        });

        return response()->json(['success' => true, 'imported' => $imported]);
    }

    public function showProfile(string $hospitalContactId)
    {
        // Accept either a hospital contact id or an island id so profiles that
        // staff maintain on their dashboard (which may be island-linked when no
        // contact exists) are always reachable from the directory.
        $contact = HospitalContact::query()->find($hospitalContactId);
        $islandId = null;
        $targetContactId = null;

        if ($contact) {
            $targetContactId = $contact->id;
            $islandId = $contact->island_id;
        } else {
            $island = Island::query()->findOrFail($hospitalContactId);
            $islandId = $island->id;
            $contact = HospitalContact::query()
                ->where('island_id', $islandId)
                ->where('status', 'active')
                ->latest('created_at')
                ->first();
            $targetContactId = $contact?->id;
        }

        $profile = HospitalProfile::query()
            ->where(function ($query) use ($targetContactId, $islandId) {
                $query->where('island_id', $islandId);
                if ($targetContactId) {
                    $query->orWhere('hospital_contact_id', $targetContactId);
                }
            });
        if ($targetContactId) {
            $profile->orderByRaw('hospital_contact_id = ? desc', [$targetContactId]);
        }
        $profile = $profile->latest('updated_at')->first();

        return response()->json($profile ?: [
            'hospital_contact_id' => $targetContactId,
            'island_id' => $islandId,
        ]);
    }

    public function saveProfile(Request $request)
    {
        $this->requirePermission('edit_hospital_profiles');
        $target = $request->validate([
            'hospital_contact_id' => ['nullable', 'string'],
        ])['hospital_contact_id'] ?? null;

        $role = auth()->user()->role;

        // Resolve the save target: a real hospital contact, an island id (an
        // island facility with no contact), or null (staff standalone draft).
        $contact = $target ? HospitalContact::query()->find($target) : null;
        $targetIsland = null;
        if (! $contact && $target) {
            $targetIsland = Island::query()->find($target);
        }

        $hospitalContactId = null;
        $islandId = null;

        if ($contact) {
            $hospitalContactId = $contact->id;
            $islandId = $contact->island_id;
            if (! $islandId) {
                throw ValidationException::withMessages([
                    'hospital_contact_id' => 'Assign this hospital to an island before editing its profile.',
                ]);
            }
            $this->assertProfileAccess($islandId);
        } elseif ($targetIsland) {
            $islandId = $targetIsland->id;

            $this->assertProfileAccess($islandId);
        } else {
            // A missing target is only resolved for staff who have an active
            // island assignment. Unassigned users cannot create shared drafts.
            if ($role !== 'staff') {
                throw ValidationException::withMessages([
                    'hospital_contact_id' => 'Select a hospital before editing its profile.',
                ]);
            }
            $assignedIsland = Island::query()
                ->where('assigned_staff_id', auth()->id())
                ->where('status', 'active')
                ->first();
            if (! $assignedIsland) {
                throw ValidationException::withMessages([
                    'island_id' => 'You must be assigned to a hospital before editing its profile.',
                ]);
            }
            $islandId = $assignedIsland->id;
        }

        $numericFields = [
            'no_of_beds', 'population', 'avg_outpatient_per_day', 'avg_inpatient_per_month',
            'staff_physiotherapy', 'staff_dermatology', 'staff_ortho', 'staff_medicine', 'staff_surgeon',
            'staff_gynaecology', 'staff_paediatrician', 'staff_ent', 'staff_dental', 'staff_ophthalmology',
            'staff_psychology', 'staff_radiology', 'staff_anesthesiologist', 'staff_medical_officer',
            'staff_psychiatrist', 'nurses_clinical', 'nurses_senior_registered', 'nurses_registered',
            'nurses_enrolled', 'admin_officers_senior', 'admin_officers', 'customer_service', 'drivers',
            'lab_tech', 'other_staffs', 'ambulance_running_condition', 'ambulance_total',
        ];
        $statusFields = [
            'medical_consumables_status', 'laboratory_reagents_status', 'life_saving_drugs_status',
            'sto_pharmacy_status', 'staff_status', 'building_status',
        ];
        $serviceFields = [
            'lab_service_available', 'poct_available', 'launch_boat_service',
            'operation_theatre_service', 'emergency_room_service', 'radiology_service',
            'public_health_unit_service', 'sterilization_service',
        ];

        $rules = [];
        foreach ($numericFields as $field) {
            $rules[$field] = ['nullable', 'integer', 'min:0', 'max:1000000'];
        }
        foreach ($statusFields as $field) {
            $rules[$field] = ['nullable', 'string', 'max:255'];
        }
        foreach ($serviceFields as $field) {
            $rules[$field] = ['nullable', 'boolean'];
        }
        $rules['grade'] = ['nullable', 'string', 'max:10'];

        $textFields = ['project_information', 'other_information'];
        foreach ($textFields as $field) {
            $rules[$field] = ['nullable', 'string', 'max:5000'];
        }

        $data = $request->validate($rules);

        // Sanitize numerics
        foreach ($numericFields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $data[$field] === null || $data[$field] === '' ? null : (int) $data[$field];
            }
        }
        foreach ($statusFields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $data[$field] === '' ? null : $data[$field];
            }
        }
        foreach ($textFields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $data[$field] === '' ? null : $data[$field];
            }
        }
        if (array_key_exists('grade', $data)) {
            $data['grade'] = $data['grade'] === '' ? null : strtoupper(trim($data['grade']));
        }

        $profile = HospitalProfile::query();
        if ($hospitalContactId) {
            $profile->where('hospital_contact_id', $hospitalContactId);
        } elseif ($islandId) {
            $profile->whereNull('hospital_contact_id')->where('island_id', $islandId);
        } else {
            $profile->whereNull('hospital_contact_id')->whereNull('island_id');
        }
        $profile = $profile->orderBy('updated_at', 'desc')->first();

        if ($profile) {
            $profile->update($data);
        } else {
            $profile = HospitalProfile::create(array_merge($data, [
                'hospital_contact_id' => $hospitalContactId,
                'island_id' => $islandId,
            ]));
        }

        return response()->json($profile, $profile->wasRecentlyCreated ? 201 : 200);
    }

    private function assertProfileAccess(string $islandId): void
    {
        $role = auth()->user()->role;
        if ($role === 'admin') {
            return;
        }

        $island = Island::query()->findOrFail($islandId);

        if ($role === 'staff') {
            if ($island->assigned_staff_id !== auth()->id()) {
                throw ValidationException::withMessages(['island_id' => 'Staff can only create hospital profiles for their assigned island']);
            }

            return;
        }

        if ($role === 'supervisor') {
            $inScope = Atoll::query()
                ->where('id', $island->atoll_id)
                ->where('supervisor_id', auth()->id())
                ->exists();
            if (! $inScope) {
                throw ValidationException::withMessages(['island_id' => 'Supervisors can only edit hospital profiles within their assigned atoll(s)']);
            }

            return;
        }

        if ($role === 'coordinator') {
            $inScope = Atoll::query()
                ->where('id', $island->atoll_id)
                ->where('coordinator_id', auth()->id())
                ->exists();
            if (! $inScope) {
                throw ValidationException::withMessages(['island_id' => 'Coordinator can only create hospital profiles within assigned atoll(s)']);
            }

            return;
        }

        abort(403, 'Forbidden: Insufficient permissions');
    }
}
