<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);

require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/persona_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/plan_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

// Verifica que se ha enviado el formulario por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validamos que los campos esenciales estén presentes
    if (!isset($_POST['cedula'], $_POST['nombre'], $_POST['fechanacimiento'], $_POST['id_planes_contrato'])) {
        echo "Faltan datos requeridos en el formulario.";
        exit();
    }

    // --- 1. Recolección de Datos Personales ---
    $cedula = htmlspecialchars($_POST['cedula']);
    $nombre = htmlspecialchars($_POST['nombre']);
    $apellido = htmlspecialchars($_POST['apellido']);
    $fechanacimiento = htmlspecialchars($_POST['fechanacimiento']);
    $genero = htmlspecialchars($_POST['genero']);
    $estado_civil = htmlspecialchars($_POST['estado_civil'] ?? '');
    $telefono = htmlspecialchars($_POST['telefono']);
    $correo = htmlspecialchars($_POST['correo']);
    $ocupacion = htmlspecialchars($_POST['ocupacion']);

    // --- 2. Datos del Contrato ---
    // El monto y las fechas ya no se digitan: se toman del plan anual.
    $idPlan = (int) $_POST['id_planes_contrato'];
    $diaPago = isset($_POST['dia_pago_mensual']) ? (int) $_POST['dia_pago_mensual'] : PLAN_DIA_PAGO_DEFECTO;
    $esAdmin = ((int) ($_SESSION['role_id'] ?? 0)) === 1;
    $fechaInscripcion = planHoy();
    $fueraDePeriodo = !planPeriodoInscripcionAbierto($fechaInscripcion);

    // --- 2.1 Validaciones de identidad y edad ---
    if (!preg_match('/^[0-9]{1,8}$/', $cedula)) {
        $_SESSION['flash_msg'] = "Error: la cédula debe contener solo números (máximo 8).";
        $_SESSION['flash_type'] = "danger";
        header("Location: /IPSPUPTM/home.php?vista=afiliados&filtro=todos");
        exit();
    }

    $estados_civiles = ['Soltero(a)', 'Casado(a)', 'Concubino(a)', 'Divorciado(a)', 'Viudo(a)'];
    if (!in_array($estado_civil, $estados_civiles, true)) {
        $_SESSION['flash_msg'] = "Error: debe seleccionar un estado civil válido.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /IPSPUPTM/home.php?vista=afiliados&filtro=todos");
        exit();
    }

    // Regla de negocio: un afiliado debe tener al menos 20 años cumplidos.
    $fecha_minima = date('Y-m-d', strtotime('-20 years'));
    $fecha_obj = DateTime::createFromFormat('Y-m-d', $fechanacimiento);
    if (!$fecha_obj || $fecha_obj->format('Y-m-d') !== $fechanacimiento) {
        $_SESSION['flash_msg'] = "Error: la fecha de nacimiento no es válida.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /IPSPUPTM/home.php?vista=afiliados&filtro=todos");
        exit();
    }
    if ($fechanacimiento > $fecha_minima) {
        $_SESSION['flash_msg'] = "Error: el afiliado debe tener al menos 20 años cumplidos.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /IPSPUPTM/home.php?vista=afiliados&filtro=todos");
        exit();
    }

    // --- 3. Inicio de Operación Segura (Transacción) ---
    $conn->begin_transaction();

    try {
        if ($fueraDePeriodo && $esAdmin && empty($_POST['autorizar_excepcion'])) {
            throw new Exception(
                'La inscripción está fuera del periodo (' . planPeriodoInscripcionTexto()
                . '). Debe confirmar la autorización excepcional.'
            );
        }

        // Verificar si el correo ya existe para OTRA persona
        $sql_check_correo = "SELECT cedula FROM persona WHERE correo = ? AND cedula != ?";
        $stmt_check_correo = $conn->prepare($sql_check_correo);
        $stmt_check_correo->bind_param("ss", $correo, $cedula);
        $stmt_check_correo->execute();
        $res_check_correo = $stmt_check_correo->get_result();

        if ($res_check_correo->num_rows > 0) {
            throw new Exception("Error: El correo electrónico ingresado ya se encuentra registrado por otra persona.");
        }
        $stmt_check_correo->close();

        // A. Resolver la persona: cédula como entrada, id_persona como clave interna
        $idPersona = buscarIdPersonaPorCedula($conn, $cedula);

        if ($idPersona === null) {
            // Si no existe, crear registro en persona
            $sql_persona = "INSERT INTO persona (cedula, nombre, apellido, fechanacimiento, genero, estado_civil, telefono, correo, ocupacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_p = $conn->prepare($sql_persona);
            $stmt_p->bind_param("sssssssss", $cedula, $nombre, $apellido, $fechanacimiento, $genero, $estado_civil, $telefono, $correo, $ocupacion);
            $stmt_p->execute();
            $idPersona = (int) $conn->insert_id;
            $stmt_p->close();
        } else {
            // Persona ya existente (por ejemplo, un beneficiario): completar el
            // estado civil solo si aún no lo tiene, sin pisar datos previos.
            $stmt_ec = $conn->prepare(
                "UPDATE persona SET estado_civil = ?
                 WHERE id_persona = ? AND (estado_civil IS NULL OR estado_civil = '')"
            );
            if ($stmt_ec) {
                $stmt_ec->bind_param("si", $estado_civil, $idPersona);
                $stmt_ec->execute();
                $stmt_ec->close();
            }
        }

        // B. Verificar si ya es AFILIADO (Evita el error Duplicate Entry)
        $sql_check_a = "SELECT id_persona FROM afiliados WHERE id_persona = ?";
        $stmt_check_a = $conn->prepare($sql_check_a);
        $stmt_check_a->bind_param("i", $idPersona);
        $stmt_check_a->execute();
        $res_a = $stmt_check_a->get_result();

        if ($res_a->num_rows > 0) {
            throw new Exception("Error: El usuario con cédula $cedula ya está registrado como afiliado.");
        }
        $stmt_check_a->close();

        // C. Insertar en tabla AFILIADOS (solo id_persona; la cédula vive en persona)
        $sql_afiliado = "INSERT INTO afiliados (id_persona, created_at, updated_at) VALUES (?, NOW(), NOW())";
        $stmt_a = $conn->prepare($sql_afiliado);
        $stmt_a->bind_param("i", $idPersona);
        $stmt_a->execute();
        $stmt_a->close();

        // D. Crear el contrato anual y sus cuotas
        $resultado = contratoCrearAnual(
            $conn,
            $idPersona,
            $idPlan,
            $diaPago,
            $esAdmin,
            $fechaInscripcion
        );

        // --- 4. Finalizar Transacción ---
        $conn->commit();

        $contrato = $resultado['contrato'];
        $resumen = $resultado['resumen'];
        $cuotas = $resumen['cuotas'];
        $montoCuota = $cuotas ? (float) $cuotas[0]['monto'] : 0.0;

        // Registro en Bitácora
        $usuario_log = $_SESSION['username'] ?? 'Sistema';
        $detalleExcepcion = $fueraDePeriodo ? ' (inscripción excepcional autorizada)' : '';
        registrarEnBitacora(
            $conn,
            $usuario_log,
            "Registro Integral",
            "Afiliado y contrato {$contrato['anio']} creados: $cedula. Inicial: $"
            . number_format($resumen['monto_inicial'], 2)
            . ", cuota: $" . number_format($montoCuota, 2)
            . " x " . count($cuotas) . $detalleExcepcion
        );

        $_SESSION['flash_msg'] = "Afiliado registrado. Contrato {$contrato['anio']} creado con pago inicial de $"
            . number_format($resumen['monto_inicial'], 2)
            . " y cuota mensual de $" . number_format($montoCuota, 2)
            . " (" . count($cuotas) . " cuotas). Queda como \"Pago inicial pendiente\""
            . " hasta que registre ese monto.";
        $_SESSION['flash_type'] = "success";

        header("Location: /IPSPUPTM/home.php?vista=afiliados&filtro=todos");
        exit();

    } catch (Exception $e) {
        // Si algo falla, se deshacen todos los cambios
        $conn->rollback();
        $_SESSION['flash_msg'] = $e->getMessage();
        $_SESSION['flash_type'] = "danger";
        header("Location: /IPSPUPTM/home.php?vista=afiliados&filtro=todos");
        exit();
    }
} else {
    echo "Método de solicitud no válido.";
}

$conn->close();
?>
