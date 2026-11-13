<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/persona_helper.php';

if (isset($_POST['cedula'])) {
    $cedula = htmlspecialchars($_POST['cedula']); // Cédula ingresada por el usuario

    // La cédula es la entrada; se resuelve el id_persona interno.
    $id_persona = buscarIdPersonaPorCedula($conn, $cedula);

    if ($id_persona === null) {
        echo json_encode(["error" => "Afiliado no encontrado"]);
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
                a.created_at, 
                a.updated_at 
            FROM 
                afiliados a
            JOIN 
                persona p ON a.id_persona = p.id_persona
            WHERE 
                a.id_persona = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $id_persona);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $afiliados = $result->fetch_assoc();
            // Devuelve todos los datos en formato JSON, incluido id_persona
            echo json_encode($afiliados);
        } else {
            echo json_encode(["error" => "Afiliado no encontrado"]);
        }

        $stmt->close();
    } else {
        echo json_encode(["error" => "Error al preparar la consulta"]);
    }
} else {
    echo json_encode(["error" => "Cédula no proporcionada"]);
}

$conn->close();
?>
