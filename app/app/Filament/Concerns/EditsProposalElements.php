<?php

namespace App\Filament\Concerns;

use App\Support\ProposalConfiguration;
use App\Support\RichEditorHtmlNormalizer;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

trait EditsProposalElements
{
    public int $proposalPreviewRevision = 0;

    abstract protected function editableProposalConfiguration(): array;

    abstract protected function saveProposalElement(string $key, string $value): void;

    public function editProposalElementAction(): Action
    {
        return Action::make('editProposalElement')
            ->modalWidth(fn (array $arguments): string => str_starts_with((string) ($arguments['key'] ?? ''), 'images.') ? 'sm' : '5xl')
            ->modalHeading(function (array $arguments): string {
                return $this->proposalElementLabel((string) ($arguments['key'] ?? ''));
            })
            ->fillForm(function (array $arguments): array {
                $key = (string) ($arguments['key'] ?? '');
                $this->proposalElementLabel($key);

                return ['content' => Arr::get($this->editableProposalConfiguration(), $key, '')];
            })
            ->form(function (array $arguments): array {
                $key = (string) ($arguments['key'] ?? '');
                $this->proposalElementLabel($key);
                if (str_starts_with($key, 'images.')) {
                    return [
                        Placeholder::make('current_image')->content(function () use ($key): HtmlString {
                            $path = ProposalConfiguration::imagePath(Arr::get($this->editableProposalConfiguration(), $key));

                            return new HtmlString($path
                                ? '<div style="position:relative;width:min(100%,300px);margin-inline:auto;aspect-ratio:1;background:#f7f5f1;border:1px solid #e8e3dc;border-radius:12px;overflow:hidden">'
                                    .'<img alt="Current image" style="position:absolute;inset:0;display:block;width:100%;height:100%;object-fit:contain" src="data:'.e(mime_content_type($path)).';base64,'.base64_encode(file_get_contents($path)).'">'
                                    .'</div>'
                                : 'No image');
                        }),
                        FileUpload::make('image')->label('Replacement image')->disk('public')
                            ->directory('proposal-templates/images')->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(10240)->required(),
                    ];
                }

                return [RichEditor::make('content')->label('Text')->required()->maxLength(30000)
                    ->helperText('Variables: [MONTH], [YYYY], [REGION], [VENUE], [COUPLE]. The subtitle uses the event date; template previews show September 2027.')
                    ->toolbarButtons(['bold', 'italic', 'underline', 'h2', 'h3', 'bulletList', 'orderedList', 'undo', 'redo'])];
            })
            ->action(function (array $data, array $arguments): void {
                abort_unless(auth()->check() && ! auth()->user()->isCustomer(), 403);
                $key = (string) ($arguments['key'] ?? '');
                $this->proposalElementLabel($key);
                if (str_starts_with($key, 'images.')) {
                    $value = $data['image'] ?? null;
                    if (is_array($value)) {
                        $value = collect($value)->first();
                    }
                    abort_unless(is_string($value) && str_starts_with($value, 'proposal-templates/images/') && ProposalConfiguration::imagePath($value), 422);
                } else {
                    $value = RichEditorHtmlNormalizer::normalizeListItems((string) $data['content']);
                }
                $this->saveProposalElement($key, $value);
                $this->proposalPreviewRevision++;
                Notification::make()->title('Proposal updated')->success()->send();
            });
    }

    protected function proposalElementLabel(string $key): string
    {
        abort_unless(auth()->check() && ! auth()->user()->isCustomer(), 403);
        if (str_starts_with($key, 'images.')) {
            $definition = ProposalConfiguration::imageFields()[substr($key, 7)] ?? null;
            abort_unless($definition, 404);

            return 'Page '.$definition[0].' — '.$definition[1];
        }
        $label = ProposalConfiguration::textFields()[$key] ?? null;
        abort_unless($label, 404);

        return $label;
    }
}
