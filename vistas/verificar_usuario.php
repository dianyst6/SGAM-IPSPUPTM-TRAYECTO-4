<?php
session_start();
require_once 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /IPSPUPTM/vistas/recuperar_contraseña.php");
    exit();
}

// Un nuevo proceso de recuperación descarta cualquier autorización previa
unset(
    $_SESSION['reset_autorizado'],
    $_SESSION['reset_user_id'],
    $_SESSION['reset_expira'],
    $_SESSION['reset_intentos'],
    $_SESSION['reset_error']
);

$username = $_POST['username'];

$sql_usuario = "SELECT id FROM usuarios WHERE username = ?";
$stmt_usuario = $conn->prepare($sql_usuario);

if ($stmt_usuario) {
    $stmt_usuario->bind_param("s", $username);
    $stmt_usuario->execute();
    $result_usuario = $stmt_usuario->get_result();

    if ($result_usuario->num_rows == 1) {
        $row_usuario = $result_usuario->fetch_assoc();
        $user_id = $row_usuario['id'];

        $sql_preguntas = "SELECT rs.pregunta_seguridad_id, ps.pregunta 
                          FROM respuestas_seguridad rs 
                          INNER JOIN preguntas_seguridad ps ON rs.pregunta_seguridad_id = ps.ID 
                          WHERE rs.usuario_id = ?";
        
        $stmt_preguntas = $conn->prepare($sql_preguntas);
        $stmt_preguntas->bind_param("i", $user_id);
        $stmt_preguntas->execute();
        $result_preguntas = $stmt_preguntas->get_result();

        if ($result_preguntas->num_rows > 0) {
            $preguntas_usuario = [];
            while ($row_pregunta = $result_preguntas->fetch_assoc()) {
                $preguntas_usuario[$row_pregunta['pregunta_seguridad_id']] = $row_pregunta['pregunta'];
            }

            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Verificar Preguntas</title>
                <link rel="stylesheet" href="/IPSPUPTM/assets/css/bootstrap.min.css">
                <link rel="stylesheet" href="/IPSPUPTM/assets/css/inicio.css">
                <?php include 'C:/xampp/htdocs/IPSPUPTM/config/alertify.php'; ?>
            </head>
            <body>
                
                <div class="min-vh-100 d-flex align-items-center justify-content-center">
                    <?php include 'formulario_seguridad.php'; ?>
                </div>

                <script src="/IPSPUPTM/assets/js/bootstrap.bundle.min.js"></script>
            </body>
            </html>
            <?php
        } else {
            $_SESSION['mensaje_alertify'] = "Este usuario no tiene preguntas de seguridad configuradas.";
            $_SESSION['tipo_alertify'] = "warning";
            header("Location: /IPSPUPTM/vistas/recuperar_contraseña.php");
            exit();
        }
        $stmt_preguntas->close();
    } else {
        $_SESSION['mensaje_alertify'] = "El nombre de usuario no existe.";
        $_SESSION['tipo_alertify'] = "error";
        header("Location: /IPSPUPTM/vistas/recuperar_contraseña.php");
        exit();
    }
    $stmt_usuario->close();
} else {
    $_SESSION['mensaje_alertify'] = "Error interno, intenta más tarde.";
    $_SESSION['tipo_alertify'] = "error";
    header("Location: /IPSPUPTM/vistas/recuperar_contraseña.php");
    exit();
}
?>