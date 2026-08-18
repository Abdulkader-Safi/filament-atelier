# Security policy

## Reporting a vulnerability

Report privately, not in a public issue.

- **Preferred:** [open a private advisory](https://github.com/Abdulkader-Safi/filament-atelier/security/advisories/new) through GitHub's private vulnerability reporting.
- **Or email:** safi.abdulkader@gmail.com with `filament-atelier` in the subject.

Include the version you are on, what an attacker can do with the bug, and the smallest set of steps that reproduces it. A failing test or a curl command is worth more than a paragraph.

Please do not open a public issue, a discussion or a pull request for a vulnerability until a fix is out. Atelier renders public pages from content a client edits, so a disclosed bug is exploitable on every site running it the moment it is published.

## What to expect

Atelier is maintained by one person, so these are honest targets rather than a contractual SLA:

- An acknowledgement within 3 working days.
- An assessment, including whether it is in scope, within 7 days.
- A fix released for a confirmed vulnerability as fast as the severity warrants, with a credit in the release notes and the advisory unless you would rather stay anonymous.

If you have not heard back in a week, send a follow-up. Mail gets lost.

## Supported versions

While the package is below 1.0.0, only the latest release gets fixes. There are no backports to earlier 0.x tags, so upgrading is the fix path.

| Version | Supported |
| ------- | --------- |
| 0.1.x   | Yes, latest patch only |

## Scope

In scope, and treated as a vulnerability:

- Anything that lets a panel user reach code execution, including template injection through block content.
- Anything that lets an unauthenticated visitor read draft or unpublished content.
- Stored XSS reachable through a block field that a lower-privileged panel user can edit.
- A preview link that resolves without a valid signature, or after it has expired.
- Slug or path handling that serves a page the request did not ask for, or escapes the intended route.

Out of scope, and better as a normal issue:

- Findings that need an already-compromised admin account, since a panel user with page-editing rights can legitimately publish arbitrary content.
- The raw HTML block executing the HTML that was deliberately typed into it. That is the feature.
- Vulnerabilities in Laravel, Filament, Livewire or another dependency. Report those upstream. If Atelier's use of one makes an upstream issue worse, that part is in scope here.
- Anything requiring physical access, a modified `config/atelier.php`, or `APP_DEBUG=true` in production.

## A note on the escape hatches

Two parts of Atelier are dangerous by design and documented as such:

- The raw HTML block renders what a panel user types. It is not compiled as Blade and never should be. `Blade::render()` on user input turns a textarea into remote code execution, and the ban on it is a standing rule in the project's own docs.
- The public render is Blade, server-side, from stored JSON. Block views escape by default. A block view that uses `{!! !!}` on a client-editable field is a bug, and reporting one is welcome.
