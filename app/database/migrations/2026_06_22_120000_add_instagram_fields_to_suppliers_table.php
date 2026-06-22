<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('instagram_profile')->nullable()->after('price_range');
            $table->string('instagram_hashtag')->nullable()->after('instagram_profile');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'instagram_profile',
                'instagram_hashtag',
            ]);
        });
    }
};
