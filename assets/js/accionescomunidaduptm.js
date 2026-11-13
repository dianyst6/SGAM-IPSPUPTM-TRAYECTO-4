// Comunidad UPTM = personas sin rol de afiliado ni beneficiario (tabla persona).

function cargarDatosModal(modal, cedula, idPersona) {
    const inputcedula = modal.querySelector('#cedula');
    const inputnombre = modal.querySelector('#nombre');
    const inputapellido = modal.querySelector('#apellido');
    const inputtelefono = modal.querySelector('#telefono');

    const formData = new FormData();
    if (idPersona) {
        formData.append('id_persona', idPersona);
    } else {
        formData.append('cedula', cedula);
    }

    fetch('/IPSPUPTM/app/comunidaduptm/getcomunidad.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error: ', data.error);
                alertify.error(data.error);
                return;
            }
            if (inputcedula) inputcedula.value = data.cedula || '';
            if (inputnombre) inputnombre.value = data.nombre || '';
            if (inputapellido) inputapellido.value = data.apellido || '';
            if (inputtelefono) inputtelefono.value = data.telefono || '';

            const hiddenId = modal.querySelector('#id_persona');
            const hiddenCedula = modal.querySelector('#cedula_original');
            if (hiddenId && data.id_persona) hiddenId.value = data.id_persona;
            if (hiddenCedula) hiddenCedula.value = data.cedula || '';
        })
        .catch(error => console.error('Error de conexión: ', error));
}

// Último registro seleccionado, para no depender del evento de Bootstrap.
let personaEnEdicion = { id_persona: '', cedula: '' };

function sincronizarIdentificador(boton) {
    if (!boton) return;

    personaEnEdicion = {
        id_persona: boton.getAttribute('data-bs-id-persona') || '',
        cedula: boton.getAttribute('data-bs-cedula') || ''
    };

    const editmodal = document.getElementById('editmodal');
    if (!editmodal) return;
    const hiddenId = editmodal.querySelector('#id_persona');
    const hiddenCedula = editmodal.querySelector('#cedula_original');
    if (hiddenId && personaEnEdicion.id_persona) hiddenId.value = personaEnEdicion.id_persona;
    if (hiddenCedula) hiddenCedula.value = personaEnEdicion.cedula;
}

document.addEventListener('click', function (event) {
    const boton = event.target && event.target.closest
        ? event.target.closest('[data-bs-target="#editmodal"]')
        : null;
    if (boton) sincronizarIdentificador(boton);
});

// Modal de edición
const editmodal = document.getElementById('editmodal');
if (editmodal) {
    editmodal.addEventListener('shown.bs.modal', event => {
        const button = event.relatedTarget;
        sincronizarIdentificador(button);
        if (button) {
            cargarDatosModal(
                editmodal,
                button.getAttribute('data-bs-cedula'),
                button.getAttribute('data-bs-id-persona')
            );
        }
    });

    const formEditar = editmodal.querySelector('form');
    if (formEditar) {
        formEditar.addEventListener('submit', function (event) {
            event.preventDefault();

            // Respaldo: si los ocultos quedaron vacíos, usar el botón que abrió el modal.
            const hiddenId = formEditar.querySelector('[name="id_persona"]');
            const hiddenCedula = formEditar.querySelector('[name="cedula_original"]');
            if (hiddenId && !hiddenId.value) hiddenId.value = personaEnEdicion.id_persona;
            if (hiddenCedula && !hiddenCedula.value) hiddenCedula.value = personaEnEdicion.cedula;

            const formData = new FormData(formEditar);

            fetch('/IPSPUPTM/app/comunidaduptm/actualizar/actualizar.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alertify.success(data.message);
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        alertify.error(data.message);
                    }
                    bootstrap.Modal.getInstance(editmodal).hide();
                })
                .catch(() => alertify.error('Error al actualizar.'));
        });
    }
}

// Formulario de alta
const formulariomodal = document.getElementById('formulariomodal');
if (formulariomodal) {
    const formAgregar = formulariomodal.querySelector('form');
    if (formAgregar) {
        formAgregar.addEventListener('submit', function (event) {
            event.preventDefault();
            const formData = new FormData(formAgregar);

            fetch('/IPSPUPTM/app/comunidaduptm/formulario/guardar.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alertify.success(data.message);
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        alertify.error(data.message);
                    }
                    bootstrap.Modal.getInstance(formulariomodal).hide();
                })
                .catch(() => alertify.error('Error al guardar.'));
        });
    }
}

// Filtro de búsqueda
const searchInput = document.getElementById('search');
if (searchInput) {
    searchInput.addEventListener('keyup', () => {
        const filter = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll('.table tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}
