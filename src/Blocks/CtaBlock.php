<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

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
        return 'Call to action';
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
            TextInput::make('heading')->label('Heading')->live(debounce: 400),
            Textarea::make('body')->label('Text')->rows(2)->live(debounce: 400),
            TextInput::make('cta_label')->label('Button label')->live(debounce: 400),
            TextInput::make('cta_url')->label('Button link')->live(debounce: 400),
        ];
    }
}
