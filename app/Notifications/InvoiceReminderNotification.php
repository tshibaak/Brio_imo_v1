<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InvoiceReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Invoice $invoice,
        private readonly string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'number' => $this->invoice->number,
            'status' => $this->invoice->status,
            'balance' => $this->invoice->balance(),
            'currency' => $this->invoice->currency,
            'message' => $this->message,
        ];
    }
}
