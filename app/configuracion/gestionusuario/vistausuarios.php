<?php
// Esto busca desde la carpeta htdocs hacia abajo, siempre funciona igual
include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/database.php';
include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/alertify.php';

$rowsPerPage = 15;
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($currentPage - 1) * $rowsPerPage;

// 1. Ocultar administrador (ID 7) en la consulta
$sqlUsuarios = "
    SELECT id, username, role_id
    FROM usuarios
    WHERE id != 7
    LIMIT $offset, $rowsPerPage
";
$Usuarios = $conn->query($sqlUsuarios);

// 1. Ocultar administrador en el conteo total
$totalRowsResult = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE id != 7");
$totalRows = $totalRowsResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $rowsPerPage);
?>
<div class="card shadow-lg">
    <div class="container-fluid mt-4 px-4">
         <h1 class="fw-bold text-center" style="color: #062974;">Gestión de Usuarios</h1>
        <p>Aquí puedes agregar, editar, eliminar y configurar los permisos de cada usuario.</p>
        
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover mt-4">
                <thead class="table-dark">
                    <tr>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Pregunta 1</th>
                        <th>Respuesta 1</th>
                        <th>Pregunta 2</th>
                        <th>Respuesta 2</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = $Usuarios->fetch_assoc()) {
                        $userId = $row['id'];
                        
                        // --- CAMBIO AQUÍ: Agregamos 'rs.pregunta_seguridad_id' ---
                        $sql_respuestas = "SELECT ps.pregunta, rs.respuesta, rs.pregunta_seguridad_id 
                                        FROM respuestas_seguridad rs 
                                        JOIN preguntas_seguridad ps ON rs.pregunta_seguridad_id = ps.ID 
                                        WHERE rs.usuario_id = $userId 
                                        ORDER BY rs.pregunta_seguridad_id ASC LIMIT 2";
                                        
                        $result_respuestas = $conn->query($sql_respuestas);
                        $respuestas = $result_respuestas->fetch_all(MYSQLI_ASSOC);

                        $sql_rol = "SELECT r.Nombre FROM usuarios u JOIN roles r ON u.role_id = r.id WHERE u.id = $userId";
                        $rol_nombre = $conn->query($sql_rol)->fetch_assoc()['Nombre'] ?? 'N/A';
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($row['username']); ?></td>
                            <td><?= htmlspecialchars($rol_nombre); ?></td>
                            <td><?= $respuestas[0]['pregunta'] ?? 'N/A'; ?></td>
                            <td><?= $respuestas[0]['respuesta'] ?? 'N/A'; ?></td>
                            <td><?= $respuestas[1]['pregunta'] ?? 'N/A'; ?></td>
                            <td><?= $respuestas[1]['respuesta'] ?? 'N/A'; ?></td>
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editModal" 
                                    data-id="<?= $row['id']; ?>" 
                                    data-username="<?= htmlspecialchars($row['username']); ?>" 
                                    data-role="<?= $row['role_id']; ?>"
                                    data-p1="<?= $respuestas[0]['pregunta_seguridad_id'] ?? ''; ?>" 
                                    data-r1="<?= $respuestas[0]['respuesta'] ?? ''; ?>"
                                    data-p2="<?= $respuestas[1]['pregunta_seguridad_id'] ?? ''; ?>" 
                                    data-r2="<?= $respuestas[1]['respuesta'] ?? ''; ?>"> 
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#eliminamodal" data-bs-id="<?= $row['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                    <li class="page-item <?= ($page == $currentPage) ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $page ?>"><?= $page ?></a></li>
                <?php endfor; ?>
            </ul>
        </nav>

        <?php
        $result_preguntas = $conn->query("SELECT ID, pregunta FROM preguntas_seguridad");
        $preguntas = [];
        while ($row = $result_preguntas->fetch_assoc()) {
            $preguntas[$row['ID']] = $row['pregunta'];
        }
        ?>

        <div class="mt-4">
            <h2 class="mb-3">Agregar Usuario</h2>
            <div class="card shadow p-4 mb-5">
                <form action="/IPSPUPTM/Inicio/registro.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nombre de usuario (debe tener entre 5 y 20 caracteres y contener solo letras, números y guiones bajos.)</label>
                        <input type="text" class="form-control" id="username" name="username" required autocomplete="off" maxlength="20" minlength="5" pattern="[A-Za-z0-9_]+" title="El nombre de usuario debe tener entre 5 y 20 caracteres y contener solo letras, números y guiones bajos.">
                        <div id="usernameFeedback" class="form-text"></div>
                    </div> 
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña (debe tener al menos 8 caracteres y como máximo 20)</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required maxlength="20" minlength="8"  title="La contraseña debe tener al menos 8 caracteres">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div><div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required maxlength="20" minlength="8" title="La contraseña debe tener al menos 8 caracteres">
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                            <i class="fas fa-eye" id="confirmEyeIcon"></i>
                        </button>
                    </div>
                    <div id="passwordFeedback" class="form-text"></div>
                </div>
                    <div class="mb-3">
                        <label for="role_id" class="form-label">Rol</label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <option value="1">Admin</option>
                            <option value="2">Usuario</option>
                            <option value="3">Médico</option>
                        </select>
                    </div>

                  <div id="campos-medico" style="display: none;" class="border p-3 mb-3 bg-light rounded">
                        <h5 class="text-primary">Datos del Médico</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ci_medico" class="form-label">Cédula del Médico</label>
                                <input type="text"
                                    name="ci_medico"
                                    id="ci_medico"
                                    class="form-control"
                                    inputmode="numeric"
                                    pattern="[0-9]{6,8}"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    minlength="6" 
                                    maxlength="8"
                                    title="La cédula debe tener entre 6 y 8 números"
                                    placeholder="Ej: 27945123">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telefono_personal" class="form-label">Teléfono Personal</label>
                                <input type="text" class="form-control" id="telefono_personal" name="telefono_personal" minlength="11" maxlength="11" placeholder="Ej: 04141234567" inputmode="numeric" pattern="[0-9]{11}" title="El teléfono debe tener 11 números" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="id_especialidad" class="form-label">Especialidad</label>
                                <select name="especialidad" id="especialidad" class="form-select">
                                    <option value="" selected disabled>Seleccionar...</option>
                                    <?php
                                    $sql_especialidades = "SELECT id_especialidad, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad ASC";
                                    $result_espe = $conn->query($sql_especialidades);
                                    while ($row_e = $result_espe->fetch_assoc()) {
                                        echo '<option value="' . $row_e['id_especialidad'] . '">' . $row_e['nombre_especialidad'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="pregunta_seguridad_id1" class="form-label">Pregunta 1</label>
                        <select class="form-select" id="pregunta_seguridad_id1" name="pregunta_seguridad_id1" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($preguntas as $id => $p): ?><option value="<?= $id ?>"><?= $p ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="respuesta_seguridad1" class="form-label">Respuesta 1</label>
                        <input type="text" class="form-control" id="respuesta_seguridad1" name="respuesta_seguridad1" required maxlength="20" minlength="3">
                    </div>

                    <div class="mb-3">
                        <label for="pregunta_seguridad_id2" class="form-label">Pregunta 2</label>
                        <select class="form-select" id="pregunta_seguridad_id2" name="pregunta_seguridad_id2" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($preguntas as $id => $p): ?><option value="<?= $id ?>"><?= $p ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="respuesta_seguridad2" class="form-label">Respuesta 2</label>
                        <input type="text" class="form-control" id="respuesta_seguridad2" name="respuesta_seguridad2" required maxlength="20" minlength="3">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Registrar</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'eliminar/eliminarmodal.php'; ?>
<?php include 'actualizar/editmodal.php'; ?>
<script src="/IPSPUPTM/assets/js/accionesusuarios.js"></script>
  