<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_checklist_options', function (Blueprint $table) {
            $table->boolean('completed')->default(false)->after('enabled');
            $table->dateTime('completed_at')->nullable()->after('completed');
            $table->text('details')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('project_checklist_options', function (Blueprint $table) {
            $table->dropColumn(['completed', 'completed_at', 'details']);
        });
    }
};
