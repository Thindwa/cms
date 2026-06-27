<?php

namespace App\Console\Commands;

use App\Core\Settings\SettingsService;
use App\Modules\CaseManagement\Models\CaseDocument;
use App\Notifications\DocumentsPendingDeletion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredDocuments extends Command
{
    protected $signature = 'documents:purge-expired {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Permanently delete soft-deleted documents past the retention period and send reminders';

    public function handle(SettingsService $settings): int
    {
        $retentionDays = (int) $settings->get('document_retention_days', 90);
        $reminderDays = (int) $settings->get('document_reminder_days', 10);
        $dryRun = (bool) $this->option('dry-run');

        $retentionThreshold = now()->subDays($retentionDays);
        $reminderStart = now()->subDays($retentionDays - $reminderDays);

        $expired = CaseDocument::onlyTrashed()
            ->where('deleted_at', '<', $retentionThreshold)
            ->get();

        if ($expired->isNotEmpty()) {
            $this->info("Found {$expired->count()} expired document(s) older than {$retentionDays} days.");

            if ($dryRun) {
                foreach ($expired as $doc) {
                    $case = $doc->case()->withTrashed()->first();
                    $caseNo = $case?->case_number ?? 'deleted';
                    $this->line("  [DRY RUN] Would purge: {$doc->original_name} (Case {$caseNo}, deleted {$doc->deleted_at->formatDate()})");
                }
            } else {
                $deleted = 0;
                foreach ($expired as $doc) {
                    if (Storage::disk('public')->exists($doc->file_path)) {
                        Storage::disk('public')->delete($doc->file_path);
                    }
                    $doc->forceDelete();
                    $deleted++;
                }
                $this->info("Permanently deleted {$deleted} expired document(s).");
            }
        } else {
            $this->line('No expired documents found.');
        }

        $pendingNotification = CaseDocument::onlyTrashed()
            ->where('deleted_at', '>=', $retentionThreshold)
            ->where('deleted_at', '<', $reminderStart)
            ->with('case:id,case_number,title')
            ->get();

        if ($pendingNotification->isEmpty()) {
            $this->line('No documents pending deletion in the reminder window.');

            if (! $dryRun) {
                return self::SUCCESS;
            }
        }

        if ($pendingNotification->isNotEmpty() && ! $dryRun) {
            $users = \App\Models\User::permission('cases.documents.purge')->get();

            if ($users->isEmpty()) {
                $this->warn('No users with permission to receive deletion reminders.');
            } else {
                $grouped = $pendingNotification->group(fn (CaseDocument $doc) => $doc->case?->case_number ?? 'Unknown');
                $totalSize = $grouped->sum->count();

                foreach ($users as $user) {
                    $user->notify(new DocumentsPendingDeletion($grouped, $retentionDays));
                }

                $this->info("Sent deletion reminder to {$users->count()} user(s) about {$totalSize} document(s).");
            }
        }

        if ($dryRun && $pendingNotification->isNotEmpty()) {
            $this->newLine();
            $this->warn('Documents in reminder window (notification would be sent):');
            foreach ($pendingNotification as $doc) {
                $case = $doc->case;
                $caseNo = $case?->case_number ?? 'deleted';
                $daysLeft = $retentionDays - now()->diffInDays($doc->deleted_at);
                $this->line("  {$doc->original_name} (Case {$caseNo}, {$daysLeft} day(s) until auto-delete)");
            }
        }

        return self::SUCCESS;
    }
}
