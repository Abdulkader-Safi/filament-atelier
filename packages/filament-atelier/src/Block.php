<?php

declare(strict_types=1);

namespace Safi\Atelier;

interface Block
{
    /** Registry key. Namespace third-party blocks, e.g. 'acme.pricing'. */
    public static function type(): string;

    /** Shown in the section picker. */
    public static function label(): string;

    /** Heroicon name for the picker. */
    public static function icon(): string;

    /** Groups the block in the picker. */
    public static function category(): string;

    /** Filament schema components. Becomes the settings pane. */
    public function schema(): array;

    /** Shared features this block opts into: background, padding, animation. */
    public static function supports(): array;

    /** Blade view rendering this block. */
    public static function view(): string;
}
