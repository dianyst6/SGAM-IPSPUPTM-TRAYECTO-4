<link href="/IPSPUPTM/assets/select2/css/select2.min.css" rel="stylesheet" />

<!-- Estilos forzados para modales anidados -->
<style>
#modalNuevoExamenCita { 
    z-index: 2000 !important; 
    background: rgba(0,0,0,0.5); /* Simulamos backdrop si falla el de BS */
}
#modalNuevoExamenCita .modal-dialog {
    margin-top: 10%;
}
</style>

<!-- Modal de Nuevo Examen movido al inicio para mayor visibilidad en el DOM -->
<div class="modal" id="modalNuevoExamenCita" aria-hidden="true" style="z-index: 2000;">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-microscope me-2"></i>Registrar Examen de Catálogo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-nuevo-examen-cita">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Examen</label>
                        <input type="text" name="nombre_examen" class="form-control border-info" placeholder="Ej: Rayos X de Muñeca" required>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Especialidad Asociada</label>
                            <select name="id_especialidad" id="id_especialidad_nuevo" class="form-select border-info" required>
                                <option value="">Seleccione...</option>
                                <?php
                                $conn_temp = include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
                                $esp = $conn->query("SELECT id_especialidad, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad ASC");
                                while($e = $esp->fetch_assoc()) {
                                    echo "<option value='{$e['id_especialidad']}'>{$e['nombre_especialidad']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Categoría (Opcional)</label>
                            <select name="id_categoria" class="form-select border-info" required>
                                <option value="">Seleccione una categoría...</option>
                                <?php
                                $cat_list = $conn->query("SELECT id_categoria, nombre_categoria FROM categorias_examenes ORDER BY nombre_categoria ASC");
                                while($cl = $cat_list->fetch_assoc()) {
                                    echo "<option value='{$cl['id_categoria']}'>{$cl['nombre_categoria']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Precio Base ($)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-info text-white">$</span>
                            <input type="number" step="0.01" name="precio" class="form-control border-info" placeholder="0.00" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-info text-white fw-bold">
                        <i class="fas fa-save me-1"></i>Guardar Examen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="formulariomodal" aria-labelledby="formulariomodallabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="FormularioModalLabel">Registrar nueva cita</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="form-registro-cita-general">
          
          <div class="mb-3">
            <label for="tipo_paciente_selector" class="form-label fw-bold">Tipo de Paciente</label>
            <select name="tipo_paciente" id="tipo_paciente_selector" class="form-select border-primary" required>
              <option value="" selected disabled>Seleccione una opción...</option>
              <option value="interno">Afiliado / Beneficiario</option>
              <option value="externo">Comunidad UPTM (Externo)</option>
            </select>
          </div>

          <hr>

          <div id="campos-interno" style="display: none;">
            <div class="mb-3">
              <label for="id_paciente" class="form-label">Seleccione o Escriba Cédula / Nombre del Paciente</label>
              <select name="id_paciente" id="id_paciente" class="form-select" style="width: 100%;" required>
                <option value="" selected disabled>Escriba para buscar...</option>
                <?php
                $sql_pacientes = "
                SELECT p.id_persona AS id_pac_val, p.cedula,
                       CONCAT(p.nombre, ' ', p.apellido, ' - (Afiliado)') AS nombre_completo
                FROM afiliados a
                JOIN persona p ON a.id_persona = p.id_persona
                JOIN contrato_plan cp ON cp.id_persona = a.id_persona
                WHERE cp.estado_contrato = 'Activo'
                UNION
                SELECT p.id_persona AS id_pac_val, p.cedula,
                       CONCAT(p.nombre, ' ', p.apellido, ' - (Beneficiario)') AS nombre_completo
                FROM beneficiarios b
                JOIN persona p ON b.id_persona = p.id_persona
                JOIN afiliados a ON b.id_afiliado = a.ID
                JOIN contrato_plan cp ON cp.id_persona = a.id_persona
                WHERE cp.estado_contrato = 'Activo'
                AND NOT (b.parentesco = 'Hijo' AND TIMESTAMPDIFF(YEAR, p.fechanacimiento, CURDATE()) >= 25)
                ORDER BY nombre_completo ASC";
                $result_pacientes = $conn->query($sql_pacientes);
                while ($row = $result_pacientes->fetch_assoc()) {
                  echo '<option value="' . $row['id_pac_val'] . '" data-cedula="' . $row['cedula'] . '">' . $row['cedula'] . ' | ' . $row['nombre_completo'] . '</option>';
                }
                ?>
              </select>
            </div>
            
            <!-- ALERTA DE COBERTURA -->
            <div id="alerta-saldo-poliza" class="alert d-none mb-3" role="alert" style="transition: all 0.3s;">
                <h5 class="alert-heading fw-bold mb-1" id="titulo-alerta-saldo">Verificando póliza...</h5>
                <p class="mb-0" id="texto-alerta-saldo">Por favor espere.</p>
            </div>
            
          </div>

          <div id="campos-externo" style="display: none;">
            <div class="row">
              <div class="col-md-4 mb-3">
                <label for="cedula_ext" class="form-label">Cédula</label>
                <input type="text" name="cedula_ext" id="cedula_ext" class="form-control" maxlength="8" pattern="[0-9]{1,8}" title="Solo se permiten hasta 8 números" placeholder="Ej: 25123456">
              </div>
              <div class="col-md-4 mb-3">
                <label for="nombre_ext" class="form-label">Nombre</label>
                <input type="text" name="nombre_ext" id="nombre_ext" class="form-control" maxlength="50" pattern="[a-zA-ZáéíóúüñÑÁÉÍÓÚÜÑ\s]+" title="Solo se permiten letras">
              </div>
              <div class="col-md-4 mb-3">
                <label for="apellido_ext" class="form-label">Apellido</label>
                <input type="text" name="apellido_ext" id="apellido_ext" class="form-control" maxlength="50" pattern="[a-zA-ZáéíóúüñÑÁÉÍÓÚÜÑ\s]+" title="Solo se permiten letras">
              </div>
            </div>
          </div>

          <div id="campos-comunes" style="display: none;">
            <div class="mb-3">
              <label for="id_especialidad" class="form-label">Especialidad</label>
              <select name="id_especialidad" id="id_especialidad" class="form-select" required>
                <option value="" selected disabled>Seleccionar...</option>
                <?php
                $sql_especialidades = "SELECT id_especialidad, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad ASC";
                $result_espe = $conn->query($sql_especialidades);
                while ($row_e = $result_espe->fetch_assoc()) {
                  echo '<option value="' . $row_e['id_especialidad'] . '">' . $row_e['nombre_especialidad'] . '</option>';
                }
                ?>
              </select>
            </div>
            <div id="contenedor-examenes" class="mb-3" style="display: none;">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label fw-bold mb-0">Seleccione los Exámenes</label>
                <button type="button" class="btn btn-sm btn-outline-info" onclick="abrirModalNuevoExamen()">
                  <i class="fas fa-plus me-1"></i>Nuevo Examen
                </button>
              </div>
              <div id="lista-examenes" class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                <!-- Se llenará dinámicamente -->
              </div>
              <div class="mt-2 d-flex flex-wrap justify-content-between align-items-center bg-white p-2 border border-info rounded shadow-sm">
                <span class="text-success fw-bold d-none" id="cont-saldo-disponible-cita" style="font-size: 1rem;">
                    <i class="fas fa-wallet me-1"></i> Disponible en Póliza: $<span id="saldo-disponible-cita">0.00</span>
                </span>
                <span class="fw-bold fs-5 text-primary ms-auto">Costo Cita: $<span id="costo-total-cita">0.00</span></span>
              </div>
            </div>
            <div class="mb-3">
              <label for="fecha_cita" class="form-label">Fecha y Hora</label>
              <input type="datetime-local" name="fecha_cita" id="fecha_cita" class="form-control" min="<?php echo date('Y-m-d\TH:i'); ?>" required>
              <!-- ALERTA DE DISPONIBILIDAD -->
              <div id="alerta-disponibilidad" class="mt-2 d-none">
                  <div class="alert alert-danger py-2 mb-0 d-flex align-items-center">
                      <i class="fas fa-exclamation-triangle me-2"></i>
                      <span id="texto-alerta-disponibilidad" class="small fw-bold"></span>
                  </div>
              </div>
            </div>
            <div class="mb-3">
              <label for="descripcion" class="form-label">Descripción / Motivo</label>
              <textarea name="descripcion" id="descripcion" class="form-control" rows="2" required></textarea>
            </div>
          </div>

          <div class="modal-footer px-0 pb-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" id="btn-guardar-cita" class="btn btn-primary" style="display: none;">Guardar Cita</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="/IPSPUPTM/assets/js/accionescitas.js"></script>
<script>
// --- FUNCIÓN GLOBAL PARA ABRIR EL MODAL DE NUEVO EXAMEN ---
window.abrirModalNuevoExamen = function() {
    console.log("--- Intentando abrir modal de nuevo examen ---");
    const modalEl = document.getElementById('modalNuevoExamenCita');
    if (modalEl) {
        // Pre-seleccionar especialidad
        const sEsp = document.getElementById('id_especialidad');
        const sEspNuevo = document.getElementById('id_especialidad_nuevo');
        if (sEsp && sEspNuevo) sEspNuevo.value = sEsp.value;
        
        // Detección híbrida de Bootstrap (BS4 vs BS5)
        if (window.bootstrap && window.bootstrap.Modal) {
            console.log("Usando Bootstrap 5 (Vanilla)");
            const myModal = new bootstrap.Modal(modalEl);
            myModal.show();
        } else if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
            console.log("Usando Bootstrap 4 (jQuery)");
            window.jQuery(modalEl).modal('show');
            // Forzar z-index en BS4
            setTimeout(() => {
                window.jQuery('.modal-backdrop').last().css('z-index', 1999);
                window.jQuery(modalEl).css('z-index', 2000);
            }, 100);
        } else {
            console.warn("Bootstrap no detectado, usando fallback manual");
            modalEl.style.display = 'block';
            modalEl.classList.add('show');
            document.body.classList.add('modal-open');
        }
    } else {
        console.error("Error: Elemento #modalNuevoExamenCita no encontrado.");
    }
};

(function() {
    const setupFormulario = () => {
        const form = document.getElementById('form-registro-cita-general');
        const selector = document.getElementById('tipo_paciente_selector');
        
        // Contenedores
        const dInterno = document.getElementById('campos-interno');
        const dExterno = document.getElementById('campos-externo');
        const dComunes = document.getElementById('campos-comunes');
        const bGuardar = document.getElementById('btn-guardar-cita');
        
        // Inputs Internos
        const iHidden = document.getElementById('id_paciente'); 
 
        // Inputs Externos
        const inputCedula = document.getElementById('cedula_ext');
        const inputNombre = document.getElementById('nombre_ext');
        const inputApellido = document.getElementById('apellido_ext');
 
        if (!selector) return;
 
        // --- 1. LÓGICA DE MOSTRAR/OCULTAR Y REQUERIDOS ---
        selector.addEventListener('change', function() {
            const esInterno = (this.value === 'interno');
            
            dComunes.style.display = 'block';
            bGuardar.style.display = 'block';
            dInterno.style.display = esInterno ? 'block' : 'none';
            dExterno.style.display = esInterno ? 'none' : 'block';
            
            // Si es externo y había alerta, la ocultamos y reactivamos botones
            if (!esInterno) {
                const alerta = document.getElementById('alerta-saldo-poliza');
                if (alerta) alerta.classList.add('d-none');
                if (selectEspecialidad) selectEspecialidad.disabled = false;
                bGuardar.disabled = false;
                
                const contSaldo = document.getElementById('cont-saldo-disponible-cita');
                if (contSaldo) contSaldo.classList.add('d-none');
            }
 
            // Ajustar requeridos de forma masiva
            const inputsExt = dExterno.querySelectorAll('input');
            inputsExt.forEach(input => {
                if (esInterno) {
                    input.removeAttribute('required');
                    input.value = ""; 
                } else {
                    input.setAttribute('required', 'required');
                }
            });
 
            if (esInterno) {
                iHidden.setAttribute('required', 'required');
            } else {
                iHidden.removeAttribute('required');
                iHidden.value = "";
                if (window.jQuery && window.jQuery(iHidden).data('select2')) {
                    window.jQuery(iHidden).val('').trigger('change');
                }
            }
            
            // Limpiar especialidad y listado de examenes al cambiar el tipo
            if (selectEspecialidad) {
                selectEspecialidad.value = "";
                selectEspecialidad.dispatchEvent(new Event('change'));
            }
        });
        
        const revisarSaldoPoliza = (forzarLimpieza) => {
            const labelCosto = document.getElementById('costo-total-cita');
            const contSaldo = document.getElementById('cont-saldo-disponible-cita');

            if (forzarLimpieza) {
                if (selectEspecialidad) {
                    selectEspecialidad.value = ''; 
                    selectEspecialidad.dispatchEvent(new Event('change'));
                }
                if (labelCosto) labelCosto.innerText = "$ 0.00";
                if (contSaldo) contSaldo.classList.add('d-none');
            }
            
            if (selector.value !== 'interno') return;
            
            // Extracción robusta Vanilla JS para saltarnos posibles bugs de Select2
            let valCed = null;
            if (iHidden.selectedIndex >= 0 && iHidden.options[iHidden.selectedIndex]) {
                valCed = iHidden.options[iHidden.selectedIndex].getAttribute('data-cedula');
            }
            
            const alertaSaldo = document.getElementById('alerta-saldo-poliza');
            const tituloAlerta = document.getElementById('titulo-alerta-saldo');
            const textoAlerta = document.getElementById('texto-alerta-saldo');
            const btnGuardarCita = document.getElementById('btn-guardar-cita');
            
            if (valCed && valCed !== 'undefined') {
                alertaSaldo.classList.remove('d-none', 'alert-success', 'alert-danger');
                alertaSaldo.classList.add('alert-info');
                tituloAlerta.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verificando póliza...';
                textoAlerta.innerText = "Consultando saldos disponibles.";
                btnGuardarCita.disabled = true;
                
                fetch(`/IPSPUPTM/app/pagos/get_afiliado_plan_limits.php?cedula=${valCed}`)
                    .then(r => r.json())
                    .then(data => {
                        alertaSaldo.classList.remove('alert-info');
                        if (data.success) {
                            const afil = data.afiliado;
                            const saldo = afil.saldo_disponible;
                            const plan = afil.plan;
                            
                            const contSaldo = document.getElementById('cont-saldo-disponible-cita');
                            const valSaldo = document.getElementById('saldo-disponible-cita');
                            if (contSaldo && valSaldo) {
                                valSaldo.innerText = saldo.toFixed(2);
                                contSaldo.classList.remove('d-none', 'text-success', 'text-danger');
                                contSaldo.classList.add(saldo > 0 ? 'text-success' : 'text-danger');
                            }
                            
                            // 1. Verificar Pago Inicial
                            if (!afil.pago_inicial_ok) {
                                alertaSaldo.classList.add('alert-danger');
                                tituloAlerta.innerHTML = '<i class="fas fa-hand-holding-usd me-2"></i>Póliza Inactiva';
                                textoAlerta.innerHTML = `<strong>Debe realizar el pago inicial (30%)</strong> para activar los beneficios del plan "${plan}".<br><span class="fw-bold fs-6">No es posible agendar citas hasta completar este pago.</span>`;
                                btnGuardarCita.disabled = true;
                                if (selectEspecialidad) selectEspecialidad.disabled = true;
                                return;
                            }

                            // 2. Verificar Solvencia (Deuda)
                            if (!afil.solvente) {
                                alertaSaldo.classList.add('alert-danger');
                                tituloAlerta.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Paciente Insolvente';
                                textoAlerta.innerHTML = `El afiliado tiene una deuda de <strong>${afil.meses_deuda} meses</strong>.<br><span class="fw-bold fs-6">Debe ponerse al día (máximo 2 meses de deuda) para usar el plan "${plan}".</span>`;
                                btnGuardarCita.disabled = true;
                                if (selectEspecialidad) selectEspecialidad.disabled = true;
                                return;
                            }

                            // 3. Verificar Saldo Disponible
                            if (saldo <= 0) {
                                alertaSaldo.classList.add('alert-danger');
                                tituloAlerta.innerHTML = '<i class="fas fa-ban me-2"></i>Póliza Agotada';
                                textoAlerta.innerHTML = `<strong>Cobertura de $0.00 restante.</strong> No es posible agendar esta cita bajo el plan "${plan}".<br><span class="fw-bold fs-6">Debe agendar al paciente como Comunidad UPTM (Externo) para que la pague por su cuenta.</span>`;
                                btnGuardarCita.disabled = true;
                                if (selectEspecialidad) selectEspecialidad.disabled = true;
                            } else {
                                alertaSaldo.classList.add('alert-success');
                                tituloAlerta.innerHTML = '<i class="fas fa-check-circle me-2"></i>Cobertura Activa';
                                textoAlerta.innerHTML = `Saldo disponible global en el plan <strong>${plan}</strong>: <span class="fw-bold fs-5 text-success">$${saldo.toFixed(2)}</span>`;
                                btnGuardarCita.disabled = false;
                                if (selectEspecialidad) selectEspecialidad.disabled = false;
                            }
                        } else {
                            alertaSaldo.classList.add('alert-danger');
                            tituloAlerta.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Error de Póliza';
                            textoAlerta.innerHTML = `No se halló un contrato activo para este afiliado. Debe registrarlo como Externo.`;
                            btnGuardarCita.disabled = true;
                            if (selectEspecialidad) selectEspecialidad.disabled = true;
                        }
                    })
                    .catch(e => {
                        alertaSaldo.classList.replace('alert-info', 'alert-warning');
                        tituloAlerta.innerText = "Error de conexión";
                        textoAlerta.innerText = "No pudimos validar el saldo.";
                    });
            } else {
                alertaSaldo.classList.add('d-none');
            }
        };

        // Fallback: evento nativo en caso de que Select2 no esté disponible.
        iHidden.addEventListener('change', () => revisarSaldoPoliza(true));
        // El evento select2:select se registra dentro del onload de Select2 más abajo.

        // Exponer al scope global para que el bloque Select2 (otro <script>) pueda llamarla
        window._revisarSaldoPolizaCitas = () => revisarSaldoPoliza(true);
 
        // --- 1.5 LÓGICA DE EXÁMENES POR ESPECIALIDAD ---
        const selectEspecialidad = document.getElementById('id_especialidad');
        const contExamenes = document.getElementById('contenedor-examenes');
        const listaExamenes = document.getElementById('lista-examenes');
        const labelCosto = document.getElementById('costo-total-cita');
 
        if (selectEspecialidad) {
            selectEspecialidad.addEventListener('change', function() {
                const idEsp = this.value;
                const tipoPac = selector.value;
                const idPac = (tipoPac === 'interno') ? iHidden.value : '';

                // Fallback: SI la alerta está d-none (nunca se calculó), llamarlo ahora.
                const alertaSaldo = document.getElementById('alerta-saldo-poliza');
                if (tipoPac === 'interno' && alertaSaldo && alertaSaldo.classList.contains('d-none')) {
                    revisarSaldoPoliza(false);
                }

                if (!idEsp) {
                    contExamenes.style.display = 'none';
                    if (labelCosto) labelCosto.innerText = "$ 0.00";
                    return;
                }
    
                fetch(`/IPSPUPTM/app/citas/get_examenes.php?id_especialidad=${idEsp}&tipo_pac=${tipoPac}&id_paciente=${idPac}`)
                    .then(r => r.json())
                    .then(data => {
                        listaExamenes.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(ex => {
                                const disableAttr = ex.is_disabled ? 'disabled' : '';
                                const txtClass = ex.is_disabled ? 'text-muted text-decoration-line-through' : '';
                                const infoDisp = ex.disponibles === 'ilimitado' ? '' : (ex.is_disabled ? '(Agotado)' : `(Restan: ${ex.disponibles})`);

                                const div = document.createElement('div');
                                div.className = 'form-check';
                                div.innerHTML = `
                                    <input class="form-check-input check-examen" type="radio" name="examenes[]" value="${ex.ID_examen}" id="ex_${ex.ID_examen}" data-precio="${ex.precio}" ${disableAttr} required>
                                    <label class="form-check-label ${txtClass}" for="ex_${ex.ID_examen}">
                                        ${ex.nombre_examen} - <span class="${ex.is_disabled ? 'text-muted' : 'text-primary'}">$${ex.precio}</span> <small class="text-danger fw-bold ms-2">${infoDisp}</small>
                                    </label>
                                `;
                                listaExamenes.appendChild(div);
                            });
                            contExamenes.style.display = 'block';
                            
                            // Añadir evento a los nuevos radios
                            document.querySelectorAll('.check-examen').forEach(chk => {
                                chk.addEventListener('change', () => {
                                    calcularCostoTotal();
                                    validarDisponibilidad();
                                });
                            });
                        } else {
                            listaExamenes.innerHTML = '<p class="text-muted small">No hay exámenes registrados para esta especialidad.</p>';
                            contExamenes.style.display = 'block';
                        }
                        calcularCostoTotal();
                    });
            });
        }
 
        function calcularCostoTotal() {
            let total = 0;
            document.querySelectorAll('.check-examen:checked').forEach(chk => {
                total += parseFloat(chk.dataset.precio || 0);
            });
            if (labelCosto) labelCosto.innerText = total.toFixed(2);
        }

        // --- 1.8 VALIDACIÓN DE DISPONIBILIDAD (TIEMPO REAL) ---
        const inputFechaCita = document.getElementById('fecha_cita');
        const alertaDisponibilidad = document.getElementById('alerta-disponibilidad');
        const textoAlertaDisponibilidad = document.getElementById('texto-alerta-disponibilidad');

        const validarDisponibilidad = () => {
            const fecha = inputFechaCita.value;
            const checks = document.querySelectorAll('.check-examen:checked');
            const examenes = Array.from(checks).map(c => c.value);

            if (!fecha || examenes.length === 0) {
                alertaDisponibilidad.classList.add('d-none');
                checkBotonesEstado();
                return;
            }

            const formData = new FormData();
            formData.append('fecha_cita', fecha);
            examenes.forEach(id => formData.append('examenes[]', id));

            fetch('/IPSPUPTM/app/citas/verificar_disponibilidad.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.count > 0) {
                    alertaDisponibilidad.classList.remove('d-none');
                    textoAlertaDisponibilidad.innerHTML = `Horario ocupado para: ${data.conflicts.join(', ')}. Por favor elija otra hora u otro examen.`;
                    bGuardar.disabled = true;
                    bGuardar.classList.replace('btn-primary', 'btn-danger');
                } else {
                    alertaDisponibilidad.classList.add('d-none');
                    checkBotonesEstado();
                }
            })
            .catch(err => console.error("Error validando disponibilidad:", err));
        };

        const checkBotonesEstado = () => {
            // Re-evaluar saldo de póliza y otros bloqueos si existen
            // Por ahora, solo restauramos el botón si no hay conflictos
            bGuardar.disabled = false;
            bGuardar.classList.replace('btn-danger', 'btn-primary');
            
            // Si es interno, respetamos la decisión del validador de póliza
            if (selector.value === 'interno') {
                const alertaSaldo = document.getElementById('alerta-saldo-poliza');
                if (alertaSaldo && alertaSaldo.classList.contains('alert-danger')) {
                    bGuardar.disabled = true;
                }
            }
        };

        if (inputFechaCita) {
            inputFechaCita.addEventListener('change', validarDisponibilidad);
        }
 
        // --- 2. BÚSQUEDA EN TIEMPO REAL (EXTERNOS) ---
        if (inputCedula) {
            inputCedula.addEventListener('blur', function() {
                const cedula = this.value.trim();
                if (cedula.length >= 6) {
                    fetch(`/IPSPUPTM/app/citas/buscar_externo.php?cedula=${cedula}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.exists) {
                                inputNombre.value = data.nombre;
                                inputApellido.value = data.apellido;
                                alertify.success("Paciente encontrado en el historial.");
                            }
                        })
                        .catch(err => console.error("Error buscando externo:", err));
                }
            });
        }

        // Sanitización en tiempo real para externos (máx 8 números)
        if (inputCedula) {
            inputCedula.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 8);
            });
        }
        if (inputNombre) {
            inputNombre.addEventListener('input', function() {
                this.value = this.value.replace(/[^a-zA-ZáéíóúüñÑÁÉÍÓÚÜÑ\s]/g, '');
            });
        }
        if (inputApellido) {
            inputApellido.addEventListener('input', function() {
                this.value = this.value.replace(/[^a-zA-ZáéíóúüñÑÁÉÍÓÚÜÑ\s]/g, '');
            });
        }
 
        // --- 5. LÓGICA DE NUEVO EXAMEN DESDE CITA ---
        const btnNuevoEx = document.getElementById('btn-nuevo-examen-cita');
        const modalEl = document.getElementById('modalNuevoExamenCita');
        const formNuevoEx = document.getElementById('form-nuevo-examen-cita');
        const selectEspNuevo = document.getElementById('id_especialidad_nuevo');
 

 
        if (formNuevoEx) {
            formNuevoEx.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
 
                fetch('/IPSPUPTM/app/pagos/guardar_examen_nuevo.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alertify.success(data.message);
                        const mInstance = bootstrap.Modal.getInstance(document.getElementById('modalNuevoExamenCita'));
                        if (mInstance) mInstance.hide();
                        formNuevoEx.reset();
                        
                        if (selectEspecialidad) {
                            selectEspecialidad.dispatchEvent(new Event('change'));
                        }
                    } else {
                        alertify.error(data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alertify.error("Error al crear el examen");
                });
            });
        }
 
        // --- 4. ENVÍO DE DATOS (CITA) ---
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (selector.value === 'interno' && !iHidden.value) {
                    alertify.error("Por favor, seleccione un paciente de la lista de Afiliados/Beneficiarios.");
                    return;
                }
                const formData = new FormData(this);
                fetch('/IPSPUPTM/app/citas/modales/formulario/guardar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alertify.success(data.message);
                        bootstrap.Modal.getInstance(document.getElementById('formulariomodal')).hide();
                        form.reset();
                        dComunes.style.display = 'none';
                        bGuardar.style.display = 'none';
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alertify.error(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alertify.error("Error de conexión al guardar");
                });
            });
        }
    };
 
    setupFormulario();
})();
</script>

<script>
(function initSelect2Citas() {
    if (window.jQuery) {
        var s2 = document.createElement('script');
        s2.src = '/IPSPUPTM/assets/select2/js/select2.min.js';
        
        s2.onload = function() {
            window.jQuery('#id_paciente').select2({
                dropdownParent: window.jQuery('#formulariomodal'),
                width: '100%',
                language: 'es'
            });

            // Registrar aquí, después de que Select2 está inicializado.
            window.jQuery('#id_paciente').on('select2:select', function() {
                if (typeof window._revisarSaldoPolizaCitas === 'function') {
                    window._revisarSaldoPolizaCitas();
                }
            });
        };
        
        document.body.appendChild(s2);
    } else {
        setTimeout(initSelect2Citas, 50);
    }
})();
</script>