<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

// Procesar eliminación si se solicita
if (isset($_GET['delete'])) {
    $id_del = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM categorias_examenes WHERE id_categoria = $id_del");
    echo "<script>alertify.success('Categoría eliminada'); window.location.href='/IPSPUPTM/home.php?vista=gestioncategorias';</script>";
}

// Procesar inserción si se solicita
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre_categoria'])) {
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre_categoria']);
    $desc = mysqli_real_escape_string($conn, $_POST['descripcion']);
    
    if (isset($_POST['id_categoria']) && !empty($_POST['id_categoria'])) {
        $id_upd = intval($_POST['id_categoria']);
        $monto = floatval($_POST['monto_maximo_cobertura']);
        $sql = "UPDATE categorias_examenes SET nombre_categoria = '$nombre', descripcion = '$desc', monto_maximo_cobertura = $monto WHERE id_categoria = $id_upd";
    } else {
        $monto = floatval($_POST['monto_maximo_cobertura']);
        $sql = "INSERT INTO categorias_examenes (nombre_categoria, descripcion, monto_maximo_cobertura) VALUES ('$nombre', '$desc', $monto)";
    }
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alertify.success('Categoría guardada correctamente'); window.location.href='/IPSPUPTM/home.php?vista=gestioncategorias';</script>";
    } else {
        echo "<script>alertify.error('Error al guardar la categoría');</script>";
    }
}
?>

<div class="card shadow-lg">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold" style="color: #062974;"><i class="fas fa-tags me-2"></i>Gestión de Categorías de Exámenes</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCategoria" onclick="limpiarFormulario()">
                <i class="fas fa-plus me-2"></i>Nueva Categoría
            </button>
        </div>
        <p class="text-muted">Las categorías permiten agrupar exámenes para establecer límites globales (ej: "Máximo 5 Consultas" en total).</p>
        <hr class="mb-4" style="height: 3px; background-color: #062974; opacity: 0.2;">

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th width="50">ID</th>
                        <th width="250">Nombre de Categoría</th>
                        <th>Descripción</th>
                        <th width="180">Cobertura Máxima</th>
                        <th width="150" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = mysqli_query($conn, "SELECT * FROM categorias_examenes ORDER BY nombre_categoria ASC");
                    while($row = mysqli_fetch_assoc($res)):
                    ?>
                    <tr>
                        <td><?php echo $row['id_categoria']; ?></td>
                        <td class="fw-bold"><?php echo $row['nombre_categoria']; ?></td>
                        <td><?php echo $row['descripcion']; ?></td>
                        <td class="fw-bold text-success">$<?php echo number_format($row['monto_maximo_cobertura'], 2); ?></td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm" onclick="editarCategoria(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="/IPSPUPTM/home.php?vista=gestioncategorias&delete=<?php echo $row['id_categoria']; ?>" 
                               class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que desea eliminar esta categoría? Los exámenes asociados quedarán sin categoría.')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($res) == 0): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No hay categorías registradas. Comience creando una nueva.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL CATEGORÍA -->
<div class="modal fade" id="modalCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="tituloModal">Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_categoria" id="id_categoria">
                    <div class="mb-3">
                        <label class="form-label">Nombre de la Categoría</label>
                        <input type="text" name="nombre_categoria" id="nombre_categoria" class="form-control" placeholder="Ej: Consultas Médicas" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" id="descripcion" class="form-control" rows="3" placeholder="Opcional..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto Máximo de Cobertura ($)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" name="monto_maximo_cobertura" id="monto_maximo_cobertura" class="form-control" placeholder="Ej: 100.00" value="0.00" required>
                        </div>
                        <small class="text-muted">Monto total que cubre el plan para esta categoría.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function limpiarFormulario() {
    document.getElementById('id_categoria').value = '';
    document.getElementById('nombre_categoria').value = '';
    document.getElementById('descripcion').value = '';
    document.getElementById('monto_maximo_cobertura').value = '0.00';
    document.getElementById('tituloModal').innerText = 'Nueva Categoría';
}

function editarCategoria(data) {
    document.getElementById('id_categoria').value = data.id_categoria;
    document.getElementById('nombre_categoria').value = data.nombre_categoria;
    document.getElementById('descripcion').value = data.descripcion;
    document.getElementById('monto_maximo_cobertura').value = data.monto_maximo_cobertura;
    document.getElementById('tituloModal').innerText = 'Editar Categoría';
    
    var modal = new bootstrap.Modal(document.getElementById('modalCategoria'));
    modal.show();
}
</script>
