jQuery(function ($) {
    var input = $('#villegas-author-search');
    var ul = $('#villegas-authors-selected');
    var hidden = $('#villegas-authors-hidden');

    function updateHiddenField() {
        var ids = [];
        ul.find('li').each(function () {
            var id = $(this).data('user-id');
            if ($.inArray(id, ids) === -1) {
                ids.push(id);
            }
        });
        hidden.val(JSON.stringify(ids));
    }

    function authorExists(id) {
        return ul.find("li[data-user-id='" + id + "']").length > 0;
    }

    input.autocomplete({
        source: function (request, response) {
            $.get(VillegasAuthorSearch.ajaxurl, {
                action: 'villegas_search_user',
                term: request.term,
                _ajax_nonce: VillegasAuthorSearch.nonce,
            }, response);
        },
        select: function (event, ui) {
            if (authorExists(ui.item.id)) {
                input.val('');
                return false;
            }

            ul.append(
                "<li data-user-id='" + ui.item.id + "'>" +
                    ui.item.label +
                    "<a href='#' class='villegas-remove-author' style='color:red;margin-left:5px;'>×</a>" +
                '</li>'
            );
            updateHiddenField();
            input.val('');
            return false;
        },
    });

    ul.on('click', '.villegas-remove-author', function (e) {
        e.preventDefault();
        $(this).closest('li').remove();
        updateHiddenField();
    });
});
