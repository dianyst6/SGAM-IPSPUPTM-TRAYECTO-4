<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

header('Content-Type: application/json');

if (!isset($_POST['id_cita'], $_POST['id_especialidad'], $_POST['fecha_cita'], $_POST['descripcion'])) {
    echo json_encode(['success' => false, 'message' => 'Campos obligatorios faltantes']);
    exit;
}

$id_cita = intval($_POST['id_cita']);
$id_especialidad = intval($_POST['id_especialidad']);
$fecha_cita = $_POST['fecha_cita'];
$descripcion = htmlspecialchars($_POST['descripcion']);

$id_paciente = isset($_POST['id_paciente']) && $_POST['id_paciente'] !== ''
    ? intval($_POST['id_paciente'])
    : 0;
$nombre_ext = isset($_POST['nombre_ext']) ? trim((string) $_POST['nombre_ext']) : '';

$transaccion_iniciada = false;

try {
    $conn->begin_transaction();
    $transaccion_iniciada = true;

    if ($id_paciente > 0) {
        // --- PACIENTE INTERNO (AFILIADO / BENEFICIARIO) ---
        $stmt = $conn->prepare("
            SELECT CASE
                       WHEN a.ID IS NOT NULL THEN 'Afiliado'
                       WHEN b.id_beneficiario IS NOT NULL THEN 'Beneficiario'
                   END AS tipo
            FROM persona p
            LEFT JOIN afiliados a     ON a.id_persona = p.id_persona
            LEFT JOIN beneficiarios b ON b.id_persona = p.id_persona
            WHERE p.id_persona = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $id_paciente);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || empty($row['tipo'])) {
            throw new Exception("El paciente seleccionado no es afiliado ni beneficiario.");
        }

        $id_persona_destino = $id_paciente;
    } elseif ($nombre_ext !== '') {
        // --- COMUNIDAD UPTM (EXTERNO) ---
        $apellido_ext = trim((string) ($_POST['apellido_ext'] ?? ''));
        $cedula_ext   = trim((string) ($_POST['cedula_ext'] ?? ''));

        if ($apellido_ext === '' || !preg_match('/^[0-9]{1,8}$/', $cedula_ext)) {
            throw new Exception("Debe indicar nombre, apellido y una cédula válida del externo.");
        }

        $nombre_ext = htmlspecialchars($nombre_ext);
        $apellido_ext = htmlspecialchars($apellido_ext);

        // Persona actualmente ligada a la cita.
        $stmt = $conn->prepare("
            SELECT c.id_persona, p.cedula,
                   (SELECT COUNT(*) FROM afiliados     WHERE id_persona = c.id_persona) AS es_afiliado,
                   (SELECT COUNT(*) FROM beneficiarios WHERE id_persona = c.id_persona) AS es_beneficiario
            FROM citas c
            INNER JOIN persona p ON p.id_persona = c.id_persona
            WHERE c.id_cita = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $id_cita);
        $stmt->execute();
        $persona_cita = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$persona_cita) {
            throw new Exception("La cita no tiene una persona asociada.");
        }
        if ((int) $persona_cita['es_afiliado'] > 0 || (int) $persona_cita['es_beneficiario'] > 0) {
            throw new Exception("La cita pertenece a un afiliado o beneficiario; no se puede editar como externo.");
        }

        $id_persona_destino = (int) $persona_cita['id_persona'];

        // Si cambió la cédula, no debe pertenecer a otra persona.
        if ($cedula_ext !== (string) $persona_cita['cedula']) {
            $stmt = $conn->prepare("SELECT id_persona FROM persona WHERE cedula = ? AND id_persona <> ? LIMIT 1");
            $stmt->bind_param("si", $cedula_ext, $id_persona_destino);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                $stmt->close();
                throw new Exception("La cédula $cedula_ext ya pertenece a otra persona.");
            }
            $stmt->close();
        }

        $stmt = $conn->prepare("UPDATE persona SET nombre = ?, apellido = ?, cedula = ? WHERE id_persona = ?");
        $stmt->bind_param("sssi", $nombre_ext, $apellido_ext, $cedula_ext, $id_persona_destino);
        $stmt->execute();
        $stmt->close();
    } else {
        throw new Exception("Debe indicar el paciente de la cita.");
    }

    // --- ACTUALIZAR LA CITA ---
    $sql_citas = "UPDATE citas
                  SET id_especialidad = ?, id_persona = ?, fecha_cita = ?, descripcion = ?, updated_at = NOW()
                  WHERE id_cita = ?";
    $stmt_citas = $conn->prepare($sql_citas);
    $stmt_citas->bind_param("iissi", $id_especialidad, $id_persona_destino, $fecha_cita, $descripcion, $id_cita);
    if (!$stmt_citas->execute()) {
        throw new Exception("Error al actualizar la cita principal.");
    }
    $stmt_citas->close();

    // --- BITÁCORA ---
    $usuario = $_SESSION['username'] ?? 'Sistema';
    $accion = "Actualización de Cita";
    $desc_bit = "Se modificó la cita ID: $id_cita";
    include_once 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';
    if (function_exists('registrarenBitacora')) {
        registrarenBitacora($conn, $usuario, $accion, $desc_bit);
    }

    $conn->commit();
    $transaccion_iniciada = false;

    echo json_encode(['success' => true, 'message' => 'Cita actualizada correctamente']);
} catch (Exception $e) {
    if ($transaccion_iniciada) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
