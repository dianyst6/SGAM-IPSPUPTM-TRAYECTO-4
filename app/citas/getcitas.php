<?php
header('Content-Type: application/json');
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

if (isset($_POST['id_cita'])) {
    $id_cita = intval($_POST['id_cita']);
    $sql = "
        SELECT
            c.id_cita,
            c.fecha_cita,
            c.descripcion,
            c.id_especialidad,
            CASE
                WHEN a.ID IS NULL AND b.id_beneficiario IS NULL THEN 'externo'
                ELSE 'interno'
            END AS tipo_origen,
            -- Datos del paciente externo (Comunidad UPTM)
            CASE WHEN a.ID IS NULL AND b.id_beneficiario IS NULL THEN p.nombre   ELSE NULL END AS nombre_ext,
            CASE WHEN a.ID IS NULL AND b.id_beneficiario IS NULL THEN p.apellido ELSE NULL END AS apellido_ext,
            CASE WHEN a.ID IS NULL AND b.id_beneficiario IS NULL THEN p.cedula   ELSE NULL END AS cedula_ext,
            -- Paciente unificado (solo internos; los externos no tienen rol)
            CASE WHEN a.ID IS NOT NULL OR b.id_beneficiario IS NOT NULL THEN p.id_persona ELSE NULL END AS id_paciente,
            CASE WHEN a.ID IS NOT NULL OR b.id_beneficiario IS NOT NULL THEN p.nombre   ELSE NULL END AS nombre_int,
            CASE WHEN a.ID IS NOT NULL OR b.id_beneficiario IS NOT NULL THEN p.apellido ELSE NULL END AS apellido_int,
            CASE WHEN a.ID IS NOT NULL OR b.id_beneficiario IS NOT NULL THEN p.cedula   ELSE NULL END AS cedula_int,
            CASE
                WHEN a.ID IS NOT NULL THEN 'Afiliado'
                WHEN b.id_beneficiario IS NOT NULL THEN 'Beneficiario'
                ELSE NULL
            END AS tipo_int
        FROM citas c
        INNER JOIN persona p ON p.id_persona = c.id_persona
        LEFT JOIN afiliados a     ON a.id_persona = p.id_persona
        LEFT JOIN beneficiarios b ON b.id_persona = p.id_persona
        LEFT JOIN especialidades e ON c.id_especialidad = e.id_especialidad
        WHERE c.id_cita = ?";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $id_cita);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Formateo para datetime-local (importante para que el input lo reconozca)
            $row['fecha_cita'] = date('Y-m-d\TH:i', strtotime($row['fecha_cita']));
            echo json_encode([$row]);
        } else {
            echo json_encode(["error" => "No se encontró la cita con ID: " . $id_cita]);
        }
        $stmt->close();
    } else {
        echo json_encode(["error" => "Error al preparar la consulta"]);
    }
} else {
    echo json_encode(["error" => "No se recibió ID"]);
}
$conn->close();
?>