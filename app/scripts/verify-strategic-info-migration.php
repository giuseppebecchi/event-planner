<?php

// Read-only database audit. Run snapshot before the migration, verify immediately after it.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    $mode = $argv[1] ?? '';
    $path = $argv[2] ?? storage_path('app/private/strategic-info-migration.json');
    if ($mode === 'snapshot') {
        $expected = [];
        foreach (DB::table('project_strategic_infos')->orderBy('id')->get() as $row) {
            $supplierIds = DB::table('category_budget_suppliers')->where('project_id', $row->project_id)
                ->where('category_id', $row->category_id)->where('proposal_status', 'confirmed')
                ->whereNotNull('supplier_id')->orderBy('supplier_id')->distinct()->pluck('supplier_id')->all();
            foreach ($supplierIds ?: [null] as $supplierId) {
                $expected[] = [
                    'original_id' => $row->id, 'project_id' => $row->project_id,
                    'strategic_info_id' => $row->strategic_info_id, 'category_id' => $row->category_id,
                    'supplier_id' => $supplierId, 'title' => $row->title, 'content' => $row->content,
                    'image_paths' => json_decode($row->image_paths ?? 'null', true),
                    'status' => $row->status, 'completed_at' => $row->completed_at,
                    'created_at' => $row->created_at, 'updated_at' => $row->updated_at,
                ];
            }
        }
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }
        file_put_contents($path, json_encode($expected, JSON_THROW_ON_ERROR), LOCK_EX);
        chmod($path, 0600);
        echo 'SNAPSHOT_OK expected_records='.count($expected).' sha256='.hash_file('sha256', $path).PHP_EOL;
    } elseif ($mode === 'verify') {
        $expected = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        if (Schema::hasColumn('project_strategic_infos', 'content')) {
            throw new RuntimeException('Legacy content columns still exist.');
        }
        $actual = DB::table('project_strategic_infos as p')->join('project_strategic_info_details as d', 'd.project_strategic_info_id', '=', 'p.id')
            ->select('p.*', 'd.content', 'd.image_paths', 'd.status', 'd.completed_at', 'd.day_key', 'd.created_at as detail_created_at', 'd.updated_at as detail_updated_at')->get();
        if ($actual->count() !== count($expected) || DB::table('project_strategic_infos')->count() !== count($expected)
            || DB::table('project_strategic_info_details')->count() !== count($expected)) {
            throw new RuntimeException('Record counts do not match the migration snapshot.');
        }
        foreach ($expected as $before) {
            $matches = $actual->filter(fn ($row): bool => (int) $row->project_id === (int) $before['project_id']
                && ($before['strategic_info_id'] === null ? (int) $row->id === (int) $before['original_id'] : (int) $row->strategic_info_id === (int) $before['strategic_info_id'])
                && $row->supplier_id === $before['supplier_id']);
            if ($matches->count() !== 1) {
                throw new RuntimeException('Missing or duplicate association for original record '.$before['original_id']);
            }
            $after = $matches->first();
            foreach (['category_id', 'title', 'content', 'status', 'completed_at', 'created_at', 'updated_at'] as $field) {
                if ($after->{$field} !== $before[$field]) {
                    throw new RuntimeException('Changed '.$field.' for original record '.$before['original_id']);
                }
            }
            if ($after->day_key !== 'common' || $after->separate_by_day
                || json_decode($after->image_paths ?? 'null', true) !== $before['image_paths']
                || $after->detail_created_at !== $before['created_at'] || $after->detail_updated_at !== $before['updated_at']) {
                throw new RuntimeException('Changed detail data for original record '.$before['original_id']);
            }
        }
        echo 'MIGRATION_VERIFIED records='.count($expected).' all_content_images_statuses_and_associations_preserved'.PHP_EOL;
    } else {
        throw new RuntimeException('Usage: php scripts/verify-strategic-info-migration.php snapshot|verify [private_snapshot_path]');
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'AUDIT_FAILED: '.$exception->getMessage().PHP_EOL);
    exit(1);
}
