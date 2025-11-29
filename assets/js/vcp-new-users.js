jQuery(document).ready(function ($) {
    console.log('VCP New Users Script Loaded');
    console.log('VCP_USERS:', typeof VCP_USERS !== 'undefined' ? VCP_USERS : 'undefined');

    // Auto-submit filter on change
    $('#vcp-period-filter').on('change', function () {
        $(this).closest('form').submit();
    });

    // Select All Checkbox
    $('#cb-select-all-1').on('change', function () {
        const isChecked = $(this).is(':checked');
        $('.user-checkbox').prop('checked', isChecked);
        toggleBulkDeleteButton();
    });

    // Individual Checkbox
    $(document).on('change', '.user-checkbox', function () {
        toggleBulkDeleteButton();
        // Update "Select All" state
        const allChecked = $('.user-checkbox').length === $('.user-checkbox:checked').length;
        $('#cb-select-all-1').prop('checked', allChecked);
    });

    function toggleBulkDeleteButton() {
        const checkedCount = $('.user-checkbox:checked').length;
        if (checkedCount > 0) {
            $('#vcp-bulk-delete-btn').fadeIn(200).text('Eliminar seleccionados (' + checkedCount + ')');
        } else {
            $('#vcp-bulk-delete-btn').fadeOut(200);
        }
    }

    // Bulk Delete Action
    $('#vcp-bulk-delete-btn').on('click', function () {
        const btn = $(this);
        const selectedUsers = [];
        $('.user-checkbox:checked').each(function () {
            selectedUsers.push($(this).val());
        });

        if (selectedUsers.length === 0) return;

        if (!confirm('¿Estás seguro de que deseas eliminar ' + selectedUsers.length + ' usuarios? Esta acción es irreversible.')) {
            return;
        }

        btn.prop('disabled', true).text('Eliminando...');

        // Create a nonce specifically for bulk delete if needed, or reuse a generic one if available.
        // Since we didn't pass a specific bulk nonce in the localized script, we might need to rely on the per-user nonce or add one.
        // However, for bulk actions, it's better to have a general nonce.
        // Let's check if we can grab a nonce from one of the rows or if we need to update the PHP to pass a bulk nonce.
        // UPDATE: I added 'vcp_bulk_delete_nonce' check in PHP, but didn't localize it.
        // I need to update the PHP to localize 'bulk_nonce' or grab it from the page.
        // For now, let's assume we need to add it to the localized script.
        // Wait, I can't easily update PHP and JS in one go.
        // Let's use a workaround: The PHP expects 'vcp_bulk_delete_nonce'.
        // I will update the PHP in the next step to localize this nonce.
        // For now, I'll write the JS code expecting VCP_USERS.bulk_nonce.

        $.post(VCP_USERS.ajax, {
            action: 'vcp_bulk_delete_users',
            user_ids: selectedUsers,
            nonce: VCP_USERS.bulk_nonce
        })
            .done((response) => {
                console.log('Bulk delete response:', response);
                if (response.success) {
                    selectedUsers.forEach(id => {
                        $('#user-' + id).css('background', '#ffcccc').fadeOut(400, function () {
                            $(this).remove();
                        });
                    });
                    btn.hide().prop('disabled', false);
                    $('#cb-select-all-1').prop('checked', false);
                } else {
                    alert(response.data?.message || 'Error al eliminar usuarios.');
                    btn.prop('disabled', false).text('Eliminar seleccionados');
                }
            })
            .fail((xhr, status, error) => {
                console.error('AJAX error:', status, error);
                alert('Error de AJAX. Por favor, inténtalo de nuevo.');
                btn.prop('disabled', false).text('Eliminar seleccionados');
            });
    });

    // Delete User with Event Delegation
    $(document).on('click', '.delete-user-btn', function (e) {
        e.preventDefault();
        console.log('Delete button clicked');
        const btn = $(this);
        const userId = btn.data('user');
        const nonce = btn.data('nonce');
        console.log('User ID:', userId, 'Nonce:', nonce);

        if (!confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción es irreversible y eliminará todos los datos asociados al usuario.')) {
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
    // Resend Confirmation Email
    $(document).on('click', '.vcp-resend-confirmation', function (e) {
        e.preventDefault();
        const btn = $(this);
        const userId = btn.data('user');
        const nonce = btn.data('nonce');
        const originalIcon = btn.html();

        btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="line-height: 1.3; animation: spin 2s linear infinite;"></span>');

        $.post(VCP_USERS.ajax, {
            action: 'vcp_resend_confirmation',
            user_id: userId,
            nonce: nonce
        })
            .done((response) => {
                if (response.success) {
                    alert('Correo de confirmación reenviado exitosamente.');
                } else {
                    alert(response.data?.message || 'Error al reenviar el correo.');
                }
            })
            .fail((xhr, status, error) => {
                console.error('AJAX error:', status, error);
                alert('Error de conexión. Por favor, inténtalo de nuevo.');
            })
            .always(() => {
                btn.prop('disabled', false).html(originalIcon);
            });
    });
});

// Add spin animation style
$('<style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>').appendTo('head');
