<?php

namespace Database\Seeders;

use App\Models\Config;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ConfigSeeder extends Seeder
{
    public function run(): void
    {
        $source = public_path('images/configs/contract-signature.png');
        $target = 'configs/contract-signature.png';

        if (File::exists($source) && ! Storage::disk('public')->exists($target)) {
            Storage::disk('public')->put($target, File::get($source));
        }

        Config::query()->updateOrCreate(
            ['slug' => 'contract-signature'],
            [
                'label' => 'Contract signature',
                'type' => Config::TYPE_IMAGE,
                'text' => null,
                'img' => $target,
            ],
        );
    }
}
