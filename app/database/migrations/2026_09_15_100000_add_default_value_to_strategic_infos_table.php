<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEFAULT_VALUES = [
        'Mise en place' => '<ul><li><p><strong>Chairs:</strong></p></li><li><p><strong>Tablecloths:</strong></p></li><li><p><strong>Tables:</strong></p></li><li><p><strong>Charger plates:</strong></p></li><li><p><strong>Plates:</strong></p></li><li><p><strong>Glasses:</strong></p></li><li><p><strong>Cutlery:</strong></p></li><li><p><strong>Napkins:</strong></p></li></ul>',
        'Menu' => '<h2>Aperitif</h2><p><br></p><h2>Dinner</h2><p><br></p><h2>Wedding cake</h2><p><br></p><h2>After dinner</h2><p><br></p>',
        'Playlist' => '<ul><li><p><strong>Ceremony entrance:</strong></p></li><li><p><strong>Ceremony exit:</strong></p></li><li><p><strong>Dinner entrance:</strong></p></li><li><p><strong>Cake cutting:</strong></p></li><li><p><strong>First dance:</strong></p></li><li><p><strong>Parent dances:</strong></p></li><li><p><strong>Bouquet toss:</strong></p></li></ul>',
    ];

    public function up(): void
    {
        Schema::table('strategic_infos', function (Blueprint $table): void {
            $table->longText('default_value')->nullable()->after('title');
        });

        foreach (self::DEFAULT_VALUES as $title => $defaultValue) {
            DB::table('strategic_infos')
                ->where('title', $title)
                ->update([
                    'default_value' => $defaultValue,
                    'updated_at' => now(),
                ]);

            DB::table('project_strategic_infos')
                ->where('title', $title)
                ->where('status', 'draft')
                ->where(function ($query): void {
                    $query->whereNull('content')->orWhere('content', '');
                })
                ->where(function ($query): void {
                    $query->whereNull('image_paths')->orWhere('image_paths', '[]');
                })
                ->update([
                    'content' => $defaultValue,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        foreach (self::DEFAULT_VALUES as $title => $defaultValue) {
            DB::table('project_strategic_infos')
                ->where('title', $title)
                ->where('status', 'draft')
                ->where('content', $defaultValue)
                ->where(function ($query): void {
                    $query->whereNull('image_paths')->orWhere('image_paths', '[]');
                })
                ->update([
                    'content' => null,
                    'updated_at' => now(),
                ]);
        }

        Schema::table('strategic_infos', function (Blueprint $table): void {
            $table->dropColumn('default_value');
        });
    }
};
