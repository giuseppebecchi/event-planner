<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dateTime('proposal_sent_at')->nullable()->after('budget_wedding_planner_special_packages');
            $table->string('proposal_response_status')->nullable()->after('proposal_sent_at');
            $table->dateTime('proposal_response_at')->nullable()->after('proposal_response_status');
            $table->json('proposal_notes_log')->nullable()->after('proposal_response_at');
            $table->dateTime('contract_sent_at')->nullable()->after('proposal_notes_log');
            $table->dateTime('contract_received_at')->nullable()->after('contract_sent_at');
            $table->foreignId('signed_contract_document_id')->nullable()->after('contract_received_at')->constrained('lead_documents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('signed_contract_document_id');
            $table->dropColumn([
                'proposal_sent_at',
                'proposal_response_status',
                'proposal_response_at',
                'proposal_notes_log',
                'contract_sent_at',
                'contract_received_at',
            ]);
        });
    }
};
