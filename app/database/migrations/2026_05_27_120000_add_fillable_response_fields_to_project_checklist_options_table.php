<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_checklist_options', function (Blueprint $table) {
            $table->boolean('to_be_filled')->default(false)->after('default');
            $table->text('response')->nullable()->after('details');
        });
    }

    public function down(): void
    {
        Schema::table('project_checklist_options', function (Blueprint $table) {
            $table->dropColumn(['to_be_filled', 'response']);
        });
    }
};
