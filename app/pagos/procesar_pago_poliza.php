<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_cita = $_POST['id_cita'];
    $id_contrato = $_POST['id_contrato'];
    $monto_pagado = $_POST['monto']; // Monto con descuento aplicado
    $monto_original = $_POST['monto_original'] ?? $monto_pagado;
    $id_descuento = $_POST['id_descuento'] ?? null;
    $usuario = $_SESSION['username'] ?? 'Sistema';

    // Calcular factor de descuento para repartirlo proporcionalmente en los exámenes
    $factor_descuento = ($monto_original > 0) ? ($monto_pagado / $monto_original) : 1;

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // 1. Obtener los exámenes de la cita para registrarlos en consumo_plan
        $sql_ex = "SELECT id_examen, precio_historico FROM citas_examenes WHERE id_cita = '$id_cita'";
        $res_ex = $conn->query($sql_ex);
        
        while ($row_ex = $res_ex->fetch_assoc()) {
            $id_examen = $row_ex['id_examen'];
            $precio_orig = $row_ex['precio_historico'];
            $precio_con_desc = round($precio_orig * $factor_descuento, 2);

            // Paciente (la cita ya guarda id_persona)
            $p_sql = "SELECT c.id_persona
                      FROM citas c
                      WHERE c.id_cita = '$id_cita'";
            $p_res = $conn->query($p_sql);
            $id_persona_paciente = $p_res->fetch_assoc()['id_persona'];

            $stmt_ins = $conn->prepare("INSERT INTO consumo_plan (ID_contrato_plan, id_persona, ID_examen_plan, monto_descontado, id_cita, fecha_consumo) 
                                        VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt_ins->bind_param("iiidi", $id_contrato, $id_persona_paciente, $id_examen, $precio_con_desc, $id_cita);
            $stmt_ins->execute();
        }

        // 2. Actualizar el estado de la cita
        $sql_upd = "UPDATE citas SET estado_pago = 'Deducida de Póliza' WHERE id_cita = '$id_cita'";
        $conn->query($sql_upd);

        // 3. Registrar en bitácora
        $accion = "Pago Cita con Póliza";
        $desc = "Cita #$id_cita pagada mediante descuento de póliza. Monto original: $monto_original $, Monto descontado: $monto_pagado $.";
        registrarenBitacora($conn, $usuario, $accion, $desc);

        $conn->commit();
        echo "<script>alert('Pago procesado correctamente con saldo de póliza.'); window.location.href='/IPSPUPTM/home.php?vista=gestionpagoscitas';</script>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
?>
