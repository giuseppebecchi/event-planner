<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->longText('proposal_content')->nullable()->after('proposal_notes_log');
            $table->longText('contract_content')->nullable()->after('contract_received_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'proposal_content',
                'contract_content',
            ]);
        });
    }
};
