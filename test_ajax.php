<?php
// 1.3 Guard central: solo web (las migraciones CLI siguen disponibles)
if (PHP_SAPI !== 'cli') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
    auth_guard([1]);
}
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

// Obtener una cédula válida para probar
$qa = mysqli_query($conn, "SELECT p.cedula FROM persona p JOIN afiliados a ON p.id_persona = a.id_persona JOIN contrato_plan cp ON cp.id_persona = a.id_persona WHERE cp.estado_contrato = 'Activo' LIMIT 1");
$cedula_test = mysqli_fetch_assoc($qa)['cedula'] ?? '';

echo "CEDULA PARA TEST: $cedula_test \n";

// Simular el GET
$_GET['cedula'] = $cedula_test;
ob_start();
include 'C:/xampp/htdocs/IPSPUPTM/app/pagos/get_afiliado_plan_limits.php';
$output = ob_get_clean();

echo "SALIDA DEL AJAX: \n";
echo $output;
?>
