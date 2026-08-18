<?php

declare(strict_types=1);

namespace Safi\Atelier\Filament\Resources\PageResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Safi\Atelier\Filament\Pages\PageEditor;
use Safi\Atelier\Filament\Resources\PageResource;
use Safi\Atelier\Filament\Resources\PageResource\Concerns\HandlesPageSlugs;
use Safi\Atelier\Models\Page;

/**
 * Everything about the page that isn't its content: title, slugs, SEO.
 * The content itself lives in the builder, one button away.
 */
class EditPageSettings extends EditRecord
{
    use HandlesPageSlugs;

    protected static string $resource = PageResource::class;

    public function getTitle(): string
    {
        return $this->record->title;
    }

    protected function getHeaderActions(): array
    {
        /** @var Page $record */
        $record = $this->record;

        return [
            Action::make('build')
                ->label('Edit page content')
                ->icon('heroicon-m-paint-brush')
                ->url(fn () => PageEditor::getUrl(['record' => $record->getKey()]))
                ->openUrlInNewTab(),

            Action::make('view')
                ->label('View live')
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => $record->url(array_key_first(config('atelier.locales'))))
                ->openUrlInNewTab()
                ->visible(fn () => $record->isPublished()),

            Action::make('publish')
                ->label('Publish')
                ->icon('heroicon-m-rocket-launch')
                ->color('gray')
                ->action(fn () => $record->publish())
                ->visible(fn () => ! $record->isPublished() || $record->hasUnpublishedChanges()),

            Action::make('unpublish')
                ->label('Unpublish')
                ->icon('heroicon-m-eye-slash')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('The page will 404 on the public site. Its content is kept.')
                ->action(fn () => $record->unpublish())
                ->visible(fn () => $record->isPublished()),

            DeleteAction::make(),
        ];
    }

    /** Slugs are a separate table, so load them into the form by hand. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['slugs'] = $this->slugsForForm($this->record);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->pullSlugs($data);
    }

    protected function afterSave(): void
    {
        $this->applySlugs($this->record);
    }
}
