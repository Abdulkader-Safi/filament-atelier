<?php

declare(strict_types=1);

namespace App\Blocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Safi\Atelier\Blocks\BaseBlock;
use Safi\Atelier\Schema\Graph;
use Safi\Atelier\Schema\StructuredData;

/**
 * Questions and answers, and the one block here that writes JSON-LD.
 *
 * A block can contribute structured data because it already holds the facts:
 * the FAQ schema below is a transform of what the client typed once, not a
 * second thing to type.
 */
class FaqBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'faq';
    }

    public static function label(): string
    {
        return 'Questions';
    }

    public static function icon(): string
    {
        return 'heroicon-o-question-mark-circle';
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
        return ['heading', 'items'];
    }

    public static function defaults(): array
    {
        return [
            'heading' => ['en' => 'Questions'],
            'items' => ['en' => [
                ['question' => 'How much notice do you need?', 'answer' => 'Three days in season, next day if we have a gap.'],
            ]],
        ];
    }

    public static function view(): string
    {
        return 'blocks.faq';
    }

    public function schema(): array
    {
        return [
            TextInput::make('heading')->label('Heading')->live(debounce: 400),
            Repeater::make('items')
                ->label('Questions')
                ->schema([
                    TextInput::make('question')->required(),
                    Textarea::make('answer')->rows(3)->required(),
                ])
                ->reorderable()
                ->collapsed()
                ->itemLabel(fn (array $state) => $state['question'] ?? 'Question')
                ->live(),
        ];
    }

    /**
     * The FAQPage node this block adds to the page's graph.
     *
     * @param  array<string, mixed>  $attributes  collapsed to $locale already
     * @return array<int, array<string, mixed>>
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
}
