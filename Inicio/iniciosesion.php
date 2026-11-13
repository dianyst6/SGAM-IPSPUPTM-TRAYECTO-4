<?php

require_once 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Consulta para buscar el usuario por nombre
    $sql = "SELECT * FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_id'] = $user['role_id'];

                header('Location: /IPSPUPTM/home.php');
                exit();
            } else {
                $_SESSION['login_error'] = 'La contraseña no coincide.';
                header('Location: /IPSPUPTM/index.php');
                exit();
            }
        } else {
            $_SESSION['login_error'] = 'Usuario no encontrado.';
            header('Location: /IPSPUPTM/index.php');
            exit();
        }

        $stmt->close();
    } else {
        $_SESSION['login_error'] = 'Error al preparar la consulta.';
        header('Location: /IPSPUPTM/index.php');
        exit();
    }
}

$conn->close();
?>