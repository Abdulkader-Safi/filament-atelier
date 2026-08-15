<?php

declare(strict_types=1);

namespace Safi\Atelier\Filament\Resources\PageResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Safi\Atelier\Filament\Resources\PageResource;
use Safi\Atelier\Filament\Resources\PageResource\Concerns\HandlesPageSlugs;
use Safi\Atelier\Models\Page;

class ListPages extends ListRecords
{
    use HandlesPageSlugs;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New page')
                ->mutateDataUsing(fn (array $data) => $this->pullSlugs($data))
                ->after(fn (Page $record) => $this->applySlugs($record)),
        ];
    }
}
