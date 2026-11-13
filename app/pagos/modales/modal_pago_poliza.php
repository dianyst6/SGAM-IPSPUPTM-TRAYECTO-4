<?php
// modal_pago_poliza.php
// Incluimos la base de datos solo si no está definida
if (!isset($conn)) {
    include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
}
?>

<div class="modal fade" id="modalPagoPoliza" tabindex="-1" aria-labelledby="labelPagoPoliza" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="labelPagoPoliza">
                    <i class="fas fa-file-invoice-dollar me-2"></i>Confirmar Descuento de Póliza
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/IPSPUPTM/app/pagos/procesar_pago_poliza.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_cita" id="poliza_id_cita">
                    <input type="hidden" name="id_contrato" id="poliza_id_contrato">
                    <input type="hidden" name="monto_original" id="poliza_monto_original">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Paciente:</label>
                            <input type="text" id="poliza_nombre_paciente" class="form-control bg-light border-0" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Especialidad:</label>
                            <input type="text" id="poliza_especialidad" class="form-control bg-light border-0" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Monto Cita ($)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" id="display_monto_original" class="form-control bg-light border-0" readonly>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Beneficio de Especialidad</label>
                            <div class="input-group">
                                <input type="text" id="poliza_descuento_texto" class="form-control bg-light border-success fw-bold text-success" readonly>
                                <span class="input-group-text bg-success text-white">%</span>
                            </div>
                            <input type="hidden" name="porcentaje_descuento" id="poliza_porcentaje_auto">
                        </div>
                    </div>

                    <div id="poliza_contenedor_calculos" style="display: none;">
                        <div class="card bg-light border-0 mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Ahorro:</span>
                                    <span class="text-success" id="poliza_ahorro">-$ 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Total a Descontar de Póliza:</span>
                                    <span class="h4 mb-0 text-primary fw-bold" id="poliza_total_usd">$ 0.00</span>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info border-0 mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <small>Tasa BCV:</small>
                                <small class="fw-bold"><span id="poliza_tasa_label">...</span> Bs</small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <span>Equivalente en Bolívares:</span>
                                <strong class="text-dark" id="poliza_total_bs">0.00 Bs</strong>
                            </div>
                            <input type="hidden" name="tasa_bcv" id="poliza_tasa_input">
                        </div>
                    </div>

                    <input type="hidden" name="monto" id="poliza_input_monto_final">

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-check-circle me-1"></i>Confirmar y Procesar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let polizaTasaBCV = 0;

function polizaObtenerTasa() {
    document.getElementById('poliza_tasa_label').innerText = 'Cargando...';
    
    // API principal
    fetch('https://ve.dolarapi.com/v1/dolares/oficial')
        .then(r => r.json())
        .then(data => {
            if (data && (data.promedio || data.venta)) {
                procesarTasaPoliza(data.promedio || data.venta);
            } else {
                throw new Error("Formato inválido en API principal");
            }
        })
        .catch(e => {
            console.warn("Fallo API principal, intentando secundaria...", e);
            // Fallback API (suponiendo que en algún momento puedan usar la de pydolarve)
            fetch('https://pydolarve.org/api/v1/dollar?page=bcv')
                .then(r => r.json())
                .then(data => {
                    if (data && data.monitors && data.monitors.bcv && data.monitors.bcv.price) {
                        procesarTasaPoliza(data.monitors.bcv.price);
                    } else {
                        throw new Error("Formato inválido en API secundaria");
                    }
                })
                .catch(err => {
                    console.error("Error definitivo tasa BCV:", err);
                    document.getElementById('poliza_tasa_label').innerText = 'Error API';
                    document.getElementById('poliza_tasa_label').classList.add('text-danger');
                });
        });
}

function procesarTasaPoliza(tasa) {
    polizaTasaBCV = parseFloat(tasa) || 0;
    document.getElementById('poliza_tasa_label').innerText = new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2 }).format(polizaTasaBCV);
    document.getElementById('poliza_tasa_input').value = polizaTasaBCV;
    document.getElementById('poliza_tasa_label').classList.remove('text-danger');
    calcularPoliza();
}

function calcularPoliza() {
    const montoOrig = parseFloat(document.getElementById('poliza_monto_original').value) || 0;
    const porc = parseFloat(document.getElementById('poliza_porcentaje_auto').value) || 0;

    const ahorro = montoOrig * (porc / 100);
    const montoFinal = montoOrig - ahorro;
    const montoBs = montoFinal * polizaTasaBCV;

    document.getElementById('poliza_ahorro').innerText = "-$ " + ahorro.toFixed(2);
    document.getElementById('poliza_total_usd').innerText = "$ " + montoFinal.toFixed(2);
    document.getElementById('poliza_input_monto_final').value = montoFinal.toFixed(2);
    
    document.getElementById('poliza_total_bs').innerText = new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2 }).format(montoBs) + " Bs";

    // Mostrar u ocultar cálculos según si hay beneficio
    const contCalculos = document.getElementById('poliza_contenedor_calculos');
    if (porc > 0) {
        contCalculos.style.display = 'block';
    } else {
        contCalculos.style.display = 'none';
        // Si no hay descuento, confirmamos que el monto final es el original
        document.getElementById('poliza_input_monto_final').value = montoOrig.toFixed(2);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    polizaObtenerTasa();
    
    const modal = document.getElementById('modalPagoPoliza');
    modal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        
        // Cargar datos del botón
        document.getElementById('poliza_id_cita').value = btn.dataset.bsIdcita;
        document.getElementById('poliza_id_contrato').value = btn.dataset.bsIdcontrato;
        document.getElementById('poliza_monto_original').value = btn.dataset.bsMonto;
        document.getElementById('display_monto_original').value = btn.dataset.bsMonto;
        document.getElementById('poliza_nombre_paciente').value = btn.dataset.bsNombre;
        document.getElementById('poliza_especialidad').value = btn.dataset.bsEspecialidad;
        
        // Cargar descuento automático desde la especialidad
        const descAuto = btn.dataset.bsDescuento || 0;
        document.getElementById('poliza_porcentaje_auto').value = descAuto;
        document.getElementById('poliza_descuento_texto').value = descAuto;
        
        calcularPoliza();
    });
});
</script>
