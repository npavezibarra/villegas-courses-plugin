jQuery(document).ready(function ($) {
    $('.delete-user-btn').on('click', function () {
        const btn = $(this);
        const userId = btn.data('user');
        const nonce = btn.data('nonce');

        if (!confirm('Are you sure you want to delete this user? This cannot be undone.')) {
            return;
        }

        btn.prop('disabled', true).text('Deleting...');

        $.post(VCP_USERS.ajax, {
            action: 'vcp_delete_user',
            user_id: userId,
            nonce: nonce
        })
            .done((response) => {
                if (response.success) {
                    $('#user-' + userId).fadeOut(300, function () {
                        $(this).remove();
                    });
                } else {
                    alert(response.data?.message || 'Error deleting user.');
                    btn.prop('disabled', false).text('Delete');
                }
            })
            .fail(() => {
                alert('AJAX error.');
                btn.prop('disabled', false).text('Delete');
            });
    });
});
