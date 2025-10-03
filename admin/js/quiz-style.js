jQuery(document).ready(function($) {
    const ajaxUrl = (typeof villegasQuizStyleData !== 'undefined' && villegasQuizStyleData.ajaxurl)
        ? villegasQuizStyleData.ajaxurl
        : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
    const securityNonce = (typeof villegasQuizStyleData !== 'undefined' && villegasQuizStyleData.security)
        ? villegasQuizStyleData.security
        : '';
    const speak = (window.wp && wp.a11y && typeof wp.a11y.speak === 'function') ? wp.a11y.speak : null;

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
                if (speak) {
                    if (response.success) {
                        speak('Imagen de estilo guardada.');
                    } else {
                        speak('No fue posible guardar la imagen de estilo.');
                    }
                }
            });
        });

        mediaFrame.open();
    });
});
