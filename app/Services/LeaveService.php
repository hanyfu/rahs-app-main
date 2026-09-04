<?php

namespace App\Services;

use App\Models\Atoll;
use App\Models\CriticalStaffAvailabilitySetup;
use App\Models\CriticalStaffLeave;
use App\Models\Island;
use App\Models\HospitalProfile;
use App\Models\UserRole;

class LeaveService
{
    public function checkLeaveShortageRisk(CriticalStaffLeave $leave): ?array
    {
        $statuses = ['submitted', 'pending_review', 'approved'];

        if ($leave->hospital_profile_id) {
            $hospital = HospitalProfile::query()->find($leave->hospital_profile_id);
            $staffField = $this->categoryFieldMap()[$leave->staff_category] ?? null;
            if (! $hospital || ! $staffField) {
                return null;
            }

            $setup = CriticalStaffAvailabilitySetup::query()
                ->where('hospital_profile_id', $hospital->id)
                ->where('staff_category', $leave->staff_category)
                ->where('status', 'active')
                ->where(fn ($query) => $query
                    ->where('shift', $leave->shift_affected ?: 'All shifts')
                    ->orWhere('shift', 'All shifts'))
                ->orderByRaw('CASE WHEN shift = ? THEN 0 ELSE 1 END', [$leave->shift_affected ?: 'All shifts'])
                ->first();

            if (! $setup) {
                return null;
            }

            $onLeave = CriticalStaffLeave::query()
                ->where('hospital_profile_id', $hospital->id)
                ->where('staff_category', $leave->staff_category)
                ->whereIn('approval_status', $statuses)
                ->where('leave_start_date', '<=', $leave->leave_end_date)
                ->where('leave_end_date', '>=', $leave->leave_start_date)
                ->when($setup->shift !== 'All shifts', fn ($query) => $query->where('shift_affected', $setup->shift))
                ->count();

            $total = max(0, (int) $hospital->{$staffField});
            $remaining = max(0, $total - $onLeave);

            return [
                'setup' => $setup,
                'hospital' => $hospital,
                'total' => $total,
                'onLeave' => $onLeave,
                'tempReplacementOut' => 0,
                'remaining' => $remaining,
                'shortage' => $remaining < (int) $setup->required_minimum_staff,
            ];
        }

        $setup = CriticalStaffAvailabilitySetup::query()
            ->where('department_unit', $leave->department_unit)
            ->where('staff_category', $leave->staff_category)
            ->where('shift', $leave->shift_affected)
            ->where('status', 'active')
            ->first();

        if (! $setup) {
            return null;
        }

        $onLeave = CriticalStaffLeave::query()
            ->where('department_unit', $leave->department_unit)
            ->where('staff_category', $leave->staff_category)
            ->where('shift_affected', $leave->shift_affected)
            ->whereIn('approval_status', $statuses)
            ->where('leave_start_date', '<=', $leave->leave_end_date)
            ->where('leave_end_date', '>=', $leave->leave_start_date)
            ->count();

        $tempReplacementOut = CriticalStaffLeave::query()
            ->where('staff_category', $leave->staff_category)
            ->where('shift_affected', $leave->shift_affected)
            ->whereIn('approval_status', $statuses)
            ->where('replacement_staff', 'ilike', "%({$leave->department_unit})%")
            ->count();

        $remaining = max(0, ($setup->total_active_staff ?? 0) - $onLeave - $tempReplacementOut);

        return [
            'setup' => $setup,
            'onLeave' => $onLeave,
            'tempReplacementOut' => $tempReplacementOut,
            'remaining' => $remaining,
            'shortage' => $remaining < ($setup->required_minimum_staff ?? 0),
        ];
    }

    public function getStaffCoordinatorSupervisor(string $userId): array
    {
        $island = Island::query()
            ->where('assigned_staff_id', $userId)
            ->with('atoll')
            ->first();

        return [
            'coordinator_id' => $island?->atoll?->coordinator_id,
            'supervisor_id' => $island?->atoll?->supervisor_id,
        ];
    }

    public function getStaffAssignedCoordinatorIds(string $userId): array
    {
        return Atoll::query()
            ->whereIn('id', Island::query()->where('assigned_staff_id', $userId)->pluck('atoll_id'))
            ->whereNotNull('coordinator_id')
            ->pluck('coordinator_id')
            ->unique()
            ->values()
            ->all();
    }

    public function getUserRoleById(?string $userId): ?string
    {
        if (! $userId) {
            return null;
        }

        return UserRole::query()->where('user_id', $userId)->value('role');
    }

    public function categoryFieldMap(): array
    {
        return [
            'Physiotherapy' => 'staff_physiotherapy',
            'Dermatology' => 'staff_dermatology',
            'Orthopaedics' => 'staff_ortho',
            'Medicine' => 'staff_medicine',
            'Surgeon' => 'staff_surgeon',
            'Gynaecology' => 'staff_gynaecology',
            'Paediatrician' => 'staff_paediatrician',
            'ENT' => 'staff_ent',
            'Dental' => 'staff_dental',
            'Ophthalmology' => 'staff_ophthalmology',
            'Psychology' => 'staff_psychology',
            'Radiology' => 'staff_radiology',
            'Anesthesiologist' => 'staff_anesthesiologist',
            'Medical Officer' => 'staff_medical_officer',
            'Psychiatrist' => 'staff_psychiatrist',
            'Clinical Nurses' => 'nurses_clinical',
            'Senior Registered Nurses' => 'nurses_senior_registered',
            'Registered Nurses' => 'nurses_registered',
            'Enrolled Nurses' => 'nurses_enrolled',
            'Senior Admin' => 'admin_officers_senior',
            'Admin Officers' => 'admin_officers',
            'Customer Service' => 'customer_service',
            'Drivers' => 'drivers',
            'Lab Technicians' => 'lab_tech',
            'Other Staff' => 'other_staffs',
        ];
    }

    public function staffCategories(): array
    {
        return array_keys($this->categoryFieldMap());
    }

    public function userCanAccessHospital(string $userId, string $role, HospitalProfile $hospital): bool
    {
        if ($role === 'admin') {
            return true;
        }

        $island = $hospital->island;
        if (! $island) {
            return false;
        }

        if ($role === 'staff') {
            return $island->assigned_staff_id === $userId;
        }

        if ($role === 'coordinator') {
            return $island->atoll?->coordinator_id === $userId;
        }

        if ($role === 'supervisor') {
            return $island->atoll?->supervisor_id === $userId;
        }

        return false;
    }
}
