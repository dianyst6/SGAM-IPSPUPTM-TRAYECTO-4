<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1]);
require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);

    try {
        $conn->begin_transaction();

        if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $id) {
            throw new Exception("No puedes eliminar tu propia cuenta.");
        }

        $sql_info_usuario = "SELECT username FROM usuarios WHERE ID = ?";
        $stmt_info = $conn->prepare($sql_info_usuario);

        if ($stmt_info) {
            $stmt_info->bind_param("i", $id);
            $stmt_info->execute();
            $result_info = $stmt_info->get_result();

            if ($result_info->num_rows > 0) {
                $info_usuario = $result_info->fetch_assoc();
            } else {
                $conn->rollback();
                header('Location: /IPSPUPTM/home.php?vista=usuarios&error=' . urlencode('No se encontró ningún usuario con el ID proporcionado.'));
                exit();
            }

            $stmt_info->close();
        } else {
            throw new Exception("Error al preparar la consulta SQL: " . $conn->error);
        }

        $sql_eliminar_respuestas = "DELETE FROM respuestas_seguridad WHERE usuario_id = ?";
        $stmt_eliminar_respuestas = $conn->prepare($sql_eliminar_respuestas);

        if ($stmt_eliminar_respuestas) {
            $stmt_eliminar_respuestas->bind_param("i", $id);
            $stmt_eliminar_respuestas->execute();
            $stmt_eliminar_respuestas->close();
        } else {
            throw new Exception("Error al preparar la consulta para eliminar respuestas de seguridad: " . $conn->error);
        }

        $sql_usuarios = "DELETE FROM usuarios WHERE ID = ?";
        $stmt_usuarios = $conn->prepare($sql_usuarios);

        if ($stmt_usuarios) {
            $stmt_usuarios->bind_param("i", $id);
            $stmt_usuarios->execute();
            $stmt_usuarios->close();
        } else {
            throw new Exception("Error al preparar la consulta de eliminación: " . $conn->error);
        }

        $usuario = $_SESSION['username'] ?? 'Admin'; // Tomado de la sesión activa
        $accion = "Eliminación de usuario";
        $descripcion = "Se eliminó al usuario: " . $info_usuario['username'] . " y sus respuestas de seguridad.";
        registrarenBitacora($conn, $usuario, $accion, $descripcion);

        $conn->commit();

        header('Location: /IPSPUPTM/home.php?vista=usuarios&mensaje=usuario_eliminado');
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        header('Location: /IPSPUPTM/home.php?vista=usuarios&error=' . urlencode($e->getMessage()));
        exit();
    }
} else {
    header('Location: /IPSPUPTM/home.php?vista=usuarios&error=id_no_proporcionado');
    exit();
}


$conn->close();
?>