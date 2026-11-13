document.getElementById('vermodal').addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  var id = button.getAttribute('data-bs-id');
  var modalBody = document.getElementById('contenidoVerModal');
  modalBody.textContent = 'Cargando...';

  fetch('/IPSPUPTM/app/configuracion/gestionusuario/get_usuario.php?id=' + id)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        modalBody.textContent = 'Error: ' + data.error;
      } else {
        modalBody.innerHTML = `
              <p><strong>Nombre completo:</strong> ${data.Nombre_completo}</p>
              <p><strong>Usuario:</strong> ${data.username}</p>
              <p><strong>Rol:</strong> ${data.role_name}</p>
            `;
      }
    })
    .catch(() => {
      modalBody.textContent = 'Error al cargar los datos';
    });
});

// Modal de eliminación
const eliminarModal = document.getElementById('eliminamodal');
if (eliminarModal) {
  eliminarModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-bs-id');
    eliminarModal.querySelector('input[name="id"]').value = id;
    console.log("ID asignado al modal de eliminar:", id);
  });

  eliminarModal.addEventListener('click', '.btn-danger', function () {
    const id = document.getElementById('eliminamodal').querySelector('input[name="id"]').value;
    console.log("ID a eliminar:", id);

    fetch('/IPSPUPTM/app/configuracion/gestionusuario/eliminar/eliminar.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'id=' + id,
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alertify.success(data.message, 2, function () {
            window.location.href = '/IPSPUPTM/app/configuracion/gestionusuarios/vistausuarios.php';
          });
        } else {
          alertify.error(data.message);
          console.error("Error al eliminar usuario:", data.message);
        }
      })
      .catch(error => {
        alertify.error("Error de red al eliminar el usuario");
        console.error('Error de red al eliminar:', error);
      });
  });
}

document.addEventListener('DOMContentLoaded', function () {
  const registroForm = document.querySelector('form');

  registroForm.addEventListener('submit', function (event) {
    event.preventDefault(); // Evitar el envío tradicional del formulario

    const formData = new FormData(registroForm);

    fetch('/IPSPUPTM/Inicio/registro_procesar.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alertify.success(data.message, 3, function () {  // Mostrar alerta de éxito
            window.location.href = '/IPSPUPTM/Inicio/login.php';  // Redirigir al login después del éxito
          });
        } else {
          alertify.error(data.message);
        }
      })
      .catch(error => {
        alertify.error('Error de red: ' + error);
      });
  });
});


