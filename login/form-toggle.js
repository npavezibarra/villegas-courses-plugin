document.addEventListener('DOMContentLoaded', function () {
    const toggleFormRegisterLink = document.getElementById('toggle-form');
    const toggleFormLoginLink = document.getElementById('toggle-form-login');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (!loginForm || !registerForm) {
        return;
    }

    if (toggleFormRegisterLink) {
        toggleFormRegisterLink.addEventListener('click', function (event) {
            event.preventDefault();
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
        });
    }

    if (toggleFormLoginLink) {
        toggleFormLoginLink.addEventListener('click', function (event) {
            event.preventDefault();
            registerForm.style.display = 'none';
            loginForm.style.display = 'block';
        });
    }
});
