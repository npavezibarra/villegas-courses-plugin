document.addEventListener('DOMContentLoaded', function () {
    console.log("JavaScript cargado correctamente"); // Verificar que el script se cargó

    const toggleFormRegisterLink = document.getElementById('toggle-form');
    const toggleFormLoginLink = document.getElementById('toggle-form-login');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    // Comprobar si los elementos están presentes y visibles en la consola
    console.log("Elementos encontrados:", {
        toggleFormRegisterLink: toggleFormRegisterLink,
        toggleFormLoginLink: toggleFormLoginLink,
        loginForm: loginForm,
        registerForm: registerForm
    });

    // Función para mostrar el formulario de registro
    if (toggleFormRegisterLink) {
        toggleFormRegisterLink.addEventListener('click', function (event) {
            event.preventDefault();
            console.log("Clic en 'Regístrate aquí' - Cambiando a formulario de registro"); // Mensaje para verificar el clic
            if (loginForm && registerForm) {
                loginForm.style.display = 'none'; // Oculta el formulario de inicio de sesión
                registerForm.style.display = 'block'; // Muestra el formulario de registro
            } else {
                console.log("Error: No se encontró uno de los formularios.");
            }
        });
    } else {
        console.log("Error: No se encontró el enlace para 'toggle-form'.");
    }

    // Función para mostrar el formulario de inicio de sesión
    if (toggleFormLoginLink) {
        toggleFormLoginLink.addEventListener('click', function (event) {
            event.preventDefault();
            console.log("Clic en 'Inicia sesión aquí' - Cambiando a formulario de inicio de sesión"); // Mensaje para verificar el clic
            if (loginForm && registerForm) {
                registerForm.style.display = 'none'; // Oculta el formulario de registro
                loginForm.style.display = 'block'; // Muestra el formulario de inicio de sesión
            } else {
                console.log("Error: No se encontró uno de los formularios.");
            }
        });
    } else {
        console.log("Error: No se encontró el enlace para 'toggle-form-login'.");
    }
});
