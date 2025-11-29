jQuery(document).ready(function ($) {
    var mediaUploader;

    $('#vcp-upload-logo-btn').click(function (e) {
        e.preventDefault();

        // If the uploader object has already been created, reopen the dialog
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        // Extend the wp.media object
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Seleccionar Logo del Correo',
            button: {
                text: 'Usar este logo'
            },
            multiple: false
        });

        // When a file is selected, grab the URL and set it as the text field's value
        mediaUploader.on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#vcp_email_logo').val(attachment.url);
            $('#vcp-logo-preview').attr('src', attachment.url).show();
        });

        // Open the uploader dialog
        mediaUploader.open();
    });

    $('#vcp-remove-logo-btn').click(function (e) {
        e.preventDefault();
        $('#vcp_email_logo').val('');
        $('#vcp-logo-preview').hide().attr('src', '');
    });
});
