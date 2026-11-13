<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);

require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/persona_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

// Verifica que se ha enviado el formulario por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validamos que los campos esenciales estén presentes
    if (isset($_POST['cedula'], $_POST['nombre'], $_POST['fechanacimiento'], $_POST['id_planes_contrato'], $_POST['fecha_inicio'])) {
        
        // --- 1. Recolección de Datos Personales ---
        $cedula = htmlspecialchars($_POST['cedula']);
        $nombre = htmlspecialchars($_POST['nombre']);
        $apellido = htmlspecialchars($_POST['apellido']);
        $fechanacimiento = htmlspecialchars($_POST['fechanacimiento']);
        $genero = htmlspecialchars($_POST['genero']);
        $telefono = htmlspecialchars($_POST['telefono']);
        $correo = htmlspecialchars($_POST['correo']);
        $ocupacion = htmlspecialchars($_POST['ocupacion']);

        // --- 2. Recolección de Datos del Contrato ---
        $id_plan = $_POST['id_planes_contrato'];
        $monto_total = $_POST['monto_total'];
        $fecha_inicio = $_POST['fecha_inicio'];
        $fecha_fin = $_POST['fecha_fin'];
        $frecuencia = $_POST['frecuencia_pago'];
        $dia_pago = $_POST['dia_pago_mensual'];
        $estado_contrato = $_POST['estado_contrato'];

        // --- 2.1 Validaciones de identidad y edad ---
        if (!preg_match('/^[0-9]{1,8}$/', $cedula)) {
            $_SESSION['flash_msg'] = "Error: la cédula debe contener solo números (máximo 8).";
            $_SESSION['flash_type'] = "danger";
            header("Location: /IPSPUPTM/home.php?vista=afiliados");
            exit();
        }

        // Regla de negocio: un afiliado debe tener al menos 20 años cumplidos.
        $fecha_minima = date('Y-m-d', strtotime('-20 years'));
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fechanacimiento);
        if (!$fecha_obj || $fecha_obj->format('Y-m-d') !== $fechanacimiento) {
            $_SESSION['flash_msg'] = "Error: la fecha de nacimiento no es válida.";
            $_SESSION['flash_type'] = "danger";
            header("Location: /IPSPUPTM/home.php?vista=afiliados");
            exit();
        }
        if ($fechanacimiento > $fecha_minima) {
            $_SESSION['flash_msg'] = "Error: el afiliado debe tener al menos 20 años cumplidos.";
            $_SESSION['flash_type'] = "danger";
            header("Location: /IPSPUPTM/home.php?vista=afiliados");
            exit();
        }

        // --- 3. Inicio de Operación Segura (Transacción) ---
        $conn->begin_transaction();

        try {
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
            $id_persona = buscarIdPersonaPorCedula($conn, $cedula);

            if ($id_persona === null) {
                // Si no existe, crear registro en persona
                $sql_persona = "INSERT INTO persona (cedula, nombre, apellido, fechanacimiento, genero, telefono, correo, ocupacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_p = $conn->prepare($sql_persona);
                $stmt_p->bind_param("ssssssss", $cedula, $nombre, $apellido, $fechanacimiento, $genero, $telefono, $correo, $ocupacion);
                $stmt_p->execute();
                $id_persona = (int) $conn->insert_id;
                $stmt_p->close();
            }

            // B. Verificar si ya es AFILIADO (Evita el error Duplicate Entry)
            $sql_check_a = "SELECT id_persona FROM afiliados WHERE id_persona = ?";
            $stmt_check_a = $conn->prepare($sql_check_a);
            $stmt_check_a->bind_param("i", $id_persona);
            $stmt_check_a->execute();
            $res_a = $stmt_check_a->get_result();

            if ($res_a->num_rows > 0) {
                // Si ya existe en la tabla afiliados, cancelamos todo
                throw new Exception("Error: El usuario con cédula $cedula ya está registrado como afiliado.");
            }
            $stmt_check_a->close();

            // C. Insertar en tabla AFILIADOS (cédula por compatibilidad + id_persona)
            $sql_afiliado = "INSERT INTO afiliados (cedula, id_persona, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
            $stmt_a = $conn->prepare($sql_afiliado);
            $stmt_a->bind_param("si", $cedula, $id_persona);
            $stmt_a->execute();
            $stmt_a->close();

            // D. Insertar en tabla CONTRATO_PLAN (la relación es por id_persona)
            $sql_contrato = "INSERT INTO contrato_plan (ID_planes_contrato, id_persona, fecha_inicio, fecha_fin, monto_total, frecuencia_pago, dia_pago_mensual, estado_contrato) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_c = $conn->prepare($sql_contrato);
            // Tipos: i (plan), i (id_persona), s, s, d (monto), s, i (día), s (estado)
            $stmt_c->bind_param("iissdsis", $id_plan, $id_persona, $fecha_inicio, $fecha_fin, $monto_total, $frecuencia, $dia_pago, $estado_contrato);
            $stmt_c->execute();
            $stmt_c->close();

            // --- 4. Finalizar Transacción ---
            $conn->commit();

            // Registro en Bitácora
            $usuario_log = $_SESSION['username'] ?? 'Sistema';
            registrarEnBitacora($conn, $usuario_log, "Registro Integral", "Afiliado y Plan creados: $cedula");

            $_SESSION['flash_msg'] = "Afiliado y Contrato registrados exitosamente.";
            $_SESSION['flash_type'] = "success";

            header("Location: /IPSPUPTM/home.php?vista=afiliados");
            exit();

        } catch (Exception $e) {
            // Si algo falla, se deshacen todos los cambios
            $conn->rollback();
            $_SESSION['flash_msg'] = $e->getMessage();
            $_SESSION['flash_type'] = "danger";
            header("Location: /IPSPUPTM/home.php?vista=afiliados");
            exit();
        }

    } else {
        echo "Faltan datos requeridos en el formulario.";
    }
} else {
    echo "Método de solicitud no válido.";
}

$conn->close();
?>