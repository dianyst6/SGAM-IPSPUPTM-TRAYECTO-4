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

foreach ($planes_disponibles as &$plan_asignar) {
    $anio_plan = (int) $plan_asignar['anio'];
    $inicio_plan = $anio_plan > (int) date('Y')
        ? sprintf('%04d-01-01', $anio_plan)
        : planHoy();
    try {
        $num_cuotas = count(planMesesCuotas($anio_plan, $inicio_plan));
    } catch (Exception $e) {
        $num_cuotas = PLAN_CUOTAS_ANUALES;
    }
    $montos_plan = planResumenMontos((float) $plan_asignar['precio'], $num_cuotas);
    $plan_asignar['inicio'] = $inicio_plan;
    $plan_asignar['fin'] = sprintf('%04d-12-31', $anio_plan);
    $plan_asignar['num_cuotas'] = $num_cuotas;
    $plan_asignar['inicial'] = $montos_plan['monto_inicial'];
    $plan_asignar['cuota'] = $montos_plan['monto_cuota'];
}
unset($plan_asignar);
?>
<div class="modal fade" id="modalAsignarPlan" tabindex="-1" aria-labelledby="modalAsignarPlanLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modalAsignarPlanLabel">Asignar Nuevo Plan de Salud</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="/IPSPUPTM/app/afiliados/modales/asignar_plan/guardar_plan.php" method="post">
          <input type="hidden" name="cedula" id="cedula_asignar" value="">

          <div class="container">
            <h6 class="text-success fw-bold mb-3"><i class="fas fa-file-contract me-2"></i>Información del Nuevo Contrato</h6>

            <?php if (!$periodo_abierto): ?>
              <div class="alert alert-warning">
                <i class="fas fa-triangle-exclamation me-1"></i>
                La inscripción está fuera del periodo (<?php echo planPeriodoInscripcionTexto(); ?>).
                <?php if ($es_admin): ?>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="autorizar_excepcion" id="autorizar_excepcion_asignar"
                      value="1" required>
                    <label class="form-check-label" for="autorizar_excepcion_asignar">
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
                <label for="id_planes_contrato_asignar" class="form-label">Plan de Salud</label>
                <select name="id_planes_contrato" id="id_planes_contrato_asignar" class="form-select" required
                  onchange="vincularPrecioPlanAsignar()">
                  <option value="">Seleccione un plan...</option>
                  <?php foreach ($planes_disponibles as $plan_asignar): ?>
                    <option value="<?php echo (int) $plan_asignar['ID_planes']; ?>"
                      data-precio="<?php echo number_format((float) $plan_asignar['precio'], 2, '.', ''); ?>"
                      data-inicial="<?php echo number_format((float) $plan_asignar['inicial'], 2, '.', ''); ?>"
                      data-cuota="<?php echo number_format((float) $plan_asignar['cuota'], 2, '.', ''); ?>"
                      data-cuotas="<?php echo (int) $plan_asignar['num_cuotas']; ?>"
                      data-cobertura="<?php echo number_format((float) $plan_asignar['monto_cobertura'], 2, '.', ''); ?>"
                      data-inicio="<?php echo $plan_asignar['inicio']; ?>"
                      data-fin="<?php echo $plan_asignar['fin']; ?>">
                      <?php echo htmlspecialchars($plan_asignar['nombre_plan']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4 col-12">
                <label for="monto_total_asignar" class="form-label">Monto del Contrato ($)</label>
                <input type="number" step="0.01" name="monto_total" id="monto_total_asignar"
                  class="form-control bg-light" readonly required>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4 col-12">
                <label for="dia_pago_mensual_asignar" class="form-label">Día de pago (1-31)</label>
                <input type="number" name="dia_pago_mensual" id="dia_pago_mensual_asignar" class="form-control"
                  min="1" max="31" value="<?php echo PLAN_DIA_PAGO_DEFECTO; ?>" required>
              </div>
              <div class="col-md-8 col-12 d-flex align-items-end">
                <div class="alert alert-info w-100 mb-0" id="resumen-plan-asignar">
                  Seleccione un plan para ver el pago inicial y las cuotas.
                </div>
              </div>
            </div>

          </div>
          <div class="modal-footer px-0 pb-0 pt-3">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Asignar Contrato</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function formatearFechaPlanAsignar(fecha) {
    if (!fecha) return '';
    const partes = fecha.split('-');
    return partes.length === 3 ? partes[2] + '/' + partes[1] + '/' + partes[0] : fecha;
}

function vincularPrecioPlanAsignar() {
    const select = document.getElementById('id_planes_contrato_asignar');
    const montoInput = document.getElementById('monto_total_asignar');
    const resumen = document.getElementById('resumen-plan-asignar');
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
        '<br><small>Vigencia: ' + formatearFechaPlanAsignar(inicio) + ' al ' + formatearFechaPlanAsignar(fin) +
        ' &middot; Cobertura familiar: $' + cobertura + ' por año</small>';
}

document.addEventListener('DOMContentLoaded', function () {
    const modalAsignarPlan = document.getElementById('modalAsignarPlan');
    if (modalAsignarPlan) {
        modalAsignarPlan.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const cedula = button.getAttribute('data-bs-cedula');
            const inputCedula = modalAsignarPlan.querySelector('#cedula_asignar');
            inputCedula.value = cedula;
        });
    }

    vincularPrecioPlanAsignar();
});
</script>
