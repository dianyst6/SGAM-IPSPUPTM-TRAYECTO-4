<div class="modal fade" id="modalVerConsumo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header  text-white" style="background-color: #062974;">
                <h5 class="modal-title"><i class="fas fa-file-medical-alt me-2"></i>Estado del Plan de Salud</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" >
                <div id="infoAfiliadoHeader" class="mb-3 p-3  rounded border-start border-info border-5" >
                    </div>
                
                <div id="tablaConsumoDinamica">
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<script>
function verResumenConsumo(cedula, nombreCompleto) {
    const header = document.getElementById('infoAfiliadoHeader');
    const tabla = document.getElementById('tablaConsumoDinamica');

    tabla.innerHTML = '<div class="text-center my-4"><div class="spinner-border text-info"></div></div>';
    
    // Mostramos el modal
    const myModal = new bootstrap.Modal(document.getElementById('modalVerConsumo'));
    myModal.show();

    fetch(`/IPSPUPTM/app/afiliados/consultar_consumo.php?cedula=${cedula}`)
        .then(response => response.text())
        .then(data => {
            tabla.innerHTML = data;
            const plan = document.getElementById('nombre_plan_db')?.value || "Sin Plan";

            header.innerHTML = `
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <small class="text-muted d-block">Titular del Contrato:</small>
                        <h6 class="mb-0 fw-bold text-uppercase">${nombreCompleto}</h6>
                        <small class="text-info"><i class="fas fa-users"></i> Incluye consumo de beneficiarios</small>
                    </div>
                    <div class="col-md-5 text-md-end">
                        <span class="badge bg-primary p-2">${plan}</span>
                    </div>
                </div>`;
        });
}
</script>