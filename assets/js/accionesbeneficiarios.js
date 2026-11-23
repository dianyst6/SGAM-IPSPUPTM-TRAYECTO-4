// Función para cargar datos en el modal
function cargarDatosModal(modal, cedula) {
  console.log("Cédula obtenida para cargar datos: ", cedula);

  let inputcedula = modal.querySelector('#cedula');
  let inputnombre = modal.querySelector('#nombre');
  let inputapellido = modal.querySelector('#apellido');
  let inputfechanacimiento = modal.querySelector('#fechanacimiento');
  let inputgenero = modal.querySelector('#genero'); // El select de género
  let inputtelefono = modal.querySelector('#telefono');
  let inputcorreo = modal.querySelector('#correo');
  let inputocupacion = modal.querySelector('#ocupacion');
  let inputparentesco = modal.querySelector('#parentesco');
  let selectAfiliado = modal.querySelector('#cedula_afil'); // El select de afiliados

  let url = "/IPSPUPTM/app/beneficiarios/getbeneficiarios.php";

  let formData = new FormData();
  formData.append('cedula', cedula);

  fetch(url, {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.error) {
      console.error("Error al cargar datos: ", data.error);
    } else {
      console.log("Datos cargados:", data);
      inputcedula.value = data.cedula || '';
      inputnombre.value = data.nombre || '';
      inputapellido.value = data.apellido || '';
      inputfechanacimiento.value = data.fechanacimiento || '';
      inputtelefono.value = data.telefono || '';
      inputcorreo.value = data.correo || '';
      inputocupacion.value = data.ocupacion || '';
      if (inputparentesco) inputparentesco.value = data.parentesco || '';

      // Para el modal de visualización
      if (modal.id === 'vermodal') {
        inputgenero.value = data.genero || '';
      }

      // Para el modal de edición, establece el género y el afiliado
      if (modal.id === 'editmodal') {
        inputgenero.value = data.genero || '';

        // Mantener sincronizados los datos internos del formulario.
        const hiddenIdPersona = modal.querySelector('#id_persona');
        const hiddenCedulaOriginal = modal.querySelector('#cedula_original');
        if (hiddenIdPersona && data.id_persona) hiddenIdPersona.value = data.id_persona;
        if (hiddenCedulaOriginal) hiddenCedulaOriginal.value = data.cedula || '';

        // Selecciona el afiliado relacionado
        if (selectAfiliado) {
          for (let i = 0; i < selectAfiliado.options.length; i++) {
            if (selectAfiliado.options[i].value == data.id_afiliado) {
              selectAfiliado.selectedIndex = i;
              break;
            }
          }
        }
      }
    }
  })
  .catch(error => console.error("Error de conexión: ", error));
}

// Modal de visualización
let vermodal = document.getElementById('vermodal');
if (vermodal) {
    vermodal.addEventListener('shown.bs.modal', event => {
      let button = event.relatedTarget;
      let cedula = button.getAttribute('data-bs-cedula');
      cargarDatosModal(vermodal, cedula);
    });
}

// Modal de edición
let editmodal = document.getElementById('editmodal');

// Último beneficiario seleccionado, para no depender del evento de Bootstrap.
let beneficiarioEnEdicion = { id_persona: '', cedula: '' };

function sincronizarIdentificadorBeneficiario(boton) {
  if (!boton) return;

  const idPersona = boton.getAttribute('data-bs-id-persona') || '';
  const cedula = boton.getAttribute('data-bs-cedula') || '';
  beneficiarioEnEdicion = { id_persona: idPersona, cedula: cedula };

  if (!editmodal) return;
  const hiddenIdPersona = editmodal.querySelector('#id_persona');
  const hiddenCedulaOriginal = editmodal.querySelector('#cedula_original');
  if (hiddenIdPersona && idPersona) hiddenIdPersona.value = idPersona;
  if (hiddenCedulaOriginal) hiddenCedulaOriginal.value = cedula;
}

// Listener delegado: rellena los campos ocultos en cuanto se hace clic en "Editar",
// aunque Bootstrap no dispare shown.bs.modal.
document.addEventListener('click', function (event) {
  const boton = event.target && event.target.closest
    ? event.target.closest('[data-bs-target="#editmodal"]')
    : null;
  if (boton) sincronizarIdentificadorBeneficiario(boton);
});

if (editmodal) {
    editmodal.addEventListener('shown.bs.modal', event => {
      let button = event.relatedTarget;
      sincronizarIdentificadorBeneficiario(button);
      if (button) {
        cargarDatosModal(editmodal, button.getAttribute('data-bs-cedula'));
      }
    });

    // Un único listener de envío (evita peticiones duplicadas al abrir el modal varias veces).
    const formularioEditarBeneficiario = editmodal.querySelector('form');
    if (formularioEditarBeneficiario) {
      formularioEditarBeneficiario.addEventListener('submit', function(event) {
        event.preventDefault(); // Previene la recarga de la página
        console.log("Formulario de edición enviado");

        // Respaldo: si los campos ocultos quedaron vacíos, usar el botón que abrió el modal.
        const hiddenIdPersona = formularioEditarBeneficiario.querySelector('[name="id_persona"]');
        const hiddenCedulaOriginal = formularioEditarBeneficiario.querySelector('[name="cedula_original"]');
        if (hiddenIdPersona && !hiddenIdPersona.value) hiddenIdPersona.value = beneficiarioEnEdicion.id_persona;
        if (hiddenCedulaOriginal && !hiddenCedulaOriginal.value) hiddenCedulaOriginal.value = beneficiarioEnEdicion.cedula;

        const formDataEditar = new FormData(formularioEditarBeneficiario);

        fetch('/IPSPUPTM/app/beneficiarios/modales/actualizar/actualizar.php', {
          method: 'POST',
          body: formDataEditar
        })
        .then(response => response.json())
        .then(data => {
          console.log("Respuesta del servidor:", data);
          if (data.success) {
            alertify.message(data.message, 3, function() {
              window.location.reload(); // Recarga la página después de la actualización exitosa
            });
          } else {
            alertify.error(data.message); // Muestra el mensaje de error
          }
          bootstrap.Modal.getInstance(editmodal).hide();
        })
        .catch(error => {
          console.error("Error en la petición fetch:", error);
          alertify.error("Error de red al actualizar.");
          bootstrap.Modal.getInstance(editmodal).hide();
        });
      });
    }
}

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
    const formularioEliminar = eliminamodal.querySelector('form');
    if (formularioEliminar) {
        formularioEliminar.addEventListener('submit', function(event) {
            event.preventDefault(); // Previene la recarga de la página
            console.log("[Event - eliminamodal] Formulario de eliminación enviado.");

            const formData = new FormData(formularioEliminar);

            fetch('/IPSPUPTM/app/beneficiarios/modales/eliminar/eliminar.php', {
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
const searchInput = document.getElementById('search');
const tableBody = document.querySelector('.table tbody');

if (searchInput && tableBody) {
    searchInput.addEventListener('keyup', () => {
      const filter = searchInput.value.toLowerCase();
      const rows = tableBody.getElementsByTagName('tr');

      for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let match = false;

        for (let j = 0; j < cells.length; j++) {
          if (cells[j] && cells[j].textContent.toLowerCase().includes(filter)) {
            match = true;
            break;
          }
        }

        rows[i].style.display = match ? '' : 'none';
      }
    });
}
