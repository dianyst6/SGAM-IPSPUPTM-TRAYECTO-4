<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);

require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/persona_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cedula_afil'], $_POST['cedula'], $_POST['nombre'], $_POST['apellido'], $_POST['fechanacimiento'], $_POST['parentesco'], $_POST['genero'], $_POST['telefono'], $_POST['correo'], $_POST['ocupacion'])) {
        // Limpiar datos
        $cedula_afil = (int) $_POST['cedula_afil'];
        $cedula = trim((string) $_POST['cedula']);
        $nombre = htmlspecialchars($_POST['nombre']);
        $apellido = htmlspecialchars($_POST['apellido']);
        $fechanacimiento = trim((string) $_POST['fechanacimiento']);
        $parentesco = htmlspecialchars($_POST['parentesco']);
        $genero = htmlspecialchars($_POST['genero']);
        $telefono = htmlspecialchars($_POST['telefono']);
        $correo = htmlspecialchars($_POST['correo']);
        $ocupacion = htmlspecialchars($_POST['ocupacion']);

        // La cédula es el dato de entrada, pero debe tener formato válido.
        if (!preg_match('/^[0-9]{1,8}$/', $cedula)) {
            $_SESSION['flash_msg'] = "Error: la cédula debe contener solo números (máximo 8).";
            $_SESSION['flash_type'] = "danger";
            header('Location: /IPSPUPTM/home.php?vista=beneficiarios');
            exit();
        }

        // Los beneficiarios pueden ser menores de edad; solo se valida la fecha.
        $hoy = date('Y-m-d');
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fechanacimiento);
        if (!$fecha_obj || $fecha_obj->format('Y-m-d') !== $fechanacimiento || $fechanacimiento > $hoy) {
            $_SESSION['flash_msg'] = "Error: la fecha de nacimiento no es válida.";
            $_SESSION['flash_type'] = "danger";
            header('Location: /IPSPUPTM/home.php?vista=beneficiarios');
            exit();
        }

        $conn->begin_transaction();

        try {
            // Verificar que el afiliado relacionado exista
            $stmt_afil = $conn->prepare("SELECT ID FROM afiliados WHERE ID = ?");
            $stmt_afil->bind_param("i", $cedula_afil);
            $stmt_afil->execute();
            $res_afil = $stmt_afil->get_result();
            if (!$res_afil || $res_afil->num_rows === 0) {
                throw new Exception("Error: el afiliado relacionado no existe.");
            }
            $stmt_afil->close();

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
                $sql_persona = "INSERT INTO persona (cedula, nombre, apellido, fechanacimiento, genero, telefono, correo, ocupacion)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_persona = $conn->prepare($sql_persona);
                $stmt_persona->bind_param("ssssssss", $cedula, $nombre, $apellido, $fechanacimiento, $genero, $telefono, $correo, $ocupacion);

                if (!$stmt_persona->execute()) {
                    throw new Exception("Error en persona: " . $stmt_persona->error);
                }
                $id_persona = (int) $conn->insert_id;
                $stmt_persona->close();
            }

            // B. Evitar que una persona ya registrada como afiliado se registre como beneficiario
            $stmt_check_afil = $conn->prepare("SELECT 1 FROM afiliados WHERE id_persona = ?");
            $stmt_check_afil->bind_param("i", $id_persona);
            $stmt_check_afil->execute();
            if ($stmt_check_afil->get_result()->num_rows > 0) {
                $stmt_check_afil->close();
                throw new Exception("Error: la persona con cédula $cedula ya está registrada como afiliado.");
            }
            $stmt_check_afil->close();

            // C. Evitar duplicados de beneficiario por id_persona
            $stmt_check_benef = $conn->prepare("SELECT 1 FROM beneficiarios WHERE id_persona = ?");
            $stmt_check_benef->bind_param("i", $id_persona);
            $stmt_check_benef->execute();
            if ($stmt_check_benef->get_result()->num_rows > 0) {
                $stmt_check_benef->close();
                throw new Exception("Error: la persona con cédula $cedula ya está registrada como beneficiario.");
            }
            $stmt_check_benef->close();

            // D. Insertar beneficiario con cédula (compatibilidad) + id_persona
            $sql_beneficiarios = "INSERT INTO beneficiarios (cedula, id_persona, id_afiliado, parentesco, created_at, updated_at)
                                VALUES (?, ?, ?, ?, NOW(), NOW())";
            $stmt_beneficiarios = $conn->prepare($sql_beneficiarios);
            $stmt_beneficiarios->bind_param("siis", $cedula, $id_persona, $cedula_afil, $parentesco);

            if (!$stmt_beneficiarios->execute()) {
                throw new Exception("Error en beneficiarios: " . $stmt_beneficiarios->error);
            }
            $stmt_beneficiarios->close();

            // Registrar en bitácora
            $usuario = $_SESSION['username'] ?? 'Sistema';
            $accion = "Registro de Beneficiario";
            $descripcion = "Cédula: $cedula, Nombre: $nombre, Apellido: $apellido";
            registrarEnBitacora($conn, $usuario, $accion, $descripcion);

            $conn->commit();

            $_SESSION['flash_msg'] = "Beneficiario registrado exitosamente.";
            $_SESSION['flash_type'] = "success";
            header('Location: /IPSPUPTM/home.php?vista=beneficiarios');
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_msg'] = $e->getMessage();
            $_SESSION['flash_type'] = "danger";
            header('Location: /IPSPUPTM/home.php?vista=beneficiarios');
            exit();
        }
    } else {
        die("Faltan datos requeridos");
    }
} else {
    die("Método no permitido");
}

$conn->close();
?>
