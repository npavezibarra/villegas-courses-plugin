jQuery(document).ready(function ($) {
    const container = $('#author-columns-container');

    if (!container.length) {
        return;
    }

    const authorId = container.data('author-id');
    let isLoading = false;

    const getPageFromHref = (href) => {
        try {
            const url = new URL(href, window.location.href);
            return parseInt(url.searchParams.get('paged'), 10) || 1;
        } catch (error) {
            return 1;
        }
    };

    const updateHistory = (paged) => {
        const newUrl = new URL(window.location.href);

        if (paged > 1) {
            newUrl.searchParams.set('paged', paged);
        } else {
            newUrl.searchParams.delete('paged');
        }

        window.history.pushState({ paged }, '', newUrl.toString());
    };

    const loadColumnsPage = (paged, pushState = true) => {
        if (isLoading) {
            return;
        }

        isLoading = true;

        $.post(
            villegasColumns.ajaxurl,
            {
                action: 'villegas_load_author_columns',
                author_id: authorId,
                paged,
            },
            (response) => {
                container.html(response);

                if (pushState) {
                    updateHistory(paged);
                }
            }
        ).always(() => {
            isLoading = false;
        });
    };

    container.on('click', '.page-numbers a', function (event) {
        event.preventDefault();

        const paged = getPageFromHref($(this).attr('href'));
        loadColumnsPage(paged);
    });

    window.addEventListener('popstate', (event) => {
        const paged = event.state && event.state.paged
            ? event.state.paged
            : parseInt(new URL(window.location.href).searchParams.get('paged'), 10) || 1;

        loadColumnsPage(paged, false);
    });
});
