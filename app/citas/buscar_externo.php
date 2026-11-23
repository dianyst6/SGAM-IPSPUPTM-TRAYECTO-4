<?php
require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
$cedula = $_GET['cedula'] ?? '';

$sql = "SELECT p.nombre, p.apellido
        FROM persona p
        LEFT JOIN afiliados a     ON a.id_persona = p.id_persona
        LEFT JOIN beneficiarios b ON b.id_persona = p.id_persona
        WHERE p.cedula = ?
          AND a.id_persona IS NULL
          AND b.id_persona IS NULL
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cedula);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['exists' => true, 'nombre' => $row['nombre'], 'apellido' => $row['apellido']]);
} else {
    echo json_encode(['exists' => false]);
}