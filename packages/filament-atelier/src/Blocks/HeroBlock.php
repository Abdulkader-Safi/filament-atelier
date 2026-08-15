<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Safi\Atelier\Media;

class HeroBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'hero';
    }

    public static function icon(): string
    {
        return 'heroicon-o-rectangle-group';
    }

    public static function category(): string
    {
        return 'Layout';
    }

    public static function translatable(): array
    {
        return ['eyebrow', 'heading', 'subheading', 'cta_label'];
    }

    public static function defaults(): array
    {
        return [
            'heading' => ['en' => 'A headline that earns the scroll'],
            'subheading' => ['en' => 'One sentence explaining what this is and who it is for.'],
            'cta_label' => ['en' => 'Get in touch'],
            'cta_url' => '#',
            'align' => 'left',
        ];
    }

    public function schema(): array
    {
        return [
            TextInput::make('eyebrow')->label('Eyebrow')->live(debounce: 400),
            TextInput::make('heading')->label('Heading')->live(debounce: 400),
            Textarea::make('subheading')->label('Subheading')->rows(3)->live(debounce: 400),
            TextInput::make('cta_label')->label('Button label')->live(debounce: 400),
            TextInput::make('cta_url')->label('Button link')->live(debounce: 400),
            Media::upload('image', 'Background image')->live(),
            Select::make('align')
                ->label('Alignment')
                ->options(['left' => 'Left', 'center' => 'Centre'])
                ->default('left')
                ->live(),
        ];
    }
}
