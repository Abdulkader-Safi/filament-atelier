<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

/**
 * The set Atelier ships with. Register all of them, or cherry-pick, or ignore
 * this entirely and register your own classes.
 */
class DefaultBlocks
{
    /** @return array<class-string> */
    public static function all(): array
    {
        return [
            HeroBlock::class,
            FeaturesBlock::class,
            RichTextBlock::class,
            ImageBlock::class,
            GalleryBlock::class,
            LogoWallBlock::class,
            TestimonialsBlock::class,
            FaqBlock::class,
            CtaBlock::class,
        ];
    }
}
