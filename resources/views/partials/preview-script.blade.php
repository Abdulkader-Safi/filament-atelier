{{--
    Editor plumbing, injected into the preview by PreviewController. Never
    reaches a public page, and no layout has to carry it.

    Two jobs, both of them about the iframe belonging to the editor around it:

    - Clicking a section selects it in the sidebar.
    - Clicking a link tells the editor where it points, instead of navigating
      the preview to a live page and leaving the editor describing something
      else. The editor decides what to do with it.
--}}
<script data-atelier-preview>
    (function () {
        var post = function (message) {
            parent.postMessage(Object.assign({ atelier: true }, message), '*');
        };

        document.addEventListener('click', function (event) {
            // A link first: it is the more specific intent, and a link inside
            // a section would otherwise only ever select the section.
            var link = event.target.closest('a[href]');

            if (link) {
                event.preventDefault();

                post({ type: 'navigate', href: link.href, target: link.target || '' });

                return;
            }

            var section = event.target.closest('[data-atelier-block]');

            if (section) {
                event.preventDefault();

                post({ type: 'select', id: section.dataset.atelierBlock });
            }
        });

        // A form in a block must not post from inside the editor. The client
        // is looking at a draft, and a submission from here would be a real
        // one against a page that may not exist publicly yet.
        document.addEventListener('submit', function (event) {
            event.preventDefault();

            post({ type: 'blocked', what: 'form' });
        });
    })();
</script>
