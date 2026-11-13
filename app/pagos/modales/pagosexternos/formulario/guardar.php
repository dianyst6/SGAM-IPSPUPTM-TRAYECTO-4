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
        $monto_base = isset($_POST['monto_base']) ? floatval($_POST['monto_base']) : 0;
        $monto_fin  = isset($_POST['monto_pago']) ? floatval($_POST['monto_pago']) : 0;
        $metodo     = isset($_POST['metodo_pago']) ? htmlspecialchars($_POST['metodo_pago']) : '';

        if ($id_cita === 0 || $monto_fin === 0) {
            throw new Exception("Datos de pago incompletos.");
        }

        // INSERT actualizado con los nuevos campos
        $sql = "INSERT INTO pagos_externos (id_cita, monto_base, monto_final, metodo_pago, fecha_pago) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idds", $id_cita, $monto_base, $monto_fin, $metodo);

        if ($stmt->execute()) {
            // ACTUALIZACIÓN: Cambiar el estado de la cita a 'Pagada'
            $stmt_upd = $conn->prepare("UPDATE citas SET estado_pago = 'Pagada' WHERE id_cita = ?");
            $stmt_upd->bind_param("i", $id_cita);
            $stmt_upd->execute();
            $stmt_upd->close();

            $usuario = $_SESSION['username'] ?? 'Sistema';
            
           
            registrarenBitacora($conn, $usuario, "Pago Registrado", "Se procesó pago de cita #$id_cita por $monto_fin $.");
            
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