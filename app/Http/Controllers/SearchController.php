<?php

namespace App\Http\Controllers;

use App\Models\HospitalContact;
use App\Models\Profile;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['tasks' => [], 'hospitals' => [], 'users' => []]);
        }

        $pattern = '%'.$q.'%';

        $tasks = Task::query()
            ->where(function ($query) use ($pattern) {
                $query->where('title', 'ilike', $pattern)
                    ->orWhere('creator_description', 'ilike', $pattern);
            })
            ->where('archived', false)
            ->limit(5);

        $tasks = (new TaskService)->applyTaskAccess($tasks, auth()->user()->role, auth()->id())
            ->get(['id', 'title', 'status']);

        $hospitals = HospitalContact::query()
            ->where('status', 'active')
            ->where(function ($query) use ($pattern) {
                $query->where('hospital_name', 'ilike', $pattern)
                    ->orWhere('manager_name', 'ilike', $pattern)
                    ->orWhere('contact_number', 'ilike', $pattern);
            })
            ->with('island:id,name')
            ->limit(5)
            ->get();

        $users = [];
        if (in_array(auth()->user()->role, ['admin', 'supervisor'], true)) {
            $users = Profile::query()
                ->where('status', 'active')
                ->where(function ($query) use ($pattern) {
                    $query->where('first_name', 'ilike', $pattern)
                        ->orWhere('last_name', 'ilike', $pattern);
                })
                ->limit(5)
                ->get(['id', 'first_name', 'last_name']);
        }

        return response()->json(compact('tasks', 'hospitals', 'users'));
    }
}
