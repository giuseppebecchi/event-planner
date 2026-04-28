<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_supplier_communications')) {
            Schema::create('project_supplier_communications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->unsignedBigInteger('category_budget_supplier_id');
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->string('communication_type')->default('other');
                $table->string('direction')->default('outgoing');
                $table->dateTime('communication_at')->nullable();
                $table->string('subject')->nullable();
                $table->longText('message')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        $proposals = DB::table('category_budget_suppliers')
            ->select([
                'id',
                'project_id',
                'supplier_id',
                'requested_at',
                'request_text',
                'responded_at',
                'response_text',
                'planner_notes',
                'notes',
            ])
            ->get();

        foreach ($proposals as $proposal) {
            if ($proposal->project_id && ($proposal->requested_at || $proposal->request_text)
                && ! DB::table('project_supplier_communications')
                    ->where('category_budget_supplier_id', $proposal->id)
                    ->where('communication_type', 'quote_request')
                    ->exists()) {
                DB::table('project_supplier_communications')->insert([
                    'project_id' => $proposal->project_id,
                    'category_budget_supplier_id' => $proposal->id,
                    'supplier_id' => $proposal->supplier_id,
                    'communication_type' => 'quote_request',
                    'direction' => 'outgoing',
                    'communication_at' => $proposal->requested_at ?: now(),
                    'subject' => 'Quote request',
                    'message' => $proposal->request_text,
                    'notes' => $proposal->planner_notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($proposal->project_id && ($proposal->responded_at || $proposal->response_text)
                && ! DB::table('project_supplier_communications')
                    ->where('category_budget_supplier_id', $proposal->id)
                    ->where('communication_type', 'quote_response')
                    ->exists()) {
                DB::table('project_supplier_communications')->insert([
                    'project_id' => $proposal->project_id,
                    'category_budget_supplier_id' => $proposal->id,
                    'supplier_id' => $proposal->supplier_id,
                    'communication_type' => 'quote_response',
                    'direction' => 'incoming',
                    'communication_at' => $proposal->responded_at ?: now(),
                    'subject' => 'Quote response',
                    'message' => $proposal->response_text,
                    'notes' => $proposal->notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_supplier_communications');
    }
};
