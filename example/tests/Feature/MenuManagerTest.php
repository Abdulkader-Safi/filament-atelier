<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use Safi\Atelier\Filament\Pages\MenuManager;
use Safi\Atelier\Models\Menu;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::factory()->create()));

function menuItem(string $id, string $label, string $url): array
{
    return ['id' => $id, 'label' => ['en' => $label], 'url' => $url, 'target' => '_self', 'children' => []];
}

it('opens on the first registered location and saves items as they change, with no explicit save call', function () {
    Livewire::test(MenuManager::class)
        ->assertSet('location', 'primary')
        ->set('data.items', [menuItem('m_one', 'About', '/about')]);

    $tree = Menu::forLocation('primary')->tree();

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['label']['en'])->toBe('About')
        ->and($tree[0]['url'])->toBe('/about');
});

it('switches locations and loads that location on its own tree', function () {
    Menu::forLocation('primary')->update(['items' => [menuItem('m_p', 'Primary item', '/')]]);
    Menu::forLocation('footer')->update(['items' => [menuItem('m_f', 'Footer item', '/contact')]]);

    // A Repeater re-keys its rows once the form has hydrated them, so read
    // the first one positionally rather than assuming the key is 0.
    $component = Livewire::test(MenuManager::class);

    expect(collect($component->get('data')['items'])->first()['label']['en'])->toBe('Primary item');

    $component->set('location', 'footer');

    expect(collect($component->get('data')['items'])->first()['label']['en'])->toBe('Footer item');
});

it('keeps one row per location however often it saves', function () {
    Livewire::test(MenuManager::class)->set('data.items', [menuItem('m_a', 'A', '/a')]);
    Livewire::test(MenuManager::class)->set('data.items', [menuItem('m_b', 'B', '/b')]);

    expect(Menu::where('location', 'primary')->count())->toBe(1)
        ->and(Menu::forLocation('primary')->tree()[0]['label']['en'])->toBe('B');
});

it('nests one level of children under an item', function () {
    Livewire::test(MenuManager::class)->set('data.items', [
        [...menuItem('m_parent', 'Parent', '/parent'), 'children' => [
            menuItem('m_child', 'Child', '/parent/child'),
        ]],
    ]);

    $tree = Menu::forLocation('primary')->tree();

    expect($tree[0]['children'])->toHaveCount(1)
        ->and($tree[0]['children'][0]['label']['en'])->toBe('Child');
});
