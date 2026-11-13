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
            
            <div class="row mb-3">
              <div class="col-md-8 col-12">
                <label for="id_planes_contrato_asignar" class="form-label">Plan de Salud</label>
                <select name="id_planes_contrato" id="id_planes_contrato_asignar" class="form-select" required onchange="vincularPrecioPlanAsignar()">
                  <option value="">Seleccione un plan...</option>
                  <?php
                  // La conexión ya debe estar incluida en principal.php, pero si este modal se carga solo, mejor requerirla o usar la existente.
                  if (isset($conn)) {
                      $planes_asignar = mysqli_query($conn, "SELECT ID_planes, nombre_plan, precio FROM planes");
                      while ($p = mysqli_fetch_assoc($planes_asignar)) {
                        echo "<option value='{$p['ID_planes']}' data-precio='{$p['precio']}'>{$p['nombre_plan']}</option>";
                      }
                  }
                  ?>
                </select>
              </div>
              <div class="col-md-4 col-12">
                <label for="monto_total_asignar" class="form-label">Monto del Contrato ($)</label>
                <input type="number" step="0.01" name="monto_total" id="monto_total_asignar" class="form-control" required>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-6 col-12">
                <label for="fecha_inicio_asignar" class="form-label">Fecha de Inicio</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio_asignar" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
              </div>
              <div class="col-md-6 col-12">
                <label for="fecha_fin_asignar" class="form-label">Fecha de Finalización</label>
                <input type="date" name="fecha_fin" id="fecha_fin_asignar" class="form-control" required value="<?php echo date('Y') . '-12-31'; ?>">
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4 col-12">
                <label for="frecuencia_pago_asignar" class="form-label">Frecuencia de Pago</label>
                <select name="frecuencia_pago" id="frecuencia_pago_asignar" class="form-select" required>
                  <option value="Mensual">Mensual</option>
                  <option value="Trimestral">Trimestral</option>
                  <option value="Semestral">Semestral</option>
                  <option value="Anual">Anual</option>
                </select>
              </div>
              <div class="col-md-4 col-12">
                <label for="dia_pago_mensual_asignar" class="form-label">Día de pago (1-31)</label>
                <input type="number" name="dia_pago_mensual" id="dia_pago_mensual_asignar" class="form-control" min="1" max="31" required>
              </div>
              <div class="col-md-4 col-12">
                <label for="estado_contrato_asignar" class="form-label">Estado</label>
                <select name="estado_contrato" id="estado_contrato_asignar" class="form-select">
                  <option value="Activo">Activo</option>
                  <option value="Pendiente">Pendiente</option>
                </select>
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
function vincularPrecioPlanAsignar() {
    const select = document.getElementById('id_planes_contrato_asignar');
    const montoInput = document.getElementById('monto_total_asignar');
    if (select.selectedIndex >= 0) {
        const precio = select.options[select.selectedIndex].getAttribute('data-precio');
        montoInput.value = precio ? precio : '';
    }
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
});
</script>
