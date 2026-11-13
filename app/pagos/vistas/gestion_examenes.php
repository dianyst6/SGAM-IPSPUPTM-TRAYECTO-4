<?php
include_once 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

$rowsPerPage = 15;
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($currentPage - 1) * $rowsPerPage;

// Calcular total de páginas
$totalQuery = "SELECT COUNT(*) as total FROM examenes";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRows = mysqli_fetch_assoc($totalResult)['total'];
$totalPages = ceil($totalRows / $rowsPerPage);
?>
<style>
.pagination .page-link {
    color: #062974;
    border-color: #dee2e6;
}
.pagination .page-link:hover {
    color: #002750;
    background-color: #e9ecef;
    border-color: #dee2e6;
}
.pagination .page-item.active .page-link {
    background-color: #062974;
    border-color: #062974;
    color: white;
}
.pagination .page-item.disabled .page-link {
    color: #6c757d;
}
</style>
<div class="card shadow-lg">
    <div class="card-body">
        <div class="row align-items-center mb-4">
            <!-- Botón Volver Atrás (Izquierda) -->
            <div class="col-auto col-md-3 text-start">
                <a href="/IPSPUPTM/home.php?vista=gestionplanes" class="btn" style="color: white; background-color: #002750; border: none; border-radius: 8px; padding: 8px 16px;"> 
                    <i class="fas fa-arrow-left me-1"></i> Volver atrás
                </a>
            </div>
            
            <!-- Título Centrado (Centro) -->
            <div class="col col-md-6 text-center">
                <h1 class="fw-bold mb-0" style="color: #062974; font-size: 2.25rem;">Gestión de Exámenes y Precios</h1>
                <hr class="mx-auto mt-2 mb-0" style="width: 50px; height: 3px; background-color: #062974; opacity: 1;">
            </div>
            
            <!-- Botón Agregar Examen (Derecha) -->
            <div class="col-auto col-md-3 text-end">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevoExamen" style="border-radius: 8px; padding: 8px 16px;">
                    <i class="fas fa-plus-circle me-1"></i> Agregar Examen
                </button>
            </div>
        </div>
        
        <div class="table-responsive mt-4">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Examen</th>
                        <th>Especialidad</th>
                        <th>Categoría</th>
                        <th>Precio ($)</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    include_once 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
                    $sql = "SELECT e.*, esp.nombre_especialidad, cat.nombre_categoria 
                            FROM examenes e 
                            INNER JOIN especialidades esp ON e.ID_especialidad_examenes = esp.id_especialidad 
                            LEFT JOIN categorias_examenes cat ON e.id_categoria = cat.id_categoria
                            ORDER BY e.estado ASC, esp.nombre_especialidad ASC
                            LIMIT $rowsPerPage OFFSET $offset";
                    $res = mysqli_query($conn, $sql);
                    while($row = mysqli_fetch_assoc($res)):
                    ?>
                    <tr>
                        <td><?php echo $row['nombre_examen']; ?></td>
                        <td><?php echo $row['nombre_especialidad']; ?></td>
                        <td><?php echo $row['nombre_categoria'] ?? 'Sin categoría'; ?></td>
                        <td>
                            <form action="/IPSPUPTM/app/pagos/vistas/actualizar_examen.php" method="POST" class="d-flex align-items-center">
                                <input type="hidden" name="id_examen" value="<?php echo $row['ID_examen']; ?>">
                                <input type="number" step="0.01" name="precio" class="form-control form-control-sm me-2" 
                                       style="width: 80px;" value="<?php echo $row['precio']; ?>" required <?= $row['estado'] == 'inactivo' ? 'disabled' : '' ?>>
                                <button type="submit" class="btn btn-outline-primary btn-sm" <?= $row['estado'] == 'inactivo' ? 'disabled' : '' ?>><i class="fas fa-save"></i></button>
                            </form>
                        </td>
                        <td>
                            <?php if($row['estado'] == 'activo'): ?>
                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Activo</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i> Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($row['estado'] == 'activo'): ?>
                                <button class="btn btn-warning btn-sm" 
                                        title="Editar Completo"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditarExamen"
                                        data-bs-id="<?= $row['ID_examen']; ?>"
                                        data-bs-nombre="<?= htmlspecialchars($row['nombre_examen']); ?>"
                                        data-bs-especialidad="<?= $row['ID_especialidad_examenes']; ?>"
                                        data-bs-categoria="<?= $row['id_categoria']; ?>"
                                        data-bs-precio="<?= $row['precio']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" 
                                        title="Desactivar Examen"
                                        onclick="confirmarEliminarExamen(<?= $row['ID_examen']; ?>, '<?= htmlspecialchars($row['nombre_examen']); ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-info btn-sm text-white" 
                                        title="Reactivar Examen"
                                        onclick="confirmarReactivarExamen(<?= $row['ID_examen']; ?>, '<?= htmlspecialchars($row['nombre_examen']); ?>')">
                                    <i class="fas fa-undo"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Navegación de exámenes" class="mt-4">
            <ul class="pagination justify-content-center pagination-sm">
                <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?vista=gestionexamenes&page=<?= $currentPage - 1 ?>">Anterior</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                    <a class="page-link" href="?vista=gestionexamenes&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?vista=gestionexamenes&page=<?= $currentPage + 1 ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL PARA EDITAR EXAMEN -->
<div class="modal fade" id="modalEditarExamen" tabindex="-1" aria-labelledby="modalEditarExamenLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="modalEditarExamenLabel"><i class="fas fa-edit me-2"></i>Editar Examen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-editar-examen">
                <div class="modal-body">
                    <input type="hidden" name="id_examen" id="edit_id_examen">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Examen</label>
                        <input type="text" name="nombre_examen" id="edit_nombre_examen" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Especialidad Asociada</label>
                        <select name="id_especialidad" id="edit_id_especialidad" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <?php
                            $esp_edit = mysqli_query($conn, "SELECT id_especialidad, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad ASC");
                            while($ee = mysqli_fetch_assoc($esp_edit)) {
                                echo "<option value='{$ee['id_especialidad']}'>{$ee['nombre_especialidad']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Categoría del Examen</label>
                        <select name="id_categoria" id="edit_id_categoria" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <?php
                            $cat_edit = mysqli_query($conn, "SELECT id_categoria, nombre_categoria FROM categorias_examenes ORDER BY nombre_categoria ASC");
                            while($ce = mysqli_fetch_assoc($cat_edit)) {
                                echo "<option value='{$ce['id_categoria']}'>{$ce['nombre_categoria']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Precio Base ($)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" name="precio" id="edit_precio" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-warning fw-bold">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL PARA CONFIRMAR ELIMINACIÓN -->
<div class="modal fade" id="modalConfirmarEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Eliminar Examen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                <p class="fs-5 mb-0">¿Está seguro de que desea eliminar el examen?</p>
                <p class="fw-bold text-primary fs-4 mb-2" id="del_nombre_examen"></p>
                <p class="text-muted small">Esta acción marcará el examen como <strong>Inactivo</strong>. Los registros históricos se mantendrán intactos.</p>
                <input type="hidden" id="del_id_examen">
            </div>
            <div class="modal-footer border-0 bg-light justify-content-center">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger px-4 fw-bold" onclick="ejecutarEliminacion()">Sí, Eliminar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PARA CONFIRMAR REACTIVACIÓN -->
<div class="modal fade" id="modalConfirmarReactivar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-undo me-2"></i>Reactivar Examen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-check-circle fa-3x text-info mb-3"></i>
                <p class="fs-5 mb-0">¿Desea reactivar el examen?</p>
                <p class="fw-bold text-primary fs-4 mb-2" id="react_nombre_examen"></p>
                <p class="text-muted small">El examen volverá a estar disponible para el registro de nuevas citas.</p>
                <input type="hidden" id="react_id_examen">
            </div>
            <div class="modal-footer border-0 bg-light justify-content-center">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info text-white px-4 fw-bold" onclick="ejecutarReactivacion()">Sí, Reactivar</button>
            </div>
        </div>
    </div>
</div>

<?php include 'C:/xampp/htdocs/IPSPUPTM/app/pagos/modales_creacion.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. CARGAR DATOS EN MODAL DE EDICIÓN
    const modalEditar = document.getElementById('modalEditarExamen');
    if (modalEditar) {
        modalEditar.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('edit_id_examen').value = button.getAttribute('data-bs-id');
            document.getElementById('edit_nombre_examen').value = button.getAttribute('data-bs-nombre');
            document.getElementById('edit_id_especialidad').value = button.getAttribute('data-bs-especialidad');
            document.getElementById('edit_id_categoria').value = button.getAttribute('data-bs-categoria');
            document.getElementById('edit_precio').value = button.getAttribute('data-bs-precio');
        });
    }

    // 2. ENVIAR FORMULARIO DE EDICIÓN
    const formEditar = document.getElementById('form-editar-examen');
    if (formEditar) {
        formEditar.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('/IPSPUPTM/app/pagos/vistas/editar_examen_logica.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alertify.success(data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alertify.error(data.message);
                }
            })
            .catch(err => alertify.error("Error al conectar con el servidor"));
        });
    }

    // 3. REACCIONAR A CREACIÓN DE NUEVO EXAMEN
    document.addEventListener('examenCreado', function() {
        location.reload();
    });
});

// Función ayudante para abrir modales de forma robusta (BS4/BS5)
function abrirModalBS(idModal) {
    const modalEl = document.getElementById(idModal);
    if (!modalEl) return;

    if (window.bootstrap && window.bootstrap.Modal) {
        // Bootstrap 5
        const m = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        m.show();
    } else if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
        // Bootstrap 4 / jQuery
        window.jQuery(modalEl).modal('show');
    } else {
        // Fallback manual
        modalEl.style.display = 'block';
        modalEl.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

// 4. FUNCIONES PARA ELIMINAR EXAMEN (CON MODAL BS)
function confirmarEliminarExamen(id, nombre) {
    document.getElementById('del_id_examen').value = id;
    document.getElementById('del_nombre_examen').innerText = nombre;
    abrirModalBS('modalConfirmarEliminar');
}

function ejecutarEliminacion() {
    const id = document.getElementById('del_id_examen').value;
    const formData = new FormData();
    formData.append('id_examen', id);

    fetch('/IPSPUPTM/app/pagos/vistas/eliminar_examen_logica.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alertify.success(data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            alertify.error(data.message);
        }
    })
    .catch(err => alertify.error("Error al conectar con el servidor"));
}

// 5. FUNCIONES PARA REACTIVAR EXAMEN (CON MODAL BS)
function confirmarReactivarExamen(id, nombre) {
    document.getElementById('react_id_examen').value = id;
    document.getElementById('react_nombre_examen').innerText = nombre;
    abrirModalBS('modalConfirmarReactivar');
}

function ejecutarReactivacion() {
    const id = document.getElementById('react_id_examen').value;
    const formData = new FormData();
    formData.append('id_examen', id);

    fetch('/IPSPUPTM/app/pagos/vistas/reactivar_examen_logica.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alertify.success(data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            alertify.error(data.message);
        }
    })
    .catch(err => alertify.error("Error al conectar con el servidor"));
}
</script>
