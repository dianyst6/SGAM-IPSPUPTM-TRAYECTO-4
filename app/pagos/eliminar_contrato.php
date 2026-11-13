<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $sql = "DELETE FROM contrato_plan WHERE ID_contrato = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['flash_msg'] = "Contrato eliminado correctamente.";
            $_SESSION['flash_type'] = "success";
        } else {
            throw new Exception("No se pudo eliminar el contrato.");
        }
    } catch (mysqli_sql_exception $e) {
        // Este error ocurre si hay pagos asociados (Llave foránea)
        $_SESSION['flash_msg'] = "Error: No se puede eliminar un contrato que ya tiene pagos registrados.";
        $_SESSION['flash_type'] = "danger";
    } catch (Exception $e) {
        $_SESSION['flash_msg'] = $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
}

header("Location: /IPSPUPTM/home.php?vista=gestionplanes");
exit();
?>