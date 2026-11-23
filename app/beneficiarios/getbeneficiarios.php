<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/persona_helper.php';

if (isset($_POST['cedula'])) {
    $cedula = trim((string) $_POST['cedula']);

    // La cédula es la entrada; se resuelve el id_persona interno.
    $id_persona = buscarIdPersonaPorCedula($conn, $cedula);

    if ($id_persona === null) {
        echo json_encode(["error" => "Beneficiario no encontrado"], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit();
    }

    $sql = "SELECT 
                p.id_persona,
                p.cedula, 
                p.nombre, 
                p.apellido, 
                p.fechanacimiento, 
                p.genero, 
                p.telefono, 
                p.correo, 
                p.ocupacion, 
                b.created_at, 
                b.updated_at, 
                b.parentesco,
                a.id AS id_afiliado, -- Incluimos el ID del afiliado
                CONCAT(pa.nombre, ' ', pa.apellido) AS afiliado_nombre_completo
            FROM 
                beneficiarios b
            JOIN 
                persona p ON b.id_persona = p.id_persona
            JOIN 
                afiliados a ON b.id_afiliado = a.id
            JOIN 
                persona pa ON a.id_persona = pa.id_persona
            WHERE 
                b.id_persona = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $id_persona);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $beneficiarios = $result->fetch_assoc();
            echo json_encode($beneficiarios, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["error" => "Beneficiario no encontrado"], JSON_UNESCAPED_UNICODE);
        }

        $stmt->close();
    } else {
        echo json_encode(["error" => "Error al preparar la consulta"], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode(["error" => "Cédula no proporcionada"], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>