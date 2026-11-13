<?php
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

// Detectar si el médico logueado es ginecólogo
$es_ginecologo = false;
$ci_medico_logueado = '';
if (isset($_SESSION['user_id'])) {
    $sqlEsp = "
        SELECT m.ci_medico, e.nombre_especialidad
        FROM medicos m
        LEFT JOIN especialidades e ON m.especialidad = e.id_especialidad
        WHERE m.id_usuario = ?
    ";
    $stmtEsp = $conn->prepare($sqlEsp);
    if ($stmtEsp) {
        $stmtEsp->bind_param('i', $_SESSION['user_id']);
        $stmtEsp->execute();
        $rowEsp = $stmtEsp->get_result()->fetch_assoc();
        if ($rowEsp) {
            $ci_medico_logueado = $rowEsp['ci_medico'];
            $es_ginecologo = (strtolower($rowEsp['nombre_especialidad']) === 'ginecología'
                || strtolower($rowEsp['nombre_especialidad']) === 'ginecologia');
        }
        $stmtEsp->close();
    }
}

$rowsPerPage = 15;
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($currentPage - 1) * $rowsPerPage;

try {
    // La consulta cambia según la especialidad del médico
    if ($es_ginecologo) {
        // Ginecólogo: solo ve sus propias historias de ginecología
        $whereClause = !empty($ci_medico_logueado) ? "WHERE g.ci_medico = '$ci_medico_logueado'" : "";

        $sqlHistorias = "
            SELECT
                g.id_historia_g AS id_historia,
                g.fecha AS fecha_historia,
                g.tipo_paciente,
                g.ci_paciente,
                COALESCE(
                    CONCAT(p_a.nombre, ' ', p_a.apellido),
                    CONCAT(p_b.nombre, ' ', p_b.apellido),
                    CONCAT(ext.nombre, ' ', ext.apellido),
                    'Desconocido'
                ) AS nombre_paciente,
                'Ginecología' AS nombre_especialidad
            FROM historias_medicas_gine g
            LEFT JOIN afiliados a ON g.ci_paciente = a.cedula
            LEFT JOIN persona p_a ON a.cedula = p_a.cedula
            LEFT JOIN beneficiarios b ON g.ci_paciente = b.cedula
            LEFT JOIN persona p_b ON b.cedula = p_b.cedula
            LEFT JOIN persona ext ON g.ci_paciente = ext.cedula
            $whereClause
            ORDER BY g.fecha DESC
            LIMIT $rowsPerPage OFFSET $offset
        ";
        $totalRowsResult = $conn->query("SELECT COUNT(*) AS total FROM historias_medicas_gine g $whereClause");
    } else {
        // Otros médicos: solo ven las historias generales (filtradas por su cédula)
        $whereClause = !empty($ci_medico_logueado) ? "WHERE h.ci_medico = '$ci_medico_logueado'" : "";
        $sqlHistorias = "
            SELECT
                h.id_historia,
                h.fecha AS fecha_historia,
                h.tipo_paciente,
                h.ci_paciente,
                COALESCE(
                    CONCAT(p_a.nombre, ' ', p_a.apellido),
                    CONCAT(p_b.nombre, ' ', p_b.apellido),
                    CONCAT(ext.nombre, ' ', ext.apellido),
                    'Desconocido'
                ) AS nombre_paciente,
                COALESCE(esp.nombre_especialidad, 'General') AS nombre_especialidad
            FROM historias_medicas h
            LEFT JOIN afiliados a ON h.ci_paciente = a.cedula
            LEFT JOIN persona p_a ON a.cedula = p_a.cedula
            LEFT JOIN beneficiarios b ON h.ci_paciente = b.cedula
            LEFT JOIN persona p_b ON b.cedula = p_b.cedula
            LEFT JOIN persona ext ON h.ci_paciente = ext.cedula
            LEFT JOIN medicos m ON h.ci_medico = m.ci_medico
            LEFT JOIN especialidades esp ON m.especialidad = esp.id_especialidad
            $whereClause
            ORDER BY h.fecha DESC
            LIMIT $rowsPerPage OFFSET $offset
        ";
        $totalRowsResult = $conn->query("SELECT COUNT(*) AS total FROM historias_medicas h $whereClause");
    }

    $historias = $conn->query($sqlHistorias);
    $totalRows = $totalRowsResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRows / $rowsPerPage);

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    exit();
}
?>

<div class="mt-3 m-2">
    <h1 class="fw-bold text-center" style="color: #062974;">
        Historias Médicas <?= $es_ginecologo ? 'Ginecología' : '' ?>
    </h1>
    <hr class="mx-auto" style="width: 50px; height: 3px; background-color: #062974;">

    <div class="row mt-4 mb-3">
        <div class="col-auto">
            <?php
            $modalTarget = $es_ginecologo ? '#formulariomodal_ginecologia' : '#formulariomodal';
            ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="<?= $modalTarget ?>">
                <i class="fas fa-plus-circle"></i> Crear historia médica
            </button>
        </div>
        <div class="col-auto">
            <select id="filterTipo" class="form-select w-auto border-primary">
                <option value="todos">Todos los Tipos</option>
                <option value="afiliado">Afiliados</option>
                <option value="beneficiario">Beneficiarios</option>
                <option value="externo">Externos (UPTM)</option>
            </select>
        </div>
        <div class="col text-end">
            <input type="text" id="search" class="form-control w-auto d-inline-block border-primary"
                placeholder="Buscar por nombre o CI...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped table-hover" id="tablaHistorias">
            <thead class="table-dark">
                <tr>
                    <th>Cédula</th>
                    <th>Nombre del Paciente</th>
                    <th>Tipo</th>
                    <th>Especialidad</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($historias && $historias->num_rows > 0): ?>
                    <?php while ($row = $historias->fetch_assoc()): ?>
                        <tr data-tipo="<?= strtolower($row['tipo_paciente']); ?>">
                            <td class="text-center fw-bold"><?= htmlspecialchars($row['ci_paciente']); ?></td>
                            <td><?= htmlspecialchars($row['nombre_paciente']); ?></td>
                            <td class="text-center">
                                <?php
                                $tipo = strtolower($row['tipo_paciente']);
                                $badgeClass = ($tipo == 'afiliado') ? 'bg-success' : (($tipo == 'beneficiario') ? 'bg-primary' : 'bg-info');
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= ucfirst($tipo); ?></span>
                            </td>
                            <td class="text-center"><?= htmlspecialchars($row['nombre_especialidad']); ?></td>
                            <td class="text-center"><?= date('d-m-Y', strtotime($row['fecha_historia'])); ?></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary btn-ver-historia" title="Ver Historia"
                                        data-id="<?= $row['id_historia'] ?>"
                                        data-tabla="<?= $es_ginecologo ? 'ginecologia' : 'general' ?>" data-bs-toggle="modal"
                                        data-bs-target="#vermodal">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btn-eliminar-historia" title="Eliminar"
                                        data-id="<?= $row['id_historia'] ?>"
                                        data-tabla="<?= $es_ginecologo ? 'ginecologia' : 'general' ?>" data-bs-toggle="modal"
                                        data-bs-target="#eliminamodal">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No hay registros médicos encontrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
if ($es_ginecologo) {
    include 'C:/xampp/htdocs/IPSPUPTM/app/historias_medicas/modales/formulario/formulariomodal_ginecologia.php';
} else {
    include 'C:/xampp/htdocs/IPSPUPTM/app/historias_medicas/modales/formulario/formulariomodal.php';
}
?>

<script>
    // Filtros y Buscador
    document.getElementById('filterTipo').addEventListener('change', function () {
        const filtro = this.value.toLowerCase();
        const filas = document.querySelectorAll('#tablaHistorias tbody tr');
        filas.forEach(f => {
            const tipoRow = f.getAttribute('data-tipo');
            f.style.display = (filtro === 'todos' || tipoRow === filtro) ? '' : 'none';
        });
    });

    document.getElementById('search').addEventListener('keyup', function () {
        const term = this.value.toLowerCase();
        const filas = document.querySelectorAll('#tablaHistorias tbody tr');
        filas.forEach(f => {
            f.style.display = f.innerText.toLowerCase().includes(term) ? '' : 'none';
        });
    });

    // Pasar id e indicador de tabla al modal de confirmación de eliminación
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-eliminar-historia');
        if (!btn) return;
        document.getElementById('elim_id_historia').value = btn.getAttribute('data-id');
        document.getElementById('elim_tipo_tabla').value = btn.getAttribute('data-tabla');
    });
</script>

<?php include 'C:/xampp/htdocs/IPSPUPTM/app/historias_medicas/modales/eliminar/eliminarmodal.php'; ?>
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/historias_medicas/modales/ver/vermodal.php'; ?>