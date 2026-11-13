<div class="modal fade" id="formulariomodal" tabindex="-1" aria-labelledby="formulariomodallabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="FormularioModalLabel">Agregar Comunidad UPTM</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="/IPSPUPTM/app/comunidaduptm/formulario/guardar.php" method="post">
          <div class="mb-3">
            <label for="cedula" class="form-label">Cédula</label>
            <input type="text" name="cedula" id="cedula" class="form-control" maxlength="8" pattern="[0-9]{1,8}" title="Solo se permiten hasta 8 números" required>
          </div>
          <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" name="nombre" id="nombre" class="form-control" maxlength="50" pattern="[a-zA-ZáéíóúüñÑÁÉÍÓÚÜÑ\s]+" title="Solo se permiten letras" required>
          </div>
          <div class="mb-3">
            <label for="apellido" class="form-label">Apellido</label>
            <input type="text" name="apellido" id="apellido" class="form-control" maxlength="50" pattern="[a-zA-ZáéíóúüñÑÁÉÍÓÚÜÑ\s]+" title="Solo se permiten letras" required>
          </div>
          <div class="mb-3">
            <label for="telefono" class="form-label">Telefono</label>
            <input type="text" name="telefono" id="telefono" class="form-control" maxlength="11" pattern="04[0-9]{9}" title="El teléfono debe tener 11 dígitos y comenzar con 04 (ej: 04121234567)" required>
            <div id="telefonoFeedback" class="text-danger mt-1" style="display:none; font-size: 0.875em;">El teléfono debe comenzar con 04 y tener exactamente 11 dígitos.</div>
          </div>
          <div class="modal-footer px-0 pb-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            <button type="submit" class="btn btn-primary">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// Usar delegación de eventos para asegurar que funcione incluso si el modal se carga dinámicamente
document.body.addEventListener('input', function(event) {
    if (!event.target) return;

    // Validación y sanitización del campo cédula (solo números, máx 8 caracteres)
    if (event.target.id === 'cedula') {
        let input = event.target;
        input.value = input.value.replace(/\D/g, '').slice(0, 8);
    }

    // Validación y sanitización de los campos Nombre y Apellido (solo letras y espacios, máx 50 caracteres)
    if (event.target.id === 'nombre' || event.target.id === 'apellido') {
        let input = event.target;
        input.value = input.value.replace(/[^a-zA-ZáéíóúüñÑÁÉÍÓÚÜÑ\s]/g, '');
    }
    // Validación y sanitización del campo teléfono (solo números, máx 11, debe comenzar con 04)
    if (event.target.id === 'telefono') {
        let input = event.target;
        input.value = input.value.replace(/\D/g, '').slice(0, 11);
        const feedback = document.getElementById('telefonoFeedback');
        if (input.value.length > 0 && !input.value.startsWith('04')) {
            feedback.style.display = 'block';
            input.classList.add('is-invalid');
        } else {
            feedback.style.display = 'none';
            input.classList.remove('is-invalid');
        }
    }
});
</script>

