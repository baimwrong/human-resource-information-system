<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(public LeaveRequest $leaveRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = ucfirst($this->leaveRequest->status);
        $leaveType = $this->leaveRequest->leaveType->name ?? 'Leave';
        $startDate = $this->leaveRequest->start_date->format('M d, Y');
        $endDate = $this->leaveRequest->end_date->format('M d, Y');
        $days = $this->leaveRequest->total_days;
        $isApproved = $this->leaveRequest->status === 'approved';

        $message = (new MailMessage)
            ->subject("Leave Request {$status}: {$leaveType}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your leave request has been **{$this->leaveRequest->status}**.")
            ->line("**Leave Type:** {$leaveType}")
            ->line("**Period:** {$startDate} to {$endDate} ({$days} days)");

        if ($this->leaveRequest->action_reason) {
            $message->line("**Remarks:** {$this->leaveRequest->action_reason}");
        }

        if ($isApproved) {
            $message->line('Your leave balance and attendance calendar have been automatically adjusted.');
        } else {
            $message->line('Your pending leave balance has been released.');
        }

        return $message
            ->action('View My Leaves', route('leaves.my'))
            ->line('Thank you for using HRIS Enterprise.');
    }
}
