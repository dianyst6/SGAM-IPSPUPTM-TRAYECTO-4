<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1]);
 
// get_usuario.php 
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php'; 

header('Content-Type: application/json'); 

// 1. Cambiamos de $_GET a $_POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) { 
    echo json_encode(['error' => 'ID no especificado o método incorrecto']); 
    exit; 
} 

$id = intval($_POST['id']); 

// 2. Ajusta los nombres de las columnas según tu base de datos real
// Asegúrate de traer role_id, no solo role_name
$sql = "SELECT id, username, role_id, 
               pregunta_seguridad_id1, respuesta1, 
               pregunta_seguridad_id2, respuesta2 
        FROM usuarios 
        WHERE id = ?"; 

$stmt = $conn->prepare($sql); 
if (!$stmt) { 
    echo json_encode(['error' => 'Error en la preparación de la consulta']); 
    exit; 
} 

$stmt->bind_param('i', $id); 
$stmt->execute(); 
$result = $stmt->get_result(); 

if ($result->num_rows === 0) { 
    echo json_encode(['error' => 'Usuario no encontrado']); 
    exit; 
} 

$usuario = $result->fetch_assoc(); 

// 3. Devolvemos el array. 
// Nota: JavaScript espera estos nombres de clave (p1, p2, r1, r2, etc)
// Asegúrate de que las claves aquí coincidan con lo que tu JS espera.
echo json_encode([
    'id'       => $usuario['id'],
    'username' => $usuario['username'],
    'role_id'  => $usuario['role_id'],
    'p1'       => $usuario['pregunta_seguridad_id1'],
    'r1'       => $usuario['respuesta1'],
    'p2'       => $usuario['pregunta_seguridad_id2'],
    'r2'       => $usuario['respuesta2']
]);
?>