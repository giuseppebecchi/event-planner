<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('label_it');
            $table->boolean('main')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        DB::table('categories')->insert([
            ['label' => 'Wedding planner', 'label_it' => 'Wedding planner', 'main' => true, 'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Venue', 'label_it' => 'Location', 'main' => true, 'order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Celebrant', 'label_it' => 'Celebrante', 'main' => true, 'order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Catering', 'label_it' => 'Catering', 'main' => true, 'order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Music / DJ', 'label_it' => 'Musica / DJ', 'main' => true, 'order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Photography', 'label_it' => 'Fotografia', 'main' => true, 'order' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Video', 'label_it' => 'Video', 'main' => true, 'order' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Make-up and hair', 'label_it' => 'Make-up e hair', 'main' => true, 'order' => 8, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Flowers', 'label_it' => 'Fiori', 'main' => true, 'order' => 9, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Lighting', 'label_it' => 'Luci', 'main' => true, 'order' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Printing / wedding stationery', 'label_it' => 'Stampe/wedding stationery', 'main' => true, 'order' => 11, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Transport', 'label_it' => 'Trasporti', 'main' => true, 'order' => 12, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'SIAE', 'label_it' => 'SIAE', 'main' => true, 'order' => 13, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Content creator', 'label_it' => 'Content creator', 'main' => true, 'order' => 14, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Cakes', 'label_it' => 'Torte', 'main' => true, 'order' => 15, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Bar', 'label_it' => 'Bar', 'main' => true, 'order' => 16, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Babysitters', 'label_it' => 'Baby sitters', 'main' => true, 'order' => 17, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Live painters', 'label_it' => 'Live painters', 'main' => true, 'order' => 18, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Rentals', 'label_it' => 'Noleggi', 'main' => true, 'order' => 19, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Fireworks', 'label_it' => 'Fuochi d’artificio', 'main' => true, 'order' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Other services', 'label_it' => 'Altri servizi', 'main' => false, 'order' => 21, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
