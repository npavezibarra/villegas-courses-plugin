jQuery(document).ready(function($) {

    const container = $('#author-columns-container');
    const authorId = container.data('author-id');

    // Intercept pagination clicks INSIDE the container
    container.on('click', '.villegas-pagination a', function(e) {
        e.preventDefault();   // ⛔ STOP WordPress from navigating

        // Extract ?paged=#
        const link = $(this).attr('href');
        const urlParams = new URLSearchParams(link.split('?')[1]);
        const paged = urlParams.get('paged') || 1;

        $.post(villegasColumns.ajaxurl, {
            action: 'villegas_load_author_columns',
            author_id: authorId,
            paged: paged
        }, function(response) {
            container.html(response);
        });
    });

});
