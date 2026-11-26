<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
// 1. Iniciar sesión y limpiar cualquier salida previa
if (session_status() === PHP_SESSION_NONE) { session_start(); }
ob_start(); 

// 2. Cabecera JSON
header('Content-Type: application/json');

// 3. Cargar archivos (Asegúrate de que estas rutas sean 100% reales)
require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id_cita    = isset($_POST['id_cita_pago']) ? intval($_POST['id_cita_pago']) : 0;
        $id_tipo    = isset($_POST['id_tipos_ext']) ? intval($_POST['id_tipos_ext']) : 0;
        $monto_base = isset($_POST['monto_base']) ? floatval($_POST['monto_base']) : 0;
        $tasa_bcv   = isset($_POST['tasa_bcv']) ? floatval($_POST['tasa_bcv']) : 0;
        $metodo     = isset($_POST['metodo_pago']) ? htmlspecialchars($_POST['metodo_pago']) : '';

        if ($id_cita === 0 || $monto_base <= 0) {
            throw new Exception("Datos de pago incompletos.");
        }

        if ($id_tipo === 0) {
            throw new Exception("Debe seleccionar el tipo de comunidad.");
        }

        // La categoría y el descuento se leen de la base de datos:
        // el monto final se calcula aquí y no se confía en el navegador.
        $stmt_tipo = $conn->prepare("SELECT nombre_tipo, descuento FROM tipos_externos WHERE id_tipos_ext = ?");
        $stmt_tipo->bind_param("i", $id_tipo);
        $stmt_tipo->execute();
        $res_tipo = $stmt_tipo->get_result();
        $tipo = $res_tipo ? $res_tipo->fetch_assoc() : null;
        $stmt_tipo->close();

        if (!$tipo) {
            throw new Exception("El tipo de comunidad seleccionado no existe.");
        }

        $descuento = (float) $tipo['descuento'];
        $monto_fin = round($monto_base * (1 - ($descuento / 100)), 2);
        $monto_bs  = round($monto_fin * $tasa_bcv, 2);

        $sql = "INSERT INTO pagos_externos
                    (id_cita, id_tipos_ext, monto_base, descuento_aplicado, monto_final, tasa_bcv, monto_bs, metodo_pago, fecha_pago)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiddddds", $id_cita, $id_tipo, $monto_base, $descuento, $monto_fin, $tasa_bcv, $monto_bs, $metodo);

        if ($stmt->execute()) {
            // ACTUALIZACIÓN: Cambiar el estado de la cita a 'Pagada'
            $stmt_upd = $conn->prepare("UPDATE citas SET estado_pago = 'Pagada' WHERE id_cita = ?");
            $stmt_upd->bind_param("i", $id_cita);
            $stmt_upd->execute();
            $stmt_upd->close();

            $usuario = $_SESSION['username'] ?? 'Sistema';
            $nombre_tipo = $tipo['nombre_tipo'];

            registrarenBitacora($conn, $usuario, "Pago Registrado", "Se procesó pago de cita #$id_cita por $monto_fin $ (categoría: $nombre_tipo, descuento: $descuento%).");

            ob_clean(); // Borramos cualquier eco previo por si acaso
            echo json_encode(['success' => true, 'message' => "¡Pago procesado exitosamente!"]);
        } else {
            throw new Exception("Error en la base de datos: " . $stmt->error);
        }
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Método no permitido"]);
}
exit; // Evita que se procese nada más
?>