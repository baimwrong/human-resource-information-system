<?php

namespace App\Notifications;

use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayslipGeneratedNotification extends Notification
{
    use Queueable;

    public function __construct(public Payslip $payslip)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $monthName = date('F', mktime(0, 0, 0, $this->payslip->month, 10));
        $year = $this->payslip->year;
        $netSalary = number_format($this->payslip->net_salary, 2);

        return (new MailMessage)
            ->subject("Your Payslip for {$monthName} {$year} is Available")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your payslip for **{$monthName} {$year}** has been processed and disbursed.")
            ->line("**Net Disbursed Amount:** \$ {$netSalary}")
            ->line("**Disbursement Status:** " . ucfirst($this->payslip->payment_status))
            ->action('View & Download Payslip', route('payroll.payslip', $this->payslip->id))
            ->line('You can view your detailed breakdown of earnings, deductions, and tax withholdings via the link above.');
    }
}
