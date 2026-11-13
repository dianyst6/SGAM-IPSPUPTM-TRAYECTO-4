<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);

require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/persona_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

header('Content-Type: application/json'); // Indica que la respuesta será en formato JSON

// Verifica si todos los datos necesarios fueron proporcionados
if (isset($_POST['cedula'], $_POST['nombre'], $_POST['apellido'],
          $_POST['fechanacimiento'], $_POST['genero'],
          $_POST['telefono'], $_POST['correo'], $_POST['ocupacion'])) {

    // Limpia los datos para prevenir inyección SQL
    $cedula = trim((string) $_POST['cedula']);
    $cedula_original = isset($_POST['cedula_original']) ? trim((string) $_POST['cedula_original']) : '';
    $id_persona_post = isset($_POST['id_persona']) ? (int) $_POST['id_persona'] : 0;
    $nombre = htmlspecialchars($_POST['nombre']);
    $apellido = htmlspecialchars($_POST['apellido']);
    $fechanacimiento = trim((string) $_POST['fechanacimiento']);
    $genero = htmlspecialchars($_POST['genero']);
    $telefono = htmlspecialchars($_POST['telefono']);
    $correo = htmlspecialchars($_POST['correo']);
    $ocupacion = htmlspecialchars($_POST['ocupacion']);

    // La cédula es visible para el usuario, pero debe tener formato válido.
    if (!preg_match('/^[0-9]{1,8}$/', $cedula)) {
        echo json_encode(['success' => false, 'message' => 'Error: la cédula debe contener solo números (máximo 8).']);
        exit();
    }

    // Regla de negocio: un afiliado debe tener al menos 20 años cumplidos.
    $fecha_minima = date('Y-m-d', strtotime('-20 years'));
    $fecha_obj = DateTime::createFromFormat('Y-m-d', $fechanacimiento);
    if (!$fecha_obj || $fecha_obj->format('Y-m-d') !== $fechanacimiento) {
        echo json_encode(['success' => false, 'message' => 'Error: la fecha de nacimiento no es válida.']);
        exit();
    }
    if ($fechanacimiento > $fecha_minima) {
        echo json_encode(['success' => false, 'message' => 'Error: el afiliado debe tener al menos 20 años cumplidos.']);
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
                ? 'No se pudo identificar al afiliado: faltan datos del formulario. Recargue la página con Ctrl+F5 e intente de nuevo.'
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

        if ($stmt_persona) {
            $stmt_persona->bind_param("ssssssssi", $cedula, $nombre, $apellido, $fechanacimiento, $genero, $telefono, $correo, $ocupacion, $id_persona);

            if ($stmt_persona->execute()) {
                // Ahora actualiza la tabla afiliados (si fuera necesario)
                $sql_afiliados = "UPDATE afiliados
                                    SET updated_at = NOW()
                                    WHERE id_persona = ?";

                $stmt_afiliados = $conn->prepare($sql_afiliados);

                if ($stmt_afiliados) {
                    $stmt_afiliados->bind_param("i", $id_persona);

                    if ($stmt_afiliados->execute()) {
                        // Registrar en bitácora
                        $usuario = $_SESSION['username'] ?? 'Sistema';
                        $accion = "Edición de Afiliado";
                        $descripcion = "Se han actualizado los datos del afiliado con cédula: $cedula, Nombre: $nombre, Apellido: $apellido";
                        if ((int) $cedula !== (int) $cedula_actual) {
                            $descripcion = "Se cambió la cédula del afiliado de $cedula_actual a $cedula. Nombre: $nombre, Apellido: $apellido";
                        }
                        registrarEnBitacora($conn, $usuario, $accion, $descripcion);

                        $conn->commit();
                        echo json_encode(['success' => true, 'message' => 'Afiliado actualizado correctamente.']);
                    } else {
                        $conn->rollback();
                        echo json_encode(['success' => false, 'message' => 'Error al actualizar afiliado: ' . $stmt_afiliados->error]);
                    }

                    $stmt_afiliados->close();
                } else {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'Error al preparar consulta para afiliados: ' . $conn->error]);
                }
            } else {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Error al actualizar en personas: ' . $stmt_persona->error]);
            }

            $stmt_persona->close();
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error al preparar consulta para personas: ' . $conn->error]);
        }

    } catch (Exception $e) {
        if ($transaccion_iniciada) {
            $conn->rollback();
        }
        echo json_encode(['success' => false, 'message' => 'Ocurrió un error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error: Todos los campos obligatorios no fueron proporcionados.']);
}

$conn->close(); // Cierra la conexión
?>

