jQuery(document).ready(function($) {
    const ajaxUrl = (typeof villegasQuizStyleData !== 'undefined' && villegasQuizStyleData.ajaxurl)
        ? villegasQuizStyleData.ajaxurl
        : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
    const securityNonce = (typeof villegasQuizStyleData !== 'undefined' && villegasQuizStyleData.security)
        ? villegasQuizStyleData.security
        : '';

    $('.select-image-button').click(function(e) {
        e.preventDefault();
        const button = $(this);
        const row = button.closest('tr');
        const imageField = row.find('.image-id-field');
        const previewContainer = row.find('.preview-image');
        const quizID = row.data('quiz-id');

        const mediaFrame = wp.media({
            title: 'Seleccionar Imagen de Estilo',
            button: { text: 'Usar esta imagen' },
            multiple: false
        });

        mediaFrame.on('select', function() {
            const attachment = mediaFrame.state().get('selection').first().toJSON();
            imageField.val(attachment.id);
            previewContainer.html('<img src="' + attachment.sizes.thumbnail.url + '" style="max-width:80px;">');

            // Guardar por AJAX
            $.post(ajaxUrl, {
                action: 'guardar_imagen_estilo_quiz',
                quiz_id: quizID,
                image_id: attachment.id,
                security: securityNonce
            }, function(response) {
                if (response.success) {
                    console.log('Imagen guardada');
                } else {
                    console.log('Error al guardar la imagen');
                }
            });
        });

        mediaFrame.open();
    });
});
