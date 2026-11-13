<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/persona_helper.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cedula = $_POST['cedula_afiliado'];
    $id_plan = intval($_POST['id_plan']);
    $monto = floatval($_POST['monto_total']);
    $estado = mysqli_real_escape_string($conn, $_POST['estado']);

    // La cédula es la entrada; el contrato se guarda por id_persona.
    $id_persona = buscarIdPersonaPorCedula($conn, $cedula);
    if ($id_persona === null) {
        echo "Error: el afiliado no existe.";
        exit;
    }

    // Insertar en contrato_plan según tu estructura
    $query = "INSERT INTO contrato_plan (id_persona, ID_planes_contrato, monto_total, estado_contrato) 
              VALUES ('$id_persona', '$id_plan', '$monto', '$estado')";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Plan contratado exitosamente'); window.location.href='gestion_afiliados.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
