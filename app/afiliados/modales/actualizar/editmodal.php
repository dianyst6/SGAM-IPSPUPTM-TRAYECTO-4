<div class="modal fade" id="editmodal" tabindex="-1" aria-labelledby="editmodallabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h1 class="modal-title fs-5" id="editmodalLabel">Editar Afiliado</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form action="/IPSPUPTM/app/afiliados/modales/actualizar/actualizar.php" method="post">
          <input type="hidden" name="id" id="id">
          <input type="hidden" name="id_persona" id="id_persona">
          <input type="hidden" name="cedula_original" id="cedula_original">

          <!-- Contenedor para los campos -->
          <div class="container">
            <!-- Primer grupo: Cédula y Nombre -->
            <div class="row mb-3">
              <div class="col-md-6 col-12">
                <label for="cedula" class="form-label">Cédula</label>
                <input type="text" id="cedula" class="form-control" name="cedula" maxlength="8"
                  pattern="[0-9]{1,8}" title="Solo se permiten hasta 8 números" required>
                <div id="cedulaFeedback" class="text-danger mt-1" style="display:none; font-size: 0.875em;">
                  La cédula debe contener solo números (máximo 8).</div>
              </div>
              <div class="col-md-6 col-12">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" name="nombre" id="nombre" class="form-control" maxlength="50" required>
              </div>
            </div>

            <!-- Segundo grupo: Apellido y Fecha -->
            <div class="row mb-3">
              <div class="col-md-6 col-12">
                <label for="apellido" class="form-label">Apellido</label>
                <input type="text" name="apellido" id="apellido" class="form-control" required>
              </div>
              <div class="col-md-6 col-12">
                <label for="fechanacimiento" class="form-label">Fecha de Nacimiento</label>
                <input type="date" name="fechanacimiento" id="fechanacimiento" class="form-control" required
                  min="<?php echo date('Y-m-d', strtotime('-110 years')); ?>" max="<?php echo date('Y-m-d', strtotime('-20 years')); ?>">
                <div class="form-text">El afiliado debe tener al menos 20 años cumplidos.</div>
              </div>
            </div>

            <!-- Tercer grupo: Género y Teléfono -->
            <div class="row mb-3">
              <div class="col-md-6 col-12">
                <label for="genero" class="form-label">Género</label>
                <input type="text" id="genero" class="form-control" readonly name="genero">
              </div>
              <div class="col-md-6 col-12">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" name="telefono" id="telefono" maxlength="11" class="form-control"
                  pattern="04[0-9]{9}" title="El teléfono debe tener 11 dígitos y comenzar con 04 (ej: 04121234567)"
                  required>
                <div id="telefonoFeedback" class="text-danger mt-1" style="display:none; font-size: 0.875em;">
                  El teléfono debe comenzar con 04 y tener exactamente 11 dígitos.</div>
              </div>
            </div>

            <!-- Cuarto grupo: Correo -->
            <div class="row mb-3">
              <div class="col-12">
                <label for="correo" class="form-label">Correo Electrónico</label>
                <input type="email" name="correo" id="correo" class="form-control" required>
                <div id="correoFeedback" class="text-danger mt-1" style="display:none; font-size: 0.875em;">
                  El correo electrónico ya está registrado por otra persona.</div>
              </div>
            </div>

            <!-- Quinto grupo: Ocupación -->
            <div class="row mb-3">
              <div class="col-12">
                <label for="ocupacion" class="form-label">Ocupación</label>
                <input type="text" name="ocupacion" id="ocupacion" class="form-control" required>
              </div>
            </div>
          </div>

          <div class="">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" id="btnGuardarEdit" class="btn btn-primary">Guardar Cambios</button>
          </div>
        </form>
      </div> <!-- Cierre modal-body -->

    </div> <!-- Cierre modal-content -->
  </div> <!-- Cierre modal-dialog -->
</div> <!-- Cierre modal -->

<script>
  document.body.addEventListener('input', function (event) {
    if (!event.target) return;

    // La cédula solo admite números (máx 8)
    if (event.target.id === 'cedula' && event.target.closest('#editmodal')) {
      let input = event.target;
      input.value = input.value.replace(/\D/g, '').slice(0, 8);
    }

    // Validación y sanitización del campo teléfono en editmodal (solo números, máx 11, debe comenzar con 04)
    if (event.target.id === 'telefono') {
      let input = event.target;
      input.value = input.value.replace(/\D/g, '').slice(0, 11);
      const feedback = input.parentNode.querySelector('#telefonoFeedback');
      const btnGuardar = input.closest('form').querySelector('button[type="submit"]');
      if (input.value.length > 0 && !input.value.startsWith('04')) {
        feedback.style.display = 'block';
        input.classList.add('is-invalid');
        if (btnGuardar) btnGuardar.disabled = true;
      } else if (input.value.length === 11 && input.value.startsWith('04')) {
        feedback.style.display = 'none';
        input.classList.remove('is-invalid');
        if (btnGuardar) btnGuardar.disabled = false;
      } else {
        feedback.style.display = 'none';
        input.classList.remove('is-invalid');
      }
    }

    // Validación asíncrona de correo electrónico
    if (event.target.id === 'correo') {
      let correoInput = event.target;
      let correo = correoInput.value.trim();
      const feedback = correoInput.parentNode.querySelector('#correoFeedback');
      const btnGuardar = correoInput.closest('form').querySelector('button[type="submit"]');
      const personaInput = correoInput.closest('form').querySelector('[name="id_persona"]');
      const idPersona = personaInput ? personaInput.value : '';

      if (correo.length > 0) {
        fetch('/IPSPUPTM/app/configuracion/check_correo.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'correo=' + encodeURIComponent(correo) + '&id_persona_ignorada=' + encodeURIComponent(idPersona)
        })
          .then(response => response.json())
          .then(data => {
            if (data.existe) {
              feedback.style.display = 'block';
              if (btnGuardar) btnGuardar.disabled = true;
              correoInput.classList.add('is-invalid');
            } else {
              feedback.style.display = 'none';
              if (btnGuardar) btnGuardar.disabled = false;
              correoInput.classList.remove('is-invalid');
            }
          })
          .catch(error => console.error('Error:', error));
      } else {
        feedback.style.display = 'none';
        if (btnGuardar) btnGuardar.disabled = false;
        correoInput.classList.remove('is-invalid');
      }
    }
  });
</script>