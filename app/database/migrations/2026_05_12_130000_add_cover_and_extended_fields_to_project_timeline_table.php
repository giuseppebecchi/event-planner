<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_timeline', function (Blueprint $table) {
            $table->boolean('cover_activity')->default(false)->after('is_surprise');
            $table->string('cover_activity_type')->nullable()->after('cover_activity');
            $table->boolean('has_extended_description')->default(false)->after('description');
            $table->longText('extended_description')->nullable()->after('has_extended_description');
        });
    }

    public function down(): void
    {
        Schema::table('project_timeline', function (Blueprint $table) {
            $table->dropColumn([
                'cover_activity',
                'cover_activity_type',
                'has_extended_description',
                'extended_description',
            ]);
        });
    }
};
