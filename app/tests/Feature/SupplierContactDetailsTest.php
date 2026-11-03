<?php

namespace Tests\Feature;

use App\Filament\Resources\SupplierResource\Pages\CreateSupplier;
use App\Filament\Resources\SupplierResource\Pages\EditSupplier;
use App\Models\Category;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Closure;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierContactDetailsTest extends TestCase
{
    use DatabaseTransactions;

    private Closure $restoreRepeater;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restoreRepeater = Repeater::fake();
        $role = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
    }

    protected function tearDown(): void
    {
        ($this->restoreRepeater)();

        parent::tearDown();
    }

    public function test_existing_supplier_keeps_default_contact_and_billing_data_when_new_details_are_added(): void
    {
        $category = Category::query()->create(['label' => 'Contact details test category', 'label_it' => 'Categoria contatti']);
        $legacyData = [
            'name' => 'Il Sorriso',
            'category_id' => $category->id,
            'contact_person' => 'Anna',
            'email' => 'anna@example.com',
            'phone' => '+39 333 1234567',
            'lang_comunication' => 'it',
            'vat_number' => 'IT12345678901',
            'tax_code' => '12345678901',
            'address_line_1' => 'Via Roma 10',
            'internal_notes' => 'Existing notes',
            'loc_website' => 'https://venue.example.com',
        ];
        $supplier = Supplier::query()->create($legacyData);
        $supplier->syncCategoriesFromMainAndOther();

        $this->assertNull($supplier->website);
        $this->assertNull($supplier->company_name);
        $this->assertNull($supplier->additional_contacts);

        Livewire::test(EditSupplier::class, ['record' => $supplier->id])
            ->assertSee('Contact 1 (default)')
            ->assertSee('Add contact')
            ->assertFormSet(['contact_person' => 'Anna', 'email' => 'anna@example.com', 'phone' => '+39 333 1234567'])
            ->fillForm([
                'website' => 'https://www.ilsorriso.example.com',
                'company_name' => 'Il Sorriso Servizi S.r.l.',
                'additional_contacts' => [
                    ['name' => 'Marco', 'phone' => '+39 333 7654321', 'email' => 'marco@example.com'],
                    ['name' => 'Laura', 'phone' => '+39 333 9876543', 'email' => 'laura@example.com'],
                ],
            ])
            ->assertSee('Contact 2')
            ->assertSee('Contact 3')
            ->call('save')
            ->assertHasNoFormErrors();

        $supplier->refresh();
        foreach ($legacyData as $field => $value) {
            $this->assertEquals($value, $supplier->{$field}, 'Existing field changed: ' . $field);
        }
        $this->assertSame('https://www.ilsorriso.example.com', $supplier->website);
        $this->assertSame('Il Sorriso Servizi S.r.l.', $supplier->company_name);
        $this->assertEquals([
            ['name' => 'Marco', 'phone' => '+39 333 7654321', 'email' => 'marco@example.com'],
            ['name' => 'Laura', 'phone' => '+39 333 9876543', 'email' => 'laura@example.com'],
        ], $supplier->additional_contacts);
        $this->assertSame([$category->id], $supplier->categories()->pluck('categories.id')->all());
    }

    public function test_supplier_can_be_created_with_default_and_additional_contacts(): void
    {
        Livewire::test(CreateSupplier::class)
            ->fillForm([
                'name' => 'New contact supplier',
                'contact_person' => 'Default reference',
                'email' => 'default@example.com',
                'phone' => '+39 333 0000000',
                'website' => 'https://supplier.example.com',
                'company_name' => 'Legal Supplier S.r.l.',
                'additional_contacts' => [
                    ['name' => 'Second reference', 'phone' => '+39 333 1111111', 'email' => 'second@example.com'],
                    ['name' => 'Third reference', 'phone' => null, 'email' => 'third@example.com'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $supplier = Supplier::query()->where('name', 'New contact supplier')->firstOrFail();
        $this->assertSame('default@example.com', $supplier->email);
        $this->assertSame('Default reference', $supplier->contact_person);
        $this->assertSame('Legal Supplier S.r.l.', $supplier->company_name);
        $this->assertSame('https://supplier.example.com', $supplier->website);
        $this->assertSame(['second@example.com', 'third@example.com'], array_column($supplier->additional_contacts, 'email'));

        Livewire::test(EditSupplier::class, ['record' => $supplier->id])
            ->assertFormSet([
                'email' => 'default@example.com',
                'additional_contacts' => $supplier->additional_contacts,
            ])
            ->assertSee('Contact 2')
            ->assertSee('Contact 3');
    }

    public function test_new_fields_are_optional_and_legacy_primary_contact_can_still_be_edited(): void
    {
        Livewire::test(CreateSupplier::class)
            ->fillForm(['name' => 'Minimal contact supplier'])
            ->call('create')
            ->assertHasNoFormErrors();
        $supplier = Supplier::query()->where('name', 'Minimal contact supplier')->firstOrFail();
        $this->assertNull($supplier->website);
        $this->assertNull($supplier->company_name);
        $this->assertEmpty($supplier->additional_contacts);

        Livewire::test(EditSupplier::class, ['record' => $supplier->id])
            ->fillForm(['contact_person' => 'Updated reference', 'email' => 'updated@example.com', 'phone' => '3331234567'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('updated@example.com', $supplier->refresh()->email);
        $this->assertSame('Updated reference', $supplier->contact_person);
    }

    public function test_removing_an_additional_contact_does_not_change_default_contact_or_other_details(): void
    {
        $supplier = Supplier::query()->create([
            'name' => 'Contact removal supplier',
            'email' => 'default@example.com',
            'contact_person' => 'Default reference',
            'phone' => '3331234567',
            'website' => 'https://supplier.example.com',
            'company_name' => 'Supplier S.r.l.',
            'additional_contacts' => [
                ['name' => 'Second reference', 'email' => 'second@example.com', 'phone' => '3337654321'],
            ],
        ]);
        Livewire::test(EditSupplier::class, ['record' => $supplier->id])
            ->fillForm(['additional_contacts' => []])
            ->call('save')
            ->assertHasNoFormErrors();

        $supplier->refresh();
        $this->assertEmpty($supplier->additional_contacts);
        $this->assertSame('default@example.com', $supplier->email);
        $this->assertSame('Default reference', $supplier->contact_person);
        $this->assertSame('3331234567', $supplier->phone);
        $this->assertSame('https://supplier.example.com', $supplier->website);
        $this->assertSame('Supplier S.r.l.', $supplier->company_name);
    }

    public function test_invalid_website_and_additional_contact_email_are_rejected_without_saving(): void
    {
        $supplier = Supplier::query()->create(['name' => 'Validation supplier', 'email' => 'default@example.com']);
        Livewire::test(EditSupplier::class, ['record' => $supplier->id])
            ->fillForm([
                'website' => 'javascript:alert(1)',
                'additional_contacts' => [['name' => 'Invalid email', 'email' => 'not-an-email', 'phone' => null]],
            ])
            ->call('save')
            ->assertHasFormErrors(['website', 'additional_contacts.0.email']);

        $this->assertNull($supplier->refresh()->website);
        $this->assertNull($supplier->additional_contacts);
        $this->assertSame('default@example.com', $supplier->email);
    }
}
