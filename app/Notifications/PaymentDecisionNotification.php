<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Payment $payment,
        private readonly bool $approved,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'invoice_id' => $this->payment->invoice_id,
            'status' => $this->approved ? 'approved' : 'rejected',
            'message' => $this->approved
                ? 'Votre paiement a été validé. Votre reçu est disponible.'
                : 'Votre paiement a été rejeté : '.$this->payment->rejection_reason,
        ];
    }
}
