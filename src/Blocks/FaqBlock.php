<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Safi\Atelier\Schema\Graph;
use Safi\Atelier\Schema\StructuredData;

class FaqBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'faq';
    }

    public static function supports(): array
    {
        return ['background', 'padding'];
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

    /**
     * The questions the client already typed, as `FAQPage`.
     *
     * The `@id` is the page rather than this block, so two FAQ blocks on one
     * page merge into a single FAQPage with all the questions, which is what
     * a crawler expects. Two FAQPage nodes on one page is a validation
     * warning and a coin toss over which one is read.
     */
    public static function structuredData(array $attributes, string $locale, string $url): array
    {
        $questions = collect($attributes['items'] ?? [])
            ->map(fn (array $item) => Graph::node([
                '@type' => 'Question',
                'name' => trim(strip_tags((string) ($item['question'] ?? ''))),
                'acceptedAnswer' => Graph::node([
                    '@type' => 'Answer',
                    'text' => trim(strip_tags((string) ($item['answer'] ?? ''))),
                ]),
            ]))
            ->filter()
            // A question with no answer is not an FAQ entry, and Google says
            // so explicitly.
            ->filter(fn (array $question) => isset($question['acceptedAnswer'], $question['name']))
            ->values()
            ->all();

        if ($questions === []) {
            return [];
        }

        return [[
            '@type' => 'FAQPage',
            '@id' => StructuredData::id($url, 'faq'),
            'inLanguage' => $locale,
            'mainEntity' => $questions,
        ]];
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
