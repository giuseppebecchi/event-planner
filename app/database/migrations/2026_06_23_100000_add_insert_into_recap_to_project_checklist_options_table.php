<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_checklist_options', function (Blueprint $table) {
            $table->boolean('insert_into_recap')->default(false)->after('to_be_filled');
        });
    }

    public function down(): void
    {
        Schema::table('project_checklist_options', function (Blueprint $table) {
            $table->dropColumn('insert_into_recap');
        });
    }
};
