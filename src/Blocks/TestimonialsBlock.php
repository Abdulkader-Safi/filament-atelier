<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
            TextInput::make('heading')->label('Heading')->live(debounce: 400),
            Repeater::make('items')
                ->label('Testimonials')
                ->schema([
                    Textarea::make('quote')->label('Quote')->rows(3)->required(),
                    TextInput::make('name')->label('Name')->required(),
                    TextInput::make('role')->label('Role'),
                    Media::upload('avatar', 'Photo'),
                ])
                ->reorderable()
                ->collapsed()
                ->itemLabel(fn (array $state) => $state['name'] ?? 'Testimonial')
                ->live(),
        ];
    }
}
