document.addEventListener('DOMContentLoaded', () => {
    // Función para cargar datos en el modal
    function cargarDatosModal(modal, cedula) {
        console.log(`[cargarDatosModal] Modal ID: ${modal.id}, Cédula obtenida: ${cedula}`);

        let inputcedula = modal.querySelector('#cedula');
        let inputnombre = modal.querySelector('#nombre');
        let inputapellido = modal.querySelector('#apellido');
        let inputfechanacimiento = modal.querySelector('#fechanacimiento');
        let inputgenero = modal.querySelector('#genero');
        let inputtelefono = modal.querySelector('#telefono');
        let inputcorreo = modal.querySelector('#correo');
        let inputocupacion = modal.querySelector('#ocupacion');
        let inputidpersona = modal.querySelector('#id_persona');
        let inputcedulaoriginal = modal.querySelector('#cedula_original');

        let url = "/IPSPUPTM/app/afiliados/getafiliados.php";
        console.log(`[cargarDatosModal] URL de la petición: ${url}`);

        // La cédula original queda disponible desde el inicio, aunque el fetch tarde o falle.
        if (inputcedulaoriginal && cedula) inputcedulaoriginal.value = cedula;

        let formData = new FormData();
        formData.append('cedula', cedula);
        console.log(`[cargarDatosModal] Datos del formulario a enviar (cedula): ${formData.get('cedula')}`);

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log(`[cargarDatosModal] Respuesta recibida (raw) para ${modal.id}:`, response);
            return response.json();
        })
        .then(data => {
            console.log(`[cargarDatosModal] Datos convertidos a JSON para ${modal.id}:`, data);
            if (data.error) {
                console.error(`[cargarDatosModal] Error al cargar datos para ${modal.id}:`, data.error);
            } else {
                console.log(`[cargarDatosModal] Datos cargados exitosamente para ${modal.id}:`, data);
                inputcedula.value = data.cedula || '';
                if (inputidpersona) inputidpersona.value = data.id_persona || '';
                if (inputcedulaoriginal) inputcedulaoriginal.value = data.cedula || '';
                inputnombre.value = data.nombre || '';
                inputapellido.value = data.apellido || '';
                inputfechanacimiento.value = data.fechanacimiento || '';
                inputtelefono.value = data.telefono || '';
                inputcorreo.value = data.correo || '';
                inputocupacion.value = data.ocupacion || '';
                if (modal.id === 'vermodal'|| modal.id === 'editmodal') {
                    inputgenero.value = data.genero || '';
                    console.log("[cargarDatosModal - editmodal] Género asignado:", data.genero);
                    // Agregar listener para el formulario de edición AQUÍ
                    const formularioEditarAfiliado = modal.querySelector('form');
                    // Evita registrar el listener varias veces al reabrir el modal
                    if (formularioEditarAfiliado && !formularioEditarAfiliado.dataset.listenerAttached) {
                        formularioEditarAfiliado.dataset.listenerAttached = '1';
                        formularioEditarAfiliado.addEventListener('submit', function(event) {
                            event.preventDefault();
                            console.log("[Event - editmodal] Formulario de edición enviado.");
                            const formDataEditar = new FormData(formularioEditarAfiliado);
                            fetch('/IPSPUPTM/app/afiliados/modales/actualizar/actualizar.php', { // Use la ruta correcta
                                method: 'POST',
                                body: formDataEditar
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log("[Fetch - actualizar.php] Respuesta:", data);
                                if (data.success) {
                                    alertify.message(data.message, 3, function(){
                                        window.location.reload();}).className = 'success-text';

                                } else {
                                    alertify.error(data.message);
                                }
                                bootstrap.Modal.getInstance(editmodal).hide(); // Cierra el modal de edición después de la respuesta
                            })
                            .catch(error => {
                                console.error("[Fetch - editar.php] Error de red:", error);
                                alertify.error('Error de red: ' + error);
                                bootstrap.Modal.getInstance(editmodal).hide(); // Cierra el modal de edición en caso de error
                            });
                        });
                    } else if (!formularioEditarAfiliado) {
                        console.error("[Error - editmodal] No se encontró el formulario dentro del modal de edición.");
                    }
                }
            }
        })
        .catch(error => console.error(`[cargarDatosModal] Error de conexión para ${modal.id}:`, error));
    }

    // Modal de visualización (sin cambios)
    const vermodal = document.getElementById('vermodal');
    if (vermodal) {
        vermodal.addEventListener('shown.bs.modal', event => {
            console.log("[Event - vermodal] El modal 'vermodal' se ha mostrado.");
            const button = event.relatedTarget;
            const cedula = button.getAttribute('data-bs-cedula');
            console.log("[Event - vermodal] Cédula obtenida del botón:", cedula);
            cargarDatosModal(vermodal, cedula);
        });
    }

    // Modal de edición
    const editmodal = document.getElementById('editmodal');
    if (editmodal) {
        editmodal.addEventListener('shown.bs.modal', event => {
            console.log("[Event - editmodal] El modal 'editmodal' se ha mostrado.");
            const button = event.relatedTarget;
            const cedula = button.getAttribute('data-bs-cedula');
            const idPersona = button.getAttribute('data-bs-id-persona');
            console.log("[Event - editmodal] Cédula obtenida del botón:", cedula, "| id_persona:", idPersona);

            // Se asignan de inmediato para no depender de la respuesta del fetch.
            const hiddenIdPersona = editmodal.querySelector('#id_persona');
            const hiddenCedulaOriginal = editmodal.querySelector('#cedula_original');
            if (hiddenIdPersona && idPersona) hiddenIdPersona.value = idPersona;
            if (hiddenCedulaOriginal) hiddenCedulaOriginal.value = cedula || '';

            cargarDatosModal(editmodal, cedula);
        });
    }
});

// Modal de eliminación
const eliminamodal = document.getElementById('eliminamodal');
if (eliminamodal) {
    eliminamodal.addEventListener('shown.bs.modal', event => {
        console.log("[Event - eliminamodal] El modal 'eliminamodal' se ha mostrado.");
        const button = event.relatedTarget;
        const cedula = button.getAttribute('data-bs-cedula');
        console.log("[Event - eliminamodal] Cédula obtenida del botón:", cedula);
        const cedulaInput = eliminamodal.querySelector('.modal-footer #cedula');
        if (cedulaInput) {
            cedulaInput.value = cedula;
            console.log("[Event - eliminamodal] Cédula asignada al campo oculto del modal de eliminación:", cedula);
        }
    });

    // Agrega este event listener para manejar el envío del formulario de eliminación
    const formularioEliminar = eliminamodal.querySelector('form'); // Asegúrate de seleccionar el formulario correcto
    if (formularioEliminar) {
        formularioEliminar.addEventListener('submit', function(event) {
            event.preventDefault(); // Previene la recarga de la página
            console.log("[Event - eliminamodal] Formulario de eliminación enviado.");

            const formData = new FormData(formularioEliminar);

            fetch('/IPSPUPTM/app/afiliados/modales/eliminar/eliminar.php', { 
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log("[Event - eliminamodal] Respuesta del servidor:", data);
                if (data.success) {
                    alertify.message(data.message, 3, function() {
                        window.location.reload(); // Recarga la página después de la eliminación exitosa
                    });
                } else {
                    alertify.error(data.message); // Muestra el mensaje de error
                }
                bootstrap.Modal.getInstance(eliminamodal).hide(); // Cierra el modal después de la respuesta
            })
            .catch(error => {
                console.error("[Event - eliminamodal] Error en la petición fetch:", error);
                alertify.error("Error de red al eliminar.");
                bootstrap.Modal.getInstance(eliminamodal).hide();
            });
        });
    }
}


  // Filtro de búsqueda en la tabla
  const searchInput = document.getElementById('search'); // Campo de búsqueda
  const tableBody = document.querySelector('.table tbody'); // Cuerpo de la tabla

  if (searchInput && tableBody) {
      searchInput.addEventListener('keyup', () => {
          const filter = searchInput.value.toLowerCase(); // Texto ingresado por el usuario
          const rows = tableBody.getElementsByTagName('tr'); // Filas de la tabla

          for (let i = 0; i < rows.length; i++) {
              const cells = rows[i].getElementsByTagName('td'); // Celdas de cada fila
              let match = false;

              // Recorre las celdas de cada fila buscando coincidencias
              for (let j = 0; j < cells.length; j++) {
                  if (cells[j] && cells[j].textContent.toLowerCase().includes(filter)) {
                      match = true;
                      break;
                  }
              }

              // Muestra u oculta la fila dependiendo de si hay coincidencias
              rows[i].style.display = match ? '' : 'none';
          }
      });
  }
