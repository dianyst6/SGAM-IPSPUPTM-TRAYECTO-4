<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_examen       = isset($_POST['id_examen']) ? intval($_POST['id_examen']) : 0;
    $nombre_examen   = isset($_POST['nombre_examen']) ? mysqli_real_escape_string($conn, $_POST['nombre_examen']) : '';
    $id_especialidad = isset($_POST['id_especialidad']) ? intval($_POST['id_especialidad']) : 0;
    $id_categoria    = isset($_POST['id_categoria']) ? intval($_POST['id_categoria']) : 0;
    $precio          = isset($_POST['precio']) ? floatval($_POST['precio']) : 0.00;

    if ($id_examen === 0 || empty($nombre_examen) || $id_especialidad === 0) {
        echo json_encode(['success' => false, 'message' => "Datos de examen incompletos o inválidos."]);
        exit;
    }

    $sql = "UPDATE examenes 
            SET nombre_examen = ?, 
                ID_especialidad_examenes = ?, 
                id_categoria = ?, 
                precio = ? 
            WHERE ID_examen = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siidi", $nombre_examen, $id_especialidad, $id_categoria, $precio, $id_examen);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => "¡Examen actualizado correctamente!"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => "Error al actualizar: " . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Método no permitido"]);
}
?>
