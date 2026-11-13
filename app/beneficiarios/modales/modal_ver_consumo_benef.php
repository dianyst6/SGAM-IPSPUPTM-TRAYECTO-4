<div class="modal fade" id="modalConsumoBenef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header  text-white" style="background-color: #062974;">
                <h5 class="modal-title fw-bold"><i class="fas fa-users me-2"></i>Consumo del Grupo Familiar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="headerBeneficiario" class="mb-3 p-3 bg-light rounded border-start border-info border-5">
                </div>
                <div id="tablaConsumoBenef">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<script>
function verConsumoBeneficiario(cedula, nombreBenef, nombreAfil) {
    const header = document.getElementById('headerBeneficiario');
    const tabla = document.getElementById('tablaConsumoBenef');

    tabla.innerHTML = '<div class="text-center my-3"><div class="spinner-border text-warning"></div></div>';

    const myModal = new bootstrap.Modal(document.getElementById('modalConsumoBenef'));
    myModal.show();

    fetch(`/IPSPUPTM/app/beneficiarios/consultar_consumo_beneficiario.php?cedula=${cedula}`)
        .then(response => response.text())
        .then(data => {
            tabla.innerHTML = data;
            const plan = document.getElementById('nombre_plan_db')?.value || "No definido";

            header.innerHTML = `
                <div class="row">
                    <div class="col-md-7">
                        <small class="text-muted">Beneficiario:</small>
                        <h6 class="mb-0 fw-bold">${nombreBenef}</h6>
                        <small class="text-muted">Titular: ${nombreAfil}</small>
                    </div>
                    <div class="col-md-5 text-md-end">
                        <span class="badge bg-primary text-white p-2 mt-2">${plan}</span>
                    </div>
                </div>`;
        });
}
</script>