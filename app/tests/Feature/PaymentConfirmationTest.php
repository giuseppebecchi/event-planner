<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ManageProjectConfirmedSupplier;
use App\Models\{Category, CategoryBudget, CategoryBudgetSupplier, Payment, PaymentMode, Project, Role, Supplier, User};
use App\Notifications\PaymentReceivedNotification;
use App\Support\PaymentConfirmationEmails;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentConfirmationTest extends TestCase
{
    use DatabaseTransactions;

    public static function entryPoints(): array
    {
        return [['new'], ['register'], ['edit']];
    }

    #[DataProvider('entryPoints')]
    public function test_paid_payment_queues_both_recipients_and_sends_only_once(string $entry): void
    {
        [$project, $budget, $payment, $mode] = $this->context();
        Notification::fake();
        $page = $this->page($project, $budget);
        if ($entry === 'new') {
            $page->set('paymentForm.payment_mode_id', (string) $mode->id)->set('paymentForm.reason', 'INITIAL DEPOSIT')
                ->set('paymentForm.amount', '500')->set('paymentForm.paid_at', '2026-11-16')->call('savePayment')->assertHasNoErrors();
        } elseif ($entry === 'register') {
            $page->call('startPaymentRegistration', $payment->id)->set('paymentCompletionForms.'.$payment->id.'.paid_at', '2026-11-16')
                ->call('registerScheduledPayment', $payment->id)->assertHasNoErrors();
        } else {
            $page->call('editPayment', $payment->id)->set('paymentEditForm.payment_status', 'paid')->set('paymentEditForm.paid_at', '2026-11-16')
                ->call('savePaymentEdit')->assertHasNoErrors();
        }
        $this->assertSame(2, DB::table('payment_confirmation_emails')->count());
        Notification::assertNothingSent();
        $this->artisan('payments:send-confirmations')->assertSuccessful();
        Notification::assertSentOnDemandTimes(PaymentReceivedNotification::class, 2);
        $this->assertSame(2, DB::table('payment_confirmation_emails')->whereNotNull('sent_at')->count());
        $this->artisan('payments:send-confirmations')->assertSuccessful();
        Notification::assertSentOnDemandTimes(PaymentReceivedNotification::class, 2);
    }

    public function test_opt_out_and_unpaid_scheduling_do_not_queue_emails(): void
    {
        [$project, $budget, $payment, $mode] = $this->context();
        $this->page($project, $budget)->call('startPaymentRegistration', $payment->id)
            ->set('paymentCompletionForms.'.$payment->id.'.notify_couple', false)->call('registerScheduledPayment', $payment->id);
        $this->page($project, $budget)->set('paymentEntryMode', 'schedule')->set('paymentForm.payment_mode_id', (string) $mode->id)
            ->set('paymentForm.amount', '100')->call('savePayment');
        $this->assertSame(0, DB::table('payment_confirmation_emails')->count());
        $this->assertSame('paid', $payment->refresh()->payment_status);
    }

    public function test_historical_paid_records_and_subsequent_edits_do_not_trigger_emails(): void
    {
        [$project, $budget, $payment] = $this->context();
        $payment->update(['payment_status' => 'paid', 'paid_at' => '2026-11-10']);
        $this->page($project, $budget)->call('editPayment', $payment->id)->set('paymentEditForm.notes', 'Receipt corrected')
            ->call('savePaymentEdit')->assertHasNoErrors();
        $this->assertSame(0, DB::table('payment_confirmation_emails')->count());
    }

    public function test_recipient_addresses_are_deduplicated_and_missing_addresses_are_skipped(): void
    {
        [$project, , $payment] = $this->context();
        $project->update(['email' => 'Couple@example.com', 'secondary_email' => 'couple@example.com']);
        $payment->update(['payment_status' => 'paid']);
        $this->assertSame(1, app(PaymentConfirmationEmails::class)->enqueue($payment));
        $this->assertSame(1, DB::table('payment_confirmation_emails')->count());
        $project->update(['email' => null, 'secondary_email' => 'invalid-address']);
        $payment->unsetRelation('project');
        $this->assertSame(0, app(PaymentConfirmationEmails::class)->enqueue($payment));
    }

    public function test_english_message_confirms_supplier_receipt_and_never_attaches_documents(): void
    {
        [, , $payment] = $this->context();
        $payment->update(['payment_status' => 'paid', 'paid_at' => '2026-11-16']);
        app(PaymentConfirmationEmails::class)->enqueue($payment);
        $row = DB::table('payment_confirmation_emails')->first();
        $notification = new PaymentReceivedNotification(json_decode($row->payload, true));
        $mail = $notification->toMail(new \Illuminate\Notifications\AnonymousNotifiable);
        $html = $mail->viewData['bodyHtml'];
        $this->assertStringContainsString('has received your payment', $html);
        $this->assertStringContainsString('EUR 500.00', $html);
        $this->assertStringContainsString('November 16, 2026', $html);
        $this->assertStringContainsString('/admin/projects/', $html);
        $this->assertSame([], $mail->attachments);
    }

    public function test_failures_preserve_payment_and_retry_only_unsent_recipient(): void
    {
        [, , $payment] = $this->context();
        $payment->update(['payment_status' => 'paid']);
        app(PaymentConfirmationEmails::class)->enqueue($payment);
        $first = DB::table('payment_confirmation_emails')->orderBy('id')->first();
        DB::table('payment_confirmation_emails')->where('id', $first->id)->update(['sent_at' => now(), 'attempts' => 1]);
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Simulated SMTP failure'));
        $this->artisan('payments:send-confirmations')->assertFailed();
        $this->assertSame('paid', $payment->refresh()->payment_status);
        $this->assertSame(1, DB::table('payment_confirmation_emails')->whereNull('sent_at')->value('attempts'));
        Notification::fake();
        $this->artisan('payments:send-confirmations')->assertSuccessful();
        Notification::assertSentOnDemandTimes(PaymentReceivedNotification::class, 1);
        $this->assertSame(2, DB::table('payment_confirmation_emails')->whereNotNull('sent_at')->count());
    }

    public function test_canceled_payment_is_not_sent_and_repeated_enqueue_is_idempotent(): void
    {
        [, , $payment] = $this->context();
        $payment->update(['payment_status' => 'paid']);
        app(PaymentConfirmationEmails::class)->enqueue($payment);
        app(PaymentConfirmationEmails::class)->enqueue($payment);
        $this->assertSame(2, DB::table('payment_confirmation_emails')->count());
        $payment->update(['payment_status' => 'unpaid']);
        Notification::fake();
        $this->artisan('payments:send-confirmations')->assertSuccessful();
        Notification::assertNothingSent();
    }

    private function page(Project $project, CategoryBudget $budget)
    {
        return Livewire::test(ManageProjectConfirmedSupplier::class, ['record' => $project->id, 'categoryBudget' => $budget->id]);
    }

    public function test_failed_notifications_are_visible_and_can_be_retried_without_resending_successful_ones(): void
    {
        [$project, $budget, $payment] = $this->context();
        $payment->update(['payment_status' => 'paid']);
        app(PaymentConfirmationEmails::class)->enqueue($payment);
        $first = DB::table('payment_confirmation_emails')->orderBy('id')->first();
        DB::table('payment_confirmation_emails')->where('id', $first->id)->update(['sent_at' => now()]);
        DB::table('payment_confirmation_emails')->whereNull('sent_at')->update(['attempts' => 5]);
        Notification::fake();
        $this->artisan('payments:send-confirmations')->assertSuccessful();
        Notification::assertNothingSent();
        $this->page($project, $budget)->call('retryPaymentConfirmation', $payment->id)->assertHasNoErrors();
        $this->artisan('payments:send-confirmations')->assertSuccessful();
        Notification::assertSentOnDemandTimes(PaymentReceivedNotification::class, 1);
    }

    public function test_no_email_is_queued_when_payment_validation_fails(): void
    {
        [$project, $budget, , $mode] = $this->context();
        $this->page($project, $budget)->set('paymentForm.payment_mode_id', (string) $mode->id)
            ->set('paymentForm.amount', 'invalid')->call('savePayment')->assertHasErrors();
        $this->assertSame(0, DB::table('payment_confirmation_emails')->count());
    }

    private function context(): array
    {
        $role = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        $project = Project::query()->create(['name' => 'Payment email wedding', 'last_name' => 'Client', 'email' => 'first@example.com', 'secondary_email' => 'second@example.com']);
        $category = Category::query()->create(['label' => 'Payment email category', 'label_it' => 'Pagamenti']);
        $budget = $project->categoryBudgets()->create(['category_id' => $category->id]);
        $mode = PaymentMode::query()->firstOrCreate(['name' => 'Email test bank transfer'], ['is_active' => true]);
        $supplier = Supplier::query()->create(['name' => 'Confirmed supplier', 'category_id' => $category->id]);
        $proposal = $budget->supplierProposals()->create(['supplier_id' => $supplier->id, 'proposal_status' => 'confirmed', 'proposed_amount' => 1000]);
        $payment = $proposal->payments()->create(['project_id' => $project->id, 'supplier_id' => $supplier->id, 'payment_mode_id' => $mode->id, 'amount' => 500, 'reason' => 'Deposit', 'payment_status' => 'unpaid']);
        return [$project, $budget, $payment, $mode];
    }
}
