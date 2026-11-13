<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/persona_helper.php';

header('Content-Type: application/json');

if (isset($_POST['cedula'])) {
    $cedula = mysqli_real_escape_string($conn, $_POST['cedula']);
    $id_persona = buscarIdPersonaPorCedula($conn, $cedula);
    $query_beneficiario = "SELECT COUNT(*) as count FROM beneficiarios WHERE cedula = '$cedula'";
    $result_beneficiario = mysqli_query($conn, $query_beneficiario);
    $count_beneficiario = 0;
    if ($result_beneficiario) {
        $row = mysqli_fetch_assoc($result_beneficiario);
        $count_beneficiario = $row['count'];
    }

    $query_afiliado = "SELECT COUNT(*) as count FROM afiliados WHERE cedula = '$cedula'";
    $result_afiliado = mysqli_query($conn, $query_afiliado);
    $count_afiliado = 0;
    if ($result_afiliado) {
        $row = mysqli_fetch_assoc($result_afiliado);
        $count_afiliado = $row['count'];
    }

    echo json_encode([
        'id_persona' => $id_persona,
        'existe_beneficiario' => $count_beneficiario > 0,
        'existe_afiliado' => $count_afiliado > 0
    ]);
}
else {
    echo json_encode(['error' => 'Cédula no proporcionada']);
}
?>
