<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_supplier', function (Blueprint $table): void {
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['category_id', 'supplier_id']);
        });

        DB::table('suppliers')
            ->whereNotNull('category_id')
            ->orderBy('id')
            ->select(['id', 'category_id'])
            ->chunk(500, function ($suppliers): void {
                $now = now();

                DB::table('category_supplier')->insertOrIgnore(
                    $suppliers
                        ->map(fn ($supplier): array => [
                            'category_id' => $supplier->category_id,
                            'supplier_id' => $supplier->id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])
                        ->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_supplier');
    }
};
