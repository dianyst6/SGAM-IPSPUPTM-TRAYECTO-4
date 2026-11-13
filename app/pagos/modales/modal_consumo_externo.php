
<!-- MODAL PARA REGISTRO DE CONSUMO EXTERNO -->
<div class="modal fade" id="modalConsumoExterno" tabindex="-1" aria-labelledby="modalConsumoExternoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalConsumoExternoLabel"><i class="fas fa-hospital-user me-2"></i>Registrar Consumo Externo (Extra-Institucional)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- BUSCADOR DE AFILIADO -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Buscar Afiliado / Beneficiario (Cédula)</label>
                        <div class="input-group">
                            <input type="text" id="busqueda_cedula_externa" class="form-control" placeholder="Ej: 25123456" maxlength="8" oninput="this.value = this.value.replace(/\D/g, '').slice(0, 8);">
                            <button class="btn btn-primary" type="button" id="btn_buscar_afiliado_externo">
                                <i class="fas fa-search me-1"></i> Buscar
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 border-start" id="info_afiliado_externo" style="display: none;">
                        <h6 class="fw-bold mb-1 text-primary" id="nombre_afiliado_ext">---</h6>
                        <small class="text-muted d-block" id="plan_afiliado_ext">Plan: ---</small>
                        <input type="hidden" id="id_contrato_ext">
                        <input type="hidden" id="id_persona_ext">
                    </div>
                </div>

                <hr>

                <!-- TABLA DE CONSUMO -->
                <div id="contenedor_consumo_ext" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle" id="tabla_items_externos">
                            <thead class="table-light text-center">
                                <tr>
                                    <th style="width: 40%;">Estudio / Consulta (Escrito a mano)</th>
                                    <th style="width: 30%;">Categoría</th>
                                    <th style="width: 15%;">Costo ($)</th>
                                    <th style="width: 10%;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_items_externos">
                                <!-- Filas dinámicas aquí -->
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none" id="btn_agregar_fila_externa">
                        <i class="fas fa-plus-circle me-1"></i> Añadir otro ítem
                    </button>

                    <div class="row mt-4 align-items-end">
                        <div class="col-md-8">
                            <div class="alert alert-info py-2" id="alert_disponibilidad">
                                <i class="fas fa-info-circle me-1"></i> Selecciona una categoría para ver la disponibilidad.
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <h4 class="fw-bold">Total: <span class="text-primary" id="total_externo_label">$0.00</span></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btn_procesar_consumo_ext" disabled>
                    <i class="fas fa-save me-1"></i> Procesar Consumo Externo
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let categorias_afiliado = [];

document.getElementById('btn_buscar_afiliado_externo').addEventListener('click', function() {
    const cedula = document.getElementById('busqueda_cedula_externa').value;
    if (!cedula) return alertify.error("Ingresa una cédula");

    fetch(`/IPSPUPTM/app/pagos/get_afiliado_plan_limits.php?cedula=${cedula}`)
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('nombre_afiliado_ext').textContent = data.afiliado.nombre + " (" + data.afiliado.tipo + ")";
            document.getElementById('plan_afiliado_ext').textContent = "Plan: " + data.afiliado.plan;
            document.getElementById('id_contrato_ext').value = data.afiliado.id_contrato;
            document.getElementById('id_persona_ext').value = data.afiliado.id_persona;
            document.getElementById('info_afiliado_externo').style.display = "block";
            document.getElementById('contenedor_consumo_ext').style.display = "block";
            
            categorias_afiliado = data.categorias;
            document.getElementById('tbody_items_externos').innerHTML = ""; // Limpiar tabla
            agregarFilaExterna();
            alertify.success("Afiliado encontrado");
        } else {
            alertify.error(data.message);
            document.getElementById('info_afiliado_externo').style.display = "none";
            document.getElementById('contenedor_consumo_ext').style.display = "none";
        }
    });
});

function agregarFilaExterna() {
    const tbody = document.getElementById('tbody_items_externos');
    const tr = document.createElement('tr');
    tr.className = "fila-externa";

    let select_options = '<option value="">Seleccione Categoría</option>';
    categorias_afiliado.forEach(cat => {
        let disp_texto = (parseFloat(cat.disponible) <= 0) ? "(Agotado)" : `(Disp: $${cat.disponible})`;
        select_options += `<option value="${cat.id_categoria}">${cat.nombre_categoria} ${disp_texto}</option>`;
    });

    tr.innerHTML = `
        <td><input type="text" class="form-control form-control-sm nombre-estudio" placeholder="Nombre del examen extra-institucional" required></td>
        <td><select class="form-select form-select-sm select-categoria-ext">${select_options}</select></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm costo-item" placeholder="0.00"></td>
        <td class="text-center"><button class="btn btn-outline-danger btn-sm border-0" onclick="this.closest('tr').remove(); calcularTotalExterno();"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(tr);

    // Eventos para recálculo
    tr.querySelector('.select-categoria-ext').addEventListener('change', validarLimitesExternos);
    tr.querySelector('.costo-item').addEventListener('input', validarLimitesExternos);
}

document.getElementById('btn_agregar_fila_externa').addEventListener('click', agregarFilaExterna);

function calcularTotalExterno() {
    let total = 0;
    document.querySelectorAll('.costo-item').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('total_externo_label').textContent = "$" + total.toFixed(2);
    return total;
}

function validarLimitesExternos() {
    calcularTotalExterno();
    let es_valido = true;
    let mensaje = "";

    // Agrupar por categoría para validar el total de la transacción vs disponibilidad
    const consumos_por_categoria = {};
    
    const filas = document.querySelectorAll('.fila-externa');
    filas.forEach(fila => {
        const id_cat = fila.querySelector('.select-categoria-ext').value;
        const costo = parseFloat(fila.querySelector('.costo-item').value) || 0;

        if (id_cat) {
            if (!consumos_por_categoria[id_cat]) consumos_por_categoria[id_cat] = 0;
            consumos_por_categoria[id_cat] += costo;
        }
    });

    // Validar cada categoría acumulada
    for (const id_cat in consumos_por_categoria) {
        const cat_info = categorias_afiliado.find(c => c.id_categoria == id_cat);
        const consumido_ahora = consumos_por_categoria[id_cat];

        if (consumido_ahora > cat_info.disponible) {
            es_valido = false;
            let disp_texto = (parseFloat(cat_info.disponible) <= 0) ? "Agotado" : `$${parseFloat(cat_info.disponible).toFixed(2)}`;
            mensaje = `¡ERROR! La categoría <b>${cat_info.nombre_categoria}</b> supera su límite disponible (${disp_texto}).`;
            break;
        }
    }

    const btn_procesar = document.getElementById('btn_procesar_consumo_ext');
    const alert_box = document.getElementById('alert_disponibilidad');

    if (!es_valido) {
        btn_procesar.disabled = true;
        alert_box.className = "alert alert-danger py-2";
        alert_box.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i> ${mensaje}`;
    } else {
        btn_procesar.disabled = (filas.length === 0);
        alert_box.className = "alert alert-info py-2";
        alert_box.innerHTML = `<i class="fas fa-info-circle me-1"></i> Límites validados. El sistema respetará los topes monetarios.`;
    }
}

document.getElementById('btn_procesar_consumo_ext').addEventListener('click', function() {
    const items = [];
    document.querySelectorAll('.fila-externa').forEach(fila => {
        items.push({
            nombre: fila.querySelector('.nombre-estudio').value,
            id_categoria: fila.querySelector('.select-categoria-ext').value,
            costo: fila.querySelector('.costo-item').value
        });
    });

    const formData = new FormData();
    formData.append('id_contrato', document.getElementById('id_contrato_ext').value);
    formData.append('id_persona', document.getElementById('id_persona_ext').value);
    formData.append('items', JSON.stringify(items));

    fetch('/IPSPUPTM/app/pagos/procesar_consumo_externo.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alertify.success(data.message);
            bootstrap.Modal.getInstance(document.getElementById('modalConsumoExterno')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            alertify.error(data.message);
        }
    });
});
</script>
