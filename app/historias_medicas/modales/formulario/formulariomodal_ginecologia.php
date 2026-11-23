<?php
// Obtener datos del médico logueado para prellenar el campo ci_medico
$ci_medico_session = '';
if (isset($_SESSION['user_id'])) {
  $sqlMed = "SELECT ci_medico FROM medicos WHERE id_usuario = ?";
  $stmtMed = $conn->prepare($sqlMed);
  if ($stmtMed) {
    $stmtMed->bind_param("i", $_SESSION['user_id']);
    $stmtMed->execute();
    $resMed = $stmtMed->get_result()->fetch_assoc();
    $ci_medico_session = $resMed['ci_medico'] ?? '';
    $stmtMed->close();
  }
}
?>

<!-- Estilos de Select2 -->
<link href="/IPSPUPTM/assets/select2/css/select2.min.css" rel="stylesheet" />

<!-- Modal Historia Médica - Ginecología -->
<div class="modal fade" id="formulariomodal_ginecologia" tabindex="-1" aria-labelledby="formularioGineLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <form class="modal-content" id="form-ginecologia"
      action="/IPSPUPTM/app/historias_medicas/modales/formulario/guardar_ginecologia.php" method="POST">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold" id="formularioGineLabel">
          <i class="fas fa-female me-2"></i>Historia Médica — Ginecología
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">

        <input type="hidden" name="ci_medico" value="<?= htmlspecialchars($ci_medico_session) ?>">

        <!-- === SECCIÓN 1: DATOS DEL PACIENTE === -->
        <h6 class="fw-bold text-primary border-bottom pb-1 mb-3"><i class="fas fa-user me-1"></i> Datos del Paciente
        </h6>

        <div class="mb-3">
          <label class="form-label fw-bold">Tipo de Paciente</label>
          <select name="tipo_paciente" id="gine_tipo_paciente" class="form-select border-primary" required>
            <option value="" selected disabled>Seleccione...</option>
            <option value="interno">Afiliado / Beneficiario</option>
            <option value="externo">Comunidad UPTM (Externo)</option>
          </select>
        </div>

        <!-- Paciente interno -->
        <div id="gine_campos_interno" style="display:none;">
          <div class="mb-3">
            <label class="form-label">Seleccione Cédula o Nombre (Afiliado/Beneficiario)</label>
            <select name="ci_paciente" id="gine_ci_paciente_hidden" class="form-select border-primary"
              style="width: 100%;">
              <option value="" selected disabled>Buscar por CI o Nombre...</option>
              <?php
              // Solo afiliados y beneficiarios (la comunidad va en el selector externo)
              $sql = "SELECT p.cedula, p.fechanacimiento,
                             CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo
                      FROM persona p
                      LEFT JOIN afiliados a     ON a.id_persona = p.id_persona
                      LEFT JOIN beneficiarios b ON b.id_persona = p.id_persona
                      WHERE a.id_persona IS NOT NULL OR b.id_persona IS NOT NULL
                      ORDER BY p.nombre ASC";
              $res = $conn->query($sql);
              while ($r = $res->fetch_assoc()) {
                echo '<option data-fecha="' . $r['fechanacimiento'] . '" value="' . $r['cedula'] . '">' . $r['cedula'] . ' | ' . htmlspecialchars($r['nombre_completo']) . '</option>';
              }
              ?>
            </select>
          </div>
        </div>

        <!-- Paciente externo -->
        <div id="gine_campos_externo" style="display:none;">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Cédula Externo</label>
              <select name="cedula_ext" id="gine_cedula_ext" class="form-select border-primary" style="width: 100%;">
                <option value="" selected disabled>Busque o ingrese Cédula...</option>
                <?php
                $sql_ext = "SELECT p.cedula, CONCAT(p.nombre, ' ', p.apellido) as nombre_completo
            FROM persona p
            LEFT JOIN afiliados a     ON a.id_persona = p.id_persona
            LEFT JOIN beneficiarios b ON b.id_persona = p.id_persona
            WHERE a.id_persona IS NULL AND b.id_persona IS NULL
            ORDER BY p.nombre ASC";
                $res_ext = $conn->query($sql_ext);
                while ($r_ext = $res_ext->fetch_assoc()) {
                  echo '<option value="' . $r_ext['cedula'] . '" data-nombre="' . htmlspecialchars($r_ext['nombre_completo']) . '">' . $r_ext['cedula'] . ' | ' . htmlspecialchars($r_ext['nombre_completo']) . '</option>';
                }
                ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Nombre y Apellido</label>
              <input type="text" name="nombre_ext" id="gine_nombre_ext" class="form-control border-primary">
            </div>
          </div>
        </div>

        <!-- Campos comunes del paciente -->
        <div id="gine_campos_comunes" style="display:none;">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Fecha Nacimiento</label>
              <input type="date" name="fecha_nacimiento" id="gine_fecha_nac" class="form-control border-primary"
                required min="<?php echo date('Y-m-d', strtotime('-110 years')); ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Edad</label>
              <input type="number" name="edad" id="gine_edad" class="form-control border-primary" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Fecha Consulta</label>
              <input type="date" name="fecha" class="form-control border-primary" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Dirección</label>
              <input type="text" name="direccion" class="form-control border-primary"
                placeholder="Av. Principal, Edif...">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Grupo Sanguíneo (GS)</label>
              <select name="gs" class="form-select border-primary">
                <option value="">—</option>
                <option>A+</option>
                <option>A-</option>
                <option>B+</option>
                <option>B-</option>
                <option>AB+</option>
                <option>AB-</option>
                <option>O+</option>
                <option>O-</option>
              </select>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">¿Fuma?</label>
              <select name="fuma" class="form-select border-primary">
                <option value="">—</option>
                <option>Sí</option>
                <option>No</option>
              </select>
            </div>
          </div>

          <!-- === SECCIÓN 2: CONSULTA === -->
          <h6 class="fw-bold text-primary border-bottom pb-1 mb-3 mt-2"><i class="fas fa-stethoscope me-1"></i> Datos de
            Consulta</h6>

          <div class="mb-3">
            <label class="form-label fw-bold">Motivo de Consulta</label>
            <textarea name="motivo_consulta" class="form-control border-primary" rows="2" required></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Enfermedad Actual</label>
            <textarea name="enfermedad_actual" class="form-control border-primary" rows="2"></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Antecedentes Familiares</label>
              <textarea name="antecedentes_familiares" class="form-control border-primary" rows="2"></textarea>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Antecedentes Personales</label>
              <textarea name="antecedentes_personales" class="form-control border-primary" rows="2"></textarea>
            </div>
          </div>

          <!-- === SECCIÓN 3: ANTECEDENTES GINECO-OBSTÉTRICOS === -->
          <h6 class="fw-bold text-primary border-bottom pb-1 mb-3 mt-2"><i class="fas fa-female me-1"></i> Antecedentes
            Gineco-Obstétricos</h6>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Ant. Gineco-Obstétrico</label>
              <input type="text" name="ant_gineco_obstetrico" class="form-control border-primary"
                placeholder="Describa...">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">C.M. (Ciclo Menstrual)</label>
              <input type="text" name="cm" class="form-control border-primary" placeholder="Ej: Regular/Irregular">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">PRS (Partos)</label>
              <input type="text" name="prs" class="form-control border-primary" placeholder="P/R/S">
            </div>
          </div>
          <div class="row">
            <div class="col-md-3 mb-3">
              <label class="form-label">C.S. (Cesáreas)</label>
              <input type="text" name="cs" class="form-control border-primary">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">MAC (Método Anticonceptivo)</label>
              <input type="text" name="mac" class="form-control border-primary">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">FUC (Fecha Última Consulta)</label>
              <input type="text" name="fuc" class="form-control border-primary">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">FUM (Fecha Última Menstruación)</label>
              <input type="text" name="fum" class="form-control border-primary">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Gestaciones</label>
              <input type="text" name="gestaciones" class="form-control border-primary">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">R.C. (Recién Nacido/Control)</label>
              <input type="text" name="rc" class="form-control border-primary">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Año</label>
              <input type="number" name="año" class="form-control border-primary" min="1900" max="<?= date('Y') ?>"
                placeholder="<?= date('Y') ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Otros</label>
            <textarea name="otros" class="form-control border-primary" rows="2"></textarea>
          </div>

          <!-- === SECCIÓN 4: EXAMEN FÍSICO === -->
          <h6 class="fw-bold text-primary border-bottom pb-1 mb-3 mt-2"><i class="fas fa-heartbeat me-1"></i> Examen
            Físico</h6>

          <div class="row">
            <div class="col-md-3 mb-3">
              <label class="form-label">Ex. Físico T.A.</label>
              <input type="text" name="ex_fisico_ta" class="form-control border-primary" placeholder="120/80">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">F.C. (Frec. Cardíaca)</label>
              <input type="text" name="fc" class="form-control border-primary" placeholder="72 lpm">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Peso (kg)</label>
              <input type="text" name="peso" class="form-control border-primary" placeholder="Ej: 60">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Talla (cm)</label>
              <input type="text" name="talla" class="form-control border-primary" placeholder="Ej: 165">
            </div>
          </div>
          <div class="row">
            <div class="col-md-3 mb-3">
              <label class="form-label">Cabeza</label>
              <input type="text" name="cabeza" class="form-control border-primary">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">O.R.L.</label>
              <input type="text" name="orl" class="form-control border-primary">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">C.V. (Cardiovascular)</label>
              <input type="text" name="cv" class="form-control border-primary">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Tiroides</label>
              <input type="text" name="tiroides" class="form-control border-primary">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Mamas</label>
              <input type="text" name="mamas" class="form-control border-primary">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Abdomen</label>
              <input type="text" name="abdomen" class="form-control border-primary">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Ginecológico</label>
              <input type="text" name="ginecologico" class="form-control border-primary">
            </div>
          </div>

          <!-- === SECCIÓN 5: DIAGNÓSTICO Y CONDUCTA === -->
          <h6 class="fw-bold text-primary border-bottom pb-1 mb-3 mt-2"><i class="fas fa-notes-medical me-1"></i>
            Diagnóstico y Conducta</h6>

          <div class="mb-3">
            <label class="form-label">Ultrasonido</label>
            <textarea name="ultrasonido" class="form-control border-primary" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Diagnóstico</label>
            <textarea name="diagnostico" class="form-control border-primary" rows="2" required></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Conducta / Tratamiento</label>
            <textarea name="conducta" class="form-control border-primary" rows="2" required></textarea>
          </div>

        </div><!-- fin gine_campos_comunes -->

      </div><!-- modal-body -->

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="btn_guardar_gine" style="display:none;">
          <i class="fas fa-save me-1"></i>Guardar Historia
        </button>
      </div>
    </form> <!-- Cierre del form (que es el modal-content) -->
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {

    const selector = document.getElementById('gine_tipo_paciente');
    const dInterno = document.getElementById('gine_campos_interno');
    const dExterno = document.getElementById('gine_campos_externo');
    const dComunes = document.getElementById('gine_campos_comunes');
    const bGuardar = document.getElementById('btn_guardar_gine');
    const iFechaNac = document.getElementById('gine_fecha_nac');
    const iEdad = document.getElementById('gine_edad');
    const form = document.getElementById('form-ginecologia');

    // -- Mostrar/ocultar secciones según tipo de paciente --
    selector.addEventListener('change', function () {
      dComunes.style.display = 'block';
      bGuardar.style.display = 'inline-block';

      if (this.value === 'interno') {
        dInterno.style.display = 'block';
        dExterno.style.display = 'none';
        iFechaNac.setAttribute('readonly', 'readonly');
      } else {
        dInterno.style.display = 'none';
        dExterno.style.display = 'block';
        iFechaNac.value = '';
        iEdad.value = '';
        iFechaNac.removeAttribute('readonly');
      }
    });

    // -- Buscador datalist de pacientes internos --
    // Removido, ahora Select2 se encarga del evento de cambio de forma asíncrona.

    // -- Calcular edad para externos --
    iFechaNac.addEventListener('change', function () {
      if (this.value && !this.readOnly) calcularEdad(this.value);
    });

    function calcularEdad(fecha) {
      const hoy = new Date();
      const cumple = new Date(fecha);
      let edad = hoy.getFullYear() - cumple.getFullYear();
      const m = hoy.getMonth() - cumple.getMonth();
      if (m < 0 || (m === 0 && hoy.getDate() < cumple.getDate())) edad--;
      iEdad.value = edad;
    }

    // -- Envío por AJAX --
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(this);

      fetch(this.action, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
          } else {
            alert('⚠️ Error: ' + data.message);
          }
        })
        .catch(() => alert('❌ Error al procesar la solicitud.'));
    });
  });

  // Esperar a que jQuery esté disponible en el layout principal antes de cargar e inicializar Select2
  (function initSelect2Ginecologia() {
    if (window.jQuery) {
      var s2 = document.createElement('script');
      s2.src = '/IPSPUPTM/assets/select2/js/select2.min.js';

      s2.onload = function () {
        // --- 1. Select2 para Internos ---
        var $select = window.jQuery('#gine_ci_paciente_hidden');
        $select.select2({
          dropdownParent: window.jQuery('#formulariomodal_ginecologia'),
          width: '100%',
          language: 'es'
        });

        $select.on('change', function () {
          var fecha = window.jQuery(this).find(':selected').data('fecha');
          var iFechaNac = document.getElementById('gine_fecha_nac');

          if (fecha) {
            iFechaNac.value = fecha;
            calcularEdadSelect2(fecha);
          } else {
            iFechaNac.value = "";
            document.getElementById('gine_edad').value = "";
          }
        });

        // --- 2. Select2 para Externos ---
        var $selectExt = window.jQuery('#gine_cedula_ext');
        $selectExt.select2({
          dropdownParent: window.jQuery('#formulariomodal_ginecologia'),
          width: '100%',
          language: 'es',
          tags: true,
          createTag: function (params) {
            var term = window.jQuery.trim(params.term);
            if (term === '' || isNaN(term)) { return null; }
            return { id: term, text: term, newTag: true };
          }
        });

        $selectExt.on('change', function () {
          var selectedOption = window.jQuery(this).find(':selected');
          var nombreCompleto = selectedOption.data('nombre');
          var iNombreExt = document.getElementById('gine_nombre_ext');

          if (nombreCompleto) {
            iNombreExt.value = nombreCompleto;
            iNombreExt.setAttribute('readonly', 'readonly');
          } else {
            iNombreExt.value = "";
            iNombreExt.removeAttribute('readonly');
          }
        });

        function calcularEdadSelect2(fecha) {
          const hoy = new Date();
          const cumple = new Date(fecha);
          let edad = hoy.getFullYear() - cumple.getFullYear();
          const m = hoy.getMonth() - cumple.getMonth();
          if (m < 0 || (m === 0 && hoy.getDate() < cumple.getDate())) edad--;
          document.getElementById('gine_edad').value = edad;
        }
      };

      document.body.appendChild(s2);
    } else {
      setTimeout(initSelect2Ginecologia, 50);
    }
  })();
</script>