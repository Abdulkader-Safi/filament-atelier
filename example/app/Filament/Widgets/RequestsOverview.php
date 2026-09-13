<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Enquiry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The dashboard answer to "what sold". Won requests only for the money,
 * because everything else is a conversation, not a sale.
 */
class RequestsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected function getStats(): array
    {
        $services = Enquiry::query()->ofKind('service');
        $products = Enquiry::query()->ofKind('product');

        $won = (float) Enquiry::query()->won()->sum('total');
        $open = Enquiry::query()->where('status', 'new')->count();

        return [
            Stat::make('Service requests', (string) $services->count())
                ->description((clone $services)->won()->count().' won')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('primary'),

            Stat::make('Product orders', (string) $products->count())
                ->description((clone $products)->won()->count().' won')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),

            Stat::make('Won', 'AED '.number_format($won))
                ->description($open.' still unanswered')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($open > 0 ? 'warning' : 'success'),
        ];
    }
}
