<?php
require_once 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
session_start();

// =========================================================================
// 1.2 CONTROL DE ACCESO
// Solo un administrador autenticado puede crear usuarios.
// =========================================================================
if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_error'] = "Debes iniciar sesión como administrador para gestionar usuarios.";
    header('Location: /IPSPUPTM/index.php');
    exit();
}

// El rol se consulta en la base de datos (no se confía únicamente en la sesión)
$rol_actual = null;
$stmt_rol = $conn->prepare("SELECT role_id FROM usuarios WHERE id = ?");
$stmt_rol->bind_param("i", $_SESSION['user_id']);
$stmt_rol->execute();
$res_rol = $stmt_rol->get_result();
if ($fila_rol = $res_rol->fetch_assoc()) {
    $rol_actual = (int) $fila_rol['role_id'];
}
$stmt_rol->close();

if ($rol_actual !== 1) {
    http_response_code(403);
    echo "Acceso denegado. Solo un administrador puede gestionar usuarios.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /IPSPUPTM/home.php?vista=usuarios');
    exit();
}

// Devuelve un mensaje al formulario de forma segura (sin inyectar HTML)
function errorRegistro($mensaje)
{
    echo "<script>alert(" . json_encode($mensaje) . "); window.history.back();</script>";
    exit();
}

// =========================================================================
// 2. RECOGIDA Y VALIDACIÓN DE DATOS EN EL SERVIDOR
// =========================================================================
$username         = trim($_POST['username'] ?? '');
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role_id          = isset($_POST['role_id']) ? (int) $_POST['role_id'] : 0;
$pregunta_id1     = isset($_POST['pregunta_seguridad_id1']) ? (int) $_POST['pregunta_seguridad_id1'] : 0;
$pregunta_id2     = isset($_POST['pregunta_seguridad_id2']) ? (int) $_POST['pregunta_seguridad_id2'] : 0;
$respuesta1       = trim($_POST['respuesta_seguridad1'] ?? '');
$respuesta2       = trim($_POST['respuesta_seguridad2'] ?? '');

if (!preg_match('/^[A-Za-z0-9_]{5,20}$/', $username)) {
    errorRegistro('El nombre de usuario debe tener entre 5 y 20 caracteres y contener solo letras, números y guiones bajos.');
}

if (strlen($password) < 8 || strlen($password) > 20) {
    errorRegistro('La contraseña debe tener entre 8 y 20 caracteres.');
}

if ($password !== $confirm_password) {
    errorRegistro('Las contraseñas no coinciden.');
}

// Solo se aceptan roles existentes en el sistema (1 = admin, 2 = usuario, 3 = médico)
if (!in_array($role_id, [1, 2, 3], true)) {
    errorRegistro('El rol seleccionado no es válido.');
}

if ($pregunta_id1 <= 0 || $pregunta_id2 <= 0 || $pregunta_id1 === $pregunta_id2) {
    errorRegistro('Debes seleccionar dos preguntas de seguridad diferentes.');
}

if (mb_strlen($respuesta1) < 3 || mb_strlen($respuesta1) > 20 ||
    mb_strlen($respuesta2) < 3 || mb_strlen($respuesta2) > 20) {
    errorRegistro('Las respuestas de seguridad deben tener entre 3 y 20 caracteres.');
}

// Las preguntas deben existir realmente en la base de datos
$stmt_preg = $conn->prepare("SELECT COUNT(*) AS total FROM preguntas_seguridad WHERE ID IN (?, ?)");
$stmt_preg->bind_param("ii", $pregunta_id1, $pregunta_id2);
$stmt_preg->execute();
$total_preguntas = (int) $stmt_preg->get_result()->fetch_assoc()['total'];
$stmt_preg->close();

if ($total_preguntas !== 2) {
    errorRegistro('Alguna de las preguntas de seguridad no es válida.');
}

// Datos del médico (solo si el rol es 3)
$ci_medico = '';
$especialidad = 0;
$telefono = '';

if ($role_id === 3) {
    $ci_medico    = trim($_POST['ci_medico'] ?? '');
    $especialidad = isset($_POST['especialidad']) ? (int) $_POST['especialidad'] : 0;
    $telefono     = trim($_POST['telefono_personal'] ?? '');

    if (!preg_match('/^[0-9]{6,8}$/', $ci_medico)) {
        errorRegistro('La cédula del médico debe tener entre 6 y 8 números.');
    }

    if ($telefono !== '' && !preg_match('/^[0-9]{11}$/', $telefono)) {
        errorRegistro('El teléfono personal debe tener 11 números.');
    }

    $stmt_esp = $conn->prepare("SELECT id_especialidad FROM especialidades WHERE id_especialidad = ?");
    $stmt_esp->bind_param("i", $especialidad);
    $stmt_esp->execute();
    if ($stmt_esp->get_result()->num_rows !== 1) {
        $stmt_esp->close();
        errorRegistro('La especialidad seleccionada no es válida.');
    }
    $stmt_esp->close();

    $stmt_ci = $conn->prepare("SELECT ci_medico FROM medicos WHERE ci_medico = ?");
    $stmt_ci->bind_param("s", $ci_medico);
    $stmt_ci->execute();
    if ($stmt_ci->get_result()->num_rows > 0) {
        $stmt_ci->close();
        errorRegistro('Ya existe un médico registrado con esa cédula.');
    }
    $stmt_ci->close();
}

// 3. Verificar si el nombre de usuario ya existe
$sql_check = "SELECT id FROM usuarios WHERE username = ?";
$stmt_check = $conn->prepare($sql_check);
if ($stmt_check) {
    $stmt_check->bind_param("s", $username);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        errorRegistro('El nombre de usuario ya está en uso. Por favor, elige otro.');
    }
    $stmt_check->close();
}

// Encriptar la contraseña
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Iniciar Transacción para asegurar que se guarde TODO o NADA
$conn->begin_transaction();

try {
    // --- PASO A: INSERTAR USUARIO ---
    $sql_usuario = "INSERT INTO usuarios (username, password, role_id) VALUES (?, ?, ?)";
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bind_param("ssi", $username, $hashed_password, $role_id);
    $stmt_usuario->execute();

    $user_id = $conn->insert_id;

    // --- PASO B: INSERTAR RESPUESTAS DE SEGURIDAD ---
    $sql_resp = "INSERT INTO respuestas_seguridad (usuario_id, pregunta_seguridad_id, respuesta) VALUES (?, ?, ?)";
    $stmt_resp = $conn->prepare($sql_resp);

    // Respuesta 1
    $stmt_resp->bind_param("iis", $user_id, $pregunta_id1, $respuesta1);
    $stmt_resp->execute();

    // Respuesta 2
    $stmt_resp->bind_param("iis", $user_id, $pregunta_id2, $respuesta2);
    $stmt_resp->execute();

    // --- PASO C: SI ES MÉDICO (ID 3), INSERTAR EN TABLA MEDICOS ---
    if ($role_id === 3) {
        $sql_medico = "INSERT INTO medicos (ci_medico, id_usuario, especialidad, telefono_personal) VALUES (?, ?, ?, ?)";
        $stmt_medico = $conn->prepare($sql_medico);
        $stmt_medico->bind_param("siss", $ci_medico, $user_id, $especialidad, $telefono);
        $stmt_medico->execute();
    }

    $conn->commit();
    header('Location: /IPSPUPTM/home.php?vista=usuarios&mensaje=usuario_agregado');
    exit();

} catch (Exception $e) {
    $conn->rollback();
    errorRegistro('Error al registrar el usuario: ' . $e->getMessage());
}

$conn->close();
