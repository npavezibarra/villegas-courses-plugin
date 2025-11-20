jQuery(document).ready(function ($) {
    const container = $('#author-columns-container');

    if (!container.length) {
        return;
    }

    const authorId = container.data('author-id');
    let isLoading = false;

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

    container.on('click', '.villegas-pagination a', function (event) {
        event.preventDefault();

        const url = $(this).attr('href');
        const params = new URLSearchParams(url.split('?')[1]);
        const paged = parseInt(params.get('paged'), 10) || 1;
        loadColumnsPage(paged);
    });

    window.addEventListener('popstate', (event) => {
        const paged = event.state && event.state.paged
            ? event.state.paged
            : parseInt(new URL(window.location.href).searchParams.get('paged'), 10) || 1;

        loadColumnsPage(paged, false);
    });
});
