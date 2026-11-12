<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\User;
use App\Services\InvoiceGenerationService;
use App\Services\PaymentReviewService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BrioWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_only_see_their_own_invoices(): void
    {
        $landlord = User::factory()->create(['role' => 'landlord']);
        $tenant = User::factory()->create(['role' => 'tenant']);
        $otherTenant = User::factory()->create(['role' => 'tenant']);
        $contract = $this->createContract($landlord, $tenant);
        $otherContract = $this->createContract($landlord, $otherTenant, 'PROP-2', 'LEASE-2');
        $ownInvoice = $this->createInvoice($contract, 'OWN-INV');
        $otherInvoice = $this->createInvoice($otherContract, 'OTHER-INV');

        $response = $this->actingAs($tenant)->get(route('tenant.invoices.index'));

        $response->assertOk()->assertSee($ownInvoice->number)->assertDontSee($otherInvoice->number);
    }

    public function test_landlord_cannot_manage_another_landlords_payment(): void
    {
        $landlord = User::factory()->create(['role' => 'landlord']);
        $otherLandlord = User::factory()->create(['role' => 'landlord']);
        $tenant = User::factory()->create(['role' => 'tenant']);
        $contract = $this->createContract($otherLandlord, $tenant, 'PROP-3', 'LEASE-3');
        $invoice = $this->createInvoice($contract, 'OTHER-OWNER');
        $payment = $invoice->payments()->create([
            'declared_by' => $tenant->id,
            'amount' => '10.00',
            'method' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $this->actingAs($landlord)
            ->post(route('payments.approve', $payment))
            ->assertNotFound();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_approved_payment_creates_a_receipt_and_reduces_balance_exactly(): void
    {
        Notification::fake();
        $landlord = User::factory()->create(['role' => 'landlord']);
        $tenant = User::factory()->create(['role' => 'tenant']);
        $contract = $this->createContract($landlord, $tenant);
        $invoice = $this->createInvoice($contract, 'PARTIAL-INV', '300.00');
        $payment = $invoice->payments()->create([
            'declared_by' => $tenant->id,
            'amount' => '100.00',
            'method' => 'mobile_money',
            'status' => 'pending',
        ]);

        $receipt = app(PaymentReviewService::class)->approve($payment, $landlord);

        $this->assertSame('approved', $payment->fresh()->status);
        $this->assertSame('200.00', $invoice->fresh()->balance());
        $this->assertSame($payment->id, $receipt->payment_id);
        $this->assertDatabaseHas('receipts', ['payment_id' => $payment->id]);
    }

    public function test_payment_cannot_be_approved_above_remaining_invoice_balance(): void
    {
        $landlord = User::factory()->create(['role' => 'landlord']);
        $tenant = User::factory()->create(['role' => 'tenant']);
        $contract = $this->createContract($landlord, $tenant);
        $invoice = $this->createInvoice($contract, 'OVERPAY-INV', '10.00');
        $invoice->payments()->create([
            'declared_by' => $tenant->id,
            'amount' => '8.00',
            'method' => 'cash',
            'status' => 'approved',
        ]);
        $payment = $invoice->payments()->create([
            'declared_by' => $tenant->id,
            'amount' => '2.01',
            'method' => 'cash',
            'status' => 'pending',
        ]);

        $this->expectException(ValidationException::class);
        app(PaymentReviewService::class)->approve($payment, $landlord);
    }

    public function test_invoice_generation_uses_the_contract_anchor_and_is_idempotent(): void
    {
        $landlord = User::factory()->create(['role' => 'landlord']);
        $tenant = User::factory()->create(['role' => 'tenant']);
        $contract = $this->createContract($landlord, $tenant, startsAt: '2026-01-31');
        $generator = app(InvoiceGenerationService::class);

        $count = $generator->generateForContract($contract, CarbonImmutable::parse('2026-03-31'));
        $again = $generator->generateForContract($contract, CarbonImmutable::parse('2026-03-31'));

        $this->assertSame(3, $count);
        $this->assertSame(0, $again);
        $this->assertDatabaseHas('invoices', ['contract_id' => $contract->id, 'period_start' => '2026-02-28']);
        $this->assertDatabaseHas('invoices', ['contract_id' => $contract->id, 'period_start' => '2026-03-31']);
    }

    public function test_tenant_cannot_view_another_tenants_ticket(): void
    {
        $landlord = User::factory()->create(['role' => 'landlord']);
        $tenant = User::factory()->create(['role' => 'tenant']);
        $otherTenant = User::factory()->create(['role' => 'tenant']);
        $contract = $this->createContract($landlord, $otherTenant);
        $property = $contract->property;
        $ticket = MaintenanceTicket::query()->create([
            'landlord_id' => $landlord->id,
            'tenant_id' => $otherTenant->id,
            'contract_id' => $contract->id,
            'property_id' => $property->id,
            'category' => 'plumbing',
            'title' => 'Fuite',
            'description' => 'Fuite sous évier',
            'status' => 'open',
        ]);

        $this->actingAs($tenant)->get(route('tickets.show', $ticket))->assertNotFound();
    }

    public function test_disabled_user_cannot_access_the_profile(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)->get(route('profile.edit'))->assertForbidden();
    }

    public function test_ticket_status_cannot_skip_a_workflow_transition(): void
    {
        Notification::fake();
        $landlord = User::factory()->create(['role' => 'landlord']);
        $tenant = User::factory()->create(['role' => 'tenant']);
        $contract = $this->createContract($landlord, $tenant);
        $ticket = MaintenanceTicket::query()->create([
            'landlord_id' => $landlord->id,
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'property_id' => $contract->property_id,
            'category' => 'plumbing',
            'title' => 'Fuite',
            'description' => 'Fuite sous évier',
            'status' => 'open',
        ]);

        $this->actingAs($landlord)
            ->patch(route('tickets.status', $ticket), ['status' => 'awaiting_tenant_confirmation'])
            ->assertStatus(422);

        $this->assertSame('open', $ticket->fresh()->status);
    }

    public function test_landlord_cannot_record_an_expense_for_another_landlords_ticket(): void
    {
        $landlord = User::factory()->create(['role' => 'landlord']);
        $otherLandlord = User::factory()->create(['role' => 'landlord']);
        $tenant = User::factory()->create(['role' => 'tenant']);
        $contract = $this->createContract($otherLandlord, $tenant, 'PROP-4', 'LEASE-4');
        $ticket = MaintenanceTicket::query()->create([
            'landlord_id' => $otherLandlord->id,
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'property_id' => $contract->property_id,
            'category' => 'plumbing',
            'title' => 'Fuite',
            'description' => 'Fuite sous évier',
            'status' => 'open',
        ]);

        $this->actingAs($landlord)
            ->post(route('tickets.expenses.store', $ticket), [
                'category' => 'repair',
                'description' => 'Réparation',
                'amount' => '50.00',
                'currency' => 'USD',
                'spent_at' => '2026-11-13',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('expenses', ['ticket_id' => $ticket->id]);
    }

    private function createContract(
        User $landlord,
        User $tenant,
        string $propertyReference = 'PROP-1',
        string $contractReference = 'LEASE-1',
        string $startsAt = '2026-01-01',
    ): Contract {
        $landlord->tenants()->syncWithoutDetaching([$tenant->id]);
        $property = Property::query()->create([
            'landlord_id' => $landlord->id,
            'reference' => $propertyReference,
            'name' => 'Appartement test '.$propertyReference,
            'address' => '1 rue du Test',
            'property_type' => 'apartment',
            'status' => 'occupied',
        ]);

        return Contract::query()->create([
            'landlord_id' => $landlord->id,
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'reference' => $contractReference,
            'starts_at' => $startsAt,
            'periodicity' => 'monthly',
            'due_day' => 5,
            'rent_amount' => '300.00',
            'charges_amount' => '0.00',
            'deposit_amount' => '300.00',
            'currency' => 'USD',
            'status' => 'active',
        ]);
    }

    private function createInvoice(Contract $contract, string $number, string $amount = '300.00'): Invoice
    {
        $invoice = $contract->invoices()->create([
            'number' => $number,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'issued_at' => '2026-01-01',
            'due_at' => '2026-01-05',
            'currency' => 'USD',
            'status' => 'pending',
        ]);
        $invoice->items()->create(['description' => 'Loyer', 'amount' => $amount]);

        return $invoice;
    }
}
