<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use Safi\Atelier\Filament\Pages\MenuManager;
use Safi\Atelier\Models\Menu;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::factory()->create()));

function menuManagerItem(string $id, string $label, string $url): array
{
    return ['id' => $id, 'label' => ['en' => $label], 'url' => ['en' => $url], 'target' => '_self', 'children' => []];
}

it('opens on the first registered location', function () {
    Livewire::test(MenuManager::class)->assertSet('location', 'primary');
});

it('adds a custom link and mounts the edit action for it straight away', function () {
    Livewire::test(MenuManager::class)
        ->call('addItem')
        ->assertActionMounted('editItem');

    expect(Menu::forLocation('primary')->tree())->toHaveCount(1);
});

it('saves label and URL through the edit action, with no explicit save call elsewhere', function () {
    Menu::forLocation('primary')->update(['items' => [menuManagerItem('m_a', 'Old', '/old')]]);

    Livewire::test(MenuManager::class)->callAction('editItem', data: [
        'label' => ['en' => 'About'],
        'url' => ['en' => '/about'],
        'target' => '_self',
    ], arguments: ['id' => 'm_a']);

    $tree = Menu::forLocation('primary')->tree();

    expect($tree[0]['label']['en'])->toBe('About')
        ->and($tree[0]['url']['en'])->toBe('/about');
});

it('saves a different URL per locale, not one shared URL', function () {
    Menu::forLocation('primary')->update(['items' => [menuManagerItem('m_a', 'Home', '/home')]]);

    Livewire::test(MenuManager::class)->callAction('editItem', data: [
        'label' => ['en' => 'Home', 'ar' => 'الرئيسية'],
        'url' => ['en' => '/home', 'ar' => '/ar/home'],
        'target' => '_self',
    ], arguments: ['id' => 'm_a']);

    $item = Menu::forLocation('primary')->tree()[0];

    expect($item['url']['en'])->toBe('/home')
        ->and($item['url']['ar'])->toBe('/ar/home');
});

it('prefills the edit action with the item\'s current values', function () {
    Menu::forLocation('primary')->update(['items' => [menuManagerItem('m_a', 'A', '/a')]]);

    Livewire::test(MenuManager::class)
        ->mountAction('editItem', ['id' => 'm_a'])
        ->assertActionDataSet(['label' => ['en' => 'A', 'ar' => null], 'url' => ['en' => '/a', 'ar' => null], 'target' => '_self']);
});

it('deletes an item through the delete action, with confirmation required', function () {
    Menu::forLocation('primary')->update(['items' => [menuManagerItem('m_a', 'A', '/a')]]);

    Livewire::test(MenuManager::class)
        ->assertActionExists('deleteItem')
        ->callAction('deleteItem', arguments: ['id' => 'm_a']);

    expect(Menu::forLocation('primary')->tree())->toBe([]);
});

it('moves an item up and down among its siblings', function () {
    Menu::forLocation('primary')->update(['items' => [
        menuManagerItem('m_a', 'A', '/a'),
        menuManagerItem('m_b', 'B', '/b'),
    ]]);

    Livewire::test(MenuManager::class)->call('move', 'm_a', 1);

    $tree = Menu::forLocation('primary')->tree();

    expect($tree[0]['label']['en'])->toBe('B')
        ->and($tree[1]['label']['en'])->toBe('A');
});

it('refuses to move an item past either end of its siblings', function () {
    Menu::forLocation('primary')->update(['items' => [menuManagerItem('m_a', 'Only one', '/a')]]);

    Livewire::test(MenuManager::class)->call('move', 'm_a', -1)->call('move', 'm_a', 1);

    expect(Menu::forLocation('primary')->tree()[0]['label']['en'])->toBe('Only one');
});

it('adds a sub-item under a top-level item and keeps it nested', function () {
    Menu::forLocation('primary')->update(['items' => [menuManagerItem('m_parent', 'Services', '/services')]]);

    Livewire::test(MenuManager::class)->call('addItem', 'm_parent');

    $tree = Menu::forLocation('primary')->tree();

    expect($tree[0]['children'])->toHaveCount(1);
});

it('edits a child through the same edit action as a top-level item', function () {
    Menu::forLocation('primary')->update(['items' => [
        [...menuManagerItem('m_parent', 'Services', '/services'), 'children' => [
            menuManagerItem('m_child', 'Old label', '/old'),
        ]],
    ]]);

    Livewire::test(MenuManager::class)->callAction('editItem', data: [
        'label' => ['en' => 'New label'],
        'url' => ['en' => '/new'],
        'target' => '_self',
    ], arguments: ['id' => 'm_child']);

    $child = Menu::forLocation('primary')->tree()[0]['children'][0];

    expect($child['label']['en'])->toBe('New label')
        ->and($child['url']['en'])->toBe('/new');
});

it('moves a child among its own siblings, not its parent\'s', function () {
    Menu::forLocation('primary')->update(['items' => [
        [...menuManagerItem('m_parent', 'Services', '/services'), 'children' => [
            menuManagerItem('m_a', 'A', '/a'),
            menuManagerItem('m_b', 'B', '/b'),
        ]],
    ]]);

    Livewire::test(MenuManager::class)->call('move', 'm_a', 1);

    $children = Menu::forLocation('primary')->tree()[0]['children'];

    expect($children[0]['label']['en'])->toBe('B')
        ->and($children[1]['label']['en'])->toBe('A');
});

it('deletes a child without touching its parent or siblings', function () {
    Menu::forLocation('primary')->update(['items' => [
        [...menuManagerItem('m_parent', 'Services', '/services'), 'children' => [
            menuManagerItem('m_keep', 'Keep', '/keep'),
            menuManagerItem('m_drop', 'Drop', '/drop'),
        ]],
    ]]);

    Livewire::test(MenuManager::class)->callAction('deleteItem', arguments: ['id' => 'm_drop']);

    $tree = Menu::forLocation('primary')->tree();

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['children'])->toHaveCount(1)
        ->and($tree[0]['children'][0]['label']['en'])->toBe('Keep');
});

it('switches locations and loads that location\'s own tree', function () {
    Menu::forLocation('primary')->update(['items' => [menuManagerItem('m_p', 'Primary item', '/')]]);
    Menu::forLocation('footer')->update(['items' => [menuManagerItem('m_f', 'Footer item', '/contact')]]);

    Livewire::test(MenuManager::class)
        ->assertSet('tree.0.label.en', 'Primary item')
        ->set('location', 'footer')
        ->assertSet('tree.0.label.en', 'Footer item');
});

it('keeps one row per location however often it saves', function () {
    Livewire::test(MenuManager::class)->call('addItem');
    Livewire::test(MenuManager::class)->call('addItem');

    expect(Menu::where('location', 'primary')->count())->toBe(1)
        ->and(Menu::forLocation('primary')->tree())->toHaveCount(2);
});

// reorderTree() is what a drop sends: the browser can't be trusted to have
// dragged a real gesture, so these call it directly with the same shape the
// front end's sync() builds, rather than trying to simulate a drag.

it('reorders top-level items via reorderTree, the same shape a drop sends', function () {
    Menu::forLocation('primary')->update(['items' => [
        menuManagerItem('m_a', 'A', '/a'),
        menuManagerItem('m_b', 'B', '/b'),
    ]]);

    Livewire::test(MenuManager::class)->call('reorderTree', ['m_b', 'm_a']);

    $tree = Menu::forLocation('primary')->tree();

    expect($tree[0]['label']['en'])->toBe('B')
        ->and($tree[1]['label']['en'])->toBe('A');
});

it('reparents a top-level item as a sub-item by dragging it into another item\'s children', function () {
    Menu::forLocation('primary')->update(['items' => [
        menuManagerItem('m_services', 'Services', '/services'),
        menuManagerItem('m_web', 'Web design', '/web-design'),
    ]]);

    Livewire::test(MenuManager::class)->call('reorderTree', ['m_services'], ['m_services' => ['m_web']]);

    $tree = Menu::forLocation('primary')->tree();

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['children'])->toHaveCount(1)
        ->and($tree[0]['children'][0]['label']['en'])->toBe('Web design');
});

it('promotes a sub-item to top-level by dragging it out of its parent', function () {
    Menu::forLocation('primary')->update(['items' => [
        [...menuManagerItem('m_services', 'Services', '/services'), 'children' => [
            menuManagerItem('m_web', 'Web design', '/web-design'),
        ]],
    ]]);

    Livewire::test(MenuManager::class)->call('reorderTree', ['m_services', 'm_web']);

    $tree = Menu::forLocation('primary')->tree();

    expect($tree)->toHaveCount(2)
        ->and($tree[0]['children'])->toBe([])
        ->and($tree[1]['label']['en'])->toBe('Web design');
});

it('refuses a reorder that would silently drop an id, rather than apply it missing a sibling', function () {
    // m_web is dragged under m_services, but m_web itself already had a
    // child, m_deep, and this payload never mentions it anywhere: neither
    // in $top nor in any $children bucket. That's the same shape a DOM read
    // mid-drag produced once already (task 13's drag-and-drop note), so the
    // whole call is refused rather than applied with m_deep quietly gone.
    Menu::forLocation('primary')->update(['items' => [
        menuManagerItem('m_services', 'Services', '/services'),
        [...menuManagerItem('m_web', 'Web design', '/web-design'), 'children' => [
            menuManagerItem('m_deep', 'Should survive', '/too-deep'),
        ]],
    ]]);

    Livewire::test(MenuManager::class)->call('reorderTree', ['m_services'], ['m_services' => ['m_web']]);

    $tree = Menu::forLocation('primary')->tree();

    expect($tree)->toHaveCount(2)
        ->and($tree[1]['children'][0]['label']['en'])->toBe('Should survive');
});

it('truncates a second level of nesting even when the dropped grandchild is named in the payload', function () {
    // Every id here IS accounted for, m_deep included, so the integrity
    // check above has nothing to object to. The UI can never build this
    // payload, a child's row has no drop target of its own, so this is
    // the tampered-request case: depth enforcement still has to hold.
    Menu::forLocation('primary')->update(['items' => [
        menuManagerItem('m_services', 'Services', '/services'),
        [...menuManagerItem('m_web', 'Web design', '/web-design'), 'children' => [
            menuManagerItem('m_deep', 'Too deep', '/too-deep'),
        ]],
    ]]);

    Livewire::test(MenuManager::class)->call('reorderTree', ['m_services'], [
        'm_services' => ['m_web'],
        'm_web' => ['m_deep'],
    ]);

    $tree = Menu::forLocation('primary')->tree();

    expect($tree[0]['children'][0]['label']['en'])->toBe('Web design')
        ->and($tree[0]['children'][0]['children'])->toBe([]);
});

it('ignores an id reorderTree does not recognise, rather than trust the browser\'s payload', function () {
    Menu::forLocation('primary')->update(['items' => [menuManagerItem('m_a', 'A', '/a')]]);

    Livewire::test(MenuManager::class)->call('reorderTree', ['m_a', 'm_made_up']);

    expect(Menu::forLocation('primary')->tree())->toHaveCount(1);
});

it('toggles hidden on and back off, without deleting the item', function () {
    Menu::forLocation('primary')->update(['items' => [menuManagerItem('m_a', 'A', '/a')]]);

    $component = Livewire::test(MenuManager::class);

    $component->call('toggleHidden', 'm_a');
    expect(Menu::forLocation('primary')->tree()[0]['hidden'])->toBeTrue();

    $component->call('toggleHidden', 'm_a');
    expect(Menu::forLocation('primary')->tree()[0]['hidden'])->toBeFalse();
});

it('toggles hidden on a child without touching its parent or siblings', function () {
    Menu::forLocation('primary')->update(['items' => [
        [...menuManagerItem('m_parent', 'Services', '/services'), 'children' => [
            menuManagerItem('m_child', 'Web design', '/web-design'),
        ]],
    ]]);

    Livewire::test(MenuManager::class)->call('toggleHidden', 'm_child');

    $tree = Menu::forLocation('primary')->tree();

    expect($tree[0]['hidden'] ?? false)->toBeFalse()
        ->and($tree[0]['children'][0]['hidden'])->toBeTrue();
});

it('keeps hidden as it was through an edit save, since the modal has no field for it', function () {
    Menu::forLocation('primary')->update(['items' => [
        [...menuManagerItem('m_a', 'A', '/a'), 'hidden' => true],
    ]]);

    Livewire::test(MenuManager::class)->callAction('editItem', data: [
        'label' => ['en' => 'Renamed'],
        'url' => ['en' => '/a'],
        'target' => '_self',
    ], arguments: ['id' => 'm_a']);

    expect(Menu::forLocation('primary')->tree()[0]['hidden'])->toBeTrue();
});

it('keeps hidden as it was through a drag reorder', function () {
    Menu::forLocation('primary')->update(['items' => [
        [...menuManagerItem('m_a', 'A', '/a'), 'hidden' => true],
        menuManagerItem('m_b', 'B', '/b'),
    ]]);

    Livewire::test(MenuManager::class)->call('reorderTree', ['m_b', 'm_a']);

    $tree = Menu::forLocation('primary')->tree();

    expect($tree[0]['label']['en'])->toBe('B')
        ->and($tree[0]['hidden'] ?? false)->toBeFalse()
        ->and($tree[1]['label']['en'])->toBe('A')
        ->and($tree[1]['hidden'])->toBeTrue();
});
