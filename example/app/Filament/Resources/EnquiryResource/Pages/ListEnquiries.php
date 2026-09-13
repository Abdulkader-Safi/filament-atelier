<?php

declare(strict_types=1);

namespace App\Filament\Resources\EnquiryResource\Pages;

use App\Filament\Resources\EnquiryResource;
use App\Models\Enquiry;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListEnquiries extends ListRecords
{
    protected static string $resource = EnquiryResource::class;

    /** Services and products side by side, and one tab for what is still unanswered. */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(Enquiry::query()->count()),

            'services' => Tab::make('Services')
                ->icon('heroicon-m-wrench-screwdriver')
                ->badge(Enquiry::query()->ofKind('service')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofKind('service')),

            'products' => Tab::make('Products')
                ->icon('heroicon-m-cube')
                ->badge(Enquiry::query()->ofKind('product')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofKind('product')),

            'new' => Tab::make('Unanswered')
                ->badge(Enquiry::query()->where('status', 'new')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'new')),

            'won' => Tab::make('Won')
                ->badge(Enquiry::query()->won()->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->won()),
        ];
    }
}
