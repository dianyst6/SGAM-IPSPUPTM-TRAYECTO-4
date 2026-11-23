<?php
/**
 * config/auth_guard.php
 *
 * Guard central de autenticación y autorización para endpoints.
 *
 * Uso al inicio de cada endpoint:
 *   require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
 *   auth_guard([1, 2]); // roles permitidos: 1=admin, 2=usuario, 3=médico
 *
 * Comportamiento:
 * - Inicia la sesión si no está activa.
 * - Exige user_id en sesión; sin sesión redirige al login (GET normal)
 *   o responde 401 (AJAX/POST).
 * - Verifica en la base de datos que la cuenta exista y obtiene su rol real.
 * - Responde 403 si el rol no está autorizado.
 */

if (!function_exists('auth_guard')) {
    function auth_guard(array $rolesPermitidos = [1, 2, 3]): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $esAjax = (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT'])
                && stripos((string) $_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        );

        $fallar = static function (int $codigo, string $mensaje) use ($esAjax): void {
            http_response_code($codigo);
            header('X-Content-Type-Options: nosniff');

            if ($esAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(
                    ['success' => false, 'error' => $mensaje],
                    JSON_UNESCAPED_UNICODE
                );
            } else {
                header('Content-Type: text/plain; charset=utf-8');
                echo $mensaje;
            }
            exit;
        };

        if (empty($_SESSION['user_id'])) {
            if (!$esAjax && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                header('Location: /IPSPUPTM/index.php');
                exit;
            }
            $fallar(401, 'Sesión no válida o expirada. Inicie sesión nuevamente.');
        }

        $usuarioId = (int) $_SESSION['user_id'];

        static $rolUsuario = null;
        if ($rolUsuario === null) {
            // database.php puede haberse cargado antes en la página; en ese caso
            // require_once no vuelve a ejecutarse y $conn vive en el ámbito global.
            global $conn;
            require_once __DIR__ . '/database.php';

            $stmt = $conn->prepare('SELECT role_id FROM usuarios WHERE id = ? LIMIT 1');
            if (!$stmt) {
                $fallar(500, 'No se pudo verificar la sesión.');
            }

            $stmt->bind_param('i', $usuarioId);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$fila) {
                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(
                        session_name(),
                        '',
                        time() - 42000,
                        $params['path'],
                        $params['domain'],
                        $params['secure'],
                        $params['httponly']
                    );
                }
                session_destroy();
                $fallar(401, 'La cuenta ya no existe. Inicie sesión nuevamente.');
            }

            $rolUsuario = (int) $fila['role_id'];
            $_SESSION['role_id'] = $rolUsuario;
        }

        if (!in_array($rolUsuario, $rolesPermitidos, true)) {
            $fallar(403, 'No tiene permisos para realizar esta acción.');
        }
    }
}
