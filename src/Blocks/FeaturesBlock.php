<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class FeaturesBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'features';
    }

    public static function supports(): array
    {
        return ['background', 'padding'];
    }

    public static function icon(): string
    {
        return 'heroicon-o-squares-plus';
    }

    public static function translatable(): array
    {
        return ['heading', 'subheading', 'items'];
    }

    public static function defaults(): array
    {
        return [
            'heading' => ['en' => 'What you get'],
            'columns' => '3',
            'items' => ['en' => [
                ['title' => 'First thing', 'body' => 'A sentence about it.'],
                ['title' => 'Second thing', 'body' => 'A sentence about it.'],
                ['title' => 'Third thing', 'body' => 'A sentence about it.'],
            ]],
        ];
    }

    public function schema(): array
    {
        return [
            TextInput::make('heading')->label('Heading')->live(debounce: 400),
            Textarea::make('subheading')->label('Subheading')->rows(2)->live(debounce: 400),
            Select::make('columns')
                ->label('Columns')
                ->options(['2' => 'Two', '3' => 'Three', '4' => 'Four'])
                ->default('3')
                ->live(),
            Repeater::make('items')
                ->label('Features')
                ->schema([
                    TextInput::make('icon')
                        ->label('Icon')
                        ->placeholder('heroicon-o-bolt')
                        ->helperText('Any Heroicon name.'),
                    TextInput::make('title')->label('Title')->required(),
                    Textarea::make('body')->label('Text')->rows(2),
                ])
                ->reorderable()
                ->collapsed()
                ->itemLabel(fn (array $state) => $state['title'] ?? 'Feature')
                ->live(),
        ];
    }
}
