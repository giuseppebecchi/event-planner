<?php

namespace App\Filament\Resources\ProposalTemplateResource\Pages;

use App\Filament\Concerns\EditsProposalElements;
use App\Filament\Resources\ProposalTemplateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ViewProposalTemplate extends Page
{
    use EditsProposalElements;
    use InteractsWithRecord;

    protected static string $resource = ProposalTemplateResource::class;

    protected string $view = 'filament.proposals.template';

    public function mount(int|string $record): void
    {
        abort_unless(ProposalTemplateResource::canViewAny(), 403);
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return $this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('settings')->label('Template settings')->url(ProposalTemplateResource::getUrl('edit', ['record' => $this->getRecord()])),
            Action::make('duplicate')->label('Duplicate template')->requiresConfirmation()->action(function (): void {
                $copy = $this->getRecord()->replicate();
                $copy->name = Str::limit($copy->name, 248, '').' (copy)';
                $copy->slug = Str::limit($copy->slug, 240, '').'-'.Str::lower(Str::random(8));
                $copy->save();
                $this->redirect(ProposalTemplateResource::getUrl('view', ['record' => $copy]));
            }),
            Action::make('download')->label('Download PDF')->icon('heroicon-o-arrow-down-tray')
                ->url(route('admin.proposal-templates.pdf', $this->getRecord()))->openUrlInNewTab(),
        ];
    }

    protected function editableProposalConfiguration(): array
    {
        return $this->getRecord()->configuration;
    }

    protected function saveProposalElement(string $key, string $value): void
    {
        $this->getRecord()->refresh();
        $config = $this->getRecord()->configuration;
        Arr::set($config, $key, $value);
        $this->getRecord()->update(['configuration' => $config]);
    }
}
