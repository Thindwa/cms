<?php

namespace App\Console\Commands;

use App\Core\Audit\AuditLog;
use Illuminate\Console\Command;

class PurgeAuditLogs extends Command
{
    protected $signature = 'audit:purge {--days=365 : Delete audit logs older than this many days} {--dry-run : Show count without deleting}';

    protected $description = 'Delete audit log entries older than the specified number of days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');
        $threshold = now()->subDays($days);

        $count = AuditLog::query()->where('created_at', '<', $threshold)->count();

        if ($count === 0) {
            $this->info("No audit logs older than {$days} days found.");
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("[DRY RUN] Would delete {$count} audit log entries older than {$days} days.");
            return self::SUCCESS;
        }

        $deleted = AuditLog::query()->where('created_at', '<', $threshold)->delete();
        $this->info("Deleted {$deleted} audit log entries older than {$days} days.");

        return self::SUCCESS;
    }
}
