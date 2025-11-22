let cropper = null;

function villegasAuthorAvatarInit() {
    const input = document.getElementById('author-upload-input');
    const modal = document.getElementById('avatar-cropper-modal');
    const img = document.getElementById('avatar-cropper-image');
    const saveBtn = document.getElementById('avatar-cropper-save');
    const cancelBtn = document.getElementById('avatar-cropper-cancel');
    const avatarButton = window.jQuery ? window.jQuery('[data-avatar-toggle]') : null;
    const avatarImage = document.querySelector('.profile-avatar img');
    const uploadStatus = document.querySelector('.upload-status');
    const avatarOverlay = document.querySelector('.avatar-overlay');

    if (!input || !modal || !img || !saveBtn || !cancelBtn) {
        return;
    }

    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const maxSize = 1024 * 1024; // 1MB

    const resetModal = () => {
        modal.classList.add('hidden');
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        img.removeAttribute('src');
        input.value = '';
    };

    const startCropper = (file) => {
        if (!file) {
            return;
        }

        if (file.size > maxSize) {
            if (uploadStatus) {
                uploadStatus.textContent = 'La imagen debe ser menor a 1MB.';
            }
            input.value = '';
            return;
        }

        if (!allowedTypes.includes(file.type)) {
            if (uploadStatus) {
                uploadStatus.textContent = 'Formatos permitidos: JPG, PNG o WEBP.';
            }
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            img.src = e.target.result;
            modal.classList.remove('hidden');

            if (cropper) {
                cropper.destroy();
            }

            cropper = new Cropper(img, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                responsive: true,
                zoomable: true,
                background: false,
            });
        };
        reader.readAsDataURL(file);
    };

    if (avatarButton && avatarButton.length) {
        avatarButton.on('click', () => input.click());
    }

    input.addEventListener('change', (evt) => {
        const file = evt.target.files[0];

        if (!file) {
            if (uploadStatus) {
                uploadStatus.textContent = 'Sin archivo seleccionado';
            }
            return;
        }

        startCropper(file);
    });

    cancelBtn.addEventListener('click', resetModal);

    saveBtn.addEventListener('click', () => {
        if (!cropper) {
            return;
        }

        const canvas = cropper.getCroppedCanvas({
            width: 280,
            height: 280,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        const croppedImageBase64 = canvas.toDataURL('image/png');

        const ajaxUrl = (window.villegasAvatar && villegasAvatar.ajaxurl) || window.ajaxurl;
        if (!ajaxUrl || !window.jQuery) {
            if (uploadStatus) {
                uploadStatus.textContent = 'Ocurrió un error al subir la imagen.';
            }
            resetModal();
            return;
        }

        if (uploadStatus) {
            uploadStatus.textContent = 'Subiendo…';
        }

        window.jQuery.post(
            ajaxUrl,
            {
                action: 'villegas_save_profile_picture',
                nonce: (window.villegasAvatar && villegasAvatar.nonce) || '',
                image: croppedImageBase64,
            },
            (response) => {
                if (response.success) {
                    const newUrl = response.data?.url || response.url;
                    if (newUrl && avatarImage) {
                        avatarImage.src = newUrl;
                    }
                    if (avatarOverlay) {
                        avatarOverlay.textContent = 'Cambiar foto';
                    }
                    if (uploadStatus) {
                        uploadStatus.textContent = 'Imagen actualizada correctamente';
                    }
                } else {
                    if (uploadStatus) {
                        uploadStatus.textContent = response.data?.message || response.data || 'No se pudo actualizar la imagen.';
                    }
                    if (response.data?.message) {
                        // eslint-disable-next-line no-console
                        console.error(response.data.message);
                    }
                }
            }
        )
            .fail(() => {
                if (uploadStatus) {
                    uploadStatus.textContent = 'Ocurrió un error al subir la imagen.';
                }
            })
            .always(() => {
                resetModal();
            });
    });
}

document.addEventListener('DOMContentLoaded', villegasAuthorAvatarInit);
