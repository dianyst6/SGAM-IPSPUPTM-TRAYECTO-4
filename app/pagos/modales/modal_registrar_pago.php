<?php
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
?>

<!-- Agregar CSS de Select2 -->
<link href="/IPSPUPTM/assets/select2/css/select2.min.css" rel="stylesheet" />

<div class="modal fade" id="modalPago" tabindex="-1" aria-labelledby="labelPago" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="labelPago">Nuevo Pago de Cuota</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/IPSPUPTM/app/pagos/modales/procesar_pago.php" method="POST" onsubmit="return validarPagoInicial()">
                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Seleccionar Contrato / Afiliado</label>
                        <select name="id_contrato" id="id_contrato_select" class="form-select" required
                            onchange="consultarSaldo(this.value)">
                            <option value="">Seleccione un contrato...</option>
                            <?php
                            $sql = "SELECT cp.ID_contrato, per.nombre, per.apellido, pl.nombre_plan 
                                    FROM contrato_plan cp 
                                    INNER JOIN persona per ON cp.id_persona = per.id_persona
                                    INNER JOIN planes pl ON cp.ID_planes_contrato = pl.ID_planes
                                    WHERE cp.estado_contrato = 'Activo'";
                            
                            $resultado = mysqli_query($conn, $sql); 
                            if ($resultado) {
                                while ($fila = mysqli_fetch_assoc($resultado)) {
                                    echo '<option value="' . $fila['ID_contrato'] . '">';
                                    echo $fila['nombre'] . ' ' . $fila['apellido'] . ' - ' . $fila['nombre_plan'];
                                    echo '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div id="info_saldo" class="alert alert-info d-none mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-info-circle"></i> Saldo Pendiente:</span>
                            <strong id="monto_pendiente">$ 0.00</strong>
                        </div>
                    </div>

                    <!-- Indicador de progreso del Pago Inicial -->
                    <div id="info_pago_inicial" class="alert alert-warning d-none mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-piggy-bank"></i> Pago Inicial (30%):</span>
                            <strong id="progreso_inicial">$ 0.00 / $ 0.00</strong>
                        </div>
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar bg-success" id="barra_inicial" role="progressbar" style="width: 0%"></div>
                        </div>
                        <small class="text-muted mt-1 d-block" id="mensaje_inicial"></small>
                    </div>

                    <!-- Formulario de Pago -->
                    <div id="contenedor_formulario_pago">
                        <!-- Selector de Tipo de Pago -->
                        <div class="mb-3">
                        <label class="form-label">Tipo de Pago</label>
                        <select name="tipo_pago" id="tipo_pago_select" class="form-select" required onchange="cambiarTipoPago()">
                            <option value="Pago Inicial">Pago Inicial (30%)</option>
                            <option value="Abonos">Abonos (Cuotas)</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Monto a Pagar ($)</label>
                            <input type="number" step="0.01" name="monto_cuota" id="monto_cuota_input" class="form-control" required
                                placeholder="0.00" oninput="calcularBolivares()">
                            <small class="text-muted d-block mt-1">
                                Tasa BCV: <span id="tasa_bcv_label">Cargando...</span> Bs |
                                Equivalente: <strong class="text-success" id="monto_bs_label">0.00 Bs</strong>
                            </small>
                            <input type="hidden" name="tasa_bcv" id="tasa_bcv_input" value="0">
                            <input type="hidden" name="monto_bs" id="monto_bs_input" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha de Pago</label>
                            <?php date_default_timezone_set('America/Caracas'); ?>
                            <input type="date" name="fecha_pago" class="form-control"
                                value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3" id="contenedor_metodo">
                            <label class="form-label">Método de Pago</label>
                            <select name="metodo_pago" class="form-select" required>
                                <option value="Transferencia">Transferencia</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Punto de Venta">Punto de Venta</option>
                                <option value="Pago Móvil">Pago Móvil</option>
                            </select>
                        </div>
                    </div>

                    <div class="row" id="contenedor_abonos" style="display: none;">
                        <div class="col-12 mb-3">
                            <label class="form-label">Seleccione las Cuotas a Pagar</label>
                            <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                <table class="table table-sm table-bordered table-hover mb-0">
                                    <thead class="table-light text-center" style="position: sticky; top: 0; z-index: 1;">
                                        <tr>
                                            <th style="width: 50px;"><input type="checkbox" id="check_all_abonos" onchange="toggleAllAbonos(this)"></th>
                                            <th>N° Cuota</th>
                                            <th class="text-end">Monto ($)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla_abonos_body">
                                        <!-- Filas dinámicas generadas en JS -->
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted d-block mt-1">Cuota fija calculada en base a los meses de duración del contrato.</small>
                        </div>
                    </div>

                    </div> <!-- Fin contenedor_formulario_pago -->

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn_guardar_pago">Guardar Pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let tasaBCVAbsoluta = 0;
// Variable global para guardar el estado del pago inicial
let estadoPagoInicial = {};

function formatearMonedaVE(valor) {
    return new Intl.NumberFormat('es-VE', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    }).format(valor);
}

function obtenerTasaBCV() {
    fetch('https://ve.dolarapi.com/v1/dolares/oficial')
        .then(response => response.json())
        .then(data => {
            if (data && data.promedio) {
                tasaBCVAbsoluta = data.promedio;
            } else if (data && data.venta) {
                tasaBCVAbsoluta = data.venta;
            }
            document.getElementById('tasa_bcv_label').innerText = formatearMonedaVE(tasaBCVAbsoluta);
            document.getElementById('tasa_bcv_input').value = tasaBCVAbsoluta;
            calcularBolivares();
        })
        .catch(error => {
            console.error('Error obteniendo tasa BCV:', error);
            document.getElementById('tasa_bcv_label').innerText = 'Error API';
        });
}

function calcularBolivares() {
    const inputMonto = document.getElementById('monto_cuota_input');
    const montoUSD = parseFloat(inputMonto.value) || 0;
    const montoBs = montoUSD * tasaBCVAbsoluta;
    
    document.getElementById('monto_bs_label').innerText = formatearMonedaVE(montoBs) + ' Bs';
    document.getElementById('monto_bs_input').value = montoBs.toFixed(2);
}

function cambiarTipoPago() {
    const tipoPago = document.getElementById('tipo_pago_select').value;
    const contenedorAbonos = document.getElementById('contenedor_abonos');
    const inputMonto = document.getElementById('monto_cuota_input');

    // Reiniciar inputs por defecto
    contenedorAbonos.style.display = 'none';
    inputMonto.readOnly = false;

    if (tipoPago === 'Pago Inicial') {
        inputMonto.value = ''; // Espera que el usuario introduzca si paga parte o todo
    } else if (tipoPago === 'Abonos') {
        contenedorAbonos.style.display = '';
        inputMonto.readOnly = true;
        calcularMontoAbonos(); // Actualizar el monto basado en los checkboxes seleccionados
    }
    
    calcularBolivares();
}

function toggleAllAbonos(checkbox) {
    const checkboxes = document.querySelectorAll('input[name="cuotas_abono[]"]:not(:disabled)');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    calcularMontoAbonos();
}

function calcularMontoAbonos() {
    const checkboxes = document.querySelectorAll('input[name="cuotas_abono[]"]:checked');
    let total = 0;
    const cuotaFija = parseFloat(estadoPagoInicial.cuota_fija) || 0;
    
    // Si la cuota elegida es la última, podría ser un monto diferente si hay decimales, 
    // pero por ahora lo calculamos con la cuota fija x cantidad.
    // Lo más exacto es sumar el value del checkbox o usar cuota_fija.
    // Usaremos cuota_fija y luego nos aseguramos de no pasar del saldo pendiente.
    total = checkboxes.length * cuotaFija;
    
    // Asegurarse de no exceder el saldo
    const saldoPendiente = parseFloat(estadoPagoInicial.saldo) || 0;
    if (total > saldoPendiente) {
        total = saldoPendiente;
    }

    const inputMonto = document.getElementById('monto_cuota_input');
    inputMonto.value = total > 0 ? total.toFixed(2) : '';
    calcularBolivares();
}

function generarTablaAbonos(data) {
    const tbody = document.getElementById('tabla_abonos_body');
    tbody.innerHTML = '';
    document.getElementById('check_all_abonos').checked = false;

    const proximaCuota = parseInt(data.proxima_cuota) || 1;
    const totalMeses = parseInt(data.total_meses) || 1;
    const cuotaFija = parseFloat(data.cuota_fija) || 0;
    let saldoRestante = parseFloat(data.saldo) || 0;

    for (let i = proximaCuota; i <= totalMeses; i++) {
        let montoFila = cuotaFija;
        // Si es la última cuota, asignar el saldo restante exacto para evitar errores de redondeo
        if (i === totalMeses && saldoRestante < cuotaFija && saldoRestante > 0) {
             montoFila = saldoRestante;
        } else if (i === totalMeses && saldoRestante > cuotaFija) {
             // Si el saldo es mayor a la cuota, y es la última, cobramos lo que quede.
             montoFila = saldoRestante;
        }
        
        saldoRestante -= montoFila;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center">
                <input class="form-check-input" type="checkbox" name="cuotas_abono[]" value="${i}" onchange="calcularMontoAbonos()">
            </td>
            <td class="text-center">${i}</td>
            <td class="text-end">$ ${montoFila.toFixed(2)}</td>
        `;
        tbody.appendChild(tr);
        
        if(saldoRestante <= 0) break;
    }
}

function validarPagoInicial() {
    const tipoPago = document.getElementById('tipo_pago_select').value;
    const inputMonto = document.getElementById('monto_cuota_input');
    const montoIngresado = parseFloat(inputMonto.value) || 0;

    if (tipoPago === 'Pago Inicial' && estadoPagoInicial && estadoPagoInicial.pago_inicial_pendiente !== undefined) {
        const montoPendiente = parseFloat(estadoPagoInicial.pago_inicial_pendiente);
        
        if (montoIngresado > montoPendiente) {
            if (typeof alertify !== 'undefined') {
                alertify.error('El monto no puede superar el pago inicial pendiente ($' + montoPendiente.toFixed(2) + ').');
            } else {
                alert('El monto no puede superar el pago inicial pendiente ($' + montoPendiente.toFixed(2) + ').');
            }
            inputMonto.focus();
            return false;
        }
    }
    return true;
}

// Inicializar Select2 en el modal, asegúrate que jQuery esté cargado primero
document.addEventListener("DOMContentLoaded", function() {
    obtenerTasaBCV();
    // Cargar dinámicamente el script de Select2 SOLO después de que el DOM esté listo
    // Esto asegura que jQuery, que se carga al final de layout.php, ya esté disponible.
    var script = document.createElement('script');
    script.src = '/IPSPUPTM/assets/select2/js/select2.min.js';
    script.onload = function() {
        $('#modalPago').on('shown.bs.modal', function () {
            $('#id_contrato_select').select2({
                dropdownParent: $('#modalPago'), // Crucial para modales Bootstrap
                placeholder: "Seleccione un contrato...",
                width: '100%',
                language: {
                    noResults: function() {
                        return "No se encontraron resultados";
                    }
                }
            });
        });

        // Reemplazar el onchange HTML con evento de Select2
        $('#id_contrato_select').on('select2:select', function (e) {
            var idSeleccionado = e.params.data.id;
            consultarSaldo(idSeleccionado);
        });
    };
    document.head.appendChild(script);
});

function consultarSaldo(idContrato) {
    const contenedor = document.getElementById('info_saldo');
    const spanMonto = document.getElementById('monto_pendiente');
    const btnGuardar = document.getElementById('btn_guardar_pago');
    const infoPagoInicial = document.getElementById('info_pago_inicial');
    const tipoPagoSelect = document.getElementById('tipo_pago_select');
    const contenedorFormulario = document.getElementById('contenedor_formulario_pago');

    if (!idContrato) {
        contenedor.classList.add('d-none');
        infoPagoInicial.classList.add('d-none');
        contenedorFormulario.style.display = 'none';
        return;
    }

    let formData = new FormData();
    formData.append('id_contrato', idContrato);

    fetch('/IPSPUPTM/app/pagos/modales/obtener_saldo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Guardar estado global
        estadoPagoInicial = data;

        // Mostrar saldo general
        spanMonto.innerText = '$ ' + data.saldo;
        contenedor.classList.remove('d-none');

        // --- LÓGICA DE PAGO INICIAL ---
        const progresoInicial = document.getElementById('progreso_inicial');
        const barraInicial = document.getElementById('barra_inicial');
        const mensajeInicial = document.getElementById('mensaje_inicial');
        const contenedorCuota = document.getElementById('contenedor_cuota');

        // Mostrar progreso del pago inicial
        progresoInicial.innerText = '$ ' + data.pago_inicial_pagado + ' / $ ' + data.pago_inicial_requerido;
        const porcentaje = (parseFloat(data.pago_inicial_pagado) / parseFloat(data.pago_inicial_requerido)) * 100;
        barraInicial.style.width = Math.min(porcentaje, 100) + '%';
        infoPagoInicial.classList.remove('d-none');

        if (parseFloat(data.saldo) <= 0) {
            // Plan totalmente solventado
            contenedor.className = "alert alert-success mb-3";
            spanMonto.innerText = "¡Plan Solventado!";
            btnGuardar.disabled = true;
            infoPagoInicial.classList.add('d-none');
            contenedorFormulario.style.display = 'none';

        } else if (data.pago_inicial_completo) {
            // Ya completó el 30%: puede pagar cuotas o abonos
            contenedorFormulario.style.display = '';
            infoPagoInicial.className = 'alert alert-success mb-3';
            mensajeInicial.innerText = '✅ Pago inicial completado.';
            tipoPagoSelect.disabled = false;
            
            const optionAbonos = tipoPagoSelect.querySelector('option[value="Abonos"]');
            optionAbonos.disabled = false;
            tipoPagoSelect.value = 'Abonos'; // Seleccionar Abonos por defecto para mostrar la tabla
            generarTablaAbonos(data);
            
            tipoPagoSelect.querySelector('option[value="Pago Inicial"]').disabled = true;
            
            btnGuardar.disabled = false;
            contenedor.className = "alert alert-warning mb-3";
            
            cambiarTipoPago(); // Refrescar UI según selección

        } else {
            // NO ha completado el 30%: solo puede pagar "Pago Inicial"
            contenedorFormulario.style.display = '';
            infoPagoInicial.className = 'alert alert-warning mb-3';
            mensajeInicial.innerText = '⚠️ Debe completar el pago inicial antes de registrar abonos.';
            tipoPagoSelect.disabled = false;
            
            tipoPagoSelect.value = 'Pago Inicial';
            tipoPagoSelect.querySelector('option[value="Abonos"]').disabled = true;
            tipoPagoSelect.querySelector('option[value="Pago Inicial"]').disabled = false;
            
            btnGuardar.disabled = false;
            contenedor.className = "alert alert-warning mb-3";
            
            cambiarTipoPago();
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>