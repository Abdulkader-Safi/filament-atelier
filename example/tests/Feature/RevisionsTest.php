<?php

declare(strict_types=1);

use App\Models\User;
use Safi\Atelier\Models\Page;

use function Pest\Laravel\actingAs;

function tree(string $heading): array
{
    return [[
        'id' => 'b_one',
        'type' => 'hero',
        'attributes' => ['heading' => ['en' => $heading]],
        'children' => [],
    ]];
}

it('leaves a revision behind on every publish', function () {
    $page = Page::create(['title' => 'Home', 'draft_content' => tree('First')]);
    $page->publish();

    $page->update(['draft_content' => tree('Second')]);
    $page->publish();

    expect($page->revisions()->count())->toBe(2)
        // Newest first, and it holds what was published, not the draft.
        ->and($page->revisions()->first()->content)->toBe(tree('Second'));
});

it('records who published', function () {
    $user = User::factory()->create();
    actingAs($user);

    $page = Page::create(['title' => 'Home', 'draft_content' => tree('First')]);
    $page->publish();

    expect($page->revisions()->first()->created_by)->toBe($user->id);
});

it('keeps only the configured number of revisions', function () {
    config()->set('atelier.revisions.keep', 3);

    $page = Page::create(['title' => 'Home']);

    foreach (range(1, 6) as $n) {
        $page->update(['draft_content' => tree("Version {$n}")]);
        $page->publish();
    }

    expect($page->revisions()->count())->toBe(3)
        ->and($page->revisions()->first()->content)->toBe(tree('Version 6'))
        // The oldest three are gone, not the newest three.
        ->and($page->revisions()->get()->last()->content)->toBe(tree('Version 4'));
});

it('restores a revision into the draft and leaves the live page alone', function () {
    $page = Page::create(['title' => 'Home', 'draft_content' => tree('First')]);
    $page->publish();
    $old = $page->revisions()->first();

    $page->update(['draft_content' => tree('Second')]);
    $page->publish();

    $page->restoreRevision($old);

    expect($page->fresh()->draft())->toBe(tree('First'))
        ->and($page->fresh()->published())->toBe(tree('Second'))
        ->and($page->fresh()->hasUnpublishedChanges())->toBeTrue();
});

it('unpublishes from the panel without losing content', function () {
    actingAs(User::factory()->create());

    $page = Page::create(['title' => 'Home', 'draft_content' => tree('First')]);
    $page->setSlugs(['en' => 'home', 'ar' => 'home-ar']);
    $page->publish();

    Livewire\Livewire::test(
        Safi\Atelier\Filament\Resources\PageResource\Pages\EditPageSettings::class,
        ['record' => $page->getKey()],
    )->callAction('unpublish');

    expect($page->fresh()->isPublished())->toBeFalse()
        ->and($page->fresh()->published())->toBe(tree('First'));

    Pest\Laravel\get('/home')->assertNotFound();
});
