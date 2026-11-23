<?php
session_start();
require_once 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

$respuestas_coinciden = false;
$user_id = null;
$error_password = null;

// 1. CONTROL DE ACCESO:
// La identidad del usuario que puede cambiar la contraseña vive SOLO en la sesión del servidor.
// No se acepta ningún parámetro por URL (elimina el bypass con ?error=...&user_id=...).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // GET: solo se continúa si YA hay una autorización vigente en la sesión.
    $autorizacion_valida = !empty($_SESSION['reset_autorizado'])
        && !empty($_SESSION['reset_user_id'])
        && !empty($_SESSION['reset_expira'])
        && time() < (int) $_SESSION['reset_expira'];

    if ($autorizacion_valida) {
        $respuestas_coinciden = true;
        $user_id = (int) $_SESSION['reset_user_id'];
        $error_password = $_SESSION['reset_error'] ?? null;
        unset($_SESSION['reset_error']);
    } else {
        // Sin autorización (o expirada): se reinicia el flujo de recuperación.
        unset($_SESSION['reset_autorizado'], $_SESSION['reset_user_id'], $_SESSION['reset_expira'], $_SESSION['reset_error']);
        header("Location: /IPSPUPTM/vistas/recuperar_contraseña.php");
        exit();
    }
} else {
    // Flujo normal cuando viene de responder las preguntas por POST
    $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    $respuestas_usuario = (isset($_POST['respuesta_seguridad']) && is_array($_POST['respuesta_seguridad']))
        ? $_POST['respuesta_seguridad']
        : [];

    // Límite de intentos por sesión (máximo 5)
    if (!isset($_SESSION['reset_intentos'])) {
        $_SESSION['reset_intentos'] = 0;
    }

    if ($user_id <= 0 || empty($respuestas_usuario)) {
        $_SESSION['mensaje_alertify'] = "Solicitud inválida. Inténtalo de nuevo.";
        $_SESSION['tipo_alertify'] = "error";
        header("Location: /IPSPUPTM/vistas/recuperar_contraseña.php");
        exit();
    }

    // Obtener respuestas correctas de la DB
    $sql_respuestas = "SELECT pregunta_seguridad_id, respuesta FROM respuestas_seguridad WHERE usuario_id = ?";
    $stmt_respuestas = $conn->prepare($sql_respuestas);
    $stmt_respuestas->bind_param("i", $user_id);
    $stmt_respuestas->execute();
    $result_respuestas = $stmt_respuestas->get_result();

    $respuestas_correctas = [];
    while ($row = $result_respuestas->fetch_assoc()) {
        $respuestas_correctas[$row['pregunta_seguridad_id']] = $row['respuesta'];
    }

    // Verificar respuestas (sin distinguir mayúsculas/minúsculas ni espacios sobrantes)
    $respuestas_coinciden = !empty($respuestas_correctas);
    foreach ($respuestas_usuario as $pregunta_id => $valor) {
        $correcta = $respuestas_correctas[$pregunta_id] ?? null;
        if ($correcta === null || mb_strtolower(trim((string) $valor)) !== mb_strtolower(trim((string) $correcta))) {
            $respuestas_coinciden = false;
            break;
        }
    }

    if ($respuestas_coinciden) {
        // Autorización de un solo uso, guardada solo en el servidor y válida por 10 minutos
        $_SESSION['reset_autorizado'] = true;
        $_SESSION['reset_user_id'] = $user_id;
        $_SESSION['reset_expira'] = time() + 600;
        $_SESSION['reset_intentos'] = 0;
    } else {
        $_SESSION['reset_intentos']++;
        if ($_SESSION['reset_intentos'] >= 5) {
            $_SESSION['reset_intentos'] = 0;
            $_SESSION['mensaje_alertify'] = "Demasiados intentos fallidos. Por seguridad, inicia de nuevo la recuperación.";
            $_SESSION['tipo_alertify'] = "error";
            header("Location: /IPSPUPTM/vistas/recuperar_contraseña.php");
            exit();
        }
    }
}

if ($respuestas_coinciden) {
    // === CASO ÉXITO (O RETORNO POR ERROR DE CONTRASEÑA) ===
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Restablecer Contraseña</title>
        <link rel="stylesheet" href="/IPSPUPTM/assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="/IPSPUPTM/assets/css/inicio.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        
        <?php include __DIR__ . '/../config/alertify.php'; ?>
    </head>
    <body>
        
        <div class="min-vh-100 d-flex align-items-center justify-content-center">
            <?php include __DIR__ . '/formulario_reset.php'; ?>
        </div>

        <script src="/IPSPUPTM/assets/js/bootstrap.bundle.min.js"></script>
        <script>
            // Función nativa para activar los ojitos de este formulario
            function agragarAlternarOjo(buttonId, inputId, iconId) {
                const btn = document.getElementById(buttonId);
                const input = document.getElementById(inputId);
                const icon = document.getElementById(iconId);
                
                if (btn && input && icon) {
                    btn.addEventListener('click', function() {
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.classList.remove('fa-eye');
                            icon.classList.add('fa-eye-slash');
                        } else {
                            input.type = 'password';
                            icon.classList.remove('fa-eye-slash');
                            icon.classList.add('fa-eye');
                        }
                    });
                }
            }

            window.onload = function() {
                // 1. Inicializamos los eventos de los ojitos
                agragarAlternarOjo('toggleResetPassword', 'reset_password', 'resetEyeIcon');
                agragarAlternarOjo('toggleResetConfirmPassword', 'reset_confirm_password', 'resetConfirmEyeIcon');

                // 2. Manejo de alertas de error (mensajes desde un array fijo, sin datos de la URL)
                <?php if ($error_password !== null): ?>
                    <?php
                    $mensajes_error = [
                        'no_coinciden' => 'Las contraseñas introducidas no coinciden.',
                        'vacia'        => 'La nueva contraseña no puede estar vacía.',
                        'corta'        => 'La nueva contraseña debe tener al menos 8 caracteres.',
                    ];
                    $mensaje_error = $mensajes_error[$error_password] ?? 'Ocurrió un error al procesar la solicitud.';
                    ?>
                    alertify.error("<?php echo htmlspecialchars($mensaje_error, ENT_QUOTES); ?>");

                    // Limpiamos el estado del formulario (sin exponer la identidad en la URL)
                    window.history.replaceState({}, document.title, window.location.pathname);
                <?php endif; ?>
            };
        </script>
    </body>
    </html>
    <?php
} else {
    // === CASO ERROR EN RESPUESTAS DE SEGURIDAD ===
    $_SESSION['mensaje_alertify'] = "Las respuestas no coinciden. Inténtalo de nuevo.";
    $_SESSION['tipo_alertify'] = "error";

    // Recargar preguntas
    $sql_preg = "SELECT rs.pregunta_seguridad_id, ps.pregunta FROM respuestas_seguridad rs 
                 INNER JOIN preguntas_seguridad ps ON rs.pregunta_seguridad_id = ps.ID 
                 WHERE rs.usuario_id = ?";
    $stmt_preg = $conn->prepare($sql_preg);
    $stmt_preg->bind_param("i", $user_id);
    $stmt_preg->execute();
    $result_preg = $stmt_preg->get_result();

    $preguntas_usuario = [];
    while ($row = $result_preg->fetch_assoc()) {
        $preguntas_usuario[$row['pregunta_seguridad_id']] = $row['pregunta'];
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Verificar Preguntas</title>
        <link rel="stylesheet" href="/IPSPUPTM/assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="/IPSPUPTM/assets/css/inicio.css">
        
        <?php include __DIR__ . '/../config/alertify.php'; ?>
    </head>
    <body>
        
        <div class="min-vh-100 d-flex align-items-center justify-content-center">
            <?php include __DIR__ . '/formulario_seguridad.php'; ?>
        </div>

        <script src="/IPSPUPTM/assets/js/bootstrap.bundle.min.js"></script>
        <?php if (isset($_SESSION['mensaje_alertify'])): ?>
            <script>
                window.onload = function() {
                    alertify.<?php echo $_SESSION['tipo_alertify']; ?>("<?php echo $_SESSION['mensaje_alertify']; ?>");
                };
            </script>
            <?php 
                unset($_SESSION['mensaje_alertify']); 
                unset($_SESSION['tipo_alertify']); 
            ?>
        <?php endif; ?>
    </body>
    </html>
    <?php
}
?>