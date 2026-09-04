<?php

namespace App\Console\Commands;

use App\Models\HospitalDocument;
use App\Models\TrackedExpiryItem;
use App\Models\TransportAsset;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendOperationsAlerts extends Command
{
    protected $signature = 'operations:send-alerts';
    protected $description = 'Notify responsible officers about operational expiries and servicing deadlines';

    public function handle(NotificationService $notifications): int
    {
        $sent = 0;

        TrackedExpiryItem::with('hospitalProfile.island.atoll')->where('status', 'active')->get()
            ->filter(fn ($item) => $item->expiry_date->lte(today()->addDays($item->warning_days)))
            ->each(function ($item) use ($notifications, &$sent) {
                $days = today()->diffInDays($item->expiry_date, false);
                $this->notifyHospital($notifications, $item->hospitalProfile, 'Expiry alert: '.$item->name, $days < 0 ? 'Expired '.abs((int) $days).' day(s) ago.' : 'Expires in '.(int) $days.' day(s).');
                $sent++;
            });

        TransportAsset::with('hospitalProfile.island.atoll')->whereNotNull('next_service_date')->whereDate('next_service_date', '<=', today()->addDays(30))->get()
            ->each(function ($asset) use ($notifications, &$sent) {
                $this->notifyHospital($notifications, $asset->hospitalProfile, 'Vehicle service due: '.$asset->name, 'Service date: '.$asset->next_service_date->format('d M Y').'.');
                $sent++;
            });

        HospitalDocument::with('hospitalProfile.island.atoll')->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', today()->addDays(30))->get()
            ->each(function ($document) use ($notifications, &$sent) {
                $this->notifyHospital($notifications, $document->hospitalProfile, 'Document expiry: '.$document->title, 'Expiry date: '.$document->expiry_date->format('d M Y').'.');
                $sent++;
            });

        $this->info("Queued {$sent} operational alert(s).");
        return self::SUCCESS;
    }

    private function notifyHospital(NotificationService $notifications, $hospital, string $title, string $message): void
    {
        $island = $hospital?->island;
        $notifications->notifyUsers(array_filter([$island?->assigned_staff_id, $island?->atoll?->coordinator_id, $island?->atoll?->supervisor_id]), $title, $message);
    }
}
