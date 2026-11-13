<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);

require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/persona_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no válido.']);
    exit();
}

$id_persona_post = isset($_POST['id_persona']) ? (int) $_POST['id_persona'] : 0;
$cedula_original = trim((string) ($_POST['cedula_original'] ?? ''));
$cedula          = trim((string) ($_POST['cedula'] ?? ''));
$nombre          = trim((string) ($_POST['nombre'] ?? ''));
$apellido        = trim((string) ($_POST['apellido'] ?? ''));
$telefono        = trim((string) ($_POST['telefono'] ?? ''));

if ($nombre === '' || $apellido === '') {
    echo json_encode(['success' => false, 'message' => 'Error: nombre y apellido son obligatorios.']);
    exit();
}
if (!preg_match('/^04[0-9]{9}$/', $telefono)) {
    echo json_encode(['success' => false, 'message' => 'Error: el teléfono debe tener 11 dígitos y comenzar con 04.']);
    exit();
}

$nombre   = htmlspecialchars($nombre);
$apellido = htmlspecialchars($apellido);
$telefono = htmlspecialchars($telefono);

$transaccion_iniciada = false;

try {
    // Identificar por id interno; la cédula queda como respaldo.
    if ($id_persona_post > 0) {
        $id_persona = $id_persona_post;
    } else {
        $cedula_busqueda = $cedula_original !== '' ? $cedula_original : $cedula;
        $id_persona = buscarIdPersonaPorCedula($conn, $cedula_busqueda);
    }

    if ($id_persona === null) {
        echo json_encode(['success' => false, 'message' => 'No se pudo identificar a la persona. Recargue la página e intente de nuevo.']);
        exit();
    }

    // Solo se puede editar si sigue siendo comunidad (sin rol de afiliado/beneficiario).
    $stmt = $conn->prepare("
        SELECT p.cedula
        FROM persona p
        LEFT JOIN afiliados a     ON a.id_persona = p.id_persona
        LEFT JOIN beneficiarios b ON b.id_persona = p.id_persona
        WHERE p.id_persona = ?
          AND a.id_persona IS NULL
          AND b.id_persona IS NULL
    ");
    $stmt->bind_param("i", $id_persona);
    $stmt->execute();
    $persona = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$persona) {
        echo json_encode(['success' => false, 'message' => 'La persona no existe o ya está registrada como afiliado/beneficiario.']);
        exit();
    }

    $conn->begin_transaction();
    $transaccion_iniciada = true;

    $stmt = $conn->prepare("UPDATE persona SET nombre = ?, apellido = ?, telefono = ? WHERE id_persona = ?");
    if (!$stmt) {
        throw new Exception('No se pudo preparar la actualización.');
    }
    $stmt->bind_param("sssi", $nombre, $apellido, $telefono, $id_persona);
    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar: ' . $stmt->error);
    }
    $stmt->close();

    $usuario = $_SESSION['username'] ?? 'Sistema';
    registrarEnBitacora(
        $conn,
        $usuario,
        'Edición de Comunidad UPTM',
        "Se actualizaron los datos del externo C.I: {$persona['cedula']} ($nombre $apellido)"
    );

    $conn->commit();
    $transaccion_iniciada = false;

    echo json_encode(['success' => true, 'message' => 'Datos actualizados correctamente.']);
} catch (Exception $e) {
    if ($transaccion_iniciada) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error: ' . $e->getMessage()]);
}
