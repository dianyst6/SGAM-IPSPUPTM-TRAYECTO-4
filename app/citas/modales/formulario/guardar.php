<?php
require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

if (!isset($_POST['id_especialidad'], $_POST['fecha_cita'], $_POST['descripcion'], $_POST['tipo_paciente'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos en el formulario.']);
    exit;
}

$id_especialidad = intval($_POST['id_especialidad']);
$fecha_cita      = htmlspecialchars($_POST['fecha_cita']);
$descripcion     = htmlspecialchars($_POST['descripcion']);
$tipo_form       = $_POST['tipo_paciente'];

$transaccion_iniciada = false;

try {
    $conn->begin_transaction();
    $transaccion_iniciada = true;

    $id_persona             = 0;
    $tipo_paciente_bitacora = '';

    if ($tipo_form === 'externo') {
        // --- COMUNIDAD UPTM (EXTERNO) ---
        $cedula   = trim((string) ($_POST['cedula_ext'] ?? ''));
        $nombre   = trim((string) ($_POST['nombre_ext'] ?? ''));
        $apellido = trim((string) ($_POST['apellido_ext'] ?? ''));

        if (!preg_match('/^[0-9]{1,8}$/', $cedula)) {
            throw new Exception('La cédula del externo debe contener solo números (máximo 8).');
        }
        if ($nombre === '' || $apellido === '') {
            throw new Exception('Debe indicar nombre y apellido del externo.');
        }

        $nombre   = htmlspecialchars($nombre);
        $apellido = htmlspecialchars($apellido);

        $check_uptm = $conn->prepare("
            SELECT p.id_persona,
                   (SELECT COUNT(*) FROM afiliados    WHERE id_persona = p.id_persona) AS es_afiliado,
                   (SELECT COUNT(*) FROM beneficiarios WHERE id_persona = p.id_persona) AS es_beneficiario
            FROM persona p
            WHERE p.cedula = ?
            LIMIT 1
        ");
        $check_uptm->bind_param("s", $cedula);
        $check_uptm->execute();
        $row_ex = $check_uptm->get_result()->fetch_assoc();
        $check_uptm->close();

        if ($row_ex) {
            if ((int) $row_ex['es_afiliado'] > 0 || (int) $row_ex['es_beneficiario'] > 0) {
                throw new Exception("La cédula $cedula pertenece a un afiliado o beneficiario. Seleccione el paciente interno.");
            }

            // Ya existe como comunidad: reutilizamos la persona y actualizamos sus datos básicos.
            $id_persona = (int) $row_ex['id_persona'];
            $upd_ext = $conn->prepare("UPDATE persona SET nombre = ?, apellido = ? WHERE id_persona = ?");
            $upd_ext->bind_param("ssi", $nombre, $apellido, $id_persona);
            $upd_ext->execute();
            $upd_ext->close();
        } else {
            // No existe: se crea como persona de comunidad.
            $ins_uptm = $conn->prepare("
                INSERT INTO persona (cedula, nombre, apellido, fechanacimiento, genero,
                                     telefono, correo, ocupacion, estado)
                VALUES (?, ?, ?, NULL, NULL, 'No aplica', 'No aplica', 'No aplica', 'No aplica')
            ");
            $ins_uptm->bind_param("sss", $cedula, $nombre, $apellido);
            $ins_uptm->execute();
            $id_persona = (int) $conn->insert_id;
            $ins_uptm->close();
        }

        $tipo_paciente_bitacora = "Comunidad UPTM (Externo)";
    } else {
        // --- AFILIADO / BENEFICIARIO (INTERNO) ---
        $id_persona = intval($_POST['id_paciente'] ?? 0);
        if ($id_persona <= 0) {
            throw new Exception("Debe seleccionar un paciente de la lista.");
        }

        $stmt_tipo = $conn->prepare("
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
        $stmt_tipo->bind_param("i", $id_persona);
        $stmt_tipo->execute();
        $row_tipo = $stmt_tipo->get_result()->fetch_assoc();
        $stmt_tipo->close();

        if (!$row_tipo || empty($row_tipo['tipo'])) {
            throw new Exception("El paciente seleccionado no es afiliado ni beneficiario.");
        }

        $tipo_paciente_bitacora = $row_tipo['tipo'];
    }

    // Insertar la cita con el paciente unificado.
    $sql_citas = "INSERT INTO citas (id_especialidad, id_persona, fecha_cita, descripcion, created_at, updated_at)
                  VALUES (?, ?, ?, ?, NOW(), NOW())";
    $stmt_citas = $conn->prepare($sql_citas);
    $stmt_citas->bind_param("iiss", $id_especialidad, $id_persona, $fecha_cita, $descripcion);
    if (!$stmt_citas->execute()) {
        throw new Exception("Error al insertar la cita maestra: " . $stmt_citas->error);
    }
    $id_cita = $conn->insert_id;
    $stmt_citas->close();

    // Registrar exámenes seleccionados.
    if (isset($_POST['examenes']) && is_array($_POST['examenes'])) {
        foreach ($_POST['examenes'] as $id_ex) {
            $id_ex = intval($id_ex);
            $p_res = $conn->query("SELECT precio FROM examenes WHERE ID_examen = $id_ex");
            $p_row = $p_res ? $p_res->fetch_assoc() : null;
            $precio_h = $p_row['precio'] ?? 0;

            $ins_ex = $conn->prepare("INSERT INTO citas_examenes (id_cita, id_examen, precio_historico) VALUES (?, ?, ?)");
            $ins_ex->bind_param("iid", $id_cita, $id_ex, $precio_h);
            $ins_ex->execute();
            $ins_ex->close();
        }
    }

    $usuario = $_SESSION['username'] ?? 'Sistema';
    $accion = "Registro de Cita";
    $desc_bitacora = "Se ha registrado una cita de tipo $tipo_paciente_bitacora para la fecha: $fecha_cita";
    registrarenBitacora($conn, $usuario, $accion, $desc_bitacora);

    $conn->commit();
    $transaccion_iniciada = false;

    echo json_encode(['success' => true, 'message' => "Cita de $tipo_paciente_bitacora registrada correctamente."]);
} catch (Exception $e) {
    if ($transaccion_iniciada) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
