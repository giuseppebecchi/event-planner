<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('rsvp_configuration')->nullable()->after('logistics_notes');
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->json('rsvp_response')->nullable()->after('thank_you_sent');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn('rsvp_response');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('rsvp_configuration');
        });
    }
};
