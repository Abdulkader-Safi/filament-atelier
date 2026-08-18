# 06. Draft, publish, preview links

> **Status, 18 Aug 2026.** Done, apart from a stable preview token and the two
> housekeeping items, which are blocked on features that do not exist yet. Revisions and
> the unpublish button landed on 18 Aug.

## What it is

Two JSON columns. Editing writes `draft_content`. Publish copies it to `published_content`, sets `published_at`, snapshots the tree into `page_revisions` and regenerates the sitemap. The public route reads `published_content` and nothing else.

Plus a shareable preview link: a signed, expiring URL that renders the draft, for showing a client or a colleague before it goes live.

## Why we're building it

A client editing a live page is a client publishing a half-finished sentence to the internet. Two columns is the cheapest possible way to make that impossible, and it's the baseline everything else assumes.

Revisions solve a different problem from the two columns: not "don't publish yet" but "put back what it was last week". Both are wanted, and they're separate mechanisms.

## How it should feel

Safe. The client should never be uncertain whether what they're doing is live. Save and Publish must not look alike, and the editor should state plainly what the page's current state is: draft with unpublished changes, or published and matching.

Publishing should feel like a deliberate act. Restoring a revision should feel like an undo, not a database operation.

## In the dashboard

- **Save** in the toolbar: writes the draft. Nothing on the public site changes.
- **Publish** in the toolbar: pushes the draft live. Confirms if the page has never been published.
- **A status badge** near the title: `Draft`, `Published`, or `Published, with unpublished changes`. The third one is the important one and the one most CMSs get wrong.
- **Copy preview link:** an action that generates a signed, expiring URL to the draft, for sending to someone without a panel login.
- **View live page:** a plain link, only when published.

Revisions in v1 are stored but not browsable. The UI for restore and diff is v2, and the PRD says so.

## Tasks

### Two-column flow

- [x] All editing writes `draft_content`.
- [x] Publish copies draft to published, sets `status` and `published_at`.
- [x] Public route reads `published_content` only, never the draft.
- [x] Unpublished page returns 404 on the public route. Covered by a test.
- [x] Status badge, including the "published with unpublished changes" state (compare the two columns). In the editor toolbar and in the resource table.
- [x] Unpublish, returning the page to draft without losing content. A confirmed action on the page settings screen, shown only while the page is published.

### Revisions

Built 18 Aug 2026. `atelier_page_revisions` ships as its own migration rather than as a
change to the first one, so an existing install picks it up by publishing migrations again.

- [x] Snapshot the full tree into `page_revisions` on every publish. The published tree, not the draft, so a revision is always something that was once live. That is what "put it back" means to the person asking.
- [x] Record who published and when. `created_by` is nullable, because a seeder or a tinker publish has no user and should not fail.
- [x] Keep the last N, configurable, with a default that won't grow the table forever. `atelier.revisions.keep` is now read, defaulting to 20, pruned on every publish.
- [x] A restore method on the model, copying a revision's content back into `draft_content`. No UI yet, but the method should exist and be tested. `Page::restoreRevision()`, deliberately writing the draft and not the published column: restoring is an undo the editor then looks at and publishes, not a silent change to the live site.

### Preview links

- [x] `temporarySignedRoute` to the preview controller for the shareable link.
- [x] Configurable expiry. `atelier.preview.link_expiry_minutes`, 24 hours by default.
- [ ] Optional rotatable `preview_token` for a stable link, and a way to rotate it. No column, no rotation. Every shared link expires and there is no stable one.
- [x] `noindex` and no analytics on every preview response. Both a meta tag and an `X-Robots-Tag` header, plus `Cache-Control: no-store`.
- [x] The link renders the draft in a chosen locale.

### Housekeeping

- [!] Regenerate the sitemap on publish and on unpublish (feature 07). Not needed: the sitemap built in 11 reads the pages table per request, so publishing changes it with nothing to invalidate.
- [!] Clear any cached page output on publish (feature 09). Blocked: there is no page cache. Carried to 09.

## Done when

- Editing a draft never changes the live page until Publish is clicked (PRD criterion 8).
- A preview link opens the draft for someone with no panel access, expires, and carries `noindex`.
- A publish leaves a revision row behind, and restoring it in tinker brings the old content back into the draft.

## Note

A plain enum `status` column is enough. `spatie/laravel-model-states` only earns its place if a real approval or scheduling workflow arrives, and scheduled publishing is already parked in v2.
