<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Safi\Atelier\Media;

class TestimonialsBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'testimonials';
    }

    public static function supports(): array
    {
        return ['background', 'padding'];
    }

    public static function label(): string
    {
        return __('filament-atelier::atelier.blocks.testimonials.label');
    }

    public static function icon(): string
    {
        return 'heroicon-o-chat-bubble-bottom-center-text';
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
                ['quote' => 'They shipped it, and it works.', 'name' => 'A client', 'role' => 'Managing Director'],
            ]],
        ];
    }

    public function schema(): array
    {
        return [
            TextInput::make('heading')->label(__('filament-atelier::atelier.blocks.testimonials.fields.heading'))->live(debounce: 400),
            Repeater::make('items')
                ->label(__('filament-atelier::atelier.blocks.testimonials.fields.items'))
                ->schema([
                    Textarea::make('quote')->label(__('filament-atelier::atelier.blocks.testimonials.fields.quote'))->rows(3)->required(),
                    TextInput::make('name')->label(__('filament-atelier::atelier.blocks.testimonials.fields.name'))->required(),
                    TextInput::make('role')->label(__('filament-atelier::atelier.blocks.testimonials.fields.role')),
                    Media::upload('avatar', __('filament-atelier::atelier.blocks.testimonials.fields.avatar')),
                ])
                ->reorderable()
                ->collapsed()
                ->itemLabel(fn (array $state) => $state['name'] ?? __('filament-atelier::atelier.blocks.testimonials.fields.item_fallback'))
                ->live(),
        ];
    }
}
