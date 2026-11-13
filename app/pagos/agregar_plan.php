<?php
// Cargar la conexión ANTES de que empiece a dibujar el HTML
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
?>

<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h4><i class="fa-solid fa-file-medical"></i> Crear Nuevo Plan de Salud</h4>
    </div>
    <form action="/IPSPUPTM/app/pagos/procesar_plan_completo.php" method="POST">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Nombre del Plan</label>
                    <input type="text" name="nombre_plan" class="form-control" maxlength="100" placeholder="Ej: Plan Platino 2026" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Precio Plan ($)</label>
                    <input type="number" min="0.00" max="99999999.99" step="0.01" name="precio" class="form-control" placeholder="Ej: 100.00" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cobertura Póliza ($)</label>
                    <input type="number" min="0.00" max="99999999.99" step="0.01" name="monto_cobertura" class="form-control" placeholder="Ej: 500.00" required>
                </div>
                <div class="col-md-12 mt-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="2"></textarea>
                </div>
            </div>

            <hr>

            <div class="row mb-4 bg-light p-3 border rounded mx-1 align-items-end">
                <div class="col-md-6">
                    <label class="form-label text-primary fw-bold">
                        <i class="fa-solid fa-clone"></i> Plan Base (Opcional)
                    </label>
                    <select id="select-plan-base" class="form-select" onchange="escucharCambioPlan(this.value)">
                        <option value="">Ninguno (Crear desde cero)</option>
                        <?php
                        // PROTECCIÓN APLICADA AQUÍ
                        if (isset($conn)) {
                            $pl = mysqli_query($conn, "SELECT ID_planes, nombre_plan FROM planes ORDER BY nombre_plan ASC");
                            if ($pl) {
                                while($p = mysqli_fetch_assoc($pl)) {
                                    echo "<option value='{$p['ID_planes']}'>{$p['nombre_plan']}</option>";
                                }
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <div id="loading-spinner" class="d-none text-primary align-items-center">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        <span>Cargando componentes...</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 border-end">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fa-solid fa-microscope text-primary"></i> Límites por Examen</h5>
                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalNuevoExamen">
                            <i class="fa-solid fa-plus"></i> Nuevo Examen
                        </button>
                    </div>
                    <div id="contenedor-examenes">
                        <div class="row g-2 mb-2 examen-item p-2 border rounded bg-white shadow-sm">
                            <div class="col-md-8">
                                <select name="id_examen[]" class="form-select select-examen">
                                    <option value="">Seleccione un examen...</option>
                                    <?php
                                
                                    // PROTECCIÓN APLICADA AQUÍ
                                    if (isset($conn)) {
                                        $ex = mysqli_query($conn, "SELECT ID_examen, nombre_examen FROM examenes ORDER BY nombre_examen ASC");
                                        if ($ex) {
                                            while($f = mysqli_fetch_assoc($ex)) {
                                                echo "<option value='{$f['ID_examen']}'>{$f['nombre_examen']}</option>";
                                            }
                                        } else {
                                            echo "<option value=''>Error al cargar exámenes</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="cantidad_examen[]" class="form-control" placeholder="Cant." title="Cantidad Máxima">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100 h-100" onclick="eliminarFila(this, '.examen-item')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none" onclick="agregarFilaExamen()">
                        <i class="fa-solid fa-circle-plus"></i> Añadir otro examen específico
                    </button>
                </div>

                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fa-solid fa-tags text-success"></i> Límites por Categoría (Global)</h5>
                        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria">
                            <i class="fa-solid fa-plus"></i> Nueva Categoría
                        </button>
                    </div>
                    <div id="contenedor-categorias">
                        <div class="row g-2 mb-2 categoria-item p-2 border rounded bg-white shadow-sm">
                            <div class="col-md-6">
                                <select name="id_categoria_comp[]" class="form-select select-categoria">
                                    <option value="">Seleccione una categoría...</option>
                                    <?php
                                    // PROTECCIÓN APLICADA AQUÍ
                                    if (isset($conn)) {
                                        $cat = mysqli_query($conn, "SELECT id_categoria, nombre_categoria FROM categorias_examenes ORDER BY nombre_categoria ASC");
                                        if ($cat) {
                                            while($c = mysqli_fetch_assoc($cat)) {
                                                echo "<option value='{$c['id_categoria']}'>{$c['nombre_categoria']}</option>";
                                            }
                                        } else {
                                            echo "<option value=''>Error al cargar categorías</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="cantidad_categoria[]" class="form-control" placeholder="Cant." title="Cantidad Máxima Global">
                            </div>
                            <div class="col-md-2">
                                <input type="number" step="0.01" name="monto_categoria[]" class="form-control" placeholder="Monto $" title="Monto Máximo Cobertura ($)">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100 h-100" onclick="eliminarFila(this, '.categoria-item')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none text-success" onclick="agregarFilaCategoria()">
                        <i class="fa-solid fa-circle-plus"></i> Añadir límite para otra categoría
                    </button>
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary px-5">Guardar Plan Completo</button>
        </div>
    </form>
</div> 

<script>
    // 1. Definimos la función en el objeto window para que sea global y accesible
    window.escucharCambioPlan = async function(idPlan) {
        console.log("Cambiando a plan ID:", idPlan);
        
        const spinner = document.getElementById('loading-spinner');
        const contenedorEx = document.getElementById('contenedor-examenes');
        const contenedorCat = document.getElementById('contenedor-categorias');

        if (!idPlan) {
            contenedorEx.innerHTML = '';
            contenedorCat.innerHTML = '';
            agregarFilaExamen();
            agregarFilaCategoria();
            return;
        }

        // Mostrar spinner
        if (spinner) {
            spinner.classList.remove('d-none');
            spinner.classList.add('d-flex');
        }

        try {
            const response = await fetch(`/IPSPUPTM/app/pagos/obtener_componentes_plan.php?id_plan=${idPlan}`);
            if (!response.ok) throw new Error("Error en la respuesta del servidor");
            
            const data = await response.json();

            // Limpiar contenedores
            contenedorEx.innerHTML = '';
            contenedorCat.innerHTML = '';

            // Inyectar Exámenes
            if (data.examenes && data.examenes.length > 0) {
                data.examenes.forEach(item => {
                    const fila = agregarFilaExamen();
                    if (fila) {
                        fila.querySelector('.select-examen').value = item.id_examen;
                        fila.querySelector('input[name="cantidad_examen[]"]').value = item.cantidad || '';
                    }
                });
            } else {
                agregarFilaExamen();
            }

            // Inyectar Categorías
            if (data.categorias && data.categorias.length > 0) {
                data.categorias.forEach(item => {
                    const fila = agregarFilaCategoria();
                    if (fila) {
                        fila.querySelector('.select-categoria').value = item.id_categoria;
                        fila.querySelector('input[name="cantidad_categoria[]"]').value = item.cantidad || '';
                        fila.querySelector('input[name="monto_categoria[]"]').value = item.monto || '';
                    }
                });
            } else {
                agregarFilaCategoria();
            }

        } catch (error) {
            console.error('Error detallado:', error);
            alert('Error al cargar componentes. Revisa la consola (F12).');
        } finally {
            if (spinner) {
                spinner.classList.add('d-none');
                spinner.classList.remove('d-flex');
            }
        }
    };

    // 2. Eventos iniciales al cargar el DOM
    document.addEventListener("DOMContentLoaded", function() {
        const selectPlan = document.getElementById('select-plan-base');
        
        // Vincular el evento change manualmente
        if (selectPlan) {
            selectPlan.addEventListener('change', function() {
                window.escucharCambioPlan(this.value);
            });
        }
        
        // Asegurar moldes al cargar
        asegurarMoldes();

        // Validación en tiempo real para inputs decimal(10,2) con delegación de eventos
        document.body.addEventListener('input', function(event) {
            const target = event.target;
            if (target && target.tagName === 'INPUT' && target.type === 'number' && target.getAttribute('step') === '0.01') {
                let val = target.value;
                if (val.includes('.')) {
                    let parts = val.split('.');
                    if (parts[0].length > 8) {
                        parts[0] = parts[0].slice(0, 8);
                    }
                    if (parts[1].length > 2) {
                        parts[1] = parts[1].slice(0, 2);
                    }
                    target.value = parts.join('.');
                } else {
                    if (val.length > 8) {
                        target.value = val.slice(0, 8);
                    }
                }
            }
        });
    });

    // 3. Funciones auxiliares
    let moldeExamen = null;
    let moldeCategoria = null;

    function asegurarMoldes() {
        if (!moldeExamen) {
            const fila = document.querySelector('.examen-item');
            if (fila) {
                moldeExamen = fila.cloneNode(true);
                limpiarFila(moldeExamen);
            }
        }
        if (!moldeCategoria) {
            const fila = document.querySelector('.categoria-item');
            if (fila) {
                moldeCategoria = fila.cloneNode(true);
                limpiarFila(moldeCategoria);
            }
        }
    }

    function limpiarFila(fila) {
        fila.querySelectorAll('input').forEach(i => i.value = '');
        fila.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    }

    function agregarFilaExamen() {
        const contenedor = document.getElementById('contenedor-examenes');
        if (moldeExamen && contenedor) {
            const nuevaFila = moldeExamen.cloneNode(true);
            contenedor.appendChild(nuevaFila);
            return nuevaFila;
        }
    }

    function agregarFilaCategoria() {
        const contenedor = document.getElementById('contenedor-categorias');
        if (moldeCategoria && contenedor) {
            const nuevaFila = moldeCategoria.cloneNode(true);
            contenedor.appendChild(nuevaFila);
            return nuevaFila;
        }
    }

    function eliminarFila(btn, selector) {
        const contenedor = btn.closest(selector).parentElement;
        const filas = contenedor.querySelectorAll(selector);
        if (filas.length > 1) {
            btn.closest(selector).remove();
        } else {
            limpiarFila(btn.closest(selector));
        }
    }
</script>

<?php include 'C:/xampp/htdocs/IPSPUPTM/app/pagos/modales_creacion.php'; ?>