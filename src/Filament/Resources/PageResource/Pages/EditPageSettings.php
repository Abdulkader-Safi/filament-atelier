<?php

declare(strict_types=1);

namespace Safi\Atelier\Filament\Resources\PageResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Safi\Atelier\Filament\Pages\PageEditor;
use Safi\Atelier\Filament\Resources\PageResource;
use Safi\Atelier\Models\Page;

/**
 * Everything about the page that isn't its content: title, slugs, SEO.
 * The content itself lives in the builder, one button away.
 */
class EditPageSettings extends EditRecord
{
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

            DeleteAction::make(),
        ];
    }

    /** Slugs and SEO are separate storage, so load them into the form by hand. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Page $record */
        $record = $this->record;

        foreach (array_keys(config('atelier.locales')) as $locale) {
            $data['slugs'][$locale] = $record->slug($locale);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->slugsToSave = $data['slugs'] ?? [];
        unset($data['slugs']);

        return $data;
    }

    /** @var array<string, string|null> */
    protected array $slugsToSave = [];

    protected function afterSave(): void
    {
        /** @var Page $record */
        $record = $this->record;

        $record->setSlugs($this->slugsToSave);
    }
}
