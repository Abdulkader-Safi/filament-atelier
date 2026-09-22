<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;

class RichTextBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'rich-text';
    }

    public static function supports(): array
    {
        return ['background', 'padding'];
    }

    public static function label(): string
    {
        return __('filament-atelier::atelier.blocks.rich_text.label');
    }

    public static function icon(): string
    {
        return 'heroicon-o-bars-3-bottom-left';
    }

    public static function translatable(): array
    {
        return ['heading', 'body'];
    }

    public static function defaults(): array
    {
        return [
            'heading' => ['en' => 'A section heading'],
            'body' => ['en' => '<p>Write something here. This block is the one that proves whether long text wraps the way you expect.</p>'],
        ];
    }

    public function schema(): array
    {
        return [
            TextInput::make('heading')
                ->label(__('filament-atelier::atelier.blocks.rich_text.fields.heading'))
                ->live(debounce: 400),

            RichEditor::make('body')
                ->label(__('filament-atelier::atelier.blocks.rich_text.fields.body'))
                ->live(debounce: 600),
        ];
    }
}
