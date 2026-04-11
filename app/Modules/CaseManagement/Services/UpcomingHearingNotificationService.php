<?php

namespace App\Modules\CaseManagement\Services;

use App\Core\Settings\SettingsService;
use App\Models\User;
use App\Modules\CaseManagement\Models\CaseHearingNotification;
use App\Modules\CaseManagement\Models\CaseModel;
use App\Notifications\UpcomingHearingNotification;
use Illuminate\Support\Carbon;

class UpcomingHearingNotificationService
{
    public function send(bool $force = false): array
    {
        $settings = app(SettingsService::class);

        $enabled = (int) $settings->get('upcoming_notifications_enabled', 0) === 1;
        $runTime = (string) $settings->get('upcoming_notifications_time', '08:00');

        if (! $force) {
            if (! $enabled) {
                return ['status' => 'disabled', 'sent' => 0];
            }

            if (Carbon::now()->format('H:i') !== $runTime) {
                return ['status' => 'outside_time_window', 'sent' => 0];
            }
        }

        $daysAhead = max(1, (int) $settings->get('upcoming_notifications_days', 7));
        $start = Carbon::today();
        $end = Carbon::today()->addDays($daysAhead);

        $cases = CaseModel::query()
            ->whereNotNull('hearing_date')
            ->whereDate('hearing_date', '>=', $start->toDateString())
            ->whereDate('hearing_date', '<=', $end->toDateString())
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'closed');
            })
            ->get();

        if ($cases->isEmpty()) {
            return ['status' => 'no_cases', 'sent' => 0];
        }

        $users = User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get()
            ->filter(fn (User $user) => $user->can('cases.view'))
            ->values();

        if ($users->isEmpty()) {
            return ['status' => 'no_recipients', 'sent' => 0];
        }

        $sent = 0;
        foreach ($cases as $case) {
            $daysUntil = Carbon::today()->diffInDays($case->hearing_date, false);
            if ($daysUntil < 0) {
                continue;
            }

            foreach ($users as $user) {
                $entry = CaseHearingNotification::firstOrCreate([
                    'case_id' => $case->id,
                    'user_id' => $user->id,
                    'hearing_date' => $case->hearing_date?->toDateString(),
                    'channel' => 'mail',
                ], [
                    'days_before' => $daysUntil,
                    'sent_at' => null,
                ]);

                if ($entry->wasRecentlyCreated) {
                    $user->notify(new UpcomingHearingNotification($case, $daysUntil));
                    $entry->sent_at = now();
                    $entry->save();
                    $sent++;
                }
            }
        }

        return ['status' => 'sent', 'sent' => $sent];
    }
}

