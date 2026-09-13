<?php

declare(strict_types=1);

namespace Safi\Atelier\Filament\Resources\PageResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;
use Safi\Atelier\BlockRegistry;
use Safi\Atelier\Filament\Resources\PageResource;
use Safi\Atelier\Filament\Resources\PageResource\Concerns\HandlesPageSlugs;
use Safi\Atelier\Models\Page;

/**
 * One class, several sidebar entries. Which type it is listing comes from the
 * resource configuration Filament set from the route, so everything here asks
 * the resource rather than assuming Pages.
 */
class ListPages extends ListRecords
{
    use HandlesPageSlugs;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        /** @var class-string<PageResource> $resource */
        $resource = static::getResource();

        return [
            CreateAction::make()
                ->label('New '.Str::lower($resource::getLabel() ?? 'page'))
                ->mutateDataUsing(function (array $data) use ($resource) {
                    $data = $this->pullSlugs($data);

                    $data['type'] = $resource::typeKey();

                    // A type's starter sections, seeded into the draft so the
                    // builder opens on a page rather than on an empty canvas.
                    $type = $resource::pageType();
                    $template = $type ? $type::template() : [];

                    if ($template !== []) {
                        $data['draft_content'] = app(BlockRegistry::class)->tree($template);
                    }

                    return $data;
                })
                ->after(fn (Page $record) => $this->applySlugs($record, $this->prefixesFor($record))),
        ];
    }
}
