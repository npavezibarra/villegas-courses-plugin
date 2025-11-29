jQuery(document).ready(function ($) {
    console.log('VCP New Users Script Loaded');
    console.log('VCP_USERS:', typeof VCP_USERS !== 'undefined' ? VCP_USERS : 'undefined');

    // Auto-submit filter on change
    $('#vcp-period-filter').on('change', function () {
        $(this).closest('form').submit();
    });

    // Delete User with Event Delegation
    $(document).on('click', '.delete-user-btn', function (e) {
        e.preventDefault();
        console.log('Delete button clicked');
        const btn = $(this);
        const userId = btn.data('user');
        const nonce = btn.data('nonce');
        console.log('User ID:', userId, 'Nonce:', nonce);

        if (!confirm('Are you sure you want to delete this user? This action is irreversible and will delete all data associated with the user.')) {
            return;
        }

        btn.prop('disabled', true).text('Deleting...');

        if (typeof VCP_USERS === 'undefined' || !VCP_USERS.ajax) {
            alert('Error: VCP_USERS configuration missing.');
            console.error('VCP_USERS is undefined');
            btn.prop('disabled', false).text('Delete');
            return;
        }

        $.post(VCP_USERS.ajax, {
            action: 'vcp_delete_user',
            user_id: userId,
            nonce: nonce
        })
            .done((response) => {
                console.log('Delete response:', response);
                if (response.success) {
                    $('#user-' + userId).css('background', '#ffcccc').fadeOut(400, function () {
                        $(this).remove();
                    });
                } else {
                    alert(response.data?.message || 'Error deleting user.');
                    btn.prop('disabled', false).text('Delete');
                }
            })
            .fail((xhr, status, error) => {
                console.error('AJAX error:', status, error);
                alert('AJAX error. Please try again.');
                btn.prop('disabled', false).text('Delete');
            });
    });
});
