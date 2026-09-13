<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Safi\Atelier\Models\Page;
use Safi\Atelier\PageTypes\BasePageType;
use Safi\Atelier\Schema\PageTypes;

/**
 * A kind of page a developer defines in code: a service, a product, a case
 * study. It gets its own entry in the panel sidebar, its own custom
 * properties, its own starter sections, and its own set of usable blocks.
 *
 * Underneath it is still an `atelier_pages` row, so a typed page inherits
 * slugs, redirects, revisions, drafts, preview, SEO and the sitemap for free.
 *
 * Not to be confused with {@see PageTypes}, which is the
 * schema.org type in the JSON-LD. A page type can name one through
 * schemaType() so the client is not asked the same question twice.
 *
 * Registered from the panel provider, the same way blocks are:
 *
 *     AtelierPlugin::make()->pageTypes([ServiceType::class]);
 *
 * Extend {@see BasePageType} for the defaults and
 * write only the parts that differ.
 */
interface PageType
{
    /** Registry key, and the value stored in the page's `type` column. */
    public static function type(): string;

    /** Singular, for the create button and the breadcrumb. */
    public static function label(): string;

    /** Plural, for the sidebar entry and the list heading. */
    public static function pluralLabel(): string;

    /** Heroicon name for the sidebar. */
    public static function icon(): string;

    /** Sidebar position. Null leaves Filament to sort it. */
    public static function navigationSort(): ?int;

    /** Panel URL segment, e.g. `services` for /admin/services. */
    public static function slug(): string;

    /**
     * The type's custom properties, as plain Filament components. Whatever is
     * returned here is what the settings screen shows and what
     * {@see Page::data()} reads back.
     *
     * Called once per locale for the translated fields, so it must return
     * fresh instances rather than cached ones.
     *
     * @return array<int, Field|Component>
     */
    public static function fields(): array;

    /**
     * Field names stored per locale, at `data.{locale}.{key}`. Everything
     * else is stored once, at `data.{key}`.
     *
     * @return array<int, string>
     */
    public static function translatable(): array;

    /**
     * The block tree a new page of this type opens with. Same shape as
     * `draft_content`: a list of nodes, each with at least a `type`.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function template(): array;

    /**
     * Block keys the section picker offers on this type, or null for all of
     * them. A block already on a page keeps working after it leaves this
     * list; only the picker narrows.
     *
     * @return array<int, string>|null
     */
    public static function blocks(): ?array;

    /**
     * The Blade view the Collection block renders per item. It receives
     * `$page` and `$locale`, and it is the only place that knows what
     * fields() named its fields.
     */
    public static function cardView(): ?string;

    /**
     * Slug prefix prefilled on a new page, per locale or one for all of them.
     * A default, not a rule: the client can edit or clear it.
     *
     * @return array<string, string>|string|null
     */
    public static function prefix(): array|string|null;

    /** The schema.org type a page of this kind defaults to. */
    public static function schemaType(): ?string;

    /**
     * The Blade view served at the prefix root, e.g. /services. It receives
     * `$pages`, `$type` and `$locale`. Null registers no index route.
     */
    public static function indexView(): ?string;
}
