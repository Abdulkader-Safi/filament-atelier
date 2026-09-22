<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

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

    public static function label(): string
    {
        return __('filament-atelier::atelier.blocks.features.label');
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
            TextInput::make('heading')->label(__('filament-atelier::atelier.blocks.features.fields.heading'))->live(debounce: 400),
            Textarea::make('subheading')->label(__('filament-atelier::atelier.blocks.features.fields.subheading'))->rows(2)->live(debounce: 400),
            Select::make('columns')
                ->label(__('filament-atelier::atelier.blocks.features.fields.columns'))
                ->options([
                    '2' => __('filament-atelier::atelier.blocks.features.options.columns.two'),
                    '3' => __('filament-atelier::atelier.blocks.features.options.columns.three'),
                    '4' => __('filament-atelier::atelier.blocks.features.options.columns.four'),
                ])
                ->default('3')
                ->live(),
            Repeater::make('items')
                ->label(__('filament-atelier::atelier.blocks.features.fields.items'))
                ->schema([
                    TextInput::make('icon')
                        ->label(__('filament-atelier::atelier.blocks.features.fields.icon'))
                        ->placeholder('heroicon-o-bolt')
                        ->helperText(__('filament-atelier::atelier.blocks.features.fields.icon_help')),
                    TextInput::make('title')->label(__('filament-atelier::atelier.blocks.features.fields.title'))->required(),
                    Textarea::make('body')->label(__('filament-atelier::atelier.blocks.features.fields.body'))->rows(2),
                ])
                ->reorderable()
                ->collapsed()
                ->itemLabel(fn (array $state) => $state['title'] ?? __('filament-atelier::atelier.blocks.features.fields.item_fallback'))
                ->live(),
        ];
    }
}
