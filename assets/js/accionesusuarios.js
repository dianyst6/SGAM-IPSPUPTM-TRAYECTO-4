document.addEventListener('DOMContentLoaded', function () {

    // =========================================================
    // --- LÓGICA DEL FORMULARIO DE REGISTRO ---
    // =========================================================
    // Nota: Es mejor buscar por ID específico para no confundirse con el form del modal
    const registerForm = document.querySelector('form');

    // Verificamos si existe el formulario de registro antes de ejecutar esta lógica
    if (registerForm && !registerForm.id.includes('edit')) {

        const submitBtn = registerForm.querySelector('button[type="submit"]');
        const allRequiredInputs = registerForm.querySelectorAll('input[required], select[required]');

        const pass1 = document.getElementById('password');
        const pass2 = document.getElementById('confirm_password');
        const passFeedback = document.getElementById('passwordFeedback');

        const usernameInput = document.getElementById("username");
        const usernameFeedback = document.getElementById("usernameFeedback");

        // --- Función Maestra de Validación ---
        function checkFormValidity() {
            let isFormValid = true;

            // A. Validar campos vacíos
            allRequiredInputs.forEach(input => {
                if (input.value.trim() === '') isFormValid = false;
            });

            // B. Validar contraseñas
            if (pass2 && pass2.value !== '') {
                if (pass1.value !== pass2.value) {
                    passFeedback.textContent = '❌ Las contraseñas no coinciden';
                    passFeedback.className = 'form-text text-danger';
                    pass2.classList.add('is-invalid');
                    isFormValid = false;
                } else {
                    passFeedback.textContent = '✔️ Las contraseñas coinciden';
                    passFeedback.className = 'form-text text-success';
                    pass2.classList.remove('is-invalid');
                    pass2.classList.add('is-valid');
                }
            } else if (pass2) {
                passFeedback.textContent = '';
                pass2.classList.remove('is-invalid', 'is-valid');
            }

            // C. Validar disponibilidad de usuario
            if (usernameInput && usernameInput.classList.contains('is-invalid')) {
                isFormValid = false;
            }

            // D. Aplicar estado al botón
            if (submitBtn) submitBtn.disabled = !isFormValid;
        }

        // Eventos
        allRequiredInputs.forEach(input => {
            input.addEventListener('input', checkFormValidity);
            input.addEventListener('change', checkFormValidity);
        });

        if (usernameInput) {
            usernameInput.addEventListener("input", function () {
                const username = usernameInput.value.trim();
                if (username.length < 3) return;

                const formData = new FormData();
                formData.append("username", username);

                fetch("/IPSPUPTM/app/configuracion/gestionusuario/verificar_usuario.php", {
                    method: "POST",
                    body: formData
                })
                    .then(res => res.text())
                    .then(data => {
                        if (data.trim() === "existe") {
                            usernameFeedback.textContent = "❌ Usuario no disponible.";
                            usernameFeedback.className = "form-text text-danger";
                            usernameInput.classList.add("is-invalid");
                        } else {
                            usernameFeedback.textContent = "✔️ Usuario disponible.";
                            usernameFeedback.className = "form-text text-success";
                            usernameInput.classList.remove("is-invalid");
                        }
                        checkFormValidity();
                    });
            });
        }

        // Toggle Contraseñas
        function toggleVisibility(buttonId, inputId, iconId) {
            const toggleBtn = document.getElementById(buttonId);
            const inputField = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (!toggleBtn) return;
            toggleBtn.addEventListener('click', () => {
                const type = inputField.getAttribute('type') === 'password' ? 'text' : 'password';
                inputField.setAttribute('type', type);
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
        // Ojitos del Formulario de Registro
        toggleVisibility('togglePassword', 'password', 'eyeIcon');
        toggleVisibility('toggleConfirmPassword', 'confirm_password', 'confirmEyeIcon');

        // Ojitos del Modal de Edición
        toggleVisibility('toggleEditPassword', 'edit_password', 'editEyeIcon');
        toggleVisibility('toggleEditConfirmPassword', 'edit_confirm_password', 'editConfirmEyeIcon')

        // Deshabilitar preguntas duplicadas
        const q1 = document.getElementById('pregunta_seguridad_id1');
        const q2 = document.getElementById('pregunta_seguridad_id2');
        if (q1 && q2) {
            function actualizarOpciones() {
                for (let option of q2.options) option.disabled = (option.value === q1.value && q1.value !== "");
                for (let option of q1.options) option.disabled = (option.value === q2.value && q2.value !== "");
                checkFormValidity();
            }
            q1.addEventListener('change', actualizarOpciones);
            q2.addEventListener('change', actualizarOpciones);
        }

        // Lógica Médico
        const roleSelector = document.getElementById('role_id');
        const camposMedico = document.getElementById('campos-medico');
        if (roleSelector && camposMedico) {
            roleSelector.addEventListener('change', function () {
                const inputsMedico = camposMedico.querySelectorAll('input, select');
                if (this.value === '3') {
                    camposMedico.style.display = 'block';
                    inputsMedico.forEach(i => i.setAttribute('required', 'required'));
                } else {
                    camposMedico.style.display = 'none';
                    inputsMedico.forEach(i => i.removeAttribute('required'));
                }
                checkFormValidity();
            });
        }

        if (submitBtn) submitBtn.disabled = true;
    }

    // =========================================================
    // --- LÓGICA DEL MODAL DE EDICIÓN ---
    // =========================================================
    const editModal = document.getElementById('editModal');
    const formEditar = document.getElementById('formEditar');

    // --- TU CÓDIGO ORIGINAL (INTACTO) ---
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            // 1. Seleccionamos inputs
            const p1Input = document.getElementById('edit_p1');
            const p2Input = document.getElementById('edit_p2');
            const roleInput = document.getElementById('edit_role_id');

            // 2. IMPORTANTE: Habilitamos todas las opciones primero 
            // para que el select permita seleccionar el valor que viene de la BD
            if (p1Input) p1Input.querySelectorAll('option').forEach(opt => opt.disabled = false);
            if (p2Input) p2Input.querySelectorAll('option').forEach(opt => opt.disabled = false);

            // 3. Asignamos valores
            document.getElementById('edit_id').value = button.getAttribute('data-id');
            document.getElementById('edit_username').value = button.getAttribute('data-username');
            document.getElementById('edit_r1').value = button.getAttribute('data-r1');
            document.getElementById('edit_r2').value = button.getAttribute('data-r2');

            if (roleInput) roleInput.value = button.getAttribute('data-role');
            if (p1Input) p1Input.value = button.getAttribute('data-p1');
            if (p2Input) p2Input.value = button.getAttribute('data-p2');

            // 4. Disparamos 'change' al final para que la lógica de validación
            // de tu formulario se ejecute con los datos ya cargados
            if (roleInput) roleInput.dispatchEvent(new Event('change'));
            if (p1Input) p1Input.dispatchEvent(new Event('change'));
            if (p2Input) p2Input.dispatchEvent(new Event('change'));

            console.log("Carga exitosa para ID:", button.getAttribute('data-id'));
        });
    }

    // --- LÓGICA FETCH (CON ALERTIFY) ---
    // --- LÓGICA FETCH (CON VALIDACIÓN DE CONTRASEÑA) ---
    if (formEditar) {
        formEditar.addEventListener('submit', function (e) {
            e.preventDefault(); // Evita que la página recargue

            // 1. CAPTURAMOS LOS VALORES DE LAS CONTRASEÑAS
            const pass = document.getElementById('edit_password').value;
            const confirmPass = document.getElementById('edit_confirm_password').value;

            // 2. VALIDACIÓN: Solo si el usuario escribió algo en el campo de contraseña
            if (pass !== "" || confirmPass !== "") {
                if (pass !== confirmPass) {
                    alertify.error(' Las contraseñas nuevas no coinciden.');
                    return; // 🛑 Detiene el flujo por completo, impidiendo el Fetch
                }
            }

            // Si las contraseñas coinciden (o están vacías), el código continúa normalmente:
            const formData = new FormData(this);

            fetch('/IPSPUPTM/app/configuracion/gestionusuario/actualizar/actualizar.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modalInstance = bootstrap.Modal.getInstance(editModal);
                        if (modalInstance) modalInstance.hide();

                        alertify.success(data.message);

                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        alertify.error("Error: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alertify.error("Ocurrió un error inesperado en el servidor.");
                });
        });
    }

    // =========================================================
    // --- DETECTOR DE ALERTAS EN LA URL (ALERTIFY) ---
    // =========================================================
    const urlParams = new URLSearchParams(window.location.search);

    // 1. Si viene un mensaje de éxito
    if (urlParams.has('mensaje')) {
        const mensaje = urlParams.get('mensaje');

        if (mensaje === 'usuario_eliminado') {
            alertify.success('¡Usuario eliminado correctamente!');
        } else if (mensaje === 'usuario_agregado') {
            alertify.success('¡Usuario registrado con éxito!'); // <-- NUEVA ALERTA
        }

        // Limpiamos la URL para evitar bucles con F5
        window.history.replaceState({}, document.title, "home.php?vista=usuarios");
    }

    // 2. Si viene un mensaje de error
    if (urlParams.has('error')) {
        let errorMsg = urlParams.get('error');

        if (errorMsg === 'id_no_proporcionado') {
            errorMsg = 'No se proporcionó el ID del usuario.';
        }

        alertify.error('❌ ' + errorMsg);
        window.history.replaceState({}, document.title, "home.php?vista=usuarios");
    }
});
