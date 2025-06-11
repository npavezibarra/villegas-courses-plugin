document.addEventListener('DOMContentLoaded', function () {
    const checkbox = document.getElementById('puntaje_privado_checkbox');
    if (!checkbox || typeof puntajePrivadoData === 'undefined') return;

    checkbox.addEventListener('change', function () {
        const isPrivate = checkbox.checked;
        const userId = puntajePrivadoData.userId;

        console.log('Enviando AJAX:', {
            action: 'guardar_privacidad_puntaje',
            user_id: userId,
            puntaje_privado: isPrivate ? '1' : '0'
        });

        fetch(puntajePrivadoData.ajaxurl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'guardar_privacidad_puntaje',
                user_id: userId,
                puntaje_privado: isPrivate ? '1' : '0'
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.log('✔ Preferencia guardada');
            } else {
                console.error('❌ Error al guardar preferencia', data);
            }
        })
        .catch(error => {
            console.error('❌ Error de red o script', error);
        });
    });
});
