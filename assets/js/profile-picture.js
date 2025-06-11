document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form.woocommerce-EditAccountForm');
    const fileInput = document.getElementById('profile_picture');
    const preview = document.getElementById('villegas-profile-preview');

    // Asegurar el enctype correcto
    if (form && !form.hasAttribute('enctype')) {
        form.setAttribute('enctype', 'multipart/form-data');
    }

    // Previsualizar imagen antes de enviarla
    if (fileInput && preview) {
        fileInput.addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
