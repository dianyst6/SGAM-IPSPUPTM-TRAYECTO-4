document.addEventListener("DOMContentLoaded", function() {
    // === 1. ELEMENTOS DEL FILTRADO ===
    const filterSelect = document.getElementById("filterTipo");
    const searchInput = document.getElementById("search");
    const tableBody = document.querySelector('.table tbody');

    // Función de filtrado mejorada
    function ejecutarFiltros() {
        if (!tableBody) return;
        
        const filterValue = filterSelect ? filterSelect.value : "todos";
        const searchText = (searchInput ? searchInput.value : "").toLowerCase().trim();
        const rows = tableBody.getElementsByTagName('tr');

        for (let row of rows) {
            // Saltamos la fila si es la de "No se encontraron resultados"
            if (row.cells.length === 1) continue; 

            const tipoPaciente = (row.getAttribute("data-tipo") || "").trim();
            const contenidoFila = row.textContent.toLowerCase();

            // Lógica de coincidencia
            const matchTipo = (filterValue === "todos" || tipoPaciente === filterValue);
            const matchTexto = (searchText === "" || contenidoFila.includes(searchText));

            // Aplicar visibilidad
            row.style.display = (matchTipo && matchTexto) ? "" : "none";
        }
    }

    // Escuchadores con protección contra NULL
    if (filterSelect) {
        filterSelect.addEventListener("change", ejecutarFiltros);
    } else {
        console.warn("Advertencia: No se encontró el ID 'filterTipo'");
    }

    if (searchInput) {
        searchInput.addEventListener("keyup", ejecutarFiltros);
    } else {
        console.warn("Advertencia: No se encontró el ID 'search'");
    }

    // === 2. MODAL DE ELIMINACIÓN ===
    const eliminamodal = document.getElementById('eliminamodal');
    if (eliminamodal) {
        eliminamodal.addEventListener('shown.bs.modal', event => {
            const button = event.relatedTarget;
            const idCita = button.getAttribute('data-bs-idcita');
            const inputId = eliminamodal.querySelector('#id_cita');
            if (inputId) inputId.value = idCita;
        });

        const formEliminar = eliminamodal.querySelector('form');
        if (formEliminar) {
            formEliminar.addEventListener('submit', function(e) {
                e.preventDefault();
                fetch('/IPSPUPTM/app/citas/modales/eliminar/eliminar.php', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alertify.success(data.message);
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        alertify.error(data.message);
                    }
                })
                .catch(() => alertify.error("Error de conexión"));
            });
        }
    }

    // === 3. MODAL DE EDICIÓN ===
    const editmodal = document.getElementById('editmodal');
    if (editmodal) {
        editmodal.addEventListener('shown.bs.modal', event => {
            const button = event.relatedTarget;
            const idCita = button.getAttribute('data-bs-idcita');
            cargarDatosModal(editmodal, idCita);
        });

        const formEditar = editmodal.querySelector('form');
        if (formEditar) {
            formEditar.addEventListener('submit', function(e) {
                e.preventDefault();
                fetch('/IPSPUPTM/app/citas/modales/actualizar/actualizar.php', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alertify.success(data.message);
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        alertify.error(data.message);
                    }
                });
            });
        }
    }
});

function cargarDatosModal(modal, id_cita) {
    console.log("ID de cita obtenida para cargar datos: ", id_cita);

    let formData = new FormData();
    formData.append('id_cita', id_cita);

    fetch("/IPSPUPTM/app/citas/getcitas.php", {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            console.error("Error al cargar datos: ", data.error);
            alertify.error("Error al cargar datos de la cita");
        } else if (data.length > 0) {
            const cita = data[0];
            console.log("Datos de la cita recibidos:", cita);

            // --- INICIO DE LA LÓGICA DE VISIBILIDAD ---
            const divInternos = modal.querySelector('#campos_internos_editar');
            const divExternos = modal.querySelector('#campos_externos_editar');
            const inputIdPaciente = modal.querySelector('#id_paciente_editar');

            if (cita.tipo_origen === 'externo') {
                // Si es externo: mostramos campos de texto, ocultamos sección interna
                if (divExternos) divExternos.style.display = 'block';
                if (divInternos) divInternos.style.display = 'none';
                // Evita enviar un id de paciente interno residual de una edición anterior.
                if (inputIdPaciente) inputIdPaciente.value = '';
            } else {
                // Si es interno: mostramos el campo de texto readonly, ocultamos campos externos
                if (divExternos) divExternos.style.display = 'none';
                if (divInternos) divInternos.style.display = 'block';
            }
            // --- FIN DE LA LÓGICA DE VISIBILIDAD ---

            // 1. ID de la cita
            const inputId = modal.querySelector('#id_cita_editar');
            if (inputId) inputId.value = cita.id_cita;

            // 2. Paciente interno: tres campos readonly + id oculto
            if (cita.tipo_origen !== 'externo') {
                const fNombre = modal.querySelector('#nombre_int_editar');
                const fApellido = modal.querySelector('#apellido_int_editar');
                const fCedula = modal.querySelector('#cedula_int_editar');
                const badge = modal.querySelector('#tipo_int_badge');
                if (fNombre)   fNombre.value   = cita.nombre_int   || '';
                if (fApellido) fApellido.value = cita.apellido_int || '';
                if (fCedula)   fCedula.value   = cita.cedula_int   || '';
                if (badge)     badge.textContent = cita.tipo_int    || '';
                if (inputIdPaciente) inputIdPaciente.value = cita.id_paciente || '';
            }

            // 3. Selección de la especialidad
            const selectEspecialidad = modal.querySelector('#id_especialidad_editar');
            if (selectEspecialidad) selectEspecialidad.value = cita.id_especialidad;

            // 4. Asignación de fecha y descripción
            const inputFecha = modal.querySelector('#fecha_cita_editar');
            if (inputFecha) inputFecha.value = cita.fecha_cita || '';
            
            const inputDesc = modal.querySelector('#descripcion_editar');
            if (inputDesc) inputDesc.value = cita.descripcion || '';

            // 5. Llenado de campos externos (Solo si existen en el JSON)
            const pNombre = modal.querySelector('#nombre_ext_editar');
            if (pNombre) pNombre.value = cita.nombre_ext || '';
            
            const pApellido = modal.querySelector('#apellido_ext_editar');
            if (pApellido) pApellido.value = cita.apellido_ext || '';

            const pCedula = modal.querySelector('#cedula_ext_editar');
            if (pCedula) pCedula.value = cita.cedula_ext || '';

        } else {
            console.error("No se encontraron datos para el ID:", id_cita);
            alertify.error("No se encontraron datos de la cita");
        }
    })
    .catch(error => {
        console.error("Error de conexión:", error);
        alertify.error("Error de conexión al cargar datos");
    });
}
