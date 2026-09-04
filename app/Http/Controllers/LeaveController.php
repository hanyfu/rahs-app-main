<?php

namespace App\Http\Controllers;

use App\Models\CriticalStaffAvailabilitySetup;
use App\Models\CriticalStaffLeave;
use App\Models\HospitalContact;
use App\Models\HospitalProfile;
use App\Models\Island;
use App\Models\Profile;
use App\Services\LeaveService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LeaveController extends Controller
{
    private LeaveService $leaveService;

    private NotificationService $notifications;

    public function __construct()
    {
        $this->leaveService = new LeaveService;
        $this->notifications = new NotificationService;
    }

    public function index()
    {
        $role = auth()->user()->role;
        $me = auth()->id();

        $leavesQuery = CriticalStaffLeave::query()
            ->with(['coordinator:id,first_name,last_name', 'supervisor:id,first_name,last_name', 'creator:id,first_name,last_name', 'hospitalProfile.hospitalContact:id,hospital_name']);

        if ($role === 'staff') {
            $leavesQuery->where('created_by', $me);
        } elseif ($role === 'coordinator') {
            $leavesQuery->where(fn ($q) => $q->where('assigned_coordinator', $me)->orWhere('created_by', $me));
        } elseif ($role === 'supervisor') {
            $leavesQuery->where(fn ($q) => $q->where('direct_supervisor', $me)->orWhere('created_by', $me));
        }

        $leaves = $leavesQuery->orderBy('created_at', 'desc')->get();

        $profiles = Profile::query()->where('status', 'active')->orderBy('first_name')->get();
        $setup = $this->availabilitySetups();
        $hospitalProfiles = $this->accessibleHospitals()->values();
        $hospitalContacts = HospitalContact::query()->where('status', 'active')->orderBy('hospital_name')->get();
        $islands = Island::query()->orderBy('name')->get(['id', 'name']);
        $staffCategories = $this->leaveService->staffCategories();
        $staffCategoryFields = $this->leaveService->categoryFieldMap();

        $assignees = null;
        if ($role === 'staff') {
            $ids = $this->leaveService->getStaffCoordinatorSupervisor($me);
            $profileIds = array_filter([$ids['coordinator_id'], $ids['supervisor_id']]);
            $map = Profile::query()->whereIn('id', $profileIds)->get(['id', 'first_name', 'last_name', 'email'])->keyBy('id');
            $assignees = [
                'coordinator' => $ids['coordinator_id'] ? $map->get($ids['coordinator_id']) : null,
                'supervisor' => $ids['supervisor_id'] ? $map->get($ids['supervisor_id']) : null,
            ];
        }

        return view('leaves.index', compact('leaves', 'profiles', 'setup', 'hospitalProfiles', 'hospitalContacts', 'islands', 'staffCategories', 'staffCategoryFields', 'role', 'assignees'));
    }

    public function data()
    {
        $role = auth()->user()->role;
        $me = auth()->id();

        $leavesQuery = CriticalStaffLeave::query()
            ->with(['coordinator:id,first_name,last_name', 'supervisor:id,first_name,last_name', 'creator:id,first_name,last_name', 'hospitalProfile.hospitalContact:id,hospital_name']);

        if ($role === 'staff') {
            $leavesQuery->where('created_by', $me);
        } elseif ($role === 'coordinator') {
            $leavesQuery->where(fn ($q) => $q->where('assigned_coordinator', $me)->orWhere('created_by', $me));
        } elseif ($role === 'supervisor') {
            $leavesQuery->where(fn ($q) => $q->where('direct_supervisor', $me)->orWhere('created_by', $me));
        }

        return response()->json($leavesQuery->orderBy('created_at', 'desc')->get());
    }

    public function assigneesMe()
    {
        $ids = $this->leaveService->getStaffCoordinatorSupervisor(auth()->id());
        $profileIds = array_filter([$ids['coordinator_id'], $ids['supervisor_id']]);
        $map = Profile::query()->whereIn('id', $profileIds)->get(['id', 'first_name', 'last_name', 'email'])->keyBy('id');

        return response()->json([
            'coordinator' => $ids['coordinator_id'] ? $map->get($ids['coordinator_id']) : null,
            'supervisor' => $ids['supervisor_id'] ? $map->get($ids['supervisor_id']) : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateLeaveData($request);

        $user = auth()->user();
        $data['created_by'] = $user->id;

        $hospital = HospitalProfile::query()->with(['island.atoll', 'hospitalContact'])->findOrFail($data['hospital_profile_id']);
        $this->authorizeHospital($hospital);
        $data['island_id'] = $hospital->island_id;
        $data['department_unit'] = $hospital->hospitalContact?->hospital_name ?? $hospital->island?->name ?? 'Hospital';
        $data['assigned_coordinator'] = $hospital->island?->atoll?->coordinator_id;
        $data['direct_supervisor'] = $hospital->island?->atoll?->supervisor_id;

        $leave = CriticalStaffLeave::create($data);

        $this->notifications->notifyLeaveCreated($leave);

        return response()->json($leave->load(['coordinator:id,first_name,last_name', 'supervisor:id,first_name,last_name', 'creator:id,first_name,last_name', 'hospitalProfile.hospitalContact:id,hospital_name']), 201);
    }

    public function update(Request $request, string $id)
    {
        $leave = CriticalStaffLeave::query()->findOrFail($id);
        $user = auth()->user();

        if (! $this->canManageLeave($leave)) {
            abort(403);
        }

        if ($request->keys() === ['approval_status']) {
            if (! in_array($user->role, ['admin', 'supervisor', 'coordinator'], true)) {
                abort(403);
            }
            $data = $request->validate(['approval_status' => ['required', 'in:submitted,pending_review,approved,rejected,cancelled']]);
            $statusChanged = $data['approval_status'] !== $leave->approval_status;
            $leave->update(['approval_status' => $data['approval_status'], 'reviewed_by' => $user->id]);
            $leave->refresh();
            if ($statusChanged) {
                $this->notifications->notifyLeaveUpdated($leave, true);
            }

            return response()->json($leave->load(['coordinator:id,first_name,last_name', 'supervisor:id,first_name,last_name', 'creator:id,first_name,last_name', 'hospitalProfile.hospitalContact:id,hospital_name']));
        }

        if ($user->role === 'staff') {
            if ($leave->created_by !== $user->id) {
                throw ValidationException::withMessages(['leave' => 'You can only update your own leave record']);
            }
            if ($leave->approval_status === 'approved') {
                throw ValidationException::withMessages(['leave' => 'Approved leave cannot be edited by staff']);
            }
            if ($request->has('assigned_coordinator') || $request->has('direct_supervisor')) {
                throw ValidationException::withMessages(['leave' => 'Staff cannot manually set coordinator or supervisor']);
            }
            if ($request->has('approval_status')) {
                throw ValidationException::withMessages(['leave' => 'Staff cannot change leave status']);
            }
        }

        $data = $this->validateLeaveData($request, $leave);
        $oldStatus = $leave->approval_status;
        $statusChanged = array_key_exists('approval_status', $data) && $data['approval_status'] !== $oldStatus;

        if (array_key_exists('approval_status', $data)) {
            if (! in_array($user->role, ['admin', 'supervisor', 'coordinator'], true)) {
                throw ValidationException::withMessages(['approval_status' => 'Only authorized users can change leave status']);
            }
            $data['reviewed_by'] = $user->id;
        }

        $leave->update($data);
        $leave->refresh();

        if ($statusChanged || array_key_exists('critical_level', $data) || array_key_exists('urgency', $data)) {
            $this->notifications->notifyLeaveUpdated($leave, $statusChanged);
        }

        return response()->json($leave->load(['coordinator:id,first_name,last_name', 'supervisor:id,first_name,last_name', 'creator:id,first_name,last_name', 'hospitalProfile.hospitalContact:id,hospital_name']));
    }

    public function destroy(string $id)
    {
        $leave = CriticalStaffLeave::query()->findOrFail($id);

        if (! $this->canManageLeave($leave)) {
            abort(403);
        }

        if (auth()->user()->role === 'staff' && $leave->created_by !== auth()->id()) {
            abort(403);
        }

        $leave->delete();

        return response()->json(['success' => true]);
    }

    private function validateLeaveData(Request $request, ?CriticalStaffLeave $existing = null): array
    {
        $role = auth()->user()->role;

        $rules = [
            'staff_name' => ['required', 'string', 'max:255'],
            'staff_id' => ['nullable', 'string', 'max:255'],
            'staff_category' => ['required', 'string'],
            'department_unit' => ['nullable', 'string', 'max:255'],
            'hospital_profile_id' => ['required', 'uuid', 'exists:hospital_profiles,id'],
            'assigned_coordinator' => ['nullable', 'string'],
            'direct_supervisor' => ['nullable', 'string'],
            'leave_type' => ['required', 'string'],
            'leave_start_date' => ['required', 'date'],
            'leave_end_date' => ['required', 'date', 'after_or_equal:leave_start_date'],
            'shift_affected' => ['nullable', 'string'],
            'reason_for_leave' => ['nullable', 'string'],
            'contact_during_leave' => ['nullable', 'string'],
            'replacement_staff' => ['nullable', 'string'],
            'handover_notes' => ['nullable', 'string'],
            'critical_level' => ['nullable', 'in:low,medium,high,critical'],
            'urgency' => ['nullable', 'in:normal,urgent,emergency'],
            'approval_status' => ['nullable', 'in:submitted,pending_review,approved,rejected,cancelled'],
            'remarks' => ['nullable', 'string'],
        ];

        $data = $request->validate($rules);

        $hospital = HospitalProfile::query()->with(['island.atoll', 'hospitalContact'])->findOrFail($data['hospital_profile_id']);
        $this->authorizeHospital($hospital);
        $data['island_id'] = $hospital->island_id;
        $data['department_unit'] = $hospital->hospitalContact?->hospital_name ?? $hospital->island?->name ?? 'Hospital';
        $data['assigned_coordinator'] = $hospital->island?->atoll?->coordinator_id;
        $data['direct_supervisor'] = $hospital->island?->atoll?->supervisor_id;

        // Staff ID and department are internal continuity fields. They are no
        // longer requested in the leave form; retain existing values on edits
        // and otherwise derive them from the signed-in user's profile.
        $currentProfile = Profile::query()
            ->with(['department:id,name', 'userDepartment:id,name'])
            ->find(auth()->id());
        $data['staff_id'] = $data['staff_id']
            ?? $existing?->staff_id
            ?? $currentProfile?->email
            ?? auth()->id();
        $data['department_unit'] = $data['department_unit']
            ?? $existing?->department_unit
            ?? $currentProfile?->userDepartment?->name
            ?? $currentProfile?->department?->name
            ?? 'General';

        $criticalLevel = strtolower($data['critical_level'] ?? $existing?->critical_level ?? 'low');
        $urgency = strtolower($data['urgency'] ?? $existing?->urgency ?? 'normal');
        $reason = $data['reason_for_leave'] ?? $existing?->reason_for_leave ?? '';

        if ((in_array($criticalLevel, ['critical', 'high'], true) || in_array($urgency, ['urgent', 'emergency'], true)) && ! trim((string) $reason)) {
            throw ValidationException::withMessages(['reason_for_leave' => 'Reason is required for High/Critical/Urgent/Emergency leave requests']);
        }

        $start = Carbon::parse($data['leave_start_date'])->startOfDay();
        $end = Carbon::parse($data['leave_end_date'])->startOfDay();
        $days = max(1, (int) $start->diffInDays($end) + 1);

        $data['number_of_leave_days'] = $days;
        $data['leave_start_date'] = $start->toDateString();
        $data['leave_end_date'] = $end->toDateString();
        $data['critical_level'] = $criticalLevel;
        $data['urgency'] = $urgency;
        $data['approval_status'] = strtolower(str_replace(' ', '_', $data['approval_status'] ?? $existing?->approval_status ?? 'submitted'));

        foreach (['reason_for_leave', 'contact_during_leave', 'replacement_staff', 'handover_notes', 'remarks', 'shift_affected'] as $nullable) {
            if (array_key_exists($nullable, $data) && $data[$nullable] === '') {
                $data[$nullable] = null;
            }
        }

        if (array_key_exists('assigned_coordinator', $data) && $data['assigned_coordinator'] === '') {
            $data['assigned_coordinator'] = null;
        }
        if (array_key_exists('direct_supervisor', $data) && $data['direct_supervisor'] === '') {
            $data['direct_supervisor'] = null;
        }

        return $data;
    }

    // --- Availability Setup ---

    public function setupData()
    {
        return response()->json($this->availabilitySetups());
    }

    public function storeSetup(Request $request)
    {
        $this->assertCanSetup();

        $data = $request->validate([
            'department_unit' => ['nullable', 'string', 'max:255'],
            'hospital_profile_id' => ['required', 'uuid', 'exists:hospital_profiles,id'],
            'staff_category' => ['required', 'string'],
            'shift' => ['required', 'string'],
            'total_active_staff' => ['nullable', 'integer', 'min:0'],
            'required_minimum_staff' => ['required', 'integer', 'min:0'],
            'coordinator_responsible' => ['nullable', 'string'],
        ]);

        $hospital = HospitalProfile::query()->with(['island.atoll', 'hospitalContact'])->findOrFail($data['hospital_profile_id']);
        $this->authorizeHospital($hospital);
        $field = $this->leaveService->categoryFieldMap()[$data['staff_category']] ?? null;
        if (! $field) {
            throw ValidationException::withMessages(['staff_category' => 'Choose a staffing category from the hospital profile.']);
        }
        $data['department_unit'] = $hospital->hospitalContact?->hospital_name ?? $hospital->island?->name ?? 'Hospital';
        $data['total_active_staff'] = (int) $hospital->{$field};
        $data['shift'] = $data['shift'] ?: 'All shifts';

        if (auth()->user()->role === 'coordinator' && empty($data['coordinator_responsible'])) {
            $data['coordinator_responsible'] = auth()->id();
        }

        $setup = CriticalStaffAvailabilitySetup::create([...$data, 'status' => 'active']);

        return response()->json($setup, 201);
    }

    public function updateSetup(Request $request, string $id)
    {
        $this->assertCanSetup();
        $setup = CriticalStaffAvailabilitySetup::query()->findOrFail($id);

        $data = $request->validate([
            'department_unit' => ['nullable', 'string', 'max:255'],
            'hospital_profile_id' => ['nullable', 'uuid', 'exists:hospital_profiles,id'],
            'staff_category' => ['nullable', 'string'],
            'shift' => ['nullable', 'string'],
            'total_active_staff' => ['nullable', 'integer', 'min:0'],
            'required_minimum_staff' => ['nullable', 'integer', 'min:0'],
            'coordinator_responsible' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $hospital = HospitalProfile::query()
            ->with(['island.atoll', 'hospitalContact'])
            ->findOrFail($data['hospital_profile_id'] ?? $setup->hospital_profile_id);
        $this->authorizeHospital($hospital);
        $category = $data['staff_category'] ?? $setup->staff_category;
        $field = $this->leaveService->categoryFieldMap()[$category] ?? null;
        if (! $field) {
            throw ValidationException::withMessages(['staff_category' => 'Choose a staffing category from the hospital profile.']);
        }
        $data['hospital_profile_id'] = $hospital->id;
        $data['department_unit'] = $hospital->hospitalContact?->hospital_name ?? $hospital->island?->name ?? 'Hospital';
        $data['total_active_staff'] = (int) $hospital->{$field};

        $setup->update($data);

        return response()->json($setup);
    }

    public function destroySetup(string $id)
    {
        $this->assertCanSetup();
        $setup = CriticalStaffAvailabilitySetup::query()->with('hospitalProfile.island.atoll')->findOrFail($id);
        if ($setup->hospitalProfile) {
            $this->authorizeHospital($setup->hospitalProfile);
        } elseif (auth()->user()->role !== 'admin') {
            abort(403);
        }
        $setup->delete();

        return response()->json(['success' => true]);
    }

    private function assertCanSetup(): void
    {
        if (! in_array(auth()->user()->role, ['admin', 'supervisor', 'coordinator'], true)) {
            abort(403);
        }
    }

    private function accessibleHospitals()
    {
        $user = auth()->user();

        return HospitalProfile::query()
            ->with(['hospitalContact:id,hospital_name', 'island.atoll'])
            ->get()
            ->filter(fn (HospitalProfile $hospital) => $this->leaveService->userCanAccessHospital($user->id, $user->role, $hospital));
    }

    private function availabilitySetups()
    {
        return CriticalStaffAvailabilitySetup::query()
            ->with('hospitalProfile.hospitalContact:id,hospital_name')
            ->orderBy('department_unit')
            ->get()
            ->filter(function (CriticalStaffAvailabilitySetup $setup) {
                if (! $setup->hospitalProfile) {
                    return true;
                }
                $user = auth()->user();

                return $this->leaveService->userCanAccessHospital($user->id, $user->role, $setup->hospitalProfile);
            })
            ->each(function (CriticalStaffAvailabilitySetup $setup) {
                $field = $this->leaveService->categoryFieldMap()[$setup->staff_category] ?? null;
                if ($field && $setup->hospitalProfile) {
                    $setup->total_active_staff = (int) $setup->hospitalProfile->{$field};
                }
            })
            ->values();
    }

    private function authorizeHospital(HospitalProfile $hospital): void
    {
        $hospital->loadMissing('island.atoll');
        $user = auth()->user();
        abort_unless($this->leaveService->userCanAccessHospital($user->id, $user->role, $hospital), 403);
    }

    private function canManageLeave(CriticalStaffLeave $leave): bool
    {
        $user = auth()->user();
        if ($user->role === 'admin') {
            return true;
        }
        if ($user->role === 'staff') {
            return $leave->created_by === $user->id;
        }

        return ($user->role === 'coordinator' && $leave->assigned_coordinator === $user->id)
            || ($user->role === 'supervisor' && $leave->direct_supervisor === $user->id);
    }
}
