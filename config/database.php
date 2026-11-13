<?php

// ConfiguraciÃ³n de la conexiÃ³n a la base de datos
$host = "localhost";
$db_name = "ipsp_fasec1_test";
$username = "root";
$password = "";

// Inicializar conexiÃ³n con mysqli (idempotente: evita duplicarla si ya existe)
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = new mysqli($host, $username, $password, $db_name);

    // Verificar si la conexiÃ³n fue exitosa
    if ($conn->connect_error) {
        die("Error en la conexiÃ³n: " . $conn->connect_error);
    }

    // Configurar la conexiÃ³n para que use UTF-8
    $conn->set_charset("utf8");
}
?>
