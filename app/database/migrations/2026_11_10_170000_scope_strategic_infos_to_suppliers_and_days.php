<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_strategic_infos', function (Blueprint $table): void {
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->restrictOnDelete();
            $table->boolean('separate_by_day')->default(false);
            $table->dropUnique('project_strategic_info_unique');
            $table->unique(['project_id', 'strategic_info_id', 'supplier_id'], 'project_strategic_supplier_unique');
        });

        Schema::create('project_strategic_info_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_strategic_info_id')->constrained('project_strategic_infos')->cascadeOnDelete();
            // "common" or an ISO date: also prevents duplicate common entries (nullable dates do not).
            $table->string('day_key', 10)->default('common');
            $table->longText('content')->nullable();
            $table->json('image_paths')->nullable();
            $table->string('status')->default('draft');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['project_strategic_info_id', 'day_key'], 'strategic_info_detail_day_unique');
        });

        DB::transaction(function (): void {
            foreach (DB::table('project_strategic_infos')->orderBy('id')->get() as $row) {
                $supplierIds = DB::table('category_budget_suppliers')
                    ->where('project_id', $row->project_id)
                    ->where('category_id', $row->category_id)
                    ->where('proposal_status', 'confirmed')
                    ->whereNotNull('supplier_id')
                    ->distinct()->orderBy('supplier_id')->pluck('supplier_id')->all();

                foreach ($supplierIds ?: [null] as $index => $supplierId) {
                    $id = $row->id;
                    if ($index === 0) {
                        DB::table('project_strategic_infos')->where('id', $id)->update(['supplier_id' => $supplierId]);
                    } else {
                        $copy = (array) $row;
                        unset($copy['id']);
                        $copy['supplier_id'] = $supplierId;
                        $id = DB::table('project_strategic_infos')->insertGetId($copy);
                    }
                    DB::table('project_strategic_info_details')->insert([
                        'project_strategic_info_id' => $id,
                        'day_key' => 'common',
                        'content' => $row->content,
                        'image_paths' => $row->image_paths,
                        'status' => $row->status,
                        'completed_at' => $row->completed_at,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);
                }
            }
        });

        Schema::table('project_strategic_infos', function (Blueprint $table): void {
            $table->dropColumn(['content', 'image_paths', 'status', 'completed_at']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('This data conversion cannot be reversed without losing supplier/day details. Restore the pre-deployment database backup together with the previous application version.');
    }
};
