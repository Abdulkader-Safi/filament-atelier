<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Safi\Atelier\Media;

class HeroBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'hero';
    }

    public static function supports(): array
    {
        return ['background', 'padding'];
    }

    public static function label(): string
    {
        return __('filament-atelier::atelier.blocks.hero.label');
    }

    public static function icon(): string
    {
        return 'heroicon-o-rectangle-group';
    }

    public static function category(): string
    {
        return __('filament-atelier::atelier.block_categories.layout');
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
            TextInput::make('eyebrow')->label(__('filament-atelier::atelier.blocks.hero.fields.eyebrow'))->live(debounce: 400),
            TextInput::make('heading')->label(__('filament-atelier::atelier.blocks.hero.fields.heading'))->live(debounce: 400),
            Textarea::make('subheading')->label(__('filament-atelier::atelier.blocks.hero.fields.subheading'))->rows(3)->live(debounce: 400),
            TextInput::make('cta_label')->label(__('filament-atelier::atelier.blocks.hero.fields.cta_label'))->live(debounce: 400),
            TextInput::make('cta_url')->label(__('filament-atelier::atelier.blocks.hero.fields.cta_url'))->live(debounce: 400),
            Media::upload('image', __('filament-atelier::atelier.blocks.hero.fields.image'))->live(),
            Select::make('align')
                ->label(__('filament-atelier::atelier.blocks.hero.fields.align'))
                ->options([
                    'left' => __('filament-atelier::atelier.blocks.hero.options.align.left'),
                    'center' => __('filament-atelier::atelier.blocks.hero.options.align.center'),
                ])
                ->default('left')
                ->live(),
        ];
    }
}
