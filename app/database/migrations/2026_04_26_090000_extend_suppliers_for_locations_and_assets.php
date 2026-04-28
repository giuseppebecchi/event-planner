<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('loc_locality')->nullable()->after('sdi_code');
            $table->string('loc_geo_area')->nullable();
            $table->decimal('loc_latitude', 10, 7)->nullable();
            $table->decimal('loc_longitude', 10, 7)->nullable();
            $table->decimal('loc_airport_distance_km', 6, 2)->nullable();
            $table->longText('loc_overview')->nullable();
            $table->string('loc_structure_type')->nullable();
            $table->string('loc_style')->nullable();
            $table->string('loc_website')->nullable();
            $table->unsignedInteger('loc_guest_max')->nullable();
            $table->unsignedInteger('loc_guest_indoor_max')->nullable();
            $table->unsignedInteger('loc_guest_outdoor_max')->nullable();
            $table->unsignedInteger('loc_guest_min')->nullable();
            $table->longText('loc_event_spaces')->nullable();
            $table->boolean('loc_has_garden')->default(false);
            $table->boolean('loc_has_indoor_hall')->default(false);
            $table->boolean('loc_has_ceremony_space')->default(false);
            $table->longText('loc_other_event_areas')->nullable();
            $table->boolean('loc_has_rooms')->default(false);
            $table->unsignedInteger('loc_room_count')->nullable();
            $table->unsignedInteger('loc_stay_guest_max')->nullable();
            $table->longText('loc_room_setup')->nullable();
            $table->boolean('loc_exclusive_use')->default(false);
            $table->unsignedInteger('loc_min_nights')->nullable();
            $table->longText('loc_stay_notes')->nullable();
            $table->string('loc_catering_type')->nullable();
            $table->boolean('loc_has_inhouse_catering')->default(false);
            $table->boolean('loc_allows_external_catering')->default(false);
            $table->longText('loc_exclusive_caterers')->nullable();
            $table->longText('loc_external_catering_rules')->nullable();
            $table->longText('loc_catering_notes')->nullable();
            $table->json('loc_ceremony_types')->nullable();
            $table->boolean('loc_allows_ceremony_on_site')->default(false);
            $table->longText('loc_ceremony_spaces')->nullable();
            $table->longText('loc_ceremony_rules')->nullable();
            $table->time('loc_music_end_time')->nullable();
            $table->boolean('loc_music_extension')->default(false);
            $table->longText('loc_sound_limits')->nullable();
            $table->longText('loc_music_rules')->nullable();
            $table->longText('loc_music_notes')->nullable();
            $table->boolean('loc_allows_fireworks')->default(false);
            $table->longText('loc_fireworks_rules')->nullable();
            $table->string('loc_fireworks_area')->nullable();
            $table->longText('loc_fireworks_permits')->nullable();
            $table->longText('loc_supplier_access')->nullable();
            $table->boolean('loc_has_parking')->default(false);
            $table->boolean('loc_accessible')->default(false);
            $table->longText('loc_protected_areas')->nullable();
            $table->longText('loc_setup_limits')->nullable();
            $table->longText('loc_setup_time_limits')->nullable();
            $table->longText('loc_other_limits')->nullable();
            $table->decimal('loc_rental_fee', 10, 2)->nullable();
            $table->string('loc_rental_mode')->nullable();
            $table->longText('loc_extra_costs')->nullable();
            $table->decimal('loc_booking_deposit', 10, 2)->nullable();
            $table->longText('loc_payment_terms')->nullable();
        });

        Schema::create('supplier_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('title');
            $table->string('document_type')->default('other');
            $table->string('file_path');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('image_path');
            $table->text('description')->nullable();
            $table->string('image_category')->default('other');
            $table->boolean('is_client_visible')->default(false);
            $table->timestamps();
        });

        if (Schema::hasColumn('suppliers', 'documents')) {
            $suppliersWithDocuments = DB::table('suppliers')
                ->select('id', 'documents')
                ->whereNotNull('documents')
                ->get();

            foreach ($suppliersWithDocuments as $supplier) {
                $documents = is_string($supplier->documents)
                    ? json_decode($supplier->documents, true)
                    : $supplier->documents;

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

                    DB::table('supplier_documents')->insert([
                        'supplier_id' => $supplier->id,
                        'title' => 'Imported document ' . ($index + 1),
                        'document_type' => 'other',
                        'file_path' => $filePath,
                        'description' => is_array($document) ? ($document['description'] ?? null) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropColumn('documents');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_images');
        Schema::dropIfExists('supplier_documents');

        Schema::table('suppliers', function (Blueprint $table) {
            $table->json('documents')->nullable();

            $table->dropColumn([
                'loc_locality',
                'loc_geo_area',
                'loc_latitude',
                'loc_longitude',
                'loc_airport_distance_km',
                'loc_overview',
                'loc_structure_type',
                'loc_style',
                'loc_website',
                'loc_guest_max',
                'loc_guest_indoor_max',
                'loc_guest_outdoor_max',
                'loc_guest_min',
                'loc_event_spaces',
                'loc_has_garden',
                'loc_has_indoor_hall',
                'loc_has_ceremony_space',
                'loc_other_event_areas',
                'loc_has_rooms',
                'loc_room_count',
                'loc_stay_guest_max',
                'loc_room_setup',
                'loc_exclusive_use',
                'loc_min_nights',
                'loc_stay_notes',
                'loc_catering_type',
                'loc_has_inhouse_catering',
                'loc_allows_external_catering',
                'loc_exclusive_caterers',
                'loc_external_catering_rules',
                'loc_catering_notes',
                'loc_ceremony_types',
                'loc_allows_ceremony_on_site',
                'loc_ceremony_spaces',
                'loc_ceremony_rules',
                'loc_music_end_time',
                'loc_music_extension',
                'loc_sound_limits',
                'loc_music_rules',
                'loc_music_notes',
                'loc_allows_fireworks',
                'loc_fireworks_rules',
                'loc_fireworks_area',
                'loc_fireworks_permits',
                'loc_supplier_access',
                'loc_has_parking',
                'loc_accessible',
                'loc_protected_areas',
                'loc_setup_limits',
                'loc_setup_time_limits',
                'loc_other_limits',
                'loc_rental_fee',
                'loc_rental_mode',
                'loc_extra_costs',
                'loc_booking_deposit',
                'loc_payment_terms',
            ]);
        });
    }
};
