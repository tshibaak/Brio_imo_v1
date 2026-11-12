<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\User;
use App\Notifications\InvoiceReminderNotification;
use App\Support\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendRentReminders extends Command
{
    protected $signature = 'brio:send-rent-reminders';

    protected $description = 'Envoie les rappels internes d’échéance sans doublons.';

    public function handle(): int
    {
        $today = today();
        $sent = 0;
        Invoice::query()
            ->whereDate('due_at', '<=', $today->copy()->addDays(7))
            ->with(['contract.tenant', 'items', 'payments'])
            ->orderBy('id')
            ->chunkById(100, function ($invoices) use ($today, &$sent): void {
                foreach ($invoices as $invoice) {
                    if (Money::minorUnits($invoice->balance()) <= 0) {
                        continue;
                    }

                    $daysUntilDue = $today->diffInDays($invoice->due_at, false);
                    $type = match ($daysUntilDue) {
                        7 => 'seven_days',
                        2 => 'two_days',
                        0 => 'due_today',
                        default => $daysUntilDue < 0 && abs($daysUntilDue) % 2 === 0 ? 'overdue' : null,
                    };
                    if ($type === null) {
                        continue;
                    }

                    $claimed = DB::table('invoice_reminders')->insertOrIgnore([
                        'invoice_id' => $invoice->id,
                        'reminder_date' => $today->toDateString(),
                        'reminder_type' => $type,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    if ($claimed !== 1) {
                        continue;
                    }

                    $invoice->contract->tenant->notify(new InvoiceReminderNotification(
                        $invoice,
                        $type === 'overdue'
                            ? 'Votre facture '.$invoice->number.' présente un solde en retard de '.Money::decimal(Money::minorUnits($invoice->balance())).' '.$invoice->currency.'.'
                            : 'Rappel : votre facture '.$invoice->number.' présente un solde de '.Money::decimal(Money::minorUnits($invoice->balance())).' '.$invoice->currency.'.',
                    ));
                    $sent++;
                }
            });

        $this->info("{$sent} rappel(s) envoyé(s).");

        return self::SUCCESS;
    }
}
