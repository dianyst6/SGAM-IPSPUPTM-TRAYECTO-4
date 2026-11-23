<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

$sql = "DELETE FROM bitacora";

if ($conn->query($sql) === TRUE) {
    echo "Todos los registros han sido eliminados exitosamente.";
} else {
    echo "Error al eliminar registros: " . $conn->error;
}

$conn->close();
?>