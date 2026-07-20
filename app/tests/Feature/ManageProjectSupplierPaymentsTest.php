<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ManageProjectConfirmedSupplier;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\CategoryBudgetSupplier;
use App\Models\Payment;
use App\Models\PaymentMode;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectImage;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ManageProjectSupplierPaymentsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_payment_delete_requires_confirmation(): void
    {
        [$project, $budget, $payment] = $this->createSupplierPaymentContext();
        $this->actingAsAdmin();

        Livewire::test(ManageProjectConfirmedSupplier::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
        ])
            ->call('setActiveWorkspaceTab', 'payments')
            ->call('promptDeletePayment', $payment->id)
            ->assertSet('confirmDeletePaymentId', $payment->id)
            ->assertSee('Delete payment?');

        $this->assertDatabaseHas('payments', ['id' => $payment->id]);

        Livewire::test(ManageProjectConfirmedSupplier::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
        ])
            ->call('promptDeletePayment', $payment->id)
            ->call('confirmDeletePayment');

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_payment_can_be_edited_from_modal(): void
    {
        [$project, $budget, $payment, $paymentMode] = $this->createSupplierPaymentContext();
        $this->actingAsAdmin();

        Livewire::test(ManageProjectConfirmedSupplier::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
        ])
            ->call('setActiveWorkspaceTab', 'payments')
            ->call('editPayment', $payment->id)
            ->assertSet('editingPaymentId', $payment->id)
            ->assertSet('paymentEditForm.reason', 'Initial deposit')
            ->set('paymentEditForm.reason', 'Updated deposit')
            ->set('paymentEditForm.amount', '1500')
            ->set('paymentEditForm.due_date', '2026-08-10')
            ->set('paymentEditForm.payment_mode_id', (string) $paymentMode->id)
            ->set('paymentEditForm.payment_status', Payment::STATUS_PAID)
            ->set('paymentEditForm.paid_at', '2026-08-11')
            ->set('paymentEditForm.invoice_reference', 'INV-100')
            ->set('paymentEditForm.notes', 'Updated note')
            ->call('savePaymentEdit')
            ->assertSet('editingPaymentId', null);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'reason' => 'Updated deposit',
            'amount' => 1500,
            'due_date' => '2026-08-10',
            'payment_status' => Payment::STATUS_PAID,
            'paid_at' => '2026-08-11',
            'invoice_reference' => 'INV-100',
            'notes' => 'Updated note',
        ]);
    }

    public function test_payment_can_be_created_with_standard_reason_option(): void
    {
        [$project, $budget, , $paymentMode] = $this->createSupplierPaymentContext();
        $this->actingAsAdmin();

        Livewire::test(ManageProjectConfirmedSupplier::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
        ])
            ->call('setActiveWorkspaceTab', 'payments')
            ->set('paymentEntryMode', 'schedule')
            ->set('paymentForm.payment_mode_id', (string) $paymentMode->id)
            ->set('paymentForm.reason', 'SECOND DEPOSIT')
            ->set('paymentForm.amount', '1200')
            ->set('paymentForm.due_date', '2026-09-01')
            ->call('savePayment');

        $this->assertDatabaseHas('payments', [
            'project_id' => $project->id,
            'reason' => 'SECOND DEPOSIT',
            'amount' => 1200,
            'payment_status' => Payment::STATUS_UNPAID,
        ]);
    }

    public function test_payment_can_be_created_with_other_reason_text(): void
    {
        [$project, $budget, , $paymentMode] = $this->createSupplierPaymentContext();
        $this->actingAsAdmin();

        Livewire::test(ManageProjectConfirmedSupplier::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
        ])
            ->call('setActiveWorkspaceTab', 'payments')
            ->set('paymentEntryMode', 'schedule')
            ->set('paymentForm.payment_mode_id', (string) $paymentMode->id)
            ->set('paymentForm.reason', 'OTHER')
            ->set('paymentForm.custom_reason', 'EXTRA SERVICE')
            ->set('paymentForm.amount', '300')
            ->set('paymentForm.due_date', '2026-09-10')
            ->call('savePayment');

        $this->assertDatabaseHas('payments', [
            'project_id' => $project->id,
            'reason' => 'EXTRA SERVICE',
            'amount' => 300,
            'payment_status' => Payment::STATUS_UNPAID,
        ]);
    }

    public function test_document_can_be_edited_and_delete_requires_confirmation(): void
    {
        Storage::fake('public');

        [$project, $budget, , , $proposal] = $this->createSupplierPaymentContext();
        $document = $proposal->projectDocuments()->create([
            'project_id' => $project->id,
            'supplier_id' => $proposal->supplier_id,
            'title' => 'Old contract',
            'document_type' => ProjectDocument::TYPE_CONTRACT,
            'type' => ProjectDocument::TYPE_CONTRACT,
            'file_path' => 'projects/documents/contract.pdf',
            'description' => 'Old description',
        ]);
        Storage::disk('public')->put($document->file_path, 'pdf');

        $this->actingAsAdmin();

        Livewire::test(ManageProjectConfirmedSupplier::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
        ])
            ->call('setActiveWorkspaceTab', 'documents')
            ->call('editDocument', $document->id)
            ->assertSet('editingDocumentId', $document->id)
            ->set('documentEditForm.title', 'Updated contract')
            ->set('documentEditForm.type', ProjectDocument::TYPE_INVOICE)
            ->set('documentEditForm.description', 'Updated description')
            ->call('saveDocumentEdit')
            ->assertSet('editingDocumentId', null)
            ->call('promptDeleteDocument', $document->id)
            ->assertSet('confirmDeleteDocumentId', $document->id);

        $this->assertDatabaseHas('project_documents', [
            'id' => $document->id,
            'title' => 'Updated contract',
            'type' => ProjectDocument::TYPE_INVOICE,
            'description' => 'Updated description',
        ]);

        Livewire::test(ManageProjectConfirmedSupplier::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
        ])
            ->call('promptDeleteDocument', $document->id);

        $this->assertDatabaseHas('project_documents', ['id' => $document->id]);

        Livewire::test(ManageProjectConfirmedSupplier::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
        ])
            ->call('promptDeleteDocument', $document->id)
            ->call('confirmDeleteDocument');

        $this->assertDatabaseMissing('project_documents', ['id' => $document->id]);
    }

    public function test_image_can_be_edited_and_delete_requires_confirmation(): void
    {
        Storage::fake('public');

        [$project, $budget, , , $proposal] = $this->createSupplierPaymentContext();
        $image = $project->projectImages()->create([
            'supplier_id' => $proposal->supplier_id,
            'image_path' => 'projects/images/photo.jpg',
            'description' => 'Old image',
            'image_category' => 'exterior',
            'is_client_visible' => false,
        ]);
        Storage::disk('public')->put($image->image_path, 'jpg');

        $this->actingAsAdmin();

        Livewire::test(ManageProjectConfirmedSupplier::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
        ])
            ->call('setActiveWorkspaceTab', 'photogallery')
            ->call('editImage', $image->id)
            ->assertSet('editingImageId', $image->id)
            ->set('imageEditForm.description', 'Updated image')
            ->set('imageEditForm.image_category', 'details')
            ->set('imageEditForm.is_client_visible', true)
            ->call('saveImageEdit')
            ->assertSet('editingImageId', null)
            ->call('promptDeleteImage', $image->id)
            ->assertSet('confirmDeleteImageId', $image->id);

        $this->assertDatabaseHas('project_images', [
            'id' => $image->id,
            'description' => 'Updated image',
            'image_category' => 'details',
            'is_client_visible' => true,
        ]);

        Livewire::test(ManageProjectConfirmedSupplier::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
        ])
            ->call('promptDeleteImage', $image->id);

        $this->assertDatabaseHas('project_images', ['id' => $image->id]);

        Livewire::test(ManageProjectConfirmedSupplier::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
        ])
            ->call('promptDeleteImage', $image->id)
            ->call('confirmDeleteImage');

        $this->assertDatabaseMissing('project_images', ['id' => $image->id]);
    }

    protected function createSupplierPaymentContext(): array
    {
        $paymentMode = PaymentMode::query()->firstOrCreate(
            ['name' => 'Bank transfer'],
            ['is_active' => true, 'sort_order' => 1],
        );

        $project = Project::query()->create([
            'name' => 'Supplier payment wedding',
            'last_name' => 'Client',
        ]);
        $category = Category::query()->create(['label' => 'Flowers', 'label_it' => 'Fiori']);
        $budget = CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $category->id,
            'initial_estimated_amount' => 1000,
        ]);
        $supplier = Supplier::query()->create([
            'name' => 'Payment supplier',
            'category_id' => $category->id,
            'accepted_payment_mode_ids' => (string) $paymentMode->id,
        ]);
        $proposal = CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $supplier->id,
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
            'proposed_amount' => 1000,
        ]);
        $payment = Payment::query()->create([
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'category_budget_supplier_id' => $proposal->id,
            'payment_mode_id' => $paymentMode->id,
            'reason' => 'Initial deposit',
            'amount' => 1000,
            'due_date' => '2026-08-01',
            'payment_status' => Payment::STATUS_UNPAID,
        ]);

        return [$project, $budget, $payment, $paymentMode, $proposal];
    }

    protected function actingAsAdmin(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));
    }
}
