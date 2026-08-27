<?php

namespace App\Http\Controllers;

use App\Models\ScheduledReport;
use Illuminate\Http\Request;

class ScheduledReportController extends Controller
{
    public function index()
    {
        return response()->json(
            ScheduledReport::query()->where('user_id', auth()->id())->orderBy('created_at', 'desc')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'recipients' => ['required', 'array'],
            'recipients.*' => ['email'],
            'frequency' => ['required', 'in:daily,weekly,monthly'],
            'day_of_week' => ['nullable', 'integer', 'min:0', 'max:6'],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:31'],
            'time_of_day' => ['required', 'string', 'max:10'],
            'filters' => ['nullable', 'array'],
        ]);

        $report = ScheduledReport::create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'recipients' => $data['recipients'],
            'frequency' => $data['frequency'],
            'day_of_week' => $data['day_of_week'] ?? null,
            'day_of_month' => $data['day_of_month'] ?? null,
            'time_of_day' => $data['time_of_day'],
            'filters' => $data['filters'] ?? [],
            'is_active' => true,
        ]);

        return response()->json($report, 201);
    }

    public function update(Request $request, string $id)
    {
        $report = ScheduledReport::query()->where('user_id', auth()->id())->findOrFail($id);

        $data = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'name' => ['nullable', 'string', 'max:255'],
            'recipients' => ['nullable', 'array'],
            'frequency' => ['nullable', 'in:daily,weekly,monthly'],
            'time_of_day' => ['nullable', 'string', 'max:10'],
            'filters' => ['nullable', 'array'],
        ]);

        $report->update($data);

        return response()->json($report);
    }

    public function destroy(string $id)
    {
        ScheduledReport::query()->where('user_id', auth()->id())->findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
