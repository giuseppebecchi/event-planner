<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use App\Models\Supplier;
use Filament\Resources\Pages\CreateRecord;

class CreateLocation extends CreateRecord
{
    protected static string $resource = LocationResource::class;

    protected array $otherCategoryIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->otherCategoryIds = $data['other_category_ids'] ?? [];
        unset($data['other_category_ids']);

        $data['category_id'] = Supplier::LOCATION_CATEGORY_ID;

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
