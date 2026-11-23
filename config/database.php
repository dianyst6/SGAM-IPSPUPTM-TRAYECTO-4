<?php

// ConfiguraciÃƒÂ³n de la conexiÃƒÂ³n a la base de datos
$host = "localhost";
$db_name = "ipsp";
$username = "root";
$password = "";

// Inicializar conexiÃƒÂ³n con mysqli (idempotente: evita duplicarla si ya existe)
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = new mysqli($host, $username, $password, $db_name);

    // Verificar si la conexiÃƒÂ³n fue exitosa
    if ($conn->connect_error) {
        die("Error en la conexiÃƒÂ³n: " . $conn->connect_error);
    }

    // Configurar la conexiÃƒÂ³n para que use UTF-8
    $conn->set_charset("utf8");
}
?>
