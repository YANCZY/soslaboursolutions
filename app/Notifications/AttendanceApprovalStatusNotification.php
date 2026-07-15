<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceApprovalStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $status,
        private readonly string $date,
        private readonly string $url,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $label = ucfirst($this->status);

        return [
            'title' => "Attendance {$label}",
            'message' => "Your attendance for {$this->date} was {$this->status}.",
            'url' => $this->url,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
