document.addEventListener('DOMContentLoaded', function () {
    const toggleFormLink = document.getElementById('toggle-form');
    const toggleFormLoginLink = document.getElementById('toggle-form-login');
    const authForm = document.getElementById('auth-form');
    const registrationForm = document.getElementById('registration-form');

    // Mostrar el formulario de registro
    if (toggleFormLink) {
        toggleFormLink.addEventListener('click', function (event) {
            event.preventDefault();
            authForm.style.display = 'none'; // Oculta el formulario de inicio de sesión
            registrationForm.style.display = 'block'; // Muestra el formulario de registro
        });
    }

    // Mostrar el formulario de inicio de sesión
    if (toggleFormLoginLink) {
        toggleFormLoginLink.addEventListener('click', function (event) {
            event.preventDefault();
            registrationForm.style.display = 'none'; // Oculta el formulario de registro
            authForm.style.display = 'block'; // Muestra el formulario de inicio de sesión
        });
    }
});
