<?php

declare(strict_types=1);

namespace App\Blocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Safi\Atelier\Blocks\BaseBlock;

/** Two to four short selling points, each with a Heroicon. */
class FeaturesBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'features';
    }

    public static function label(): string
    {
        return 'Selling points';
    }

    public static function icon(): string
    {
        return 'heroicon-o-squares-plus';
    }

    public static function category(): string
    {
        return 'Sections';
    }

    public static function supports(): array
    {
        return ['background', 'padding'];
    }

    /** The whole repeater is per locale, so both languages keep their own list. */
    public static function translatable(): array
    {
        return ['heading', 'subheading', 'items'];
    }

    public static function defaults(): array
    {
        return [
            'heading' => ['en' => 'Why people stay with us'],
            'columns' => '3',
            'items' => ['en' => [
                ['icon' => 'heroicon-o-currency-dollar', 'title' => 'Fixed prices', 'body' => 'The price on the page is the price on the invoice.'],
                ['icon' => 'heroicon-o-shield-check', 'title' => 'Vetted and insured', 'body' => 'Police-checked, trained and covered.'],
                ['icon' => 'heroicon-o-user-group', 'title' => 'The same team', 'body' => 'They learn your home. You stop giving instructions.'],
            ]],
        ];
    }

    public static function view(): string
    {
        return 'blocks.features';
    }

    public function schema(): array
    {
        return [
            TextInput::make('heading')->label('Heading')->live(debounce: 400),
            Textarea::make('subheading')->label('Sentence below')->rows(2)->live(debounce: 400),
            Select::make('columns')
                ->label('Columns')
                ->options(['2' => 'Two', '3' => 'Three', '4' => 'Four'])
                ->default('3')
                ->native(false)
                ->live(),
            Repeater::make('items')
                ->label('Points')
                ->schema([
                    TextInput::make('icon')
                        ->label('Icon')
                        ->placeholder('heroicon-o-sparkles')
                        ->helperText('Any Heroicon name.'),
                    TextInput::make('title')->label('Title')->required(),
                    Textarea::make('body')->label('Text')->rows(2),
                ])
                ->reorderable()
                ->collapsed()
                ->itemLabel(fn (array $state) => $state['title'] ?? 'Point')
                ->live(),
        ];
    }
}
