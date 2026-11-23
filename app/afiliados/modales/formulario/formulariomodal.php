<?php
require_once 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
require_once 'C:/xampp/htdocs/IPSPUPTM/config/plan_helper.php';

$planes_disponibles = [];
$res_planes = $conn->query(
    "SELECT ID_planes, nombre_plan, precio, monto_cobertura, anio
     FROM planes WHERE activo = 1 ORDER BY anio DESC, ID_planes DESC"
);
if ($res_planes) {
    while ($p = $res_planes->fetch_assoc()) {
        $planes_disponibles[] = $p;
    }
}

$periodo_abierto = planPeriodoInscripcionAbierto();
$es_admin = ((int) ($_SESSION['role_id'] ?? 0)) === 1;

foreach ($planes_disponibles as &$plan_form) {
    $anio_plan = (int) $plan_form['anio'];
    $inicio_plan = $anio_plan > (int) date('Y')
        ? sprintf('%04d-01-01', $anio_plan)
        : planHoy();
    try {
        $num_cuotas = count(planMesesCuotas($anio_plan, $inicio_plan));
    } catch (Exception $e) {
        $num_cuotas = PLAN_CUOTAS_ANUALES;
    }
    $montos_plan = planResumenMontos((float) $plan_form['precio'], $num_cuotas);
    $plan_form['inicio'] = $inicio_plan;
    $plan_form['fin'] = sprintf('%04d-12-31', $anio_plan);
    $plan_form['num_cuotas'] = $num_cuotas;
    $plan_form['inicial'] = $montos_plan['monto_inicial'];
    $plan_form['cuota'] = $montos_plan['monto_cuota'];
}
unset($plan_form);
?>
<div class="modal fade" id="formulariomodal" tabindex="-1" aria-labelledby="formulariomodallabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="FormularioModalLabel">Formulario de registro de Afiliado</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="/IPSPUPTM/app/afiliados/modales/formulario/guardar.php" method="post">
          <?php if (!$periodo_abierto && !$es_admin): ?>
            <div class="alert alert-warning mb-0">
              <i class="fas fa-lock me-1"></i>
              La inscripción está fuera del periodo (<?php echo planPeriodoInscripcionTexto(); ?>).
              <strong>Solo un administrador puede autorizar una inscripción excepcional.</strong>
            </div>
          <?php else: ?>
          <div class="container">

            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-user me-2"></i>Datos Personales</h6>
            <div class="row mb-3">
              <div class="col-md-6 col-12">
                <label for="cedula" class="form-label">Cédula</label>
                <input type="text" name="cedula" id="cedula" class="form-control" pattern="[0-9]{1,8}"
                  title="Solo se permiten hasta 8 números" maxlength="8" required>
                <div id="cedulaFeedback" class="text-danger mt-1" style="display:none; font-size: 0.875em;">La cédula ya
                  está registrada como beneficiario.</div>
              </div>
              <div class="col-md-6 col-12">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" name="nombre" id="nombre" class="form-control" maxlength="50"
                  pattern="[a-zA-ZáéíóúüñÑÁÉÍÓÚÜÑ\s]+" title="Solo se permiten letras" required>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6 col-12">
                <label for="apellido" class="form-label">Apellido</label>
                <input type="text" name="apellido" id="apellido" class="form-control" maxlength="50"
                  pattern="[a-zA-ZáéíóúüñÑÁÉÍÓÚÜÑ\s]+" title="Solo se permiten letras" required>
              </div>
              <div class="col-md-6 col-12">
                <label for="fechanacimiento" class="form-label">Fecha de Nacimiento</label>
                <input type="date" name="fechanacimiento" id="fechanacimiento" class="form-control" required
                  min="<?php echo date('Y-m-d', strtotime('-110 years')); ?>" max="<?php echo date('Y-m-d', strtotime('-20 years')); ?>">
                <div class="form-text">El afiliado debe tener al menos 20 años cumplidos.</div>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6 col-12">
                <label for="genero" class="form-label">Género</label>
                <select name="genero" id="genero" class="form-select" required>
                  <option value="">Seleccionar...</option>
                  <option value="masculino">Masculino</option>
                  <option value="femenino">Femenino</option>
                </select>
              </div>
              <div class="col-md-6 col-12">
                <label for="estado_civil" class="form-label">Estado Civil</label>
                <select name="estado_civil" id="estado_civil" class="form-select" required>
                  <option value="">Seleccionar...</option>
                  <option value="Soltero(a)">Soltero(a)</option>
                  <option value="Casado(a)">Casado(a)</option>
                  <option value="Concubino(a)">Concubino(a)</option>
                  <option value="Divorciado(a)">Divorciado(a)</option>
                  <option value="Viudo(a)">Viudo(a)</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6 col-12">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" name="telefono" id="telefono" maxlength="11" class="form-control"
                  pattern="04[0-9]{9}" title="El teléfono debe tener 11 dígitos y comenzar con 04 (ej: 04121234567)"
                  required>
                <div id="telefonoFeedback" class="text-danger mt-1" style="display:none; font-size: 0.875em;">
                  El teléfono debe comenzar con 04 y tener exactamente 11 dígitos.</div>
              </div>
              <div class="col-md-6 col-12">
                <label for="correo" class="form-label">Correo Electrónico</label>
                <input type="email" name="correo" id="correo" maxlength="100" class="form-control" required>
                <div id="correoFeedback" class="text-danger mt-1" style="display:none; font-size: 0.875em;">
                  El correo electrónico ya está registrado por otra persona.</div>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6 col-12">
                <label for="ocupacion" class="form-label">Ocupación</label>
                <input type="text" name="ocupacion" id="ocupacion" maxlength="50" class="form-control"
                  pattern="[a-zA-ZáéíóúüñÑÁÉÍÓÚÜÑ\s]+" title="Solo se permiten letras" required>
              </div>
            </div>

            <hr class="my-4">
            <h6 class="text-success fw-bold mb-3"><i class="fas fa-file-contract me-2"></i>Información del Contrato</h6>

            <?php if (!$periodo_abierto): ?>
              <div class="alert alert-warning">
                <i class="fas fa-triangle-exclamation me-1"></i>
                La inscripción está fuera del periodo (<?php echo planPeriodoInscripcionTexto(); ?>).
                <?php if ($es_admin): ?>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="autorizar_excepcion" id="autorizar_excepcion"
                      value="1" required>
                    <label class="form-check-label" for="autorizar_excepcion">
                      Autorizo esta inscripción excepcional como administrador.
                    </label>
                  </div>
                <?php else: ?>
                  <strong>Solo un administrador puede autorizar esta inscripción.</strong>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="row mb-3">
              <div class="col-md-8 col-12">
                <label for="id_planes_contrato" class="form-label">Plan de Salud</label>
                <select name="id_planes_contrato" id="id_planes_contrato" class="form-select" required
                  onchange="vincularPrecioPlan()">
                  <option value="">Seleccione un plan...</option>
                  <?php foreach ($planes_disponibles as $plan_form): ?>
                    <option value="<?php echo (int) $plan_form['ID_planes']; ?>"
                      data-precio="<?php echo number_format((float) $plan_form['precio'], 2, '.', ''); ?>"
                      data-inicial="<?php echo number_format((float) $plan_form['inicial'], 2, '.', ''); ?>"
                      data-cuota="<?php echo number_format((float) $plan_form['cuota'], 2, '.', ''); ?>"
                      data-cuotas="<?php echo (int) $plan_form['num_cuotas']; ?>"
                      data-cobertura="<?php echo number_format((float) $plan_form['monto_cobertura'], 2, '.', ''); ?>"
                      data-inicio="<?php echo $plan_form['inicio']; ?>"
                      data-fin="<?php echo $plan_form['fin']; ?>">
                      <?php echo htmlspecialchars($plan_form['nombre_plan']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4 col-12">
                <label for="monto_total" class="form-label">Monto del Contrato ($)</label>
                <input type="number" step="0.01" name="monto_total" id="monto_total_input"
                  class="form-control bg-light" readonly required>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4 col-12">
                <label for="dia_pago_mensual" class="form-label">Día de pago (1-31)</label>
                <input type="number" name="dia_pago_mensual" id="dia_pago_mensual" class="form-control" min="1"
                  max="31" value="<?php echo PLAN_DIA_PAGO_DEFECTO; ?>" required>
              </div>
              <div class="col-md-8 col-12 d-flex align-items-end">
                <div class="alert alert-info w-100 mb-0" id="resumen-plan">
                  Seleccione un plan para ver el pago inicial y las cuotas.
                </div>
              </div>
            </div>

          </div>
          <?php endif; ?>
          <div class="modal-footer px-0 pb-0 pt-3">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            <?php if ($periodo_abierto || $es_admin): ?>
              <button type="submit" id="btnRegistrar" class="btn btn-primary">Guardar Registro Completo</button>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  function formatearFechaPlan(fecha) {
    if (!fecha) return '';
    const partes = fecha.split('-');
    return partes.length === 3 ? partes[2] + '/' + partes[1] + '/' + partes[0] : fecha;
  }

  function vincularPrecioPlan() {
    const select = document.getElementById('id_planes_contrato');
    const montoInput = document.getElementById('monto_total_input');
    const resumen = document.getElementById('resumen-plan');
    if (!select || !montoInput || !resumen) return;

    const opcion = select.options[select.selectedIndex];
    const precio = opcion ? opcion.getAttribute('data-precio') : '';
    montoInput.value = precio ? precio : '';

    if (!precio) {
      resumen.innerHTML = 'Seleccione un plan para ver el pago inicial y las cuotas.';
      return;
    }

    const inicial = opcion.getAttribute('data-inicial') || '0.00';
    const cuota = opcion.getAttribute('data-cuota') || '0.00';
    const cuotas = opcion.getAttribute('data-cuotas') || '0';
    const cobertura = opcion.getAttribute('data-cobertura') || '0.00';
    const inicio = opcion.getAttribute('data-inicio');
    const fin = opcion.getAttribute('data-fin');

    resumen.innerHTML =
      '<strong>Pago inicial (30 %):</strong> $' + inicial +
      ' &middot; <strong>Cuota mensual:</strong> $' + cuota + ' x ' + cuotas + ' cuotas' +
      '<br><small>Vigencia: ' + formatearFechaPlan(inicio) + ' al ' + formatearFechaPlan(fin) +
      ' &middot; Cobertura familiar: $' + cobertura + ' por año</small>';
  }

  document.addEventListener('DOMContentLoaded', vincularPrecioPlan);

  // Usar delegación de eventos para asegurar que funcione incluso si el modal se carga dinámicamente
  document.body.addEventListener('input', function (event) {
    if (!event.target) return;

    // Validación y sanitización del campo cédula (solo números, máx 8 dígitos)
    if (event.target.id === 'cedula') {
      let cedulaInput = event.target;
      cedulaInput.value = cedulaInput.value.replace(/\D/g, '').slice(0, 8);
      let cedula = cedulaInput.value.trim();
      const feedback = document.getElementById('cedulaFeedback');
      const btnRegistrar = document.getElementById('btnRegistrar');

      if (cedula.length > 0) {
        fetch('/IPSPUPTM/app/afiliados/modales/formulario/check_cedula.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'cedula=' + encodeURIComponent(cedula)
        })
          .then(response => response.json())
          .then(data => {
            if (data.existe_beneficiario) {
              feedback.textContent = 'La cédula ya está registrada como beneficiario.';
              feedback.style.display = 'block';
              btnRegistrar.disabled = true;
              cedulaInput.classList.add('is-invalid');
            } else if (data.existe_afiliado) {
              feedback.textContent = 'La cédula ya está registrada como afiliado.';
              feedback.style.display = 'block';
              btnRegistrar.disabled = true;
              cedulaInput.classList.add('is-invalid');
            } else {
              feedback.style.display = 'none';
              btnRegistrar.disabled = false;
              cedulaInput.classList.remove('is-invalid');
            }
          })
          .catch(error => {
            console.error('Error verificando la cédula:', error);
          });
      } else {
        feedback.style.display = 'none';
        btnRegistrar.disabled = false;
        cedulaInput.classList.remove('is-invalid');
      }
    }

    // Validación asíncrona de correo electrónico
    if (event.target.id === 'correo') {
      let correoInput = event.target;
      let correo = correoInput.value.trim();
      const feedback = correoInput.parentNode.querySelector('#correoFeedback');
      const btnRegistrar = correoInput.closest('form').querySelector('button[type="submit"]');

      if (correo.length > 0) {
        fetch('/IPSPUPTM/app/configuracion/check_correo.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'correo=' + encodeURIComponent(correo)
        })
        .then(response => response.json())
        .then(data => {
            if (data.existe) {
              feedback.style.display = 'block';
              btnRegistrar.disabled = true;
              correoInput.classList.add('is-invalid');
            } else {
              feedback.style.display = 'none';
              btnRegistrar.disabled = false;
              correoInput.classList.remove('is-invalid');
            }
        })
        .catch(error => console.error('Error:', error));
      } else {
        feedback.style.display = 'none';
        btnRegistrar.disabled = false;
        correoInput.classList.remove('is-invalid');
      }
    }

    // Validación y sanitización de los campos Nombre, Apellido y Ocupación (solo letras y espacios, máx 50 caracteres)
    if (event.target.id === 'nombre' || event.target.id === 'apellido' || event.target.id === 'ocupacion') {
      let input = event.target;
      input.value = input.value.replace(/[^a-zA-ZáéíóúüñÑÁÉÍÓÚÜÑ\s]/g, '');
    }

    // Validación y sanitización del campo teléfono (solo números, máx 11, debe comenzar con 04)
    if (event.target.id === 'telefono') {
      let input = event.target;
      input.value = input.value.replace(/\D/g, '').slice(0, 11);
      const feedback = input.parentNode.querySelector('#telefonoFeedback');
      const btnRegistrar = input.closest('form').querySelector('button[type="submit"]');
      if (input.value.length > 0 && !input.value.startsWith('04')) {
        feedback.style.display = 'block';
        input.classList.add('is-invalid');
        if (btnRegistrar) btnRegistrar.disabled = true;
      } else if (input.value.length === 11 && input.value.startsWith('04')) {
        feedback.style.display = 'none';
        input.classList.remove('is-invalid');
        if (btnRegistrar) btnRegistrar.disabled = false;
      } else {
        feedback.style.display = 'none';
        input.classList.remove('is-invalid');
      }
    }
  });
</script>