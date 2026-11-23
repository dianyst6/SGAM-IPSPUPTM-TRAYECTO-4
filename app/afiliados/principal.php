<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/alertify.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/plan_helper.php';

$hoy = planHoy();
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'activos';
$periodo_abierto = planPeriodoInscripcionAbierto();
$es_admin = ((int) ($_SESSION['role_id'] ?? 0)) === 1;

// Un contrato "vigente" para el listado es el que no está inactivo y aún no
// ha vencido: incluye los que están pendientes de pago inicial y los que
// empiezan en el próximo año.
$condicionContratoVigente = "EXISTS (
    SELECT 1 FROM contrato_plan cp
    WHERE cp.id_persona = a.id_persona
      AND cp.estado_contrato NOT IN ('Inactivo', 'Vencido')
      AND cp.fecha_fin >= '$hoy'
)";

$whereClause = "";
if ($filtro === 'activos') {
    $whereClause = "WHERE $condicionContratoVigente";
} elseif ($filtro === 'inactivos') {
    $whereClause = "WHERE NOT $condicionContratoVigente";
} else {
    $filtro = 'todos';
}

$rowsPerPage = 15; // Número de registros por página
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1; // Página actual
$offset = ($currentPage - 1) * $rowsPerPage;

// Consulta para obtener datos de afiliados junto con información de personas
$sqlAfiliados = "
    SELECT 
        p.id_persona,
        p.cedula, 
        p.nombre, 
        p.apellido, 
        a.created_at, 
        a.updated_at,
        $condicionContratoVigente as tiene_contrato_vigente
    FROM afiliados a
    JOIN persona p ON a.id_persona = p.id_persona
    $whereClause
    LIMIT $offset, $rowsPerPage
";
$afiliados = $conn->query($sqlAfiliados);

$totalRowsResult = $conn->query("SELECT COUNT(*) AS total FROM afiliados a $whereClause");
$totalRows = $totalRowsResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $rowsPerPage);

?>
<div class="card shadow-lg">
    <div class="mt-3 m-3 text-justify">

        <h1 class="fw-bold text-center" style="color: #062974;">Afiliados</h1>
        <hr class="mx-auto" style="width: 50px; height: 3px; background-color: #062974;">

        <!-- Contenedor para el botón y el input de búsqueda -->
        <div class="row mt-4 align-items-center">
            <div class="col-auto">
                <!-- Botón Agregar Afiliado alineado a la izquierda -->
                <?php if ($periodo_abierto): ?>
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formulariomodal">
                        <i class="fas fa-plus-circle"></i> Agregar afiliado
                    </a>
                <?php elseif ($es_admin): ?>
                    <button type="button" class="btn btn-warning text-dark" data-bs-toggle="modal"
                        data-bs-target="#formulariomodal"
                        title="Fuera del periodo de inscripción. Puede autorizar una inscripción excepcional.">
                        <i class="fas fa-user-plus"></i> Agregar afiliado
                        <span class="badge bg-dark ms-1">Excepción</span>
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-secondary" disabled
                        title="Las inscripciones están cerradas. El periodo es del <?= htmlspecialchars(planPeriodoInscripcionTexto()) ?>.">
                        <i class="fas fa-lock"></i> Agregar afiliado
                    </button>
                <?php endif; ?>
            </div>
            <div class="col text-end mt-2">
                <!-- Select dinámico para filtrar por estado de póliza -->
                <select id="filtro_estado" class="form-select w-auto d-inline-block me-2" onchange="let u = new URLSearchParams(window.location.search); u.set('filtro', this.value); u.delete('page'); window.location.search = u.toString();">
                    <option value="activos" <?= ($filtro == 'activos') ? 'selected' : '' ?>>Con Contrato Vigente</option>
                    <option value="inactivos" <?= ($filtro == 'inactivos') ? 'selected' : '' ?>>Sin Contrato / Vencidos</option>    
                    <option value="todos" <?= ($filtro == 'todos') ? 'selected' : '' ?>>Todos los Afiliados</option>
                </select>
                <!-- Input de búsqueda alineado a la derecha -->
                <input type="text" id="search" class="form-control w-auto d-inline-block"
                    placeholder="Buscar afiliado...">
            </div>
        </div>

        <div id="alert-container"></div>


        <!-- Tabla de afiliados -->
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover mt-4">
                <thead class="table-dark">
                    <tr>
                        <th>Cédula</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Estado Póliza</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $afiliados->fetch_assoc()) {
                        $contrato_row = planContratoRelevante($conn, (int) $row['id_persona']);
                        $resumen_row = $contrato_row ? contratoResumen($conn, $contrato_row) : null;
                        [$estado_texto, $estado_clase] = planEstadoVisualContrato($contrato_row, $resumen_row);
                    ?>
                        <tr>
                            <td><?php echo $row['cedula']; ?></td>
                            <td><?php echo $row['nombre']; ?></td>
                            <td><?php echo $row['apellido']; ?></td>
                            <td>
                                <span class="badge <?= $estado_clase ?>"
                                    <?= $resumen_row ? 'title="' . htmlspecialchars($resumen_row['motivo']) . '"' : '' ?>>
                                    <?= htmlspecialchars($estado_texto) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <!-- Botón Ver Información -->
                                <a href="#" class="btn btn-primary-custom btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#vermodal" data-bs-cedula="<?= $row['cedula']; ?>">
                                    <i class="fas fa-eye"></i> Ver información
                                </a>
                                <button class="btn btn-sm btn-primary text-white" title="Ver Consumo"
                                    onclick="verResumenConsumo('<?php echo $row['cedula']; ?>', '<?php echo $row['nombre'] . ' ' . $row['apellido']; ?>')">
                                    <i class="fas fa-eye"></i> Ver Plan
                                </button>
                                <!-- Botón Editar -->
                                <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#editmodal" data-bs-cedula="<?= $row['cedula']; ?>"
                                    data-bs-id-persona="<?= $row['id_persona']; ?>">
                                    <i class="fas fa-edit"></i>Editar
                                </a>
                                <?php if ($row['tiene_contrato_vigente']) { ?>
                                <!-- Botón Registrar pago del contrato existente -->
                                <a href="/IPSPUPTM/home.php?vista=gestionpagoscontrato" class="btn btn-sm btn-success text-white" title="Registrar pago del contrato">
                                    <i class="fas fa-money-bill-wave"></i> Registrar pago
                                </a>
                                <?php } else { ?>
                                <!-- Botón Asignar Plan -->
                                <button class="btn btn-sm btn-success text-white" title="Asignar Nuevo Plan" data-bs-toggle="modal" data-bs-target="#modalAsignarPlan" data-bs-cedula="<?= $row['cedula']; ?>">
                                    <i class="fas fa-file-signature"></i> Asignar Plan
                                </button>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content mt-3">
            <?php for ($i = 1; $i <= $totalPages; $i++) { 
                $qParams = $_GET;
                $qParams['page'] = $i;
                $qParams['filtro'] = $filtro;
                $qStr = http_build_query($qParams);
            ?>
                <a href="?<?php echo $qStr; ?>"
                    class="btn btn-sm <?php echo ($i == $currentPage) ? 'btn-secondary' : 'btn-primary'; ?> mx-1">
                    <?php echo $i; ?>
                </a>
            <?php } ?>
        </div>

    </div>
</div>
<!-- Incluye los modales -->
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/afiliados/modales/eliminar/eliminamodal.php'; ?>
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/afiliados/modales/formulario/formulariomodal.php'; ?>
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/afiliados/vermodal.php'; ?>
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/afiliados/modales/actualizar/editmodal.php'; ?>
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/afiliados/modales/modal_ver_consumo.php'; ?>
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/afiliados/modales/asignar_plan/modal_asignar.php'; ?>


<script src="/IPSPUPTM/assets/js/accionesafiliados.js?v=20261111-2"></script>




</body>

</html>