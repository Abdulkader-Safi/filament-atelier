<?php

declare(strict_types=1);

namespace App\Blocks;

/**
 * Every section this site can be built from.
 *
 * Deliberately not `DefaultBlocks::all()`. The package ships an equivalent
 * set, and a real project usually starts from it, but this app defines its
 * own so that everything on the site can be read in one folder without
 * opening the package. Nine classes here, nine views in
 * `resources/views/blocks`, and nothing else decides what a page can hold.
 */
class SiteBlocks
{
    /** @return array<int, class-string> */
    public static function all(): array
    {
        return [
            HeroBlock::class,
            FeaturesBlock::class,
            RichTextBlock::class,
            CollectionBlock::class,
            PricingBlock::class,
            RequestFormBlock::class,
            TestimonialsBlock::class,
            FaqBlock::class,
            CtaBlock::class,
        ];
    }
}
