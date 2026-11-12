<?php

namespace App\Console\Commands;

use App\Services\InvoiceGenerationService;
use Illuminate\Console\Command;

class GenerateInvoices extends Command
{
    protected $signature = 'brio:generate-invoices';

    protected $description = 'Génère les factures correspondant aux périodes de contrats actifs.';

    public function handle(InvoiceGenerationService $service): int
    {
        $count = $service->generateForActiveContracts();
        $this->info("{$count} facture(s) créée(s).");

        return self::SUCCESS;
    }
}
