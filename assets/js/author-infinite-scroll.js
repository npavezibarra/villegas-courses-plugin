jQuery(document).ready(function($){

    const container = $('#author-columns-container');

    if (!container.length) {
        return;
    }

    const authorId  = container.data('author-id');
    let currentPage = parseInt(container.data('current-page'), 10);
    const maxPages  = parseInt(container.data('max-pages'), 10);

    let loading = false;

    // Trigger on scroll INSIDE container
    container.on('scroll', function(){

        const nearBottom = container.scrollTop() + container.innerHeight() >= container[0].scrollHeight - 50;

        if (!loading && nearBottom) {

            if (currentPage >= maxPages) return;

            loading = true;
            currentPage++;

            $.post(villegasColumns.ajaxurl, {
                action: 'villegas_load_author_columns',
                author_id: authorId,
                paged: currentPage

            }, function(response){

                // Extract only the posts
                const newPosts = $(response).find('.columns-list').children();
                container.find('.columns-list').append(newPosts);

                container.attr('data-current-page', currentPage);

                loading = false;
            });
        }
    });

});
