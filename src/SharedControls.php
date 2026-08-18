<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Illuminate\View\ComponentAttributeBag;

/**
 * The controls a block opts into through `supports()`, built once here rather
 * than reimplemented in every block.
 *
 * Everything is emitted as an inline style built from design tokens, never as
 * a utility class. A class written in PHP is a class Tailwind never scans, so
 * it would compile in the example app and vanish on a client site. Tokens have
 * no such problem, which is most of the reason they exist.
 *
 * Leaving a control unset means the block keeps its own styling. Setting one
 * overrides it, because an inline style beats a utility class.
 */
class SharedControls
{
    /** Padding presets, resolved against the spacing tokens. */
    protected const PADDING = [
        'none' => '0',
        'tight' => 'calc(var(--atelier-space-section) / 3)',
        'normal' => 'calc(var(--atelier-space-section) / 1.5)',
        'loose' => 'var(--atelier-space-section)',
    ];

    /**
     * Settings-pane components for whatever the block declares.
     *
     * @param  array<int, string>  $supports
     * @return array<int, Section>
     */
    public static function schema(array $supports): array
    {
        $fields = [];

        if (in_array('background', $supports, true)) {
            $fields[] = Select::make('background')
                ->label('Background')
                ->options(Tokens::options('color'))
                ->placeholder('Default')
                // Stored as a reference, not a literal, so changing the token
                // changes every page that picked it.
                ->formatStateUsing(fn (mixed $state) => is_array($state) ? ($state['token'] ?? null) : $state)
                ->dehydrateStateUsing(fn (?string $state) => $state ? ['token' => $state] : null)
                ->live();
        }

        if (in_array('padding', $supports, true)) {
            $fields[] = Select::make('padding')
                ->label('Vertical space')
                ->options([
                    'none' => 'None',
                    'tight' => 'Tight',
                    'normal' => 'Normal',
                    'loose' => 'Loose',
                ])
                ->placeholder("The block's own")
                ->live();
        }

        return $fields === [] ? [] : [
            Section::make('Section style')
                ->schema($fields)
                ->collapsed(),
        ];
    }

    /**
     * The root element's attributes: the block id the editor tracks sections
     * by, plus whatever the shared controls resolved to.
     *
     * @param  array<int, string>  $supports
     * @param  array<string, mixed>  $attributes  already token-resolved
     */
    public static function attributes(array $supports, array $attributes, string $id): ComponentAttributeBag
    {
        $styles = [];

        if (in_array('background', $supports, true)) {
            $background = $attributes['background'] ?? null;

            if (is_string($background) && $background !== '') {
                $styles[] = "background-color:{$background}";
            }
        }

        if (in_array('padding', $supports, true)) {
            $padding = self::PADDING[$attributes['padding'] ?? ''] ?? null;

            if ($padding !== null) {
                $styles[] = "padding-block:{$padding}";
            }
        }

        return new ComponentAttributeBag(array_filter([
            'data-atelier-block' => $id,
            'style' => $styles === [] ? null : implode(';', $styles),
        ]));
    }
}
