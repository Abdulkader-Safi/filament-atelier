<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use Safi\Atelier\Blocks\HeroBlock;
use Safi\Atelier\Blocks\RichTextBlock;
use Safi\Atelier\Filament\Pages\PageEditor;
use Safi\Atelier\Models\Page;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::factory()->create());

    $this->page = Page::create([
        'title' => 'Test page',
        'status' => 'draft',
        'draft_content' => [
            [
                'id' => 'b_one',
                'type' => 'hero',
                'attributes' => HeroBlock::defaults(),
                'children' => [],
            ],
        ],
    ]);
});

function editor(Page $page): \Livewire\Features\SupportTesting\Testable
{
    return Livewire::test(PageEditor::class, ['record' => $page->getKey()]);
}

it('renders the editor page through the panel', function () {
    get("/admin/atelier/{$this->page->getKey()}")
        ->assertOk()
        ->assertSee('Add section');
});

it('opens with the first section selected', function () {
    editor($this->page)
        ->assertSet('selectedId', 'b_one')
        ->assertSet('data.cta_url', '#');
});

it('writes an edited field into the draft, scoped to the current locale', function () {
    editor($this->page)
        ->set('data.heading', 'Edited in English')
        ->assertSet('tree.0.attributes.heading.en', 'Edited in English');

    expect($this->page->fresh()->draft()[0]['attributes']['heading']['en'])
        ->toBe('Edited in English');
});

it('keeps the other locale when editing one of them', function () {
    $this->page->update(['draft_content' => [[
        'id' => 'b_one',
        'type' => 'hero',
        'attributes' => ['heading' => ['en' => 'English', 'ar' => 'عربي']],
        'children' => [],
    ]]]);

    editor($this->page)
        ->call('setLocale', 'ar')
        ->assertSet('data.heading', 'عربي')
        ->set('data.heading', 'عربي محدث')
        ->assertSet('tree.0.attributes.heading.en', 'English')
        ->assertSet('tree.0.attributes.heading.ar', 'عربي محدث');
});

it('never touches the published page while editing the draft', function () {
    $this->page->publish();
    $published = $this->page->fresh()->published();

    editor($this->page)->set('data.heading', 'Changed after publishing');

    expect($this->page->fresh()->published())->toBe($published)
        ->and($this->page->fresh()->draft())->not->toBe($published);
});

it('adds, duplicates, hides, moves and deletes sections', function () {
    $component = editor($this->page)
        ->call('addBlock', RichTextBlock::type())
        ->assertCount('tree', 2);

    $added = $component->get('selectedId');

    $component
        ->call('duplicateBlock', $added)
        ->assertCount('tree', 3)
        ->call('toggleHidden', $added)
        ->assertSet('tree.1.hidden', true)
        ->call('move', $added, -1)
        ->assertSet('tree.0.id', $added)
        ->call('deleteBlock', $added)
        ->assertCount('tree', 2);
});

it('refreshes the preview after any change', function () {
    editor($this->page)
        ->set('data.heading', 'Anything')
        ->assertDispatched('atelier-refresh');
});

it('hides hidden sections from the public render but keeps them in the editor', function () {
    $this->page->update(['draft_content' => [[
        'id' => 'b_one',
        'type' => 'hero',
        'attributes' => ['heading' => ['en' => 'Visible to nobody']],
        'hidden' => true,
        'children' => [],
    ]]]);

    $renderer = app(Safi\Atelier\Renderer::class);

    expect($renderer->render($this->page->draft(), 'en'))->not->toContain('Visible to nobody')
        ->and($renderer->render($this->page->draft(), 'en', editing: true))->toContain('Visible to nobody');
});

it('renders an unknown block type as nothing publicly and a warning in the editor', function () {
    $tree = [['id' => 'b_x', 'type' => 'does-not-exist', 'attributes' => [], 'children' => []]];
    $renderer = app(Safi\Atelier\Renderer::class);

    expect(trim($renderer->render($tree, 'en')))->toBe('')
        ->and($renderer->render($tree, 'en', editing: true))->toContain('Unknown block type');
});

it('rejects an unsigned preview url', function () {
    get("/atelier/preview/{$this->page->getKey()}/en")->assertForbidden();
});

it('serves a signed preview with noindex', function () {
    get(URL::signedRoute('atelier.preview', ['page' => $this->page->getKey(), 'locale' => 'en']))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('data-atelier-canvas', escape: false);
});

it('404s on an unknown locale', function () {
    get(URL::signedRoute('atelier.preview', ['page' => $this->page->getKey(), 'locale' => 'fr']))
        ->assertNotFound();
});
