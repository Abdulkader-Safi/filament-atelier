<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class CtaBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'cta';
    }

    public static function supports(): array
    {
        return ['background', 'padding'];
    }

    public static function label(): string
    {
        return __('filament-atelier::atelier.blocks.cta.label');
    }

    public static function icon(): string
    {
        return 'heroicon-o-megaphone';
    }

    public static function translatable(): array
    {
        return ['heading', 'body', 'cta_label'];
    }

    public static function defaults(): array
    {
        return [
            'heading' => ['en' => 'Ready when you are'],
            'body' => ['en' => 'Tell us what you need and we will come back within a day.'],
            'cta_label' => ['en' => 'Start a project'],
            'cta_url' => '#',
        ];
    }

    public function schema(): array
    {
        return [
            TextInput::make('heading')->label(__('filament-atelier::atelier.blocks.cta.fields.heading'))->live(debounce: 400),
            Textarea::make('body')->label(__('filament-atelier::atelier.blocks.cta.fields.body'))->rows(2)->live(debounce: 400),
            TextInput::make('cta_label')->label(__('filament-atelier::atelier.blocks.cta.fields.cta_label'))->live(debounce: 400),
            TextInput::make('cta_url')->label(__('filament-atelier::atelier.blocks.cta.fields.cta_url'))->live(debounce: 400),
        ];
    }
}
