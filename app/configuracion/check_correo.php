<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

header('Content-Type: application/json');

if (!isset($_POST['correo'])) {
    echo json_encode(['error' => 'Correo no proporcionado']);
    exit();
}

$correo = trim((string) $_POST['correo']);
$id_persona_ignorada = isset($_POST['id_persona_ignorada']) ? (int) $_POST['id_persona_ignorada'] : 0;
$cedula_ignorada = isset($_POST['cedula_ignorada']) ? trim((string) $_POST['cedula_ignorada']) : '';

if ($id_persona_ignorada > 0) {
    // Edición de afiliado: se ignora la persona por su id interno
    // (la cédula puede estar cambiando en el mismo formulario).
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM persona WHERE correo = ? AND id_persona != ?");
    $stmt->bind_param("si", $correo, $id_persona_ignorada);
} elseif ($cedula_ignorada !== '') {
    // Compatibilidad con beneficiarios y otros módulos que aún usan cédula.
    $cedula_int = (int) $cedula_ignorada;
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM persona WHERE correo = ? AND cedula != ?");
    $stmt->bind_param("si", $correo, $cedula_int);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM persona WHERE correo = ?");
    $stmt->bind_param("s", $correo);
}

$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$existe = $row ? ((int) $row['count'] > 0) : false;
$stmt->close();

echo json_encode(['existe' => $existe]);
?>
