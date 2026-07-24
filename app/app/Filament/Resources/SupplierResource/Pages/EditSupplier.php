<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use Filament\Resources\Pages\EditRecord;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected array $otherCategoryIds = [];

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['other_category_ids'] = $this->getRecord()->otherCategoryIds();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->otherCategoryIds = $data['other_category_ids'] ?? [];
        unset($data['other_category_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->getRecord()->syncCategoriesFromMainAndOther($this->otherCategoryIds);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
