<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use App\Models\Supplier;
use Filament\Resources\Pages\EditRecord;

class EditLocation extends EditRecord
{
    protected static string $resource = LocationResource::class;

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

        $data['category_id'] = Supplier::LOCATION_CATEGORY_ID;

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
