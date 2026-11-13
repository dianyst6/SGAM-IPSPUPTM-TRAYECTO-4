<?php
session_start();
require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

header('Content-Type: application/json');

$id    = intval($_GET['id'] ?? 0);
$tabla = $_GET['tabla'] ?? 'general'; // 'general' o 'ginecologia'

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID no válido.']);
    exit();
}

try {
    if ($tabla === 'ginecologia') {
        $sql = "
            SELECT g.*,
                COALESCE(
                    CONCAT(p_a.nombre, ' ', p_a.apellido),
                    CONCAT(p_b.nombre, ' ', p_b.apellido),
                    CONCAT(ext.nombre, ' ', ext.apellido),
                    'Desconocido'
                ) AS nombre_paciente
            FROM historias_medicas_gine g
            LEFT JOIN afiliados a ON g.ci_paciente = a.cedula
            LEFT JOIN persona p_a ON a.cedula = p_a.cedula
            LEFT JOIN beneficiarios b ON g.ci_paciente = b.cedula
            LEFT JOIN persona p_b ON b.cedula = p_b.cedula
            LEFT JOIN persona ext ON g.ci_paciente = ext.cedula
            WHERE g.id_historia_g = ?
        ";
    } else {
        $sql = "
            SELECT h.*,
                COALESCE(
                    CONCAT(p_a.nombre, ' ', p_a.apellido),
                    CONCAT(p_b.nombre, ' ', p_b.apellido),
                    CONCAT(ext.nombre, ' ', ext.apellido),
                    'Desconocido'
                ) AS nombre_paciente,
                COALESCE(esp.nombre_especialidad, 'General') AS nombre_especialidad
            FROM historias_medicas h
            LEFT JOIN afiliados a ON h.ci_paciente = a.cedula
            LEFT JOIN persona p_a ON a.cedula = p_a.cedula
            LEFT JOIN beneficiarios b ON h.ci_paciente = b.cedula
            LEFT JOIN persona p_b ON b.cedula = p_b.cedula
            LEFT JOIN persona ext ON h.ci_paciente = ext.cedula
            LEFT JOIN medicos m ON h.ci_medico = m.ci_medico
            LEFT JOIN especialidades esp ON m.especialidad = esp.id_especialidad
            WHERE h.id_historia = ?
        ";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Error: " . $conn->error);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) throw new Exception("Historia no encontrada.");

    $row['tabla'] = $tabla;
    echo json_encode(['success' => true, 'data' => $row]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>
