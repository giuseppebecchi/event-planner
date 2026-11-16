<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->foreignId('default_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->boolean('auto_confirm_default_supplier')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('default_supplier_id');
            $table->dropColumn('auto_confirm_default_supplier');
        });
    }
};
