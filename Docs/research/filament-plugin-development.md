# Filament plugin development

> Research brief for building a custom page-builder plugin on Laravel + Filament. Verified against official docs and GitHub in June 2026. Where a version or API couldn't be confirmed, it's flagged inline.

## Version reality check (read this first)

The "v3 vs v4" framing is out of date. As of June 2026:

| Version | Status | Released | Notes |
|---|---|---|---|
| Filament v3 | Legacy, still patched | 2023 | Forms/Infolists are separate namespaces. Pre-Schema. |
| Filament v4 | Stable, supported | 12 Aug 2025 | Introduced the unified Schema system. Big jump from v3. |
| Filament v5 | Current stable | 16 Jan 2026 | = v4 + Livewire v4 support. No functional breaking changes vs v4. |

The real API break is v3 to v4 (the Schema system plus namespace moves). v5 is a compatibility release: write a v4-compatible plugin and supporting v5 is usually just widening the composer constraint to `^4.0|^5.0`. Build against v4 APIs, which is what this brief uses. Docs live at `/docs/4.x/` and `/docs/5.x/` (5.x mirrors 4.x).

## 1. Plugin anatomy

A Filament plugin is a normal Laravel package (installed via Composer, registers via a service provider) plus a small Plugin class that hooks into a panel's config.

### The Plugin contract

Implement `Filament\Contracts\Plugin` (namespace unchanged across v3/v4/v5). Three required methods: `getId()`, `register(Panel $panel)`, `boot(Panel $panel)`.

```php
namespace Acme\PageBuilder;

use Filament\Contracts\Plugin;
use Filament\Panel;

class PageBuilderPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'page-builder';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([PageResource::class])
            ->pages([VisualBuilder::class]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
```

Per-panel config uses getter/setter pairs on the plugin class. Reach a plugin's config from anywhere via `filament('page-builder')->someOption()`.

### The service provider (important v4 change)

This is the one footgun. In v3 you extended `Filament\PluginServiceProvider`. In v4 that's deprecated. Extend Spatie's `PackageServiceProvider` instead and add a static `$name`:

```php
namespace Acme\PageBuilder;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;

class PageBuilderServiceProvider extends PackageServiceProvider
{
    public static string $name = 'page-builder';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews()
            ->hasMigrations(['create_pages_table'])
            ->hasConfigFile()
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Js::make('page-builder', __DIR__ . '/../resources/dist/page-builder.js'),
        ], package: 'acme/page-builder');
    }
}
```

### How a third party installs it

```bash
composer require acme/page-builder
php artisan filament:assets
```

Then in their panel provider: `return $panel->plugin(PageBuilderPlugin::make());`. The package's `composer.json` auto-registers the service provider via Laravel package discovery.

## 2. The Builder form field (closest native thing to Gutenberg blocks)

Filament's `Builder` field (`Filament\Forms\Components\Builder`) is a repeatable field where each item is one of several predefined block types, each with its own schema. This is the native, batteries-included block editor.

```php
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;

Builder::make('content')
    ->blocks([
        Builder\Block::make('heading')
            ->icon('heroicon-o-bars-3-bottom-left')
            ->label('Heading')
            ->schema([
                TextInput::make('content')->label('Heading')->required(),
                Select::make('level')
                    ->options(['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3'])
                    ->default('h2'),
            ]),

        Builder\Block::make('paragraph')
            ->icon('heroicon-o-document-text')
            ->schema([RichEditor::make('content')->required()]),

        Builder\Block::make('image')
            ->icon('heroicon-o-photo')
            ->schema([
                FileUpload::make('url')->image()->required(),
                TextInput::make('alt')->label('Alt text'),
            ])
            ->maxItems(5),
    ])
    ->blockNumbers(false)
    ->blockPickerColumns(['md' => 2, 'xl' => 4])
    ->collapsible()
    ->cloneable()
    ->reorderable()
    ->maxItems(20)
    ->columnSpanFull();
```

Verified capabilities: `->blocks([...])`, `Builder\Block::make()`, per-block `->label()`/`->icon()`/`->schema()`/`->maxItems()`, builder-level `->collapsible()`/`->collapsed()`, `->cloneable()`, `->reorderable()`/`->reorderableWithButtons()`, `->maxItems()`, `->blockNumbers(false)`, `->blockPickerColumns(int|array)`. Dynamic block labels via a closure are supported.

This is the realistic core of a page-builder plugin. Most production Filament page builders are a `PageResource` whose edit form is one big `Builder` field. The visual drag-and-drop canvas (section 3) is the harder, optional upgrade.

## 3. Custom form fields / custom Livewire + Alpine components

When the Builder field isn't enough (e.g. a true visual canvas), build a custom field. Key fact: Filament form fields are NOT Livewire components. A field is a config object that renders a Blade view; the view binds to a property on the host Livewire component via the field's state path.

```bash
php artisan make:filament-form-field VisualCanvas
```

```php
use Filament\Forms\Components\Field;
use Closure;

class VisualCanvas extends Field
{
    protected string $view = 'filament.forms.components.visual-canvas';

    protected int | Closure | null $gridSize = null;

    public function gridSize(int | Closure | null $size): static
    {
        $this->gridSize = $size;
        return $this;
    }

    public function getGridSize(): ?int
    {
        return $this->evaluate($this->gridSize);
    }
}
```

State binding in the Blade view (the load-bearing part):

```blade
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="visualCanvas({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            gridSize: {{ $getGridSize() ?? 12 }},
        })"
        x-init="init()"
    >
        <!-- your drag/drop canvas DOM -->
    </div>
</x-dynamic-component>
```

`$getStatePath()` is the property path to read/write. `$wire.$entangle(...)` two-way binds an Alpine variable to that Livewire property. `$applyStateBindingModifiers(...)` respects `defer`/`live()` modes.

### Calling PHP from canvas JS (new in v4)

Mark a method `#[ExposedLivewireMethod]` (optionally `#[Renderless]`) and call from Alpine:

```php
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Livewire\Attributes\Renderless;

#[ExposedLivewireMethod]
#[Renderless]
public function snapToGrid(array $coords): array { /* ... */ }
```

```js
this.result = await $wire.callSchemaComponentMethod(@js($getKey()), 'snapToGrid', { coords })
```

Only `#[ExposedLivewireMethod]` methods are callable, a deliberate security boundary. For a heavy canvas with third-party JS, don't inline it. Compile it as an async Alpine component and lazy-load it (section 5).

## 4. Custom pages and custom panel pages

A custom page is a full-page Livewire component with Filament chrome. This is how you'd add a dedicated "Visual Builder" screen separate from the resource CRUD.

```bash
php artisan make:filament-page VisualBuilder
```

```php
namespace Acme\PageBuilder\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;

class VisualBuilder extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static ?string $navigationIcon  = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $title           = 'Visual Builder';
    protected string $view = 'page-builder::pages.visual-builder';
}
```

Pages extend `Filament\Pages\Page`. To embed forms/schemas, implement `HasSchemas` + `use InteractsWithSchemas` (the v4 Schema-era trait/contract; v3 used `HasForms`/`InteractsWithForms`). Register the page from the plugin's `register()` via `$panel->pages([VisualBuilder::class])`.

## 5. How Filament uses Livewire + Alpine, and adding your own JS

Filament is built on the TALL stack (Tailwind, Alpine, Laravel, Livewire). Every page/resource/custom-page is a Livewire component; Alpine handles in-browser interactivity; the two glue with `$wire` and `$entangle`. Your plugin assets ride on Filament's shared asset system, not the app's Vite build.

### FilamentAsset

Register in a service provider `boot()`/`packageBooted()`. Running `php artisan filament:assets` copies them into `/public` (under a per-plugin subdir when you pass `package:`).

```php
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Js;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\AlpineComponent;

FilamentAsset::register([
    Css::make('page-builder-styles', __DIR__ . '/../resources/css/builder.css'),
    Js::make('page-builder', __DIR__ . '/../resources/dist/builder.js'),
    AlpineComponent::make('visual-canvas', __DIR__ . '/../resources/dist/visual-canvas.js'),
], package: 'acme/page-builder');
```

Key APIs: `Css::make`/`Js::make` (auto-loaded on every Filament page), `->loadedOnRequest()` (opt out, then load on demand with `x-load-css`/`x-load-js`), `AlpineComponent::make` (for an Alpine component bundled with third-party libs via esbuild, the recommended path for a canvas editor), `FilamentAsset::registerScriptData([...])` (pass backend data to JS via `window.filamentData`), `FilamentAsset::registerCssVariables([...])`.

The plain `filament:assets` copy does not bundle/resolve imports. Use esbuild (async Alpine) or Vite for anything with npm imports.

Tailwind caveat for plugin authors: don't ship a prebuilt Tailwind file. Ship raw CSS and tell users to add `@source '../../../../vendor/acme/page-builder/resources/views/**/*';` to their custom theme so your utility classes compile.

## 6. Schema / Infolist system

The headline v4 change. Schemas (`Filament\Schemas\Schema`) are the unified foundation for server-driven UI: form fields, infolist entries, and layout components all live in or compose through the Schema namespace. You describe the UI in PHP; Filament renders the HTML plus Livewire/Alpine wiring.

For a page builder, your block schemas, custom canvas field, and a front-end/preview render can all be expressed as Schemas. For a read-only public render of saved content you typically map each stored block to a Blade partial per block type (the Fabricator approach).

v3 vs v4: in v3, Forms and Infolists were separate subsystems with their own namespaces. In v4 they migrated under the Schema umbrella, the main reason plugin code needs touching on the v3 to v4 jump.

## 7. Storing builder content

Builder field state serializes to a predictable JSON array of blocks. Each block is `{ type, data }`:

```json
[
  { "type": "heading",   "data": { "content": "Welcome", "level": "h1" } },
  { "type": "paragraph", "data": { "content": "<p>Hello</p>" } },
  { "type": "image",     "data": { "url": "uploads/x.jpg", "alt": "X" } }
]
```

Store it in a JSON column and cast it on the model:

```php
Schema::create('pages', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->json('content')->nullable();
    $table->timestamps();
});
```

```php
protected $casts = ['content' => 'array'];
```

Cast options: `'array'`/`'json'` (most common, what Builder expects round-trip), `AsArrayObject`/`AsCollection` (object/Collection ergonomics), or a custom cast (hydrate blocks into typed value objects, nice for rendering but adds complexity). The Builder reads the JSON, rebuilds each item's sub-schema by `type`, and writes the same shape back on save.

## 8. Existing page-builder packages on Filament (honest maturity read)

### Z3d0X/filament-fabricator, the reference choice

A block-based page-builder skeleton. It gives you the `PageResource` plus front-end routing; you define the Layouts and Page Blocks. Intentionally a skeleton, not a turnkey CMS.

Model: a `pages` table; each page picks a Layout and has a Builder-style stack of Page Blocks; Fabricator resolves the URL/slug and renders the layout with the blocks. Front-end routing handled for you.

Maturity: most mature, clearly page-builder-focused option. 379 stars, 70 forks, 44 releases, MIT, generated from the official plugin skeleton, has tests/PHPStan/Pint. Actively maintained. Version matrix: 3.x → Filament ^4, 4.x → Filament ^5. Latest v4.1.0 (6 May 2026); Filament v5 support landed in v4.0.0 (16 Feb 2026), within weeks of Filament v5. Strong signal. Credits pboivin/Filament Peek for live preview.

### tomatophp/filament-cms, "CMS Builder"

Tagline says "easy page builder," but the README surface is Posts + Categories with SEO, comments, import/export. Did not see a block/Builder-based visual page builder; treat the "page builder" claim with caution, it reads more as content/post management. 119 stars, MIT, latest v4.0.0 (9 Oct 2025). Part of the broader TomatoPHP ecosystem (lots of interdependencies).

### lara-zeus/sky, "Sky CMS"

A simple CMS (posts, pages, tags, categories) with front-end scaffolding ready to use. A blog/site CMS, not a block page-builder. Packagist shows v5.0.0 (Filament v5). Solid, but solves a different problem.

### geosem42/filamentor

A genuine drag-and-drop page builder (different model from Fabricator's block-stack form), closest in spirit to a visual canvas. Smaller, more experimental. Could not confirm current v5 support or star count from sources fetched. Flagged as lower-maturity, verify before adopting. Useful as a reference for the drag-and-drop UX even if you don't depend on it.

### Companions worth a look

- **pboivin/filament-peek.** Adds live page preview to Filament forms/pages. Strong companion to whatever builder you ship.
- **awcodes/filament-curator.** Media library manager. You'll likely want it for image blocks.

## Recommendation: build from scratch vs fork Fabricator

Start from `Z3d0X/filament-fabricator`, don't reinvent the skeleton. It solves the boring, error-prone 80%: the `PageResource`, slug/URL resolution, front-end routing, and the Layouts plus Page Blocks abstraction, and it tracks Filament v5 closely. It's MIT, tested, and designed to be extended with your blocks.

Spend your effort where it differentiates you: your block library (rich, well-styled blocks with good schemas), a nicer editing UX (drag-and-drop canvas as a custom field, borrow ideas from Filamentor as reference not dependency), and live preview (bolt on filament-peek rather than building it yourself).

Build entirely from scratch only if your page model is fundamentally different from "page = layout + ordered blocks" (e.g. a multi-region, nested, component-tree editor), in which case you want a custom-field canvas storing a nested JSON tree.

## Sources

- Plugin development (v4): https://filamentphp.com/docs/4.x/plugins/panel-plugins
- Plugins getting started (v4): https://filamentphp.com/docs/4.x/plugins/getting-started
- Builder field (v4): https://filamentphp.com/docs/4.x/forms/builder
- Custom fields (v4): https://filamentphp.com/docs/4.x/forms/custom-fields
- Custom pages (v4): https://filamentphp.com/docs/4.x/navigation/custom-pages
- Registering assets (v4): https://filamentphp.com/docs/4.x/advanced/assets
- Schemas overview (v4): https://filamentphp.com/docs/4.x/schemas/overview
- Filament v5 / Blueprint: https://filamentphp.com/insights/danharrin-filament-v5-blueprint
- Filament v5 release (Laravel News): https://laravel-news.com/filament-5
- Filament v4 stable: https://filamentphp.com/content/alexandersix-filament-v4-is-stable
- Z3d0X/filament-fabricator: https://github.com/Z3d0X/filament-fabricator
- Fabricator releases: https://github.com/Z3d0X/filament-fabricator/releases
- tomatophp/filament-cms: https://github.com/tomatophp/filament-cms
- Lara Zeus Sky: https://github.com/lara-zeus/sky
- Filamentor: https://github.com/geosem42/filamentor
- Filament CMS packages comparison: https://elmapicms.com/blog/filament-cms-packages-comparison-tomatophp-lara-zeus-panini
- spatie/laravel-package-tools: https://github.com/spatie/laravel-package-tools
