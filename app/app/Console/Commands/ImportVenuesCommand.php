<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ImportVenuesCommand extends Command
{
    protected $signature = 'venues:import
        {path=database/data/venues-import.json : JSON file path, relative to base path unless absolute}
        {--dry-run : Show what would be imported without writing to the database}
        {--force : Overwrite existing non-empty fields}
        {--restore : Restore matching soft-deleted venues}';

    protected $description = 'Import venue suppliers from a normalized JSON dataset.';

    /** @var array<int, string> */
    protected array $ignoredPayloadKeys = [
        'import_source',
    ];

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->argument('path'));

        if (! is_file($path)) {
            $this->error("JSON file not found: {$path}");

            return self::FAILURE;
        }

        $payload = json_decode((string) file_get_contents($path), true);

        if (! is_array($payload)) {
            $this->error('Invalid JSON payload.');

            return self::FAILURE;
        }

        $venues = Arr::isAssoc($payload) ? ($payload['venues'] ?? []) : $payload;

        if (! is_array($venues)) {
            $this->error('The JSON payload must contain a venues array.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $restore = (bool) $this->option('restore');

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $restored = 0;
        $invalid = 0;
        $updatesPreview = [];

        DB::beginTransaction();

        try {
            foreach (array_values($venues) as $index => $venue) {
                if (! is_array($venue) || blank($venue['name'] ?? null)) {
                    $invalid++;
                    $this->warn('Skipping invalid venue at index ' . $index);
                    continue;
                }

                $data = $this->databasePayload($venue);
                $supplier = $this->findExistingVenue($venue);

                if (! $supplier) {
                    $created++;

                    if (! $dryRun) {
                        Supplier::query()
                            ->create($data)
                            ->syncCategoriesFromMainAndOther();
                    }

                    continue;
                }

                if ($supplier->trashed() && $restore) {
                    $restored++;

                    if (! $dryRun) {
                        $supplier->restore();
                    }
                }

                $changes = $this->changesFor($supplier, $data, $force);

                if ($changes === []) {
                    $unchanged++;
                    continue;
                }

                $updated++;

                if (count($updatesPreview) < 10) {
                    $updatesPreview[] = [
                        $supplier->id,
                        $supplier->name,
                        implode(', ', array_keys($changes)),
                    ];
                }

                if (! $dryRun) {
                    $supplier->forceFill($changes)->save();
                    $supplier->syncCategoriesFromMainAndOther();
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $exception) {
            DB::rollBack();
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Mode', 'Input rows', 'Created', 'Updated', 'Unchanged', 'Restored', 'Invalid'],
            [[
                $dryRun ? 'dry-run' : 'write',
                count($venues),
                $created,
                $updated,
                $unchanged,
                $restored,
                $invalid,
            ]]
        );

        if ($updatesPreview !== []) {
            $this->newLine();
            $this->info('First updated records preview:');
            $this->table(['ID', 'Venue', 'Fields'], $updatesPreview);
        }

        if ($dryRun) {
            $this->comment('Dry-run only: no database changes were written.');
        }

        return self::SUCCESS;
    }

    protected function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * @param array<string, mixed> $venue
     * @return array<string, mixed>
     */
    protected function databasePayload(array $venue): array
    {
        $data = Arr::except($venue, $this->ignoredPayloadKeys);
        $data['category_id'] = Supplier::LOCATION_CATEGORY_ID;

        return collect($data)
            ->reject(fn ($value): bool => $value === null || $value === '')
            ->all();
    }

    /**
     * @param array<string, mixed> $venue
     */
    protected function findExistingVenue(array $venue): ?Supplier
    {
        return Supplier::withTrashed()
            ->where('category_id', Supplier::LOCATION_CATEGORY_ID)
            ->where('name', (string) $venue['name'])
            ->where('loc_geo_area', $venue['loc_geo_area'] ?? null)
            ->where('loc_locality', $venue['loc_locality'] ?? null)
            ->first();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function changesFor(Supplier $supplier, array $data, bool $force): array
    {
        $changes = [];

        foreach ($data as $field => $value) {
            if ($field === 'category_id') {
                continue;
            }

            $current = $supplier->{$field};

            if (! $force && ! blank($current)) {
                continue;
            }

            if ($this->sameValue($current, $value)) {
                continue;
            }

            $changes[$field] = $value;
        }

        return $changes;
    }

    protected function sameValue(mixed $current, mixed $incoming): bool
    {
        if (is_array($current) || is_array($incoming)) {
            return json_encode($current ?: []) === json_encode($incoming ?: []);
        }

        return (string) ($current ?? '') === (string) ($incoming ?? '');
    }
}
