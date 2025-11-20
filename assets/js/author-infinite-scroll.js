jQuery(document).ready(function($){

    const container = $('#author-columns-container');
    const authorId = container.data('author-id');
    let currentPage = parseInt(container.data('current-page'), 10);
    const maxPages = parseInt(container.data('max-pages'), 10);
    let loading = false;

    container.on('scroll', function() {

        // Load if 80% scrolled & not already loading
        if (!loading && container.scrollTop() + container.innerHeight() >= container[0].scrollHeight * 0.8) {

            if (currentPage >= maxPages) return;

            loading = true;
            currentPage++;

            $.post(villegasColumns.ajaxurl, {
                action: 'villegas_load_author_columns',
                author_id: authorId,
                paged: currentPage
            }, function(response){

                const newContent = $(response).find('.columns-list').html();
                container.find('.columns-list').append(newContent);

                loading = false;
            });
        }
    });

});
