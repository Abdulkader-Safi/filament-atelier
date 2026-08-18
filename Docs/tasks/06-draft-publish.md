# 06. Draft, publish, preview links

> **Status, 18 Aug 2026.** The two-column flow is done and is the part that matters.
> Revisions are not built at all, including the table, so a deleted section is gone.
> Unpublish exists on the model with no button, and the housekeeping items are blocked
> on features that do not exist yet.

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
- [~] Unpublish, returning the page to draft without losing content. `Page::unpublish()` exists and is correct. Nothing in the panel calls it, so taking a page down means editing the database.

### Revisions

**None of this exists**, starting with the table (02). Publishing overwrites
`published_content` with no snapshot, so there is no way back to last week's page and
no way back from a deleted section. The editor's delete confirm says the action is not
reversible, and it is telling the truth.

- [ ] Snapshot the full tree into `page_revisions` on every publish.
- [ ] Record who published and when.
- [ ] Keep the last N, configurable, with a default that won't grow the table forever. `atelier.revisions.keep` is in the config file and read by nothing.
- [ ] A restore method on the model, copying a revision's content back into `draft_content`. No UI yet, but the method should exist and be tested.

### Preview links

- [x] `temporarySignedRoute` to the preview controller for the shareable link.
- [x] Configurable expiry. `atelier.preview.link_expiry_minutes`, 24 hours by default.
- [ ] Optional rotatable `preview_token` for a stable link, and a way to rotate it. No column, no rotation. Every shared link expires and there is no stable one.
- [x] `noindex` and no analytics on every preview response. Both a meta tag and an `X-Robots-Tag` header, plus `Cache-Control: no-store`.
- [x] The link renders the draft in a chosen locale.

### Housekeeping

- [!] Regenerate the sitemap on publish and on unpublish (feature 07). Blocked: there is no sitemap. Carried to 11.
- [!] Clear any cached page output on publish (feature 09). Blocked: there is no page cache. Carried to 09.

## Done when

- Editing a draft never changes the live page until Publish is clicked (PRD criterion 8).
- A preview link opens the draft for someone with no panel access, expires, and carries `noindex`.
- A publish leaves a revision row behind, and restoring it in tinker brings the old content back into the draft.

## Note

A plain enum `status` column is enough. `spatie/laravel-model-states` only earns its place if a real approval or scheduling workflow arrives, and scheduled publishing is already parked in v2.
