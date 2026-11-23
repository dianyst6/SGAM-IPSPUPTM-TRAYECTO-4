<?php
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
?>

<!-- Agregar CSS de Select2 -->
<link href="/IPSPUPTM/assets/select2/css/select2.min.css" rel="stylesheet" />

<div class="modal fade" id="modalPago" tabindex="-1" aria-labelledby="labelPago" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="labelPago">Registrar Pago de Contrato</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/IPSPUPTM/app/pagos/modales/procesar_pago.php" method="POST"
                onsubmit="return validarPagoPlan()">
                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Contrato / Afiliado</label>
                        <select name="id_contrato" id="id_contrato_select" class="form-select" required>
                            <option value="">Seleccione un contrato con saldo pendiente...</option>
                            <?php
                            $sql = "SELECT cp.ID_contrato, per.nombre, per.apellido, pl.nombre_plan,
                                           cp.anio, cp.estado_contrato, cp.monto_total,
                                           (SELECT COALESCE(SUM(pc.monto_cuota), 0)
                                              FROM pagos_contrato pc
                                             WHERE pc.ID_contrato = cp.ID_contrato
                                               AND pc.tipo_pago = 'Pago Inicial') AS inicial_pagado,
                                           (SELECT COUNT(*)
                                              FROM contrato_cuotas cc
                                             WHERE cc.id_contrato = cp.ID_contrato
                                               AND cc.monto_pagado < cc.monto) AS cuotas_pendientes
                                    FROM contrato_plan cp
                                    INNER JOIN persona per ON cp.id_persona = per.id_persona
                                    INNER JOIN planes pl ON cp.ID_planes_contrato = pl.ID_planes
                                    WHERE cp.estado_contrato <> 'Inactivo'
                                    HAVING inicial_pagado < ROUND(cp.monto_total * 0.30, 2)
                                        OR cuotas_pendientes > 0
                                    ORDER BY cp.anio DESC, per.nombre ASC";

                            $resultado = mysqli_query($conn, $sql);
                            if ($resultado) {
                                while ($fila = mysqli_fetch_assoc($resultado)) {
                                    $anio = $fila['anio'] ? ' (' . $fila['anio'] . ')' : '';
                                    echo '<option value="' . (int) $fila['ID_contrato'] . '">';
                                    echo htmlspecialchars(
                                        trim($fila['nombre'] . ' ' . $fila['apellido'])
                                        . ' - ' . $fila['nombre_plan'] . $anio
                                        . ' - ' . $fila['estado_contrato']
                                    );
                                    echo '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div id="panel_contrato" class="d-none">
                        <div id="estado_contrato_alerta" class="alert alert-info mb-3"></div>

                        <div class="row mb-3">
                            <div class="col-md-4 col-12 mb-2">
                                <small class="text-muted d-block">Saldo total pendiente</small>
                                <strong id="saldo_total" class="fs-5">$ 0.00</strong>
                            </div>
                            <div class="col-md-4 col-12 mb-2">
                                <small class="text-muted d-block">Cobertura disponible</small>
                                <strong id="cobertura_info" class="fs-5">$ 0.00</strong>
                            </div>
                            <div class="col-md-4 col-12 mb-2">
                                <small class="text-muted d-block">Cuotas vencidas</small>
                                <strong id="mora_info" class="fs-5">0</strong>
                            </div>
                        </div>

                        <div id="panel_inicial" class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-piggy-bank me-1"></i> Pago inicial (30 %)</span>
                                <strong id="progreso_inicial">$ 0.00 / $ 0.00</strong>
                            </div>
                            <div class="progress mt-2" style="height: 8px;">
                                <div class="progress-bar bg-success" id="barra_inicial" role="progressbar"
                                    style="width: 0%"></div>
                            </div>
                            <small class="text-muted mt-1 d-block" id="mensaje_inicial"></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo de Pago</label>
                            <select name="tipo_pago" id="tipo_pago_select" class="form-select" required
                                onchange="cambiarTipoPagoPlan()">
                                <option value="Pago Inicial">Pago Inicial (30 %)</option>
                                <option value="Cuota">Cuotas mensuales</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Monto a Pagar ($)</label>
                                <input type="number" step="0.01" min="0.01" name="monto_cuota"
                                    id="monto_cuota_input" class="form-control" required placeholder="0.00"
                                    oninput="recalcularBolivares()">
                                <small class="text-muted d-block mt-1">
                                    Tasa BCV: <span id="tasa_bcv_label">Cargando...</span> Bs |
                                    Equivalente: <strong class="text-success" id="monto_bs_label">0.00 Bs</strong>
                                </small>
                                <input type="hidden" name="tasa_bcv" id="tasa_bcv_input" value="0">
                                <input type="hidden" name="monto_bs" id="monto_bs_input" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de Pago</label>
                                <input type="date" name="fecha_pago" class="form-control"
                                    value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Método de Pago</label>
                            <select name="metodo_pago" class="form-select" required>
                                <option value="Transferencia">Transferencia</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Punto de Venta">Punto de Venta</option>
                                <option value="Pago Móvil">Pago Móvil</option>
                            </select>
                        </div>

                        <div id="panel_pago_inicial">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">
                                    Puede abonar cualquier monto hasta completar el 30 %.
                                </small>
                                <button type="button" class="btn btn-sm btn-outline-success"
                                    onclick="pagarTodoInicial()">
                                    Pagar todo
                                </button>
                            </div>
                        </div>

                        <div id="panel_cuotas" class="d-none">
                            <div class="row align-items-end mb-2">
                                <div class="col-md-5 col-12">
                                    <label class="form-label" for="cantidad_cuotas">Cantidad de cuotas</label>
                                    <input type="number" name="cantidad_cuotas" id="cantidad_cuotas"
                                        class="form-control" min="1" value="1" oninput="actualizarTotalCuotas()">
                                </div>
                                <div class="col-md-7 col-12 text-md-end mt-2 mt-md-0">
                                    <small class="text-muted d-block" id="detalle_cuotas">Se aplicará a la cuota más antigua.</small>
                                    <strong>Total: <span id="total_cuotas">$ 0.00</span></strong>
                                </div>
                            </div>
                            <small class="text-muted d-block mb-2">
                                Las cuotas se pagan completas y siempre se aplican a las más antiguas pendientes.
                            </small>
                            <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                                <table class="table table-sm table-bordered table-hover mb-0">
                                    <thead class="table-light text-center"
                                        style="position: sticky; top: 0; z-index: 1;">
                                        <tr>
                                            <th>N°</th>
                                            <th>Vencimiento</th>
                                            <th class="text-end">Monto ($)</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla_cuotas_body">
                                        <!-- Filas dinámicas -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
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
let estadoContrato = null;
let tasaBCVAbsoluta = 0;

function formatearMonedaVE(valor) {
    return new Intl.NumberFormat('es-VE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(Number(valor) || 0);
}

function obtenerTasaBCV() {
    fetch('https://ve.dolarapi.com/v1/dolares/oficial')
        .then(response => response.json())
        .then(data => {
            if (data && data.promedio) {
                tasaBCVAbsoluta = parseFloat(data.promedio);
            } else if (data && data.venta) {
                tasaBCVAbsoluta = parseFloat(data.venta);
            }
            document.getElementById('tasa_bcv_label').innerText = formatearMonedaVE(tasaBCVAbsoluta);
            document.getElementById('tasa_bcv_input').value = tasaBCVAbsoluta;
            recalcularBolivares();
        })
        .catch(error => {
            console.error('Error obteniendo tasa BCV:', error);
            document.getElementById('tasa_bcv_label').innerText = 'No disponible';
        });
}

function recalcularBolivares() {
    const montoInput = document.getElementById('monto_cuota_input');
    if (!montoInput) return;
    const montoUSD = parseFloat(montoInput.value) || 0;
    const montoBs = montoUSD * tasaBCVAbsoluta;

    document.getElementById('monto_bs_label').innerText = formatearMonedaVE(montoBs) + ' Bs';
    document.getElementById('monto_bs_input').value = montoBs.toFixed(2);
}

function cambiarTipoPagoPlan() {
    if (!estadoContrato) return;

    const tipoPago = document.getElementById('tipo_pago_select').value;
    const panelInicial = document.getElementById('panel_pago_inicial');
    const panelCuotas = document.getElementById('panel_cuotas');
    const montoInput = document.getElementById('monto_cuota_input');

    if (tipoPago === 'Pago Inicial') {
        panelInicial.classList.remove('d-none');
        panelCuotas.classList.add('d-none');
        montoInput.readOnly = false;
        montoInput.max = estadoContrato.inicial_pendiente;
        montoInput.value = estadoContrato.inicial_pendiente > 0
            ? Number(estadoContrato.inicial_pendiente).toFixed(2)
            : '';
    } else {
        panelInicial.classList.add('d-none');
        panelCuotas.classList.remove('d-none');
        montoInput.readOnly = true;

        const cantidadInput = document.getElementById('cantidad_cuotas');
        cantidadInput.max = estadoContrato.cuotas_pendientes.length;
        cantidadInput.value = 1;
        actualizarTotalCuotas();
    }

    recalcularBolivares();
}

function pagarTodoInicial() {
    if (!estadoContrato) return;
    const montoInput = document.getElementById('monto_cuota_input');
    montoInput.value = Number(estadoContrato.inicial_pendiente).toFixed(2);
    recalcularBolivares();
}

function actualizarTotalCuotas() {
    if (!estadoContrato) return;

    const pendientes = estadoContrato.cuotas_pendientes || [];
    const cantidadInput = document.getElementById('cantidad_cuotas');
    let cantidad = parseInt(cantidadInput.value, 10) || 1;
    cantidad = Math.max(1, Math.min(cantidad, pendientes.length || 1));
    cantidadInput.value = cantidad;

    let total = 0;
    for (let i = 0; i < pendientes.length; i++) {
        const fila = document.getElementById('fila_cuota_' + i);
        if (fila) fila.classList.remove('table-primary');

        if (i < cantidad) {
            total += parseFloat(pendientes[i].saldo) || 0;
            if (fila) fila.classList.add('table-primary');
        }
    }

    total = Math.round(total * 100) / 100;
    document.getElementById('monto_cuota_input').value = total > 0 ? total.toFixed(2) : '';
    document.getElementById('total_cuotas').innerText = '$ ' + formatearMonedaVE(total);

    const primera = pendientes[0] ? pendientes[0].numero : '-';
    const ultima = pendientes[cantidad - 1] ? pendientes[cantidad - 1].numero : '-';
    document.getElementById('detalle_cuotas').innerText = pendientes.length
        ? 'Se aplicará de la cuota ' + primera + ' a la ' + ultima + '.'
        : 'No hay cuotas pendientes.';

    recalcularBolivares();
}

function generarTablaCuotas(data) {
    const tbody = document.getElementById('tabla_cuotas_body');
    tbody.innerHTML = '';

    (data.cuotas_pendientes || []).forEach((cuota, indice) => {
        const partes = cuota.fecha_vencimiento.split('-');
        const fecha = partes.length === 3 ? partes[2] + '/' + partes[1] + '/' + partes[0] : cuota.fecha_vencimiento;
        const tr = document.createElement('tr');
        tr.id = 'fila_cuota_' + indice;
        tr.innerHTML =
            '<td class="text-center">' + cuota.numero + '</td>' +
            '<td class="text-center">' + fecha + '</td>' +
            '<td class="text-end">$ ' + formatearMonedaVE(cuota.saldo) + '</td>' +
            '<td class="text-center">' + (cuota.vencida
                ? '<span class="badge bg-danger">Vencida</span>'
                : '<span class="badge bg-secondary">Pendiente</span>') + '</td>';
        tbody.appendChild(tr);
    });
}

function consultarSaldo(idContrato) {
    const panel = document.getElementById('panel_contrato');
    const btnGuardar = document.getElementById('btn_guardar_pago');

    if (!idContrato) {
        panel.classList.add('d-none');
        btnGuardar.disabled = true;
        estadoContrato = null;
        return;
    }

    let formData = new FormData();
    formData.append('id_contrato', idContrato);
    formData.append('tasa_bcv', tasaBCVAbsoluta || 0);

    fetch('/IPSPUPTM/app/pagos/modales/obtener_saldo.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'No se pudo consultar el contrato.');
            }

            estadoContrato = data;
            if (parseFloat(data.tasa_bcv) > 0) {
                tasaBCVAbsoluta = parseFloat(data.tasa_bcv);
                document.getElementById('tasa_bcv_label').innerText = formatearMonedaVE(tasaBCVAbsoluta);
                document.getElementById('tasa_bcv_input').value = tasaBCVAbsoluta;
            }

            document.getElementById('saldo_total').innerText = '$ ' + formatearMonedaVE(data.saldo);
            document.getElementById('cobertura_info').innerText = '$ ' + formatearMonedaVE(data.disponible)
                + ' de $ ' + formatearMonedaVE(data.cobertura);
            document.getElementById('mora_info').innerText = data.cuotas_vencidas;

            const alerta = document.getElementById('estado_contrato_alerta');
            alerta.className = 'alert mb-3 ' + (data.cobertura_activa ? 'alert-success' : 'alert-warning');
            alerta.innerHTML = '<strong>' + data.contrato.estado + ':</strong> ' + data.motivo;

            document.getElementById('progreso_inicial').innerText =
                '$ ' + formatearMonedaVE(data.inicial_pagado) + ' / $ ' + formatearMonedaVE(data.inicial_requerido);
            const porcentaje = data.inicial_requerido > 0
                ? (data.inicial_pagado / data.inicial_requerido) * 100
                : 100;
            document.getElementById('barra_inicial').style.width = Math.min(porcentaje, 100) + '%';
            document.getElementById('mensaje_inicial').innerText = data.inicial_completo
                ? 'Pago inicial completado.'
                : 'Pendiente: $ ' + formatearMonedaVE(data.inicial_pendiente);

            const tipoSelect = document.getElementById('tipo_pago_select');
            const opcionInicial = tipoSelect.querySelector('option[value="Pago Inicial"]');
            const opcionCuotas = tipoSelect.querySelector('option[value="Cuota"]');
            opcionInicial.disabled = data.inicial_completo;
            opcionCuotas.disabled = !data.inicial_completo || data.cuotas_pendientes.length === 0;

            if (!data.inicial_completo) {
                tipoSelect.value = 'Pago Inicial';
            } else if (data.cuotas_pendientes.length > 0) {
                tipoSelect.value = 'Cuota';
            }

            generarTablaCuotas(data);
            panel.classList.remove('d-none');
            btnGuardar.disabled = false;
            cambiarTipoPagoPlan();
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message);
            panel.classList.add('d-none');
            btnGuardar.disabled = true;
            estadoContrato = null;
        });
}

function validarPagoPlan() {
    if (!estadoContrato) {
        alert('Seleccione un contrato.');
        return false;
    }

    const tipoPago = document.getElementById('tipo_pago_select').value;
    const monto = parseFloat(document.getElementById('monto_cuota_input').value) || 0;

    if (monto <= 0) {
        alert('El monto debe ser mayor que cero.');
        return false;
    }

    if (tipoPago === 'Pago Inicial') {
        const pendiente = parseFloat(estadoContrato.inicial_pendiente) || 0;
        if (monto > pendiente + 0.009) {
            alert('El monto no puede superar el pago inicial pendiente ($ ' + formatearMonedaVE(pendiente) + ').');
            return false;
        }
    } else {
        const cantidad = parseInt(document.getElementById('cantidad_cuotas').value, 10) || 0;
        if (cantidad < 1 || cantidad > estadoContrato.cuotas_pendientes.length) {
            alert('Seleccione una cantidad válida de cuotas.');
            return false;
        }
    }

    if (tasaBCVAbsoluta <= 0) {
        alert('No se pudo obtener la tasa BCV. Recargue la página o verifique la conexión.');
        return false;
    }

    return true;
}

document.addEventListener('DOMContentLoaded', function () {
    obtenerTasaBCV();

    var script = document.createElement('script');
    script.src = '/IPSPUPTM/assets/select2/js/select2.min.js';
    script.onload = function () {
        $('#modalPago').on('shown.bs.modal', function () {
            $('#id_contrato_select').select2({
                dropdownParent: $('#modalPago'),
                placeholder: 'Seleccione un contrato...',
                width: '100%',
                language: {
                    noResults: function () {
                        return 'No se encontraron resultados';
                    }
                }
            });
        });

        $('#id_contrato_select').on('select2:select', function (e) {
            consultarSaldo(e.params.data.id);
        });
    };
    document.head.appendChild(script);
});
</script>
