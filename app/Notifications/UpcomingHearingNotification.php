<?php

namespace App\Notifications;

use App\Modules\CaseManagement\Models\CaseModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpcomingHearingNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected CaseModel $case,
        protected int $daysUntil
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $when = $this->case->hearing_date?->format('Y-m-d') ?? 'N/A';
        $caseNo = $this->case->case_number;
        $officer = $this->case->title ?: 'Unassigned';

        return (new MailMessage)
            ->subject("Upcoming Hearing: {$caseNo}")
            ->greeting('Hello,')
            ->line("A case hearing is coming up in {$this->daysUntil} day(s).")
            ->line("Case: {$caseNo}")
            ->line("Hearing Date: {$when}")
            ->line("Officer Dealing: {$officer}")
            ->action('View Case', route('cases.show', $this->case))
            ->line('Please review and take necessary action in advance.');
    }
}
