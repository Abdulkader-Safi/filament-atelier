<?php

declare(strict_types=1);

namespace Safi\Atelier\Filament\Resources\PageResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Safi\Atelier\Filament\Resources\PageResource;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New page'),
        ];
    }
}
