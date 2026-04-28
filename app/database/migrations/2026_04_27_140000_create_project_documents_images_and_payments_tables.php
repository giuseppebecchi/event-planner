<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('category_budget_supplier_id')->nullable()->constrained('category_budget_suppliers')->nullOnDelete();
            $table->string('title');
            $table->string('document_type')->default('other');
            $table->string('type')->default('other');
            $table->string('file_path');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('project_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('image_path');
            $table->text('description')->nullable();
            $table->string('image_category')->default('other');
            $table->boolean('is_client_visible')->default(false);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('category_budget_supplier_id')->nullable()->constrained('category_budget_suppliers')->nullOnDelete();
            $table->string('reason');
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->nullable();
            $table->string('payment_status')->default('unpaid');
            $table->date('paid_at')->nullable();
            $table->string('invoice_reference')->nullable();
            $table->foreignId('payment_receipt_document_id')->nullable()->constrained('project_documents')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        if (Schema::hasColumn('projects', 'documents')) {
            $projectsWithDocuments = DB::table('projects')
                ->select('id', 'documents')
                ->whereNotNull('documents')
                ->get();

            foreach ($projectsWithDocuments as $project) {
                $documents = is_string($project->documents)
                    ? json_decode($project->documents, true)
                    : $project->documents;

                if (! is_array($documents)) {
                    continue;
                }

                foreach (array_values($documents) as $index => $document) {
                    $filePath = is_array($document)
                        ? ($document['file_path'] ?? $document['path'] ?? null)
                        : $document;

                    if (! is_string($filePath) || $filePath === '') {
                        continue;
                    }

                    DB::table('project_documents')->insert([
                        'project_id' => $project->id,
                        'supplier_id' => null,
                        'category_budget_supplier_id' => null,
                        'title' => 'Imported document ' . ($index + 1),
                        'document_type' => 'other',
                        'type' => 'other',
                        'file_path' => $filePath,
                        'description' => is_array($document) ? ($document['description'] ?? null) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('documents');
            });
        }

        if (Schema::hasColumn('category_budget_suppliers', 'attachments')) {
            $proposalsWithAttachments = DB::table('category_budget_suppliers')
                ->select('id', 'project_id', 'supplier_id', 'attachments')
                ->whereNotNull('attachments')
                ->get();

            foreach ($proposalsWithAttachments as $proposal) {
                $attachments = is_string($proposal->attachments)
                    ? json_decode($proposal->attachments, true)
                    : $proposal->attachments;

                if (! is_array($attachments) || ! $proposal->project_id) {
                    continue;
                }

                foreach (array_values($attachments) as $index => $attachment) {
                    $filePath = is_array($attachment)
                        ? ($attachment['file_path'] ?? $attachment['path'] ?? null)
                        : $attachment;

                    if (! is_string($filePath) || $filePath === '') {
                        continue;
                    }

                    DB::table('project_documents')->insert([
                        'project_id' => $proposal->project_id,
                        'supplier_id' => $proposal->supplier_id,
                        'category_budget_supplier_id' => $proposal->id,
                        'title' => 'Imported quote ' . ($index + 1),
                        'document_type' => 'quote',
                        'type' => 'quote',
                        'file_path' => $filePath,
                        'description' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Schema::table('category_budget_suppliers', function (Blueprint $table) {
                $table->dropColumn('attachments');
            });
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('documents')->nullable();
        });

        Schema::table('category_budget_suppliers', function (Blueprint $table) {
            $table->json('attachments')->nullable();
        });

        Schema::dropIfExists('payments');
        Schema::dropIfExists('project_images');
        Schema::dropIfExists('project_documents');
    }
};
