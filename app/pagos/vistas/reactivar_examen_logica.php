<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_examen = isset($_POST['id_examen']) ? intval($_POST['id_examen']) : 0;

    if ($id_examen === 0) {
        echo json_encode(['success' => false, 'message' => "ID de examen no válido."]);
        exit;
    }

    // Reactivar examen
    $sql = "UPDATE examenes SET estado = 'activo' WHERE ID_examen = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_examen);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => "¡Examen reactivado correctamente!"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => "Error al reactivar: " . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Método no permitido"]);
}
?>
