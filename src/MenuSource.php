<?php

declare(strict_types=1);

namespace Safi\Atelier;

/**
 * Lets an Eloquent model be picked as a menu item.
 *
 * A model implements this and the developer registers the class:
 *
 *     AtelierPlugin::make()->menuSources([\App\Models\Page::class]);
 *
 * getMenuLabel() and getMenuUrl() are read once, when the item is added, and
 * copied into the menu's own tree rather than kept as a live reference. A
 * menu is edited far more often than the models it points at are renamed,
 * and a live foreign key would mean every public page render resolves it,
 * with a 500 waiting for the day someone deletes the row. WordPress menus
 * work the same way: pick a post, and its title and URL become the menu
 * item's own values, not a pointer back to the post.
 */
interface MenuSource
{
    /** Shown as the group heading when picking what kind of item to add. */
    public static function menuSourceLabel(): string;

    /** @return array<int|string, string> id to label, for the picker. */
    public static function menuSourceOptions(): array;

    /** One instance by the id a {@see menuSourceOptions} key gave back, or null if it's gone. */
    public static function menuSourceFind(int|string $id): ?static;

    /** The label copied into the menu item when this instance is picked. */
    public function getMenuLabel(): string;

    /** The URL copied into the menu item when this instance is picked. */
    public function getMenuUrl(): string;
}
