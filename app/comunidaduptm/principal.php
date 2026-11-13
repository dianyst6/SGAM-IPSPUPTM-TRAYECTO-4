<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);

include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/alertify.php';

$rowsPerPage = 15;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($currentPage - 1) * $rowsPerPage;

// Comunidad UPTM = personas que no son afiliados ni beneficiarios.
// No hay tabla propia: se calcula por exclusión sobre persona.
$sqlBase = "
    FROM persona p
    LEFT JOIN afiliados a    ON a.id_persona = p.id_persona
    LEFT JOIN beneficiarios b ON b.id_persona = p.id_persona
    WHERE a.id_persona IS NULL AND b.id_persona IS NULL
";

$sqlComunidad = "SELECT p.id_persona, p.cedula, p.nombre, p.apellido, p.telefono
                 $sqlBase
                 ORDER BY p.apellido ASC, p.nombre ASC
                 LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sqlComunidad);
$stmt->bind_param("ii", $rowsPerPage, $offset);
$stmt->execute();
$comunidad = $stmt->get_result();

$totalRowsResult = $conn->query("SELECT COUNT(*) AS total $sqlBase");
$totalRows = $totalRowsResult ? (int) $totalRowsResult->fetch_assoc()['total'] : 0;
$totalPages = (int) ceil($totalRows / $rowsPerPage);
?>

<div class="card shadow-lg">
    <div class="mt-3 m-3 text-justify">
        <h1 class="fw-bold text-center" style="color: #062974;">Comunidad UPTM (Externos)</h1>
        <hr class="mx-auto" style="width: 50px; height: 3px; background-color: #062974;">

        <p class="text-muted text-center mb-0">
            Personas registradas que no son afiliados ni beneficiarios.
        </p>

        <div class="row mt-4 align-items-center">
            <div class="col-auto">
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formulariomodal">
                    <i class="fas fa-plus-circle"></i> Agregar a Comunidad
                </a>
            </div>
            <div class="col text-end mt-2">
                <input type="text" id="search" class="form-control w-auto d-inline-block"
                    placeholder="Buscar por cédula o nombre...">
            </div>
        </div>

        <div id="alert-container"></div>

        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover mt-4">
                <thead class="table-dark">
                    <tr>
                        <th>Cédula</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Teléfono</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-comunidad">
                    <?php if ($comunidad && $comunidad->num_rows > 0): ?>
                        <?php while ($row = $comunidad->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center"><?= htmlspecialchars($row['cedula']) ?></td>
                            <td><?= htmlspecialchars($row['nombre']) ?></td>
                            <td><?= htmlspecialchars($row['apellido']) ?></td>
                            <td><?= htmlspecialchars($row['telefono']) ?></td>
                            <td class="text-center">
                                <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#editmodal"
                                    data-bs-cedula="<?= htmlspecialchars($row['cedula']) ?>"
                                    data-bs-id-persona="<?= (int) $row['id_persona'] ?>">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No hay personas registradas como comunidad.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>"
                class="btn btn-sm <?= ($i == $currentPage) ? 'btn-secondary' : 'btn-primary' ?> mx-1">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<?php include 'C:/xampp/htdocs/IPSPUPTM/app/comunidaduptm/formulario/formulariomodal.php'; ?>
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/comunidaduptm/actualizar/editmodal.php'; ?>
<script src="/IPSPUPTM/assets/js/accionescomunidaduptm.js?v=<?= @filemtime('C:/xampp/htdocs/IPSPUPTM/assets/js/accionescomunidaduptm.js') ?: '20261112-1' ?>"></script>
