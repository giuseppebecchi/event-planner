<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ViewProjectGuests;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use ReflectionMethod;
use Tests\TestCase;

class ProjectGuestFormTest extends TestCase
{
    use DatabaseTransactions;

    public function test_new_guest_form_starts_with_an_empty_guest_list_and_hides_rsvp_number(): void
    {
        $project = $this->createProject();
        $this->actingAsAdmin();

        Livewire::test(ViewProjectGuests::class, ['record' => $project->id])
            ->call('startCreateGuest')
            ->assertSet('guestForm.guest_list', '')
            ->assertSee('Additional guests / childs')
            ->assertDontSeeHtml('wire:model="guestForm.rsvp_number"');
    }

    public function test_unspecified_plus_one_disables_and_clears_partner_fields(): void
    {
        $project = $this->createProject();
        $this->actingAsAdmin();

        Livewire::test(ViewProjectGuests::class, ['record' => $project->id])
            ->call('startCreateGuest')
            ->set('guestForm.primary_first_name', 'Alex')
            ->set('guestForm.partner_first_name', 'Stored partner')
            ->set('guestForm.partner_role', 'Witness')
            ->set('guestForm.unspecified_plus_one', true)
            ->assertSeeHtml('wm-guests-grid is-five is-disabled')
            ->call('saveGuest')
            ->assertHasNoErrors();

        $guest = $project->guests()->sole();

        $this->assertTrue($guest->unspecified_plus_one);
        $this->assertNull($guest->partner_first_name);
        $this->assertNull($guest->partner_role);
        $this->assertNull($guest->guest_list);
    }

    public function test_guest_template_contains_child_column_and_three_example_parties(): void
    {
        $project = $this->createProject();
        $this->actingAsAdmin();
        $page = Livewire::test(ViewProjectGuests::class, ['record' => $project->id])->instance();
        $response = $page->downloadGuestTemplate();

        ob_start();
        ($response->getCallback())();
        $contents = ob_get_clean();

        $path = $this->temporaryXlsxPath();
        file_put_contents($path, $contents);

        try {
            $rows = $this->readSpreadsheetRows($path);
            $parties = $this->invokeProtected($page, 'readGuestPartiesFromSpreadsheet', [$path]);
        } finally {
            unlink($path);
        }

        $this->assertSame('Child', $rows[0][14]);
        $this->assertCount(3, $parties);
        $this->assertSame('YES', $parties[2][2][14]);

        $thirdParty = $this->invokeProtected($page, 'normalizeImportedParty', [$parties[2]]);

        $this->assertSame('Michael', $thirdParty['primary_first_name']);
        $this->assertSame('Emily', $thirdParty['partner_first_name']);
        $this->assertSame('Sophie', $thirdParty['additional_guests'][0]['first_name']);
        $this->assertSame('Child', $thirdParty['additional_guests'][0]['type']);
    }

    public function test_guest_import_recognizes_child_values_and_keeps_old_templates_compatible(): void
    {
        $project = $this->createProject();
        $this->actingAsAdmin();
        $page = Livewire::test(ViewProjectGuests::class, ['record' => $project->id])->instance();

        foreach (['yes', 'YES', 'y', 'Y', 'child', 'CHILD'] as $value) {
            $this->assertTrue($this->invokeProtected($page, 'isChildSpreadsheetValue', [$value]));
        }
        $this->assertFalse($this->invokeProtected($page, 'isChildSpreadsheetValue', ['NO']));
        $this->assertFalse($this->invokeProtected($page, 'isChildSpreadsheetValue', ['']));

        $newPath = $this->writeGuestSpreadsheet([
            ['Last Name', 'First Name', 'Title', 'Suffix', 'Street Address', 'Address Line 2', 'City', 'State', 'Zip', 'Country', 'Phone', 'Email', 'Group', 'Guest List', 'Child'],
            ['Stone', 'Alice', 'Mrs.', '', '', '', '', '', '', '', '', '', '', '', 'NO'],
            ['Stone', 'Charlie', '', '', '', '', '', '', '', '', '', '', '', '', 'Y'],
            ['Stone', 'Bob', 'Mr.', '', '', '', '', '', '', '', '', '', '', '', 'NO'],
        ]);
        $oldPath = $this->writeGuestSpreadsheet([
            ['Last Name', 'First Name', 'Title', 'Suffix', 'Street Address', 'Address Line 2', 'City', 'State', 'Zip', 'Country', 'Phone', 'Email', 'Group', 'Guest List'],
            ['Miller', 'Anna', 'Mrs.', '', '', '', '', '', '', '', '', '', '', ''],
            ['Miller', 'David', 'Mr.', '', '', '', '', '', '', '', '', '', '', ''],
            ['Miller', 'Laura', '', '', '', '', '', '', '', '', '', '', '', ''],
        ]);

        try {
            $newParties = $this->invokeProtected($page, 'readGuestPartiesFromSpreadsheet', [$newPath]);
            $newParty = $this->invokeProtected($page, 'normalizeImportedParty', [$newParties[0]]);
            $oldParties = $this->invokeProtected($page, 'readGuestPartiesFromSpreadsheet', [$oldPath]);
            $oldParty = $this->invokeProtected($page, 'normalizeImportedParty', [$oldParties[0]]);
        } finally {
            unlink($newPath);
            unlink($oldPath);
        }

        $this->assertSame('Alice', $newParty['primary_first_name']);
        $this->assertSame('Bob', $newParty['partner_first_name']);
        $this->assertSame('Charlie', $newParty['additional_guests'][0]['first_name']);
        $this->assertSame('Child', $newParty['additional_guests'][0]['type']);
        $this->assertSame('Anna', $oldParty['primary_first_name']);
        $this->assertSame('David', $oldParty['partner_first_name']);
        $this->assertSame('Laura', $oldParty['additional_guests'][0]['first_name']);
        $this->assertSame('Guest', $oldParty['additional_guests'][0]['type']);
    }

    protected function createProject(): Project
    {
        return Project::query()->create([
            'name' => 'Guest form wedding',
            'last_name' => 'Client',
        ]);
    }

    protected function actingAsAdmin(): void
    {
        $role = Role::query()->firstOrCreate(['name' => Role::ADMIN]);

        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
    }

    protected function temporaryXlsxPath(): string
    {
        return tempnam(sys_get_temp_dir(), 'guest-import-');
    }

    protected function writeGuestSpreadsheet(array $rows): string
    {
        $path = $this->temporaryXlsxPath();
        $writer = new Writer;
        $writer->openToFile($path);

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return $path;
    }

    protected function readSpreadsheetRows(string $path): array
    {
        $reader = new Reader;
        $reader->open($path);
        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }

            break;
        }

        $reader->close();

        return $rows;
    }

    protected function invokeProtected(object $object, string $method, array $arguments = []): mixed
    {
        return (new ReflectionMethod($object, $method))->invokeArgs($object, $arguments);
    }
}
