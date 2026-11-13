
<?php
// 1.3 Guard central: solo web (las migraciones CLI siguen disponibles)
if (PHP_SAPI !== 'cli') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
    auth_guard([1]);
}
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
$sql = "ALTER TABLE consumo_plan ADD COLUMN id_categoria_externa INT(11) DEFAULT NULL AFTER nombre_estudio_externo";
$res = mysqli_query($conn, $sql);
echo "Final migration: " . ($res ? "Éxito" : "Falló: " . mysqli_error($conn)) . "\n";
?>
