document.addEventListener('DOMContentLoaded', function () {
    const checkbox = document.getElementById('puntaje_privado_checkbox');
    if (!checkbox) return;

    const ajaxConfig = window.villegasAjax || {};
    const ajaxUrl = ajaxConfig.ajaxUrl || '';
    const nonce = ajaxConfig.privacyNonce || '';

    checkbox.addEventListener('change', function () {
        const isPrivate = checkbox.checked;
        const userId = parseInt(checkbox.dataset.userId || '0', 10);

        if (!ajaxUrl || !nonce || !userId) {
            console.error('No se pudo determinar la información necesaria para actualizar la privacidad del puntaje.');
            return;
        }

        fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'guardar_privacidad_puntaje',
                user_id: userId,
                puntaje_privado: isPrivate ? '1' : '0',
                nonce: nonce
            })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                console.error('❌ Error al guardar preferencia', data);
            }
        })
        .catch(error => {
            console.error('❌ Error de red o script', error);
        });
    });
});
