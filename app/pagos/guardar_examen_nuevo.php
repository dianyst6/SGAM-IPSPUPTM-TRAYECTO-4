<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
header('Content-Type: application/json');
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre_examen']);
    $id_esp = intval($_POST['id_especialidad']);
    $id_cat = intval($_POST['id_categoria']);
    $precio = floatval($_POST['precio']);

    if (!empty($nombre) && !empty($id_esp) && !empty($id_cat)) {
        $sql = "INSERT INTO examenes (nombre_examen, ID_especialidad_examenes, id_categoria, precio) 
                VALUES ('$nombre', '$id_esp', '$id_cat', '$precio')";
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Examen guardado y añadido a la lista con categoría.',
                'id_examen' => mysqli_insert_id($conn),
                'nombre_examen' => $nombre
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>
