
<?php
// 1.3 Guard central: solo web (las migraciones CLI siguen disponibles)
if (PHP_SAPI !== 'cli') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
    auth_guard([1]);
}
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

// Añadir columna nombre_estudio_externo
$sql1 = "ALTER TABLE consumo_plan 
          ADD COLUMN nombre_estudio_externo VARCHAR(255) DEFAULT NULL AFTER ID_examen_plan,
          ADD COLUMN id_categoria_externa INT(11) DEFAULT NULL AFTER nombre_estudio_externo";
$res1 = mysqli_query($conn, $sql1);
echo "Migración 1 (Añadir columna): " . ($res1 ? "Éxito" : "Falló o ya existe: " . mysqli_error($conn)) . "\n";

// Modificar ID_examen_plan para permitir nulos
$sql2 = "ALTER TABLE consumo_plan MODIFY COLUMN ID_examen_plan INT(11) DEFAULT NULL";
$res2 = mysqli_query($conn, $sql2);
echo "Migración 2 (Permitir Nulo en ID_examen_plan): " . ($res2 ? "Éxito" : "Falló: " . mysqli_error($conn)) . "\n";
?>
