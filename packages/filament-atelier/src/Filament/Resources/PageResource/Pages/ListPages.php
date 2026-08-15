<?php

declare(strict_types=1);

namespace Safi\Atelier\Filament\Resources\PageResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Safi\Atelier\Filament\Pages\PageEditor;
use Safi\Atelier\Filament\Resources\PageResource;
use Safi\Atelier\Models\Page;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New page')
                // Straight into the editor. Creating a page and then hunting
                // for where to build it is a pointless extra step.
                ->successRedirectUrl(fn (Page $record) => PageEditor::getUrl(['record' => $record->getKey()])),
        ];
    }
}
