<!-- Estilos de Select2 -->
<link href="/IPSPUPTM/assets/select2/css/select2.min.css" rel="stylesheet" />

<div class="modal fade" id="formulariomodal" tabindex="-1" aria-labelledby="formulariomodallabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5">Registrar Historia Médica</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="form-registro-historia" action="/IPSPUPTM/app/historias_medicas/modales/formulario/guardar.php"
          method="POST">

          <input type="hidden" name="ci_medico" id="ci_medico_input" value="<?= htmlspecialchars($ci_medico_logueado); ?>">

          <div class="mb-3">
            <label class="form-label fw-bold">Tipo de Paciente</label>
            <select name="tipo_paciente" id="tipo_paciente_selector" class="form-select border-primary" required>
              <option value="" selected disabled>Seleccione...</option>
              <option value="interno">Afiliado / Beneficiario</option>
              <option value="externo">Comunidad UPTM (Externo)</option>
            </select>
          </div>

          <div id="campos-interno" style="display: none;">
            <div class="mb-3">
              <label class="form-label">Seleccione Cédula o Nombre (Afiliado/Beneficiario)</label>
              <select name="ci_paciente" id="ci_paciente_hidden" class="form-select" style="width: 100%;">
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
                  echo '<option value="' . $r['cedula'] . '" data-fecha="' . $r['fechanacimiento'] . '">' . $r['cedula'] . ' | ' . htmlspecialchars($r['nombre_completo']) . '</option>';
                }
                ?>
              </select>
            </div>
          </div>

          <div id="campos-externo" style="display: none;">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Cédula Externo</label>
                <select name="cedula_ext" id="cedula_ext" class="form-select" style="width: 100%;">
                  <option value="" selected disabled>Busque o ingrese Cédula...</option>
                  <?php
                  // Comunidad UPTM: personas sin rol de afiliado ni beneficiario
                  $sql_ext = "SELECT p.cedula, CONCAT(p.nombre, ' ', p.apellido) as nombre_completo
            FROM persona p
            LEFT JOIN afiliados a     ON a.id_persona = p.id_persona
            LEFT JOIN beneficiarios b ON b.id_persona = p.id_persona
            WHERE a.id_persona IS NULL AND b.id_persona IS NULL
            ORDER BY p.nombre ASC";
                  $res_ext = $conn->query($sql_ext);
                  while ($r_ext = $res_ext->fetch_assoc()) {
                    echo '<option value="' . $r_ext['cedula'] . '" data-nombre="' . $r_ext['nombre_completo'] . '">' . $r_ext['cedula'] . ' | ' . $r_ext['nombre_completo'] . '</option>';
                  }
                  ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Nombre y Apellido</label>
                <input type="text" name="nombre_ext" id="nombre_ext" class="form-control">
              </div>
            </div>
          </div>

          <div id="campos-comunes" style="display: none;">
            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label">Fecha Nacimiento</label>
                <input type="date" name="fecha_nacimiento" id="fecha_nacimiento_input" class="form-control" required
                  min="<?php echo date('Y-m-d', strtotime('-110 years')); ?>" max="<?php echo date('Y-m-d'); ?>">
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Edad</label>
                <input type="number" name="edad" id="edad_input" class="form-control" required>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Fecha Consulta</label>
                <input type="date" name="fecha_consulta" class="form-control" value="<?= date('Y-m-d') ?>" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Dirección</label>
              <input type="text" name="direccion" class="form-control" required placeholder="Av. Principal, Edif...">
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Motivo de Consulta</label>
              <textarea name="motivo_consulta" class="form-control" rows="2" required></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Enfermedad Actual</label>
              <textarea name="enfermedad_actual" class="form-control" rows="2" required></textarea>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Antecedentes Familiares</label>
                <textarea name="antecedentes_familiares" class="form-control" rows="2"></textarea>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Antecedentes Personales</label>
                <textarea name="antecedentes_personales" class="form-control" rows="2"></textarea>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Información Adicional (Opcional)</label>
              <textarea name="info_adicional" class="form-control" rows="2"></textarea>
            </div>
          </div>

        </form> <!-- Cierre del form dentro del modal-body -->
      </div> <!-- Cierre modal-body -->

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" form="form-registro-historia" class="btn btn-primary" id="btn_guardar_historia"
          style="display:none;">Guardar Historia</button>
      </div>
    </div> <!-- Cierre del modal-content -->
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {

    const selector = document.getElementById('tipo_paciente_selector');
    const dInterno = document.getElementById('campos-interno');
    const dExterno = document.getElementById('campos-externo');
    const dComunes = document.getElementById('campos-comunes');
    const bGuardar = document.getElementById('btn_guardar_historia');

    const iFechaNac = document.getElementById('fecha_nacimiento_input');
    const iEdad = document.getElementById('edad_input');
    const form = document.getElementById('form-registro-historia');

    // 1. Lógica para mostrar/ocultar secciones según tipo de paciente
    selector.addEventListener('change', function () {
      const opcion = this.value;
      dComunes.style.display = 'block';
      bGuardar.style.display = 'block';

      if (opcion === 'interno') {
        dInterno.style.display = 'block';
        dExterno.style.display = 'none';
        iFechaNac.setAttribute('readonly', 'readonly');
      } else {
        dInterno.style.display = 'none';
        dExterno.style.display = 'block';
        iFechaNac.value = "";
        iEdad.value = "";
        iFechaNac.removeAttribute('readonly');
      }
    });

    // 2. Buscador de Pacientes Internos (Datalist)
    // El código nativo fue removido. Ahora el evento "change" se maneja vía jQuery al cargar Select2 asíncronamente.

    // 3. Cálculo de edad para externos
    iFechaNac.addEventListener('change', function () {
      if (this.value && !this.readOnly) {
        const hoy = new Date();
        const cumple = new Date(this.value);
        let edad = hoy.getFullYear() - cumple.getFullYear();
        const m = hoy.getMonth() - cumple.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < cumple.getDate())) {
          edad--;
        }
        iEdad.value = edad;
      }
    });

    // 4. Envío de datos por AJAX
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const ruta = this.getAttribute('action');

      fetch(ruta, {
        method: 'POST',
        body: formData
      })
        .then(response => response.json()) // Cambiado a JSON para coincidir con guardar.php
        .then(data => {
          if (data.success === true) {
            alert("✅ " + data.message);
            location.reload();
          } else {
            alert("⚠️ Error: " + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert("❌ Error al procesar la solicitud.");
        });
    });
  });

  // Esperar a que jQuery esté disponible en el layout principal antes de cargar e inicializar Select2
  (function initSelect2Historias() {
    if (window.jQuery) {
      var s2 = document.createElement('script');
      s2.src = '/IPSPUPTM/assets/select2/js/select2.min.js';

      s2.onload = function () {
        // --- 1. Select2 para Internos ---
        var $select = window.jQuery('#ci_paciente_hidden');
        $select.select2({
          dropdownParent: window.jQuery('#formulariomodal'),
          width: '100%',
          language: 'es'
        });

        // Lógica para auto-completar fecha y edad al seleccionar en Select2
        $select.on('change', function () {
          // Al usar 'data-fecha', lo leemos directamente del :selected
          var fecha = window.jQuery(this).find(':selected').data('fecha');
          var iFechaNac = document.getElementById('fecha_nacimiento_input');
          var iEdad = document.getElementById('edad_input');

          if (fecha) {
            iFechaNac.value = fecha;
            const hoy = new Date();
            const cumple = new Date(fecha);
            let edad = hoy.getFullYear() - cumple.getFullYear();
            const m = hoy.getMonth() - cumple.getMonth();
            if (m < 0 || (m === 0 && hoy.getDate() < cumple.getDate())) {
              edad--;
            }
            iEdad.value = edad;
          } else {
            iFechaNac.value = "";
            iEdad.value = "";
          }
        });

        // --- 2. Select2 para Externos ---
        var $selectExt = window.jQuery('#cedula_ext');
        $selectExt.select2({
          dropdownParent: window.jQuery('#formulariomodal'),
          width: '100%',
          language: 'es',
          tags: true, // Permite crear una nueva cédula si no existe
          createTag: function (params) {
            var term = window.jQuery.trim(params.term);
            if (term === '' || isNaN(term)) { return null; } // Guardar solo números
            return { id: term, text: term, newTag: true };
          }
        });

        // Autocompletar nombre si el externo ya existe en la BD
        $selectExt.on('change', function () {
          var selectedOption = window.jQuery(this).find(':selected');
          var nombreCompleto = selectedOption.data('nombre');
          var iNombreExt = document.getElementById('nombre_ext');

          if (nombreCompleto) {
            iNombreExt.value = nombreCompleto;
            iNombreExt.setAttribute('readonly', 'readonly');
          } else {
            iNombreExt.value = "";
            iNombreExt.removeAttribute('readonly');
          }
        });
      };

      document.body.appendChild(s2);
    } else {
      setTimeout(initSelect2Historias, 50);
    }
  })();
</script>