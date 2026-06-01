<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_tables', function (Blueprint $table): void {
            $table->unsignedTinyInteger('curve_count')->nullable()->after('seats_by_side_json');
            $table->string('curve_type')->nullable()->after('curve_count');
        });
    }

    public function down(): void
    {
        Schema::table('project_tables', function (Blueprint $table): void {
            $table->dropColumn(['curve_count', 'curve_type']);
        });
    }
};
