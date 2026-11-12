<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Invoice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class InvoiceGenerationService
{
    public function generateForActiveContracts(?CarbonImmutable $through = null): int
    {
        $through ??= CarbonImmutable::today();
        $generated = 0;

        Contract::query()
            ->where('status', 'active')
            ->whereDate('starts_at', '<=', $through)
            ->orderBy('id')
            ->chunkById(100, function ($contracts) use ($through, &$generated): void {
                foreach ($contracts as $contract) {
                    $generated += $this->generateForContract($contract, $through);
                }
            });

        return $generated;
    }

    public function generateForContract(Contract $contract, ?CarbonImmutable $through = null): int
    {
        $through ??= CarbonImmutable::today();
        $interval = match ($contract->periodicity) {
            'quarterly' => 3,
            'yearly' => 12,
            default => 1,
        };
        $anchor = CarbonImmutable::parse($contract->starts_at)->startOfDay();
        $count = 0;

        for ($period = 0; ; $period++) {
            $periodStart = $anchor->addMonthsNoOverflow($period * $interval);
            if ($periodStart->greaterThan($through)) {
                break;
            }

            $nextStart = $anchor->addMonthsNoOverflow(($period + 1) * $interval);
            $periodEnd = $nextStart->subDay();
            if ($contract->ends_at && $periodEnd->greaterThan(CarbonImmutable::parse($contract->ends_at))) {
                $periodEnd = CarbonImmutable::parse($contract->ends_at);
            }

            if ($contract->ends_at && $periodStart->greaterThan(CarbonImmutable::parse($contract->ends_at))) {
                break;
            }

            $dueAt = $periodStart->setDay(min((int) $contract->due_day, $periodStart->daysInMonth));
            $invoice = DB::transaction(function () use ($contract, $periodStart, $periodEnd, $dueAt): ?Invoice {
                $invoice = Invoice::query()->firstOrCreate(
                    [
                        'contract_id' => $contract->id,
                        'period_start' => $periodStart->toDateString(),
                        'period_end' => $periodEnd->toDateString(),
                    ],
                    [
                        'number' => 'INV-'.now()->format('Ymd').'-'.strtoupper(str()->random(8)),
                        'issued_at' => $periodStart->toDateString(),
                        'due_at' => $dueAt->toDateString(),
                        'currency' => $contract->currency,
                        'status' => 'pending',
                    ],
                );

                if (! $invoice->wasRecentlyCreated) {
                    return null;
                }

                $invoice->items()->createMany([
                    ['description' => 'Loyer', 'amount' => $contract->rent_amount],
                    ...((float) $contract->charges_amount > 0
                        ? [['description' => 'Charges locatives', 'amount' => $contract->charges_amount]]
                        : []),
                ]);

                return $invoice;
            });

            if ($invoice) {
                $count++;
            }
        }

        return $count;
    }
}
