<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Checklist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/checklists.json');
        $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $categories = Category::query()->get();

        foreach ($payload as $row) {
            $categoryLabel = $row['category'] ?? null;
            $categoryId = null;

            if ($categoryLabel) {
                $needle = mb_strtolower(trim((string) $categoryLabel));
                $categoryId = $categories
                    ->first(function (Category $category) use ($needle): bool {
                        return in_array($needle, [
                            mb_strtolower(trim((string) $category->label)),
                            mb_strtolower(trim((string) $category->label_it)),
                        ], true);
                    })?->id;
            }

            $options = collect($row['options'] ?? [])
                ->map(function (array $option): array {
                    return [
                        'order' => (int) ($option['order'] ?? 0),
                        'title' => (string) ($option['title'] ?? ''),
                        'default' => (bool) ($option['default'] ?? false),
                        'anticipation' => $option['default'] ? ($option['anticipation'] ?? null) : null,
                        'assigned_to' => Arr::get($option, 'assigned_to', 'none'),
                    ];
                })
                ->sortBy('order')
                ->values()
                ->all();

            Checklist::query()->updateOrCreate(
                ['title' => (string) $row['title']],
                [
                    'category_id' => $categoryId,
                    'options' => $options,
                ]
            );
        }
    }
}
