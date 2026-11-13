<?php
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

$rowsPerPage = 15; // Número de registros por página
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1; // Página actual
$offset = ($currentPage - 1) * $rowsPerPage;

// Determinar pestaña actual
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'activas';

// Construir condición de filtrado según pestaña
$whereTab = "";
switch ($currentTab) {
    case 'pagadas':
        $whereTab = "AND (c.estado_pago = 'Pagada' OR c.estado_pago = 'Deducida de Póliza')";
        break;
    case 'canceladas':
        $whereTab = "AND c.estado = 'cancelada'";
        break;
    case 'activas':
    default:
        $whereTab = "AND c.estado = 'activa' AND c.estado_pago = 'Por Pagar'";
        $currentTab = 'activas'; // Asegurar valor por defecto
        break;
}

try {
    // Consulta SQL combinada    
    $sqlCitas = "
    SELECT 
        c.id_cita, 
        c.fecha_cita, 
        c.descripcion, 
        c.estado,
        c.estado_pago,
        e.nombre_especialidad,
        CASE 
            WHEN a.ID IS NOT NULL THEN 'Afiliado'
            WHEN b.id_beneficiario IS NOT NULL THEN 'Beneficiario'
            ELSE 'Comunidad UPTM'
        END AS tipo_paciente,
        CONCAT(p.nombre, ' ', p.apellido) AS nombre_paciente,
        p.cedula AS cedula_paciente
    FROM citas c
    INNER JOIN persona p ON p.id_persona = c.id_persona
    LEFT JOIN afiliados a     ON a.id_persona = p.id_persona
    LEFT JOIN beneficiarios b ON b.id_persona = p.id_persona
    LEFT JOIN especialidades e ON c.id_especialidad = e.id_especialidad

    WHERE c.fecha_cita >= CURDATE() $whereTab
    ORDER BY c.fecha_cita ASC
    LIMIT $rowsPerPage OFFSET $offset
";

    $citas = $conn->query($sqlCitas);

    if (!$citas) {
        throw new Exception("Error en la consulta de citas: " . $conn->error);
    }

    // Obtener el total de filas para calcular páginas
    $totalRowsResult = $conn->query("SELECT COUNT(*) AS total FROM citas c WHERE c.fecha_cita >= CURDATE() $whereTab");
    if (!$totalRowsResult) {
        throw new Exception("Error en la consulta del total de citas: " . $conn->error);
    }

    $totalRows = $totalRowsResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRows / $rowsPerPage);

} catch (Exception $e) {
    // Manejo de errores
    echo "Ocurrió un error: " . $e->getMessage();
    exit();
}
?>
<div class="card shadow-lg">
    <div class="cont-general">
        <div class="card-body" style="margin-left: 0;">
            <!-- Elimina el margen izquierdo -->
            <div class="mt-3 m-3 text-justify">
                <h1 class="fw-bold text-center" style="color: #062974;">Citas</h1>
                <hr class="mx-auto" style="width: 50px; height: 3px; background-color: #062974;">

                </h1>
                <br>


                <div class="row mt-3">
                    <div class="col-auto">
                        <!-- Botón Agregar Cita -->
                        <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formulariomodal">
                            <i class="fas fa-plus-circle"></i> Agregar cita
                        </a>
                    </div>
                    <div class="col-auto">
                        <select id="filterTipo" class="form-select w-auto">
                            <option value="todos">Todos los Tipos</option>
                            <option value="Afiliado">Afiliados</option>
                            <option value="Beneficiario">Beneficiarios</option>
                            <option value="Comunidad UPTM">Comunidad UPTM</option>
                        </select>
                    </div>
                    <div class="col text-end mt-2">
                        <!-- Input de búsqueda alineado a la derecha -->
                        <input type="text" id="search" class="form-control w-auto d-inline-block"
                            placeholder="Buscar cita...">
                    </div>
                </div>
                <!-- Pestañas de Filtrado -->
                <ul class="nav nav-tabs mb-4 shadow-sm rounded-top" id="citasTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentTab == 'activas') ? 'active fw-bold' : '' ?>"
                            href="?vista=citas&tab=activas">
                            <i class="fas fa-calendar-check me-2"></i>Citas Activas (Por Pagar)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentTab == 'pagadas') ? 'active fw-bold text-success' : '' ?>"
                            href="?vista=citas&tab=pagadas">
                            <i class="fas fa-file-invoice-dollar me-2"></i>Citas Pagadas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentTab == 'canceladas') ? 'active fw-bold text-danger' : '' ?>"
                            href="?vista=citas&tab=canceladas">
                            <i class="fas fa-calendar-times me-2"></i>Citas Canceladas
                        </a>
                    </li>
                </ul>


                <h4>
                    <?php
                    if ($currentTab == 'pagadas')
                        echo "Citas Pagadas / Deducidas";
                    elseif ($currentTab == 'canceladas')
                        echo "Citas Canceladas";
                    else
                        echo "Citas Pendientes de Pago";
                    ?>
                </h4>
                <div class="table-responsive mt-3">
                    <!-- Tabla de citas -->
                    <table class="table table-sm table-striped table-hover mx-auto">
                        <thead class="table-dark">
                            <tr>
                                <th>Cédula</th>
                                <th>Nombre del Paciente</th>
                                <th>Tipo de Paciente</th>
                                <th>Especialidad</th>
                                <th>Descripción</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($citas->num_rows > 0) { ?>
                                <?php while ($row = $citas->fetch_assoc()) { ?>
                                    <tr data-tipo="<?php echo $row['tipo_paciente']; ?>">
                                        <td><?php echo htmlspecialchars($row['cedula_paciente']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nombre_paciente']); ?></td>
                                        <td><?php echo htmlspecialchars($row['tipo_paciente']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nombre_especialidad']); ?></td>
                                        <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                        <td><?php echo date('d-m-Y H:i', strtotime($row['fecha_cita'])); ?></td>
                                        <td>
                                            <?php if ($row['estado'] == 'activa'): ?>
                                                <?php if ($row['estado_pago'] == 'Por Pagar'): ?>
                                                    <span class="badge bg-success">Activa / Por Pagar</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary"><?php echo $row['estado_pago']; ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Cancelada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#editmodal"
                                                data-bs-idcita="<?= htmlspecialchars($row['id_cita']); ?>">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <a href="#"
                                                class="btn btn-danger btn-sm <?php echo ($row['estado'] == 'cancelada') ? 'disabled' : ''; ?>"
                                                data-bs-toggle="modal" data-bs-target="#eliminamodal"
                                                data-bs-idcita="<?= htmlspecialchars($row['id_cita']); ?>">
                                                <i class="fas fa-ban"></i> Cancelar
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                } ?>
                                <?php
                            } else { ?>
                                <tr>
                                    <td colspan="8" class="text-center">No se encontraron citas registradas.</td>
                                </tr>
                                <?php
                            } ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
                        <a href="?vista=citas&page=<?php echo $i; ?>&tab=<?php echo $currentTab; ?>"
                            class="btn btn-sm <?php echo ($i == $currentPage) ? 'btn-secondary' : 'btn-primary'; ?> mx-1">
                            <?php echo $i; ?>
                        </a>
                        <?php
                    } ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Incluye los modales -->
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/citas/modales/eliminar/eliminamodal.php'; ?>
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/citas/modales/formulario/formulariomodal.php'; ?>
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/citas/modales/actualizar/editmodal.php'; ?>

<script src="/IPSPUPTM/assets/js/accionescitas.js"></script>