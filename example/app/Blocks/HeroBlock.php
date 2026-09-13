<?php

declare(strict_types=1);

namespace App\Blocks;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Safi\Atelier\Blocks\BaseBlock;
use Safi\Atelier\Media;

/**
 * The top of a page: an eyebrow, a headline, a sentence and one button.
 *
 * A block is this class plus `resources/views/blocks/hero.blade.php`. The
 * class says what the client can edit; the view says what it looks like.
 */
class HeroBlock extends BaseBlock
{
    /** The key stored in the page's JSON. */
    public static function type(): string
    {
        return 'hero';
    }

    public static function label(): string
    {
        return 'Hero';
    }

    public static function icon(): string
    {
        return 'heroicon-o-bars-arrow-up';
    }

    /** Groups it in the section picker. */
    public static function category(): string
    {
        return 'Sections';
    }

    /** Shared controls this block opts into, built by the package. */
    public static function supports(): array
    {
        return ['background', 'padding'];
    }

    /** Stored as { "en": "...", "ar": "..." }. Everything else is shared. */
    public static function translatable(): array
    {
        return ['eyebrow', 'heading', 'subheading', 'cta_label'];
    }

    /** What a freshly added hero says before anyone types. */
    public static function defaults(): array
    {
        return [
            'heading' => ['en' => 'A headline that earns the scroll'],
            'subheading' => ['en' => 'One sentence on what this is and who it is for.'],
            'cta_label' => ['en' => 'Get a price'],
            'cta_url' => '/contact',
            'align' => 'left',
        ];
    }

    /** `blocks.hero`, not `atelier::blocks.hero`: this app owns the view. */
    public static function view(): string
    {
        return 'blocks.hero';
    }

    /**
     * The settings pane, as a plain Filament schema.
     *
     * `->live(debounce: 400)` is what makes the preview update as you type.
     * Without it a field only refreshes the preview when focus leaves.
     */
    public function schema(): array
    {
        return [
            TextInput::make('eyebrow')->label('Small line above')->live(debounce: 400),
            TextInput::make('heading')->label('Headline')->live(debounce: 400),
            Textarea::make('subheading')->label('Sentence below')->rows(3)->live(debounce: 400),
            TextInput::make('cta_label')->label('Button label')->live(debounce: 400),
            TextInput::make('cta_url')->label('Button link')->live(debounce: 400),
            Media::upload('image', 'Background image')->live(),
            Select::make('align')
                ->label('Alignment')
                ->options(['left' => 'Left', 'center' => 'Centre'])
                ->default('left')
                ->native(false)
                ->live(),
        ];
    }
}
