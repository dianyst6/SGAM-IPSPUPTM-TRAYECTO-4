<div class="modal fade" id="pagomodal" tabindex="-1" aria-labelledby="pagomodalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="pagomodalLabel">Registrar Pago Externo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-registro-pago-ext">
                    <input type="hidden" name="id_cita_pago" id="id_cita_pago">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Paciente:</label>
                        <input type="text" id="display_nombre_pago" class="form-control bg-light" readonly>
                    </div>

                    <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Monto Base ($)</label>
                        <input type="number" step="0.01" name="monto_base" id="monto_base" 
                            class="form-control border-primary" placeholder="Ingrese monto..." 
                            oninput="calcularMontoFinal()">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tipo de Comunidad</label>
                        <select name="id_tipos_ext" id="id_tipos_ext" class="form-select border-primary" 
                                required onchange="calcularMontoFinal()">
                            <option value="" data-descuento="0">Seleccione...</option>
                            <?php
                            $sql_tipos = "SELECT id_tipos_ext, nombre_tipo, descuento FROM tipos_externos";
                            $res_tipos = mysqli_query($conn, $sql_tipos);
                            while($t = mysqli_fetch_assoc($res_tipos)){
                                echo '<option value="'.$t['id_tipos_ext'].'" data-descuento="'.$t['descuento'].'">'.$t['nombre_tipo'].' ('.$t['descuento'].'%)</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                    <input type="hidden" name="porcentaje_desc" id="porcentaje_desc" value="0">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="monto_pago" class="form-label fw-bold text-success">Monto Final ($)</label>
                            <input type="number" step="0.01" name="monto_pago" id="monto_pago" 
                                   class="form-control border-success fw-bold bg-light" readonly>
                            <small class="text-muted d-block mt-1">
                                Tasa BCV: <span id="ext_tasa_bcv_label">Cargando...</span> Bs |
                                Equivalente: <strong class="text-success" id="ext_monto_bs_label">0,00 Bs</strong>
                            </small>
                            <input type="hidden" name="tasa_bcv" id="ext_tasa_bcv_input" value="0">
                            <input type="hidden" name="monto_bs" id="ext_monto_bs_input" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="metodo_pago" class="form-label fw-bold">Método</label>
                            <select name="metodo_pago" id="metodo_pago" class="form-select" required>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Pago Móvil">Pago Móvil</option>
                                <option value="Transferencia">Transferencia</option>
                                <option value="Punto de Venta">Punto de Venta</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer px-0 pb-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Guardar Pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
let extTasaBCVAbsoluta = 0;

function formatearMonedaVE(valor) {
    return new Intl.NumberFormat('es-VE', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    }).format(valor);
}

function extObtenerTasaBCV() {
    fetch('https://ve.dolarapi.com/v1/dolares/oficial')
        .then(response => response.json())
        .then(data => {
            if (data && data.promedio) {
                extTasaBCVAbsoluta = data.promedio;
            } else if (data && data.venta) {
                extTasaBCVAbsoluta = data.venta;
            }
            document.getElementById('ext_tasa_bcv_label').innerText = formatearMonedaVE(extTasaBCVAbsoluta);
            document.getElementById('ext_tasa_bcv_input').value = extTasaBCVAbsoluta;
            if (typeof window.calcularMontoFinal === 'function') {
                window.calcularMontoFinal();
            }
        })
        .catch(error => {
            console.error('Error obteniendo tasa BCV:', error);
            document.getElementById('ext_tasa_bcv_label').innerText = 'Error API';
        });
}

document.addEventListener('DOMContentLoaded', function() {
    extObtenerTasaBCV();
    const modalPago = document.getElementById('pagomodal');
    const formPago = document.getElementById('form-registro-pago-ext');

    // 1. Función para calcular el descuento en tiempo real
    // Se define fuera para que el "onchange" del select pueda verla
   window.calcularMontoFinal = function() {
    // Captura lo que la secretaria escribe
    const montoBase = parseFloat(document.getElementById('monto_base').value) || 0;
    const selectTipos = document.getElementById('id_tipos_ext');
    
    // Obtiene el descuento del select
    const selectedOption = selectTipos.options[selectTipos.selectedIndex];
    const descuentoPorc = parseFloat(selectedOption.getAttribute('data-descuento')) || 0;

    // Realiza la operación
    const ahorro = montoBase * (descuentoPorc / 100);
    const montoFinalUSD = montoBase - ahorro;

    // Refleja el resultado en dólares
    document.getElementById('monto_pago').value = montoFinalUSD.toFixed(2);
    document.getElementById('porcentaje_desc').value = descuentoPorc;
    
    // Calcula y refleja el equivalente en bolívares
    const montoFinalBs = montoFinalUSD * extTasaBCVAbsoluta;
    document.getElementById('ext_monto_bs_label').innerText = formatearMonedaVE(montoFinalBs) + ' Bs';
    document.getElementById('ext_monto_bs_input').value = montoFinalBs.toFixed(2);
};

    // 2. Al abrir el modal: Cargar datos iniciales
    modalPago.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // Botón que activó el modal
        
        // Extraer info de los atributos data-bs-* del botón
        const idCita = button.getAttribute('data-bs-idcita');
        const nombre = button.getAttribute('data-bs-nombre');
        const costo = button.getAttribute('data-bs-costobase');

        // Resetear el formulario y llenar campos base
        formPago.reset();
        document.getElementById('id_cita_pago').value = idCita;
        document.getElementById('display_nombre_pago').value = nombre;
        document.getElementById('monto_base').value = costo;
        
        // Limpiar el monto final hasta que elijan un tipo
        document.getElementById('monto_pago').value = "";
    });

    // 3. Envío del formulario por AJAX
    formPago.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('/IPSPUPTM/app/pagos/modales/pagosexternos/formulario/guardar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alertify.success(data.message);
                // Cerrar modal y recargar
                const modalInstance = bootstrap.Modal.getInstance(modalPago);
                modalInstance.hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                alertify.error(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alertify.error("Error al procesar el pago");
        });
    });
});
</script>