<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/persona_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/plan_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/plan_vista.php';

if (isset($_GET['cedula'])) {
    $cedula = trim((string) $_GET['cedula']);

    // La cédula es la entrada; se resuelve el id_persona interno.
    $id_persona = buscarIdPersonaPorCedula($conn, $cedula);

    if ($id_persona === null) {
        echo '<div class="alert alert-warning text-center">No se encontró a la persona indicada.</div>';
        $conn->close();
        exit();
    }

    // El estado del plan se presenta desde el contrato del núcleo familiar
    // (titular), con la cobertura y el consumo reales de consumo_plan.
    echo planVistaConsumo($conn, (int) $id_persona, true);
}
$conn->close();
