<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);

header('Content-Type: application/json');
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

$id_persona = isset($_POST['id_persona']) ? (int) $_POST['id_persona'] : 0;
$cedula = isset($_POST['cedula']) ? trim((string) $_POST['cedula']) : '';

if ($id_persona <= 0 && $cedula === '') {
    echo json_encode(['error' => 'Falta la cédula o el identificador de la persona']);
    exit();
}

// Comunidad UPTM = persona sin rol de afiliado ni beneficiario.
$sql = "SELECT p.id_persona, p.cedula, p.nombre, p.apellido, p.telefono
        FROM persona p
        LEFT JOIN afiliados a     ON a.id_persona = p.id_persona
        LEFT JOIN beneficiarios b ON b.id_persona = p.id_persona
        WHERE a.id_persona IS NULL
          AND b.id_persona IS NULL
          AND (" . ($id_persona > 0 ? "p.id_persona = ?" : "p.cedula = ?") . ")
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Error al preparar la consulta']);
    exit();
}

if ($id_persona > 0) {
    $stmt->bind_param("i", $id_persona);
} else {
    $stmt->bind_param("s", $cedula);
}
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'No existe una persona de comunidad con esa cédula']);
}
