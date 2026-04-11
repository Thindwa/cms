<?php

use App\Models\User;
use App\Modules\CaseManagement\Imports\ExcelCaseImportService;
use App\Modules\CaseManagement\Services\UpcomingHearingNotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('cases:import-excel {file} {--sheet=Sheet1} {--user=} {--dry-run}', function (string $file): int {
    $sheet = (string) $this->option('sheet');
    $dryRun = (bool) $this->option('dry-run');

    $resolvedFile = is_file($file) ? $file : base_path($file);
    if (! is_file($resolvedFile)) {
        $this->error("File not found: {$file}");
        return self::FAILURE;
    }

    $userId = null;
    if (! $dryRun) {
        $userIdOption = $this->option('user');
        $userId = $userIdOption !== null
            ? (int) $userIdOption
            : User::query()->where('username', 'admin')->value('id');

        if ($userId === null) {
            $userId = User::query()->value('id');
        }

        if ($userId === null) {
            $this->error('No users available. Provide --user=<id> or seed a user first.');
            return self::FAILURE;
        }
    }

    /** @var ExcelCaseImportService $service */
    $service = app(ExcelCaseImportService::class);

    $this->line('Starting Excel import...');
    $this->line('File: ' . $resolvedFile);
    $this->line('Sheet: ' . $sheet);
    $this->line('Mode: ' . ($dryRun ? 'DRY RUN' : 'LIVE'));

    try {
        $result = $service->import($resolvedFile, $sheet, $userId, $dryRun);
    } catch (\Throwable $e) {
        $this->error('Import failed: ' . $e->getMessage());
        return self::FAILURE;
    }

    $this->newLine();
    $this->table(['Metric', 'Value'], [
        ['Rows total', $result['rows_total']],
        ['Rows processed', $result['rows_processed']],
        ['Rows skipped', $result['rows_skipped']],
        ['Cases created', $result['cases_created']],
        ['Cases matched', $result['cases_matched']],
        ['Cases updated', $result['cases_updated']],
        ['Notes created', $result['notes_created']],
        ['Errors', count($result['errors'])],
    ]);

    if (! empty($result['errors'])) {
        $this->newLine();
        $this->warn('Errors:');
        foreach ($result['errors'] as $error) {
            $this->line('Row ' . $error['row'] . ': ' . $error['message']);
        }
    }

    $this->info($dryRun ? 'Dry-run completed.' : 'Import completed.');

    return self::SUCCESS;
})->purpose('Import cases from the legacy Excel file');

Artisan::command('cases:notify-upcoming-hearings {--force : Ignore enabled/time guards}', function (): int {
    /** @var UpcomingHearingNotificationService $service */
    $service = app(UpcomingHearingNotificationService::class);
    $result = $service->send((bool) $this->option('force'));

    $this->info('Upcoming hearing notifications status: ' . ($result['status'] ?? 'unknown'));
    $this->info('Notifications sent: ' . (int) ($result['sent'] ?? 0));

    return self::SUCCESS;
})->purpose('Send email reminders for upcoming case hearing dates');

Schedule::command('cases:notify-upcoming-hearings')
    ->everyMinute()
    ->withoutOverlapping()
    ->name('cases-notify-upcoming-hearings');
