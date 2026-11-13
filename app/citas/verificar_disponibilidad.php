<?php
require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

header('Content-Type: application/json');

if (isset($_POST['fecha_cita']) && isset($_POST['examenes'])) {
    $fecha = $_POST['fecha_cita'];
    $examenes = $_POST['examenes']; // Se espera un array

    if (!is_array($examenes) || empty($examenes)) {
        echo json_encode(['success' => true, 'count' => 0, 'conflicts' => []]);
        exit;
    }

    // Convertir array a lista para SQL
    $ids = implode(',', array_map('intval', $examenes));

    // Consulta para buscar citas activas que coincidan en fecha y tengan los mismos exámenes
    $sql = "
        SELECT e.nombre_examen 
        FROM citas c
        INNER JOIN citas_examenes ce ON c.id_cita = ce.id_cita
        INNER JOIN examenes e ON ce.id_examen = e.ID_examen
        WHERE c.fecha_cita = ? 
          AND c.estado = 'activa' 
          AND ce.id_examen IN ($ids)
    ";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $fecha);
        $stmt->execute();
        $result = $stmt->get_result();

        $conflicts = [];
        while ($row = $result->fetch_assoc()) {
            $conflicts[] = $row['nombre_examen'];
        }

        echo json_encode([
            'success'   => true, 
            'count'     => count($conflicts), 
            'conflicts' => array_unique($conflicts)
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros']);
}
?>
