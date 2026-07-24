<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected array $otherCategoryIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->otherCategoryIds = $data['other_category_ids'] ?? [];
        unset($data['other_category_ids']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->getRecord()->syncCategoriesFromMainAndOther($this->otherCategoryIds);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
