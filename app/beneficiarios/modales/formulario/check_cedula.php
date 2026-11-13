<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

header('Content-Type: application/json');

if (isset($_POST['cedula'])) {
    $cedula = trim((string) $_POST['cedula']);

    $count_beneficiario = 0;
    $stmt_beneficiario = $conn->prepare("SELECT COUNT(*) AS count FROM beneficiarios WHERE cedula = ?");
    if ($stmt_beneficiario) {
        $stmt_beneficiario->bind_param("s", $cedula);
        $stmt_beneficiario->execute();
        $result_beneficiario = $stmt_beneficiario->get_result();
        if ($result_beneficiario && ($row = $result_beneficiario->fetch_assoc())) {
            $count_beneficiario = (int) $row['count'];
        }
        $stmt_beneficiario->close();
    }

    $count_afiliado = 0;
    $stmt_afiliado = $conn->prepare("SELECT COUNT(*) AS count FROM afiliados WHERE cedula = ?");
    if ($stmt_afiliado) {
        $stmt_afiliado->bind_param("s", $cedula);
        $stmt_afiliado->execute();
        $result_afiliado = $stmt_afiliado->get_result();
        if ($result_afiliado && ($row = $result_afiliado->fetch_assoc())) {
            $count_afiliado = (int) $row['count'];
        }
        $stmt_afiliado->close();
    }

    echo json_encode([
        'existe_beneficiario' => $count_beneficiario > 0,
        'existe_afiliado' => $count_afiliado > 0
    ]);
}
else {
    echo json_encode(['error' => 'Cédula no proporcionada']);
}
?>
