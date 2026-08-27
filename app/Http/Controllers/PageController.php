<?php

namespace App\Http\Controllers;

use App\Models\HospitalContact;
use App\Models\HospitalProfile;
use App\Models\Island;
use App\Services\TaskService;

class PageController extends Controller
{
    public function landing()
    {
        if (! auth()->check()) {
            return redirect()->route('auth.show');
        }

        $role = auth()->user()->role;

        if ($role !== 'staff') {
            return redirect()->route('dashboard');
        }

        $profile = (new TaskService)->currentProfile();
        $assignedIsland = null;
        $hospitalProfile = null;
        $atollName = null;
        $hospitalName = null;

        if ($profile) {
            $assignedIsland = Island::query()
                ->where('assigned_staff_id', auth()->id())
                ->where('status', 'active')
                ->with('atoll')
                ->first();

            if ($assignedIsland) {
                $atollName = $assignedIsland->atoll?->name;
                $hospitalContact = HospitalContact::query()
                    ->where('island_id', $assignedIsland->id)
                    ->where('status', 'active')
                    ->latest('created_at')
                    ->first();
                $hospitalProfile = HospitalProfile::query()
                    ->where(function ($query) use ($hospitalContact, $assignedIsland) {
                        if ($hospitalContact) {
                            $query->where('hospital_contact_id', $hospitalContact->id)
                                ->orWhere('island_id', $assignedIsland->id);
                        } else {
                            $query->where('island_id', $assignedIsland->id);
                        }
                    })
                    ->orderByRaw('hospital_contact_id = ? desc', [$hospitalContact?->id ?? ''])
                    ->latest('updated_at')
                    ->first();
                $hospitalName = $hospitalContact?->hospital_name ?: "{$assignedIsland->name} Health Facility";
            }
        }

        return view('index', compact('profile', 'assignedIsland', 'hospitalProfile', 'atollName', 'hospitalName'));
    }

    public function install()
    {
        return view('install.index');
    }

    public function coordinators()
    {
        $data = (new CoordinatorController)->directoryData();
        $data['role'] = auth()->user()->role;

        return view('coordinators.index', $data);
    }
}
