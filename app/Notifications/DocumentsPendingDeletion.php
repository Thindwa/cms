<?php

namespace App\Notifications;

use App\Modules\CaseManagement\Models\CaseDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class DocumentsPendingDeletion extends Notification
{
    use Queueable;

    protected Collection $grouped;

    protected int $retentionDays;

    public function __construct(Collection $grouped, int $retentionDays)
    {
        $this->grouped = $grouped;
        $this->retentionDays = $retentionDays;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $total = $this->grouped->sum->count();

        $message = (new MailMessage)
            ->subject("Action Required: {$total} document(s) pending permanent deletion")
            ->greeting('Hello,')
            ->line("The following soft-deleted documents will be permanently deleted after {$this->retentionDays} days in the recycle bin.")
            ->line('Please review and restore any important documents before they are automatically removed.');

        foreach ($this->grouped as $caseNo => $documents) {
            $lines = $documents->map(fn (CaseDocument $doc) => (
                "- {$doc->original_name} (deleted {$doc->deleted_at->formatDate()})"
            ))->toArray();

            $message->line("Case {$caseNo}:");
            $message->line(implode("\n", $lines));
        }

        $message->action('View Recycle Bin', route('cases.documents.recycle-bin'))
            ->line('Documents that are not restored before the deadline will be permanently lost.');

        return $message;
    }
}
