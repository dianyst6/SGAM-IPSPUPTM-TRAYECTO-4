<?php
session_start();
require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

header('Content-Type: application/json');

if (!isset($_POST['id_historia']) || !isset($_POST['tipo_tabla'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit();
}

$id_historia = intval($_POST['id_historia']);
$tipo_tabla  = $_POST['tipo_tabla']; // 'general' o 'ginecologia'

// Determinar tabla e ID a usar
if ($tipo_tabla === 'ginecologia') {
    $tabla   = 'historias_medicas_gine';
    $columna = 'id_historia_g';
} else {
    $tabla   = 'historias_medicas';
    $columna = 'id_historia';
}

try {
    // Verificar que el registro exista antes de eliminarlo
    $sqlCheck = "SELECT $columna, ci_paciente FROM $tabla WHERE $columna = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    if (!$stmtCheck) {
        throw new Exception("Error al preparar consulta: " . $conn->error);
    }
    $stmtCheck->bind_param("i", $id_historia);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();

    if ($resCheck->num_rows === 0) {
        throw new Exception("La historia médica no existe o ya fue eliminada.");
    }
    $rowInfo = $resCheck->fetch_assoc();
    $ci_pac  = $rowInfo['ci_paciente'];
    $stmtCheck->close();

    // Eliminar el registro
    $sqlDel  = "DELETE FROM $tabla WHERE $columna = ?";
    $stmtDel = $conn->prepare($sqlDel);
    if (!$stmtDel) {
        throw new Exception("Error al preparar eliminación: " . $conn->error);
    }
    $stmtDel->bind_param("i", $id_historia);
    if (!$stmtDel->execute()) {
        throw new Exception("Error al eliminar: " . $stmtDel->error);
    }
    $stmtDel->close();

    // Registrar en la bitácora
    $usuario     = $_SESSION['username'] ?? 'Sistema';
    $especialidad = ($tipo_tabla === 'ginecologia') ? 'Ginecología' : 'General';
    registrarenBitacora(
        $conn,
        $usuario,
        "Eliminación de Historia Médica",
        "Historia $especialidad ID: $id_historia — Paciente CI: $ci_pac"
    );

    echo json_encode(['success' => true, 'message' => 'Historia médica eliminada correctamente.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
