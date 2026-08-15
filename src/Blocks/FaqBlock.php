<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class FaqBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'faq';
    }

    public static function label(): string
    {
        return 'FAQ';
    }

    public static function icon(): string
    {
        return 'heroicon-o-question-mark-circle';
    }

    public static function translatable(): array
    {
        return ['heading', 'items'];
    }

    public static function defaults(): array
    {
        return [
            'heading' => ['en' => 'Questions'],
            'items' => ['en' => [
                ['question' => 'How long does it take?', 'answer' => 'Depends on the scope. Usually weeks, not months.'],
            ]],
        ];
    }

    public function schema(): array
    {
        return [
            TextInput::make('heading')->label('Heading')->live(debounce: 400),
            Repeater::make('items')
                ->label('Questions')
                ->schema([
                    TextInput::make('question')->label('Question')->required(),
                    Textarea::make('answer')->label('Answer')->rows(3)->required(),
                ])
                ->reorderable()
                ->collapsed()
                ->itemLabel(fn (array $state) => $state['question'] ?? 'Question')
                ->live(),
        ];
    }
}
