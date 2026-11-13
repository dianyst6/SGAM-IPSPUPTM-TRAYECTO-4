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

$cedula   = trim((string) ($_POST['cedula'] ?? ''));
$nombre   = trim((string) ($_POST['nombre'] ?? ''));
$apellido = trim((string) ($_POST['apellido'] ?? ''));
$telefono = trim((string) ($_POST['telefono'] ?? ''));

if (!preg_match('/^[0-9]{1,8}$/', $cedula)) {
    echo json_encode(['success' => false, 'message' => 'Error: la cédula debe contener solo números (máximo 8).']);
    exit();
}
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
    // La cédula ya existe: hay que decir exactamente por qué no se puede registrar.
    $id_existente = buscarIdPersonaPorCedula($conn, $cedula);
    if ($id_existente !== null) {
        $stmt = $conn->prepare("
            SELECT
                (SELECT COUNT(*) FROM afiliados    WHERE id_persona = ?) AS es_afiliado,
                (SELECT COUNT(*) FROM beneficiarios WHERE id_persona = ?) AS es_beneficiario
        ");
        $stmt->bind_param("ii", $id_existente, $id_existente);
        $stmt->execute();
        $roles = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ((int) $roles['es_afiliado'] > 0) {
            echo json_encode(['success' => false, 'message' => 'La cédula ya está registrada como afiliado.']);
            exit();
        }
        if ((int) $roles['es_beneficiario'] > 0) {
            echo json_encode(['success' => false, 'message' => 'La cédula ya está registrada como beneficiario.']);
            exit();
        }

        echo json_encode(['success' => false, 'message' => 'La cédula ya está registrada en la Comunidad UPTM.']);
        exit();
    }

    $conn->begin_transaction();
    $transaccion_iniciada = true;

    // Solo se piden 4 datos; el resto queda como "No aplica" / NULL.
    $sql = "INSERT INTO persona
                (cedula, nombre, apellido, fechanacimiento, genero, telefono, correo, ocupacion, estado)
            VALUES (?, ?, ?, NULL, NULL, ?, 'No aplica', 'No aplica', 'No aplica')";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('No se pudo preparar el registro.');
    }
    $stmt->bind_param("ssss", $cedula, $nombre, $apellido, $telefono);
    if (!$stmt->execute()) {
        throw new Exception('Error al registrar: ' . $stmt->error);
    }
    $stmt->close();

    $usuario = $_SESSION['username'] ?? 'Sistema';
    registrarEnBitacora(
        $conn,
        $usuario,
        'Registro de Comunidad UPTM',
        "Se registró a la persona en comunidad: $nombre $apellido (C.I: $cedula)"
    );

    $conn->commit();
    $transaccion_iniciada = false;

    echo json_encode(['success' => true, 'message' => 'Miembro de la comunidad registrado exitosamente.']);
} catch (Exception $e) {
    if ($transaccion_iniciada) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error: ' . $e->getMessage()]);
}
