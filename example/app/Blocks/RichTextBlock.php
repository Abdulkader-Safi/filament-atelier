<?php

declare(strict_types=1);

namespace App\Blocks;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Safi\Atelier\Blocks\BaseBlock;

/** A heading and prose. The block that proves whether long text reads well. */
class RichTextBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'rich-text';
    }

    public static function label(): string
    {
        return 'Text';
    }

    public static function icon(): string
    {
        return 'heroicon-o-document-text';
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
        return ['heading', 'body'];
    }

    public static function defaults(): array
    {
        return [
            'heading' => ['en' => 'A section heading'],
            'body' => ['en' => '<p>Write something here.</p>'],
        ];
    }

    public static function view(): string
    {
        return 'blocks.rich-text';
    }

    public function schema(): array
    {
        return [
            TextInput::make('heading')->label('Heading')->live(debounce: 400),
            RichEditor::make('body')
                ->label('Text')
                ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'h3'])
                ->live(debounce: 600),
        ];
    }
}
