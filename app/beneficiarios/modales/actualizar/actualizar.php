<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);

require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/persona_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

header('Content-Type: application/json');

// Verifica si todos los datos necesarios fueron proporcionados
if (isset($_POST['cedula_afil'], $_POST['cedula'], $_POST['nombre'], $_POST['apellido'],
    $_POST['fechanacimiento'], $_POST['parentesco'], $_POST['genero'],
    $_POST['telefono'], $_POST['correo'], $_POST['ocupacion'])) {

    // Limpia los datos para prevenir inyección SQL
    $cedula_afil = (int) $_POST['cedula_afil'];
    $cedula = trim((string) $_POST['cedula']);
    $cedula_original = isset($_POST['cedula_original']) ? trim((string) $_POST['cedula_original']) : '';
    $id_persona_post = isset($_POST['id_persona']) ? (int) $_POST['id_persona'] : 0;
    $nombre = htmlspecialchars($_POST['nombre']);
    $apellido = htmlspecialchars($_POST['apellido']);
    $fechanacimiento = trim((string) $_POST['fechanacimiento']);
    $parentesco = htmlspecialchars($_POST['parentesco']);
    $genero = htmlspecialchars($_POST['genero']);
    $telefono = htmlspecialchars($_POST['telefono']);
    $correo = htmlspecialchars($_POST['correo']);
    $ocupacion = htmlspecialchars($_POST['ocupacion']);

    // La cédula es visible para el usuario, pero debe tener formato válido.
    if (!preg_match('/^[0-9]{1,8}$/', $cedula)) {
        echo json_encode(['success' => false, 'message' => 'Error: la cédula debe contener solo números (máximo 8).']);
        exit();
    }

    // Los beneficiarios pueden ser menores de edad; solo se valida la fecha.
    $hoy = date('Y-m-d');
    $fecha_obj = DateTime::createFromFormat('Y-m-d', $fechanacimiento);
    if (!$fecha_obj || $fecha_obj->format('Y-m-d') !== $fechanacimiento || $fechanacimiento > $hoy) {
        echo json_encode(['success' => false, 'message' => 'Error: la fecha de nacimiento no es válida.']);
        exit();
    }

    $transaccion_iniciada = false;

    try {
        // Identificar la persona por su id interno; la cédula original sirve de respaldo.
        $id_persona = $id_persona_post > 0
            ? $id_persona_post
            : buscarIdPersonaPorCedula($conn, $cedula_original !== '' ? $cedula_original : $cedula);

        if ($id_persona === null) {
            $mensaje = ($id_persona_post <= 0 && $cedula_original === '')
                ? 'No se pudo identificar al beneficiario. Verifique la cédula ingresada o recargue la página con Ctrl+F5 e intente de nuevo.'
                : 'Error: la persona no existe.';
            echo json_encode(['success' => false, 'message' => $mensaje]);
            exit();
        }

        // Confirmar que la persona existe y obtener su cédula actual.
        $stmt_persona_actual = $conn->prepare("SELECT cedula FROM persona WHERE id_persona = ?");
        $stmt_persona_actual->bind_param("i", $id_persona);
        $stmt_persona_actual->execute();
        $res_persona_actual = $stmt_persona_actual->get_result();
        $persona_actual = $res_persona_actual ? $res_persona_actual->fetch_assoc() : null;
        $stmt_persona_actual->close();

        if (!$persona_actual) {
            echo json_encode(['success' => false, 'message' => 'Error: la persona no existe.']);
            exit();
        }
        $cedula_actual = (string) $persona_actual['cedula'];

        // Confirmar que la persona realmente es un beneficiario y obtener su afiliado actual.
        $stmt_check_benef = $conn->prepare("SELECT id_afiliado AS cedula_afil FROM beneficiarios WHERE id_persona = ?");
        $stmt_check_benef->bind_param("i", $id_persona);
        $stmt_check_benef->execute();
        $res_check_benef = $stmt_check_benef->get_result();
        $beneficiario_actual = $res_check_benef ? $res_check_benef->fetch_assoc() : null;
        $stmt_check_benef->close();

        if (!$beneficiario_actual) {
            echo json_encode(['success' => false, 'message' => 'Error: el beneficiario no existe.']);
            exit();
        }
        $afiliado_actual = (int) $beneficiario_actual['cedula_afil'];

        // Verificar que el afiliado relacionado exista.
        $stmt_check_afil = $conn->prepare("SELECT 1 FROM afiliados WHERE ID = ?");
        $stmt_check_afil->bind_param("i", $cedula_afil);
        $stmt_check_afil->execute();
        if ($stmt_check_afil->get_result()->num_rows === 0) {
            $stmt_check_afil->close();
            echo json_encode(['success' => false, 'message' => 'Error: el afiliado relacionado no existe.']);
            exit();
        }
        $stmt_check_afil->close();

        // Si la cédula cambió, verificar que no pertenezca a otra persona.
        if ((int) $cedula !== (int) $cedula_actual) {
            $sql_check_cedula = "SELECT id_persona FROM persona WHERE cedula = ? AND id_persona != ?";
            $stmt_check_cedula = $conn->prepare($sql_check_cedula);
            $stmt_check_cedula->bind_param("si", $cedula, $id_persona);
            $stmt_check_cedula->execute();
            $res_check_cedula = $stmt_check_cedula->get_result();

            if ($res_check_cedula->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Error: la cédula ingresada ya está registrada por otra persona.']);
                exit();
            }
            $stmt_check_cedula->close();
        }

        // Verificar si el correo ya existe para OTRA persona (por id interno)
        $sql_check_correo = "SELECT id_persona FROM persona WHERE correo = ? AND id_persona != ?";
        $stmt_check_correo = $conn->prepare($sql_check_correo);
        $stmt_check_correo->bind_param("si", $correo, $id_persona);
        $stmt_check_correo->execute();
        $res_check_correo = $stmt_check_correo->get_result();

        if ($res_check_correo->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Error: El correo electrónico ingresado ya se encuentra registrado por otra persona.']);
            exit();
        }
        $stmt_check_correo->close();

        // Inicia una transacción
        $conn->begin_transaction();
        $transaccion_iniciada = true;

        // Actualizar datos en la tabla persona (incluida la cédula)
        $sql_persona = "UPDATE persona
                            SET cedula = ?, nombre = ?, apellido = ?, fechanacimiento = ?,
                            genero = ?, telefono = ?, correo = ?, ocupacion = ?
                            WHERE id_persona = ?";
        $stmt_persona = $conn->prepare($sql_persona);

        if (!$stmt_persona) {
            throw new Exception("Error al preparar consulta para persona: " . $conn->error);
        }

        $stmt_persona->bind_param("ssssssssi", $cedula, $nombre, $apellido, $fechanacimiento, $genero, $telefono, $correo, $ocupacion, $id_persona);

        if (!$stmt_persona->execute()) {
            throw new Exception("Error al actualizar en persona: " . $stmt_persona->error);
        }
        $stmt_persona->close();

        // Actualizar datos en la tabla beneficiarios (la cédula se actualiza por cascada de la FK)
        $sql_beneficiarios = "UPDATE beneficiarios
                                    SET id_afiliado = ?, parentesco = ?, updated_at = NOW()
                                    WHERE id_persona = ?";
        $stmt_beneficiarios = $conn->prepare($sql_beneficiarios);

        if (!$stmt_beneficiarios) {
            throw new Exception("Error al preparar consulta para beneficiarios: " . $conn->error);
        }

        $stmt_beneficiarios->bind_param("isi", $cedula_afil, $parentesco, $id_persona);

        if (!$stmt_beneficiarios->execute()) {
            throw new Exception("Error al actualizar en beneficiarios: " . $stmt_beneficiarios->error);
        }
        $stmt_beneficiarios->close();

        // Registrar en la bitácora los cambios realizados
        $usuario = $_SESSION['username'] ?? 'Sistema';
        $accion = "Actualización de Beneficiario";
        $descripcion = "Se actualizó al beneficiario con cédula $cedula y nombre $nombre $apellido";
        if ((int) $cedula !== (int) $cedula_actual) {
            $descripcion = "Se cambió la cédula del beneficiario de $cedula_actual a $cedula. Nombre: $nombre $apellido";
        }
        if ($cedula_afil !== $afiliado_actual) {
            $descripcion .= " Se cambió el afiliado relacionado (ID $afiliado_actual → ID $cedula_afil).";
        }
        registrarEnBitacora($conn, $usuario, $accion, $descripcion);

        // Confirma los cambios
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Beneficiario actualizado correctamente']);
        exit();
    }
    catch (Exception $e) {
        if ($transaccion_iniciada) {
            $conn->rollback();
        }
        echo json_encode(['success' => false, 'message' => 'Ocurrió un error: ' . $e->getMessage()]);
        exit();
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Error: Todos los campos obligatorios no fueron proporcionados.']);
    exit();
}

$conn->close();
?>
