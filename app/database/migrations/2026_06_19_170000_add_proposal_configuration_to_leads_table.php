<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->longText('proposal_wedding_planning_service')->nullable()->after('proposal_content');
            $table->json('proposal_images_json_config')->nullable()->after('proposal_wedding_planning_service');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn([
                'proposal_wedding_planning_service',
                'proposal_images_json_config',
            ]);
        });
    }
};
