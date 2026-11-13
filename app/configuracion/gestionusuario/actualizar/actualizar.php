<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1]);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

// Validar que los campos básicos existan
// Validar que los campos básicos existan usando los 'name' correctos del HTML
if (isset($_POST['id'], $_POST['username'], $_POST['role_id'], 
          $_POST['pregunta1_id'], $_POST['respuesta1'], $_POST['pregunta2_id'], $_POST['respuesta2'])) {

    $id = $_POST['id'];
    $username = htmlspecialchars($_POST['username']);
    $role_id = intval($_POST['role_id']);
    $password = $_POST['password'] ?? ''; 
    
    $p1 = intval($_POST['pregunta1_id']);
    $r1 = htmlspecialchars($_POST['respuesta1']);
    $p2 = intval($_POST['pregunta2_id']);
    $r2 = htmlspecialchars($_POST['respuesta2']);

    // ... (El resto del try { ... } se queda exactamente igual)

    try {
        $conn->begin_transaction();

        // 1. Actualizar usuario
        if (!empty($password)) {
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET username = ?, role_id = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $role_id, $hashed_pass, $id);
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET username = ?, role_id = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $role_id, $id);
        }
        $stmt->execute();

        // 2. Borrar respuestas antiguas
        $del = $conn->prepare("DELETE FROM respuestas_seguridad WHERE usuario_id = ?");
        $del->bind_param("i", $id);
        $del->execute();

        // 3. Insertar nuevas respuestas
        $ins = $conn->prepare("INSERT INTO respuestas_seguridad (usuario_id, pregunta_seguridad_id, respuesta) VALUES (?, ?, ?)");
        
        $ins->bind_param("iis", $id, $p1, $r1);
        $ins->execute();
        
        $ins->bind_param("iis", $id, $p2, $r2);
        $ins->execute();

        // 4. Bitácora
        $usuario = $_SESSION['username'] ?? 'Sistema';
        $accion = "Edición de Usuario";
        $descripcion = "Se han actualizado los datos del usuario: $username (ID: $id)";
        registrarEnBitacora($conn, $usuario, $accion, $descripcion);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente.']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error: Faltan datos obligatorios.']);
}

$conn->close();
?>