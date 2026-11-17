<x-filament-panels::page>
    <p style="color:#6c655d">Edit the text or use the pencil on a photo to replace it. Changes affect this template and future proposals only. Preview dates and prices are examples.</p>
    @include('filament.proposals.preview', ['previewUrl' => route('admin.proposal-templates.preview', $this->getRecord())])
    <x-filament-actions::modals />
</x-filament-panels::page>
