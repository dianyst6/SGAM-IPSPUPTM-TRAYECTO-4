<?php
session_start();
require_once 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

// Solo se acepta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /IPSPUPTM/vistas/recuperar_contraseña.php");
    exit();
}

// Sin autorización vigente en la sesión -> rechazar
$autorizacion_valida = !empty($_SESSION['reset_autorizado'])
    && !empty($_SESSION['reset_user_id'])
    && !empty($_SESSION['reset_expira'])
    && time() < (int) $_SESSION['reset_expira'];

if (!$autorizacion_valida) {
    unset($_SESSION['reset_autorizado'], $_SESSION['reset_user_id'], $_SESSION['reset_expira']);
    $_SESSION['mensaje_alertify'] = "Sesión de recuperación inválida o expirada. Inicia de nuevo el proceso.";
    $_SESSION['tipo_alertify'] = "error";
    header("Location: /IPSPUPTM/vistas/recuperar_contraseña.php");
    exit();
}

// La identidad viene SIEMPRE de la sesión, nunca del cliente
$user_id = (int) $_SESSION['reset_user_id'];
$nueva_password = $_POST['nueva_password'] ?? '';
$confirmar_password = $_POST['confirmar_password'] ?? '';

// Validar que las contraseñas coincidan
if ($nueva_password !== $confirmar_password) {
    $_SESSION['reset_error'] = 'no_coinciden';
    header("Location: /IPSPUPTM/vistas/verificar_respuestas.php");
    exit();
}

// Validar longitud mínima (igual que el registro de usuarios)
if (strlen($nueva_password) < 8) {
    $_SESSION['reset_error'] = 'corta';
    header("Location: /IPSPUPTM/vistas/verificar_respuestas.php");
    exit();
}

// Encriptar la nueva contraseña
$hashed_nueva_password = password_hash($nueva_password, PASSWORD_BCRYPT);

// Actualizar la contraseña en la base de datos
$sql_actualizar = "UPDATE usuarios SET password = ? WHERE id = ?";
$stmt_actualizar = $conn->prepare($sql_actualizar);

if ($stmt_actualizar) {
    $stmt_actualizar->bind_param("si", $hashed_nueva_password, $user_id);
    if ($stmt_actualizar->execute()) {
        // Un solo uso: se destruye la autorización y se renueva el ID de sesión
        unset(
            $_SESSION['reset_autorizado'],
            $_SESSION['reset_user_id'],
            $_SESSION['reset_expira'],
            $_SESSION['reset_intentos'],
            $_SESSION['reset_error']
        );
        session_regenerate_id(true);
        header("Location: /IPSPUPTM/index.php?contrasena_restablecida=1");
        exit();
    }
    $stmt_actualizar->close();
}

$_SESSION['reset_error'] = 'db_error';
header("Location: /IPSPUPTM/vistas/verificar_respuestas.php");
exit();
