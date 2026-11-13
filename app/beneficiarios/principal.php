<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

$rowsPerPage = 15; // Número de registros por página
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1; // Página actual
$offset = ($currentPage - 1) * $rowsPerPage;

$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'activos';

$whereClause = "";
if ($filtro === 'activos') {
    $whereClause = "WHERE EXISTS (SELECT 1 FROM contrato_plan cp WHERE cp.id_persona = a.id_persona AND cp.estado_contrato = 'Activo')";
} elseif ($filtro === 'inactivos') {
    $whereClause = "WHERE NOT EXISTS (SELECT 1 FROM contrato_plan cp WHERE cp.id_persona = a.id_persona AND cp.estado_contrato = 'Activo')";
}

try {
    // Consulta para obtener datos de beneficiarios junto con información de personas y afiliados
    $sqlBeneficiarios = "
        SELECT 
            b.id_persona,
            b.cedula, 
            p_b.nombre AS nombre_beneficiario, 
            p_b.apellido AS apellido_beneficiario, 
            b.parentesco,
            p_b.fechanacimiento,
            CONCAT(p_a.nombre, ' ', p_a.apellido) AS nombre_afiliado, 
            b.created_at, 
            b.updated_at,
            EXISTS (SELECT 1 FROM contrato_plan cp WHERE cp.id_persona = a.id_persona AND cp.estado_contrato = 'Activo') as tiene_activo
        FROM beneficiarios b
        JOIN persona p_b ON b.id_persona = p_b.id_persona -- Datos del beneficiario
        JOIN afiliados a ON b.id_afiliado = a.ID -- Relación entre beneficiarios y afiliados
        JOIN persona p_a ON a.id_persona = p_a.id_persona -- Datos del afiliado
        $whereClause
        LIMIT $rowsPerPage OFFSET $offset
    ";
    $beneficiarios = $conn->query($sqlBeneficiarios);

    // Consulta para detectar beneficiarios hijos con edad excedida
    $sqlExcedidos = "
        SELECT p_b.nombre, p_b.apellido, p_b.cedula, 
               TIMESTAMPDIFF(YEAR, p_b.fechanacimiento, CURDATE()) AS edad
        FROM beneficiarios b
        JOIN persona p_b ON b.id_persona = p_b.id_persona
        WHERE b.parentesco = 'Hijo' 
        AND TIMESTAMPDIFF(YEAR, p_b.fechanacimiento, CURDATE()) >= 25
    ";
    $resultExcedidos = $conn->query($sqlExcedidos);

    // Validación de errores en la consulta
    if (!$beneficiarios) {
        throw new Exception("Error en la consulta de beneficiarios: " . $conn->error);
    }

    // Obtener el total de filas para calcular páginas
    $totalRowsResult = $conn->query("SELECT COUNT(*) AS total FROM beneficiarios b JOIN afiliados a ON b.id_afiliado = a.ID $whereClause");
    if (!$totalRowsResult) {
        throw new Exception("Error en la consulta del total de beneficiarios: " . $conn->error);
    }

    $totalRows = $totalRowsResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRows / $rowsPerPage);

    // Consulta para obtener la lista de afiliados para el formulario
    $sql_afiliados = "
        SELECT a.ID, CONCAT(p.nombre, ' ', p.apellido) AS nombre_afiliado 
        FROM afiliados a
        JOIN persona p ON a.id_persona = p.id_persona
        ORDER BY p.nombre ASC
    ";
    $result_afiliados = $conn->query($sql_afiliados);
    if (!$result_afiliados) {
        throw new Exception("Error en la consulta de afiliados: " . $conn->error);
    }

} catch (Exception $e) {
    // Manejo de errores
    echo "Ocurrió un error: " . $e->getMessage();
    exit();
}
?>

<div class="card shadow-lg">
    <div class="cont-general">
        
        <?php if ($resultExcedidos && $resultExcedidos->num_rows > 0): ?>
        <div class="alert alert-warning m-3 shadow-sm border-start border-warning border-5" role="alert">
            <h5 class="alert-heading fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Atención: Hijos con Edad Excedida</h5>
            <p class="mb-2">Los siguientes beneficiarios han alcanzado o superado los 25 años y <strong>no podrán recibir cobertura en nuevas citas</strong>:</p>
            <ul class="mb-0">
                <?php while ($ex = $resultExcedidos->fetch_assoc()): ?>
                <li><strong><?= $ex['nombre'].' '.$ex['apellido'] ?></strong> (C.I: <?= $ex['cedula'] ?>) - Edad: <?= $ex['edad'] ?> años.</li>
                <?php endwhile; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="mt-3 m-3 text-justify">
             <h1 class="fw-bold text-center" style="color: #062974;">Beneficiarios</h1>
            <hr class="mx-auto" style="width: 50px; height: 3px; background-color: #062974;">

            <!-- Contenedor para el botón y el input de búsqueda -->
            <div class="row mt-4 align-items-center">
                <div class="col-auto">
                    <!-- Botón Agregar Beneficiario alineado a la izquierda -->
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formulariomodal">
                        <i class="fas fa-plus-circle"></i> Agregar beneficiario
                    </a>
                </div>
                <div class="col text-end mt-2">
                    <!-- Select dinámico para filtrar por estado de póliza -->
                    <select id="filtro_estado" class="form-select w-auto d-inline-block me-2" onchange="let u = new URLSearchParams(window.location.search); u.set('filtro', this.value); u.delete('page'); window.location.search = u.toString();">
                        <option value="activos" <?= ($filtro == 'activos') ? 'selected' : '' ?>>Titular Con Contrato Activo</option>
                        <option value="inactivos" <?= ($filtro == 'inactivos') ? 'selected' : '' ?>>Titular Sin Contrato / Inactivo</option>    
                        <option value="todos" <?= ($filtro == 'todos') ? 'selected' : '' ?>>Todos los Beneficiarios</option>
                    </select>
                    <!-- Input de búsqueda alineado a la derecha -->
                    <input type="text" id="search" class="form-control w-auto d-inline-block"
                        placeholder="Buscar beneficiario...">
                </div>
            </div>


            <!-- Tabla de beneficiarios -->
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover mt-4">
                    <thead class="table-dark">
                        <tr>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Parentesco</th>
                            <th>Afiliado</th> <!-- Nueva columna -->
                            <th>Estado Póliza Titular</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $beneficiarios->fetch_assoc()) { 
                            $edad = date_diff(date_create($row['fechanacimiento']), date_create('today'))->y;
                            $alertaEdad = ($row['parentesco'] == 'Hijo' && $edad >= 25);
                        ?>
                        <tr class="<?= $alertaEdad ? 'table-warning' : '' ?>">
                            <td><?php echo $row['cedula']; ?></td>
                            <td><?php echo $row['nombre_beneficiario']; ?></td>
                            <td><?php echo $row['apellido_beneficiario']; ?></td>
                            <td>
                                <span class="badge bg-secondary"><?php echo $row['parentesco']; ?></span>
                                <?php if ($alertaEdad): ?>
                                    <span class="badge bg-danger" title="Edad: <?= $edad ?> años"><i class="fas fa-clock"></i> +25</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['nombre_afiliado']; ?></td> <!-- Nombre del afiliado relacionado -->
                            <td>
                                <?php if ($row['tiene_activo']) { ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php } else { ?>
                                    <span class="badge bg-danger">Inactivo / Sin Plan</span>
                                <?php } ?>
                            </td>
                            <td class="text-center">
                                <!-- Botón Ver Información -->
                                <a href="#" class="btn btn-primary-custom btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#vermodal" data-bs-cedula="<?= $row['cedula']; ?>">
                                    <i class="fas fa-eye"></i> Ver información
                                </a>
                                <button class="btn btn-sm btn-primary"
                                    onclick="verConsumoBeneficiario('<?= $row['cedula']; ?>', '<?= $row['nombre_beneficiario'].' '.$row['apellido_beneficiario']; ?>', '<?= $row['nombre_afiliado']; ?>')">
                                    <i class="fas fa-chart-pie"></i> Ver Plan
                                </button>
                                <!-- Botón Editar -->
                                <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#editmodal" data-bs-cedula="<?= $row['cedula']; ?>"
                                    data-bs-id-persona="<?= $row['id_persona']; ?>">
                                    <i class="fas fa-edit"></i>Editar
                                </a>
                                <!-- Botón Eliminar -->
                                <a href="#" class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#eliminamodal" data-bs-cedula="<?= $row['cedula']; ?>">
                                    <i class="fas fa-trash"></i> Eliminar
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">
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

</div>
<!-- Incluye los modales -->
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/beneficiarios/modales/eliminar/eliminamodal.php'; ?>
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/beneficiarios/modales/formulario/formulariomodal.php'; ?>
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/beneficiarios/modales/actualizar/editmodal.php'; ?>
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/beneficiarios/vermodal.php'; ?>
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/beneficiarios/modales/modal_ver_consumo_benef.php'; ?>

<script src="/IPSPUPTM/assets/js/accionesbeneficiarios.js?v=<?= @filemtime('C:/xampp/htdocs/IPSPUPTM/assets/js/accionesbeneficiarios.js') ?: '20261112-2' ?>"></script>