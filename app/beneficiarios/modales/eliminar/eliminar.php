<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);

require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

header('Content-Type: application/json'); // Asegura que la respuesta sea JSON

if (isset($_POST['cedula'])) {
    $cedula = trim((string) $_POST['cedula']); // Limpia el dato para mayor seguridad

    try {
        // Inicia una transacción para asegurar la integridad de los datos
        $conn->begin_transaction();

        // 0. Resolver la persona por cédula (la cédula vive en persona)
        $sql_persona_id = "SELECT id_persona FROM persona WHERE cedula = ?";
        $stmt_persona_id = $conn->prepare($sql_persona_id);
        if (!$stmt_persona_id) {
            throw new Exception("Error al preparar la consulta de la persona: " . $conn->error);
        }
        $stmt_persona_id->bind_param("s", $cedula);
        $stmt_persona_id->execute();
        $res_persona_id = $stmt_persona_id->get_result();
        if ($res_persona_id->num_rows === 0) {
            throw new Exception("El beneficiario no existe o ya fue eliminado.");
        }
        $id_persona = (int) $res_persona_id->fetch_assoc()['id_persona'];
        $stmt_persona_id->close();

        // 1. Eliminar citas relacionadas al beneficiario (la cita guarda id_persona)
        $sql_eliminar_citas = "DELETE FROM citas WHERE id_persona = ?";

        $stmt_eliminar_citas = $conn->prepare($sql_eliminar_citas);

        if ($stmt_eliminar_citas) {
            $stmt_eliminar_citas->bind_param("i", $id_persona);  //Usar la persona del beneficiario para eliminar las citas relacionadas
            if (!$stmt_eliminar_citas->execute()) {
                throw new Exception("Error al eliminar las citas relacionadas: " . $stmt_eliminar_citas->error);
            }
            $stmt_eliminar_citas->close();
        } else {
            throw new Exception("Error al preparar la consulta para eliminar citas: " . $conn->error);
        }

        // 3. Consulta para obtener información del beneficiario antes de eliminarlo (para la bitácora)
        $sql_info_beneficiario = "SELECT p.nombre, p.apellido
                                FROM beneficiarios b
                                JOIN persona p ON b.id_persona = p.id_persona
                                WHERE b.id_persona = ?";
        $stmt_info = $conn->prepare($sql_info_beneficiario);

        if ($stmt_info) {
            $stmt_info->bind_param("i", $id_persona);
            $stmt_info->execute();
            $result_info = $stmt_info->get_result();

            if ($result_info->num_rows > 0) {
                $info_beneficiario = $result_info->fetch_assoc();
            } else {
                throw new Exception("El beneficiario no existe o ya fue eliminado.");
            }
            $stmt_info->close();
        } else {
            throw new Exception("Error al preparar la consulta para obtener información del beneficiario: " . $conn->error);
        }
       // 4. Eliminar de beneficiarios
        $sql_beneficiarios = "DELETE FROM beneficiarios WHERE id_persona = ?";
        $stmt_beneficiarios = $conn->prepare($sql_beneficiarios);
        if ($stmt_beneficiarios) {
            $stmt_beneficiarios->bind_param("i", $id_persona);
            if (!$stmt_beneficiarios->execute()) {
                throw new Exception("Error al eliminar el beneficiario: " . $stmt_beneficiarios->error);
            }
            $stmt_beneficiarios->close();
        } else {
            throw new Exception("Error al preparar la consulta para eliminar beneficiarios: " . $conn->error);
        }

        // 5. Verificar si quedan relaciones en beneficiarios
        $sql_check_beneficiarios = "SELECT id_persona FROM beneficiarios WHERE id_persona = ?";
        $stmt_check = $conn->prepare($sql_check_beneficiarios);

        if ($stmt_check) {
            $stmt_check->bind_param("i", $id_persona);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows === 0) {
                // 6. Si no hay más relaciones en beneficiarios, elimina de persona
                $sql_persona = "DELETE FROM persona WHERE id_persona = ?";
                $stmt_persona = $conn->prepare($sql_persona);

                if ($stmt_persona) {
                    $stmt_persona->bind_param("i", $id_persona);
                    if (!$stmt_persona->execute()) {
                        throw new Exception("Error al eliminar el registro en persona: " . $stmt_persona->error);
                    }
                    $stmt_persona->close();
                } else {
                    throw new Exception("Error al preparar la consulta para eliminar persona: " . $conn->error);
                }
            }
            $stmt_check->close();
        } else {
            throw new Exception("Error al preparar la consulta para verificar beneficiarios: " . $conn->error);
        }
        // 7. Registrar en la bitácora
        $usuario = $_SESSION['username'] ?? 'Sistema';
        $accion = "Eliminación de Beneficiario y Citas";
        $descripcion = "Se eliminó al beneficiario: " . $info_beneficiario['nombre'] . " " . $info_beneficiario['apellido'] . " (Cédula: $cedula) y todas las citas relacionadas.";
        registrarenBitacora($conn, $usuario, $accion, $descripcion);

        // 8. Confirma la transacción
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Beneficiario y citas relacionadas eliminadas correctamente.']);
        exit();  //Importante terminar la ejecución del script
    } catch (Exception $e) {
        // 9. Cancela la transacción si algo falla
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => "Ocurrió un error: " . $e->getMessage()]);
        exit(); //Importante terminar la ejecución del script
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Cédula no proporcionada.']);
    exit(); //Importante terminar la ejecución del script
}

$conn->close();
?>
