<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestedNotification extends Notification
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
        $employee = $this->leaveRequest->employee;
        $leaveType = $this->leaveRequest->leaveType->name ?? 'Leave';
        $startDate = $this->leaveRequest->start_date->format('M d, Y');
        $endDate = $this->leaveRequest->end_date->format('M d, Y');
        $days = $this->leaveRequest->total_days;

        return (new MailMessage)
            ->subject("New Leave Request: {$employee->full_name} ({$leaveType})")
            ->greeting("Hello {$notifiable->name},")
            ->line("An employee has submitted a new leave request requiring review:")
            ->line("**Employee:** {$employee->full_name} ({$employee->employee_id})")
            ->line("**Leave Type:** {$leaveType}")
            ->line("**Duration:** {$startDate} to {$endDate} ({$days} working days)")
            ->line("**Reason:** {$this->leaveRequest->reason}")
            ->action('Review Leave Request', route('leaves.index'))
            ->line('Please review and take appropriate action in the HRIS portal.');
    }
}
