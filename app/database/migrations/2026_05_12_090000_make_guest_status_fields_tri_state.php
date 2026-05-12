<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->statusColumns() as $column) {
            DB::statement("ALTER TABLE guests MODIFY {$column} TINYINT NOT NULL DEFAULT 0");
        }
    }

    public function down(): void
    {
        foreach ($this->statusColumns() as $column) {
            DB::statement("ALTER TABLE guests MODIFY {$column} TINYINT(1) NOT NULL DEFAULT 0");
        }
    }

    private function statusColumns(): array
    {
        return [
            'invite_sent',
            'ceremony',
            'reception',
            'out_of_town',
            'gift_received',
            'thank_you_sent',
        ];
    }
};
