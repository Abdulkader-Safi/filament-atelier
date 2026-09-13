<?php

declare(strict_types=1);

namespace App\Blocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Safi\Atelier\Blocks\BaseBlock;

/** What clients said, in their words. */
class TestimonialsBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'testimonials';
    }

    public static function label(): string
    {
        return 'Testimonials';
    }

    public static function icon(): string
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    public static function category(): string
    {
        return 'Sections';
    }

    public static function supports(): array
    {
        return ['background', 'padding'];
    }

    public static function translatable(): array
    {
        return ['heading', 'items'];
    }

    public static function defaults(): array
    {
        return [
            'heading' => ['en' => 'What clients say'],
            'items' => ['en' => [
                ['quote' => 'They turned up when they said they would.', 'name' => 'A client', 'role' => 'Marina'],
            ]],
        ];
    }

    public static function view(): string
    {
        return 'blocks.testimonials';
    }

    public function schema(): array
    {
        return [
            TextInput::make('heading')->label('Heading')->live(debounce: 400),
            Repeater::make('items')
                ->label('Quotes')
                ->schema([
                    Textarea::make('quote')->label('Quote')->rows(3)->required(),
                    TextInput::make('name')->label('Who said it'),
                    TextInput::make('role')->label('Where they are'),
                ])
                ->reorderable()
                ->collapsed()
                ->itemLabel(fn (array $state) => $state['name'] ?? 'Quote')
                ->live(),
        ];
    }
}
