<?php

namespace App\Filament\Resources\ProjectResource\Pages;

class ManageProjectSupplier extends ManageProjectConfirmedSupplier
{
    public function mount(int|string $record, int|string $proposal): void
    {
        $this->mountSupplierWorkspace($record, null, $proposal);
    }
}
