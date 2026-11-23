<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1]);
// Incluir el archivo de conexión
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

// Definir el nombre temporal del archivo de respaldo
$nombreRespaldo = $db_name . "_" . date("Y-m-d_H-i-s") . ".sql";

// Comando para realizar el respaldo con mysqldump
$comando = "C:/xampp/mysql/bin/mysqldump --user=$username --password=$password --host=$host $db_name";

// Ejecutar el comando y capturar el contenido
$output = [];
exec($comando . " 2>&1", $output, $returnVar);

if ($returnVar === 0) {
    // Configuración del encabezado para la descarga directa
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $nombreRespaldo . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    // Imprimir el contenido generado por mysqldump al navegador
    foreach ($output as $linea) {
        echo $linea . PHP_EOL;
    }
    exit;
} else {
    // Mostrar mensaje de error
    echo "Hubo un error al generar el respaldo.<br>";
    echo "Salida del comando:<br>";
    print_r($output);
}
?>
