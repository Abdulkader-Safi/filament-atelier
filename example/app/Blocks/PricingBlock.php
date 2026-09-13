<?php

declare(strict_types=1);

namespace App\Blocks;

use Filament\Forms\Components\TextInput;
use Safi\Atelier\Blocks\BaseBlock;

/**
 * The pricing table on a service's own page.
 *
 * It has no pricing fields of its own: it reads the tiers off the page it is
 * rendered on, which is what `$page` in a block view is for. Edit the tiers
 * in Services, and every page carrying this block follows.
 */
class PricingBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'pricing';
    }

    public static function label(): string
    {
        return 'Pricing table';
    }

    public static function icon(): string
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function category(): string
    {
        return 'Selling';
    }

    public static function supports(): array
    {
        return ['background', 'padding'];
    }

    public static function translatable(): array
    {
        return ['heading', 'subheading', 'note'];
    }

    public static function defaults(): array
    {
        return [
            'heading' => ['en' => 'Pick a package'],
            'subheading' => ['en' => 'Every visit includes supplies, insurance and a vetted team.'],
            'note' => ['en' => 'Prices are per visit unless the tier says otherwise. VAT included.'],
        ];
    }

    public static function view(): string
    {
        return 'blocks.pricing';
    }

    public function schema(): array
    {
        return [
            TextInput::make('heading')->live(debounce: 400),
            TextInput::make('subheading')->live(debounce: 400),
            TextInput::make('note')
                ->label('Small print')
                ->live(debounce: 400),
        ];
    }
}
