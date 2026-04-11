<?php

namespace App\Console\Commands;

use App\Core\Settings\SettingsService;
use App\Modules\CaseManagement\Services\UpcomingHearingNotificationService;
use Illuminate\Console\Command;

class SendUpcomingHearingNotifications extends Command
{
    protected $signature = 'cases:notify-upcoming-hearings {--force : Ignore enabled/time guards}';

    protected $description = 'Send email reminders for upcoming case hearing dates';

    public function handle(SettingsService $settings, UpcomingHearingNotificationService $service): int
    {
        $force = (bool) $this->option('force');
        $result = $service->send($force);
        $this->info('Upcoming hearing notifications status: ' . ($result['status'] ?? 'unknown'));
        $this->info('Notifications sent: ' . (int) ($result['sent'] ?? 0));

        return self::SUCCESS;
    }
}
