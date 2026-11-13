<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
header('Content-Type: application/json');
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre_categoria']);
    $desc = mysqli_real_escape_string($conn, $_POST['descripcion']);

    if (!empty($nombre)) {
        $monto = isset($_POST['monto_maximo_cobertura']) ? floatval($_POST['monto_maximo_cobertura']) : 0;
        $sql = "INSERT INTO categorias_examenes (nombre_categoria, descripcion, monto_maximo_cobertura) VALUES ('$nombre', '$desc', '$monto')";
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Categoría registrada con éxito.',
                'id_categoria' => mysqli_insert_id($conn),
                'nombre_categoria' => $nombre
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'El nombre de la categoría es obligatorio.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>
