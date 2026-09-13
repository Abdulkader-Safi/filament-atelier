<?php

declare(strict_types=1);

namespace App\Blocks;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Safi\Atelier\Blocks\BaseBlock;

/** The loud panel at the bottom of a page. Painted from the design tokens. */
class CtaBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'cta';
    }

    public static function label(): string
    {
        return 'Call to action';
    }

    public static function icon(): string
    {
        return 'heroicon-o-megaphone';
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
        return ['heading', 'body', 'cta_label'];
    }

    public static function defaults(): array
    {
        return [
            'heading' => ['en' => 'Get a price today'],
            'body' => ['en' => 'Tell us what needs cleaning and we will come back within one working day.'],
            'cta_label' => ['en' => 'Ask for a quote'],
            'cta_url' => '/contact',
        ];
    }

    public static function view(): string
    {
        return 'blocks.cta';
    }

    public function schema(): array
    {
        return [
            TextInput::make('heading')->label('Heading')->live(debounce: 400),
            Textarea::make('body')->label('Sentence below')->rows(2)->live(debounce: 400),
            TextInput::make('cta_label')->label('Button label')->live(debounce: 400),
            TextInput::make('cta_url')->label('Button link')->live(debounce: 400),
        ];
    }
}
