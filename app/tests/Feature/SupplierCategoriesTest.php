<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SupplierCategoriesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_supplier_syncs_main_and_other_categories_to_pivot(): void
    {
        $mainCategory = Category::query()->create([
            'label' => 'Florals',
            'label_it' => 'Florals',
            'order' => 1,
        ]);
        $otherCategory = Category::query()->create([
            'label' => 'Decor',
            'label_it' => 'Decor',
            'order' => 2,
        ]);
        $duplicateCategory = Category::query()->create([
            'label' => 'Lighting',
            'label_it' => 'Lighting',
            'order' => 3,
        ]);

        $supplier = Supplier::query()->create([
            'name' => 'Studio Supplier',
            'category_id' => $mainCategory->id,
        ]);

        $supplier->syncCategoriesFromMainAndOther([
            $otherCategory->id,
            $mainCategory->id,
            $duplicateCategory->id,
            $duplicateCategory->id,
        ]);

        $this->assertSame([
            $mainCategory->id,
            $otherCategory->id,
            $duplicateCategory->id,
        ], $supplier->categories()->orderBy('categories.id')->pluck('categories.id')->all());
    }
}
