<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Safi\Atelier\Blocks\FaqBlock;
use Safi\Atelier\Blocks\HeroBlock;
use Safi\Atelier\Blocks\RichTextBlock;
use Safi\Atelier\Filament\Pages\PageEditor;
use Safi\Atelier\Media;
use Safi\Atelier\Models\Page;
use Safi\Atelier\Renderer;

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

function editor(Page $page): Testable
{
    return Livewire::test(PageEditor::class, ['record' => $page->getKey()]);
}

it('renders the editor page through the panel', function () {
    get("/admin/atelier/{$this->page->getKey()}")
        ->assertOk()
        ->assertSee('Add section')
        ->assertSee('Sections')
        // Full-screen shell, not the panel chrome.
        ->assertDontSee('fi-sidebar-nav', escape: false);
});

it('opens on the section list, with nothing selected', function () {
    editor($this->page)->assertSet('selectedId', null);
});

it('swaps the panel to the inspector and back', function () {
    editor($this->page)
        ->call('selectBlock', 'b_one')
        ->assertSet('selectedId', 'b_one')
        ->assertSet('data.cta_url', '#')
        ->call('closeInspector')
        ->assertSet('selectedId', null);
});

it('stores rich text as html, not the editor internal format', function () {
    editor($this->page)
        ->call('addBlock', RichTextBlock::type())
        ->set('data.body', '<p>Typed into the editor</p>');

    $body = $this->page->fresh()->draft()[1]['attributes']['body']['en'];

    // Reading $data raw used to store TipTap JSON here, and the Blade view
    // then tried to echo an array.
    expect($body)->toBeString()->toContain('Typed into the editor');
});

it('writes an edited field into the draft, scoped to the current locale', function () {
    editor($this->page)
        ->call('selectBlock', 'b_one')
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
        ->call('selectBlock', 'b_one')
        ->call('setLocale', 'ar')
        ->assertSet('data.heading', 'عربي')
        ->set('data.heading', 'عربي محدث')
        ->assertSet('tree.0.attributes.heading.en', 'English')
        ->assertSet('tree.0.attributes.heading.ar', 'عربي محدث');
});

it('never touches the published page while editing the draft', function () {
    $this->page->publish();
    $published = $this->page->fresh()->published();

    editor($this->page)
        ->call('selectBlock', 'b_one')
        ->set('data.heading', 'Changed after publishing');

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

it('persists a repeater row deleted through the repeater own delete action', function () {
    $this->page->update(['draft_content' => [[
        'id' => 'b_faq',
        'type' => FaqBlock::type(),
        'attributes' => ['items' => ['en' => [
            ['question' => 'Keep me', 'answer' => 'Still here'],
            ['question' => 'Delete me', 'answer' => 'Gone'],
        ]]],
        'children' => [],
    ]]]);

    $component = editor($this->page)->call('selectBlock', 'b_faq');

    // Filament keys repeater items by uuid once the form is filled.
    $keys = array_keys($component->get('data.items'));

    $component->callFormComponentAction('items', 'delete', arguments: ['item' => $keys[1]]);

    // The action writes component state on the server, so updatedData() never
    // ran. Before dehydrate() synced the tree, the row vanished from the
    // screen and came straight back on the next load.
    $items = array_values($this->page->fresh()->draft()[0]['attributes']['items']['en']);

    expect($items)->toHaveCount(1)
        ->and($items[0]['question'])->toBe('Keep me');
});

it('refreshes the preview after any change', function () {
    editor($this->page)
        ->call('selectBlock', 'b_one')
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

    $renderer = app(Renderer::class);

    expect($renderer->render($this->page->draft(), 'en'))->not->toContain('Visible to nobody')
        ->and($renderer->render($this->page->draft(), 'en', editing: true))->toContain('Visible to nobody');
});

it('renders an unknown block type as nothing publicly and a warning in the editor', function () {
    $tree = [['id' => 'b_x', 'type' => 'does-not-exist', 'attributes' => [], 'children' => []]];
    $renderer = app(Renderer::class);

    expect(trim($renderer->render($tree, 'en')))->toBe('')
        ->and($renderer->render($tree, 'en', editing: true))->toContain('Unknown block type');
});

it('rejects an unsigned preview url', function () {
    get("/atelier/preview/{$this->page->getKey()}/en")->assertForbidden();
});

it('serves a signed preview with noindex', function () {
    get(URL::signedRoute('atelier.preview', ['page' => $this->page->getKey(), 'locale' => 'en'], absolute: false))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('data-atelier-canvas', escape: false);
});

it('404s on an unknown locale', function () {
    get(URL::signedRoute('atelier.preview', ['page' => $this->page->getKey(), 'locale' => 'fr'], absolute: false))
        ->assertNotFound();
});

it('accepts a relative signature regardless of host', function () {
    $url = URL::signedRoute(
        'atelier.preview',
        ['page' => $this->page->getKey(), 'locale' => 'en'],
        absolute: false,
    );

    // Same signature, different host. Absolute signing would 403 here, which
    // is exactly what broke when browsing 127.0.0.1 with APP_URL on localhost.
    get('http://127.0.0.1'.$url)->assertOk();
});

it('saves an uploaded image to the disk and stores its path', function () {
    Storage::fake('public');

    $component = editor($this->page)
        ->call('addBlock', 'image')
        ->set('data.image', [UploadedFile::fake()->image('photo.jpg', 800, 600)]);

    $stored = $this->page->fresh()->draft()[1]['attributes']['image'];

    // FileUpload only moves the temp file onto the disk in its
    // beforeStateDehydrated hook. Skipping that hook left the field showing
    // "upload complete" while the tree stored [] and the page showed nothing.
    expect($stored)->toBeString()->not->toBeEmpty()
        ->and(Storage::disk('public')->exists($stored))->toBeTrue();

    $component->assertDispatched('atelier-refresh');
});

it('renders an uploaded image on the published page', function () {
    Storage::fake('public');

    editor($this->page)
        ->call('addBlock', 'image')
        ->set('data.image', [UploadedFile::fake()->image('photo.jpg', 800, 600)]);

    $page = $this->page->fresh();
    $page->setSlugs(['en' => 'with-image', 'ar' => 'with-image']);
    $page->publish();

    $path = $page->draft()[1]['attributes']['image'];

    get('/with-image')->assertOk()->assertSee(basename($path));
});

/**
 * Add an image block, upload a file, then remove it the way the panel does.
 * Removal runs BaseFileUpload::deleteUploadedFile(), which is renderless and
 * writes component state directly, so Livewire's updated hook never fires.
 */
function removeUploadedImage(Page $page): array
{
    $component = editor($page)
        ->call('addBlock', 'image')
        ->set('data.image', [UploadedFile::fake()->image('photo.jpg', 800, 600)]);

    $stored = $page->fresh()->draft()[1]['attributes']['image'];

    $component->call('callSchemaComponentMethod', 'form.image', 'deleteUploadedFile', ['0']);

    return [$component, $stored];
}

it('clears the path from the tree when an image is removed', function () {
    Storage::fake('public');

    [$component] = removeUploadedImage($this->page);

    // The tree kept the old path, so the preview still showed the image and
    // publishing put it back on the live page.
    expect(Media::url($this->page->fresh()->draft()[1]['attributes']['image']))->toBeNull();

    // Renderless skips the HTML diff, not the dispatch, so the preview reloads.
    $component->assertDispatched('atelier-refresh');
});

it('deletes the removed image from the disk', function () {
    Storage::fake('public');

    [, $stored] = removeUploadedImage($this->page);

    // Filament only deletes the stored file when deleteUploadedFileUsing is
    // set. Without it every removed or replaced image stayed on the disk.
    expect(Storage::disk('public')->exists($stored))->toBeFalse();
});
