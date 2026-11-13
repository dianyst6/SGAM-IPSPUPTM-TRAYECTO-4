<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

if (isset($_POST['id_contrato'])) {
    $id = $_POST['id_contrato'];

    // 1. Obtener el monto total del contrato y sus fechas
    $sql_total = "SELECT monto_total, fecha_inicio, fecha_fin FROM contrato_plan WHERE ID_contrato = '$id'";
    $res_total = mysqli_query($conn, $sql_total);
    $data_total = mysqli_fetch_assoc($res_total);
    $monto_total = $data_total['monto_total'] ?? 0;
    $fecha_inicio = $data_total['fecha_inicio'] ?? date('Y-m-d');
    $fecha_fin = $data_total['fecha_fin'] ?? date('Y-m-d');

    // 2. Calcular el 30% requerido como pago inicial
    $monto_inicial_requerido = $monto_total * 0.30;

    // 3. Sumar todos los pagos con tipo_pago = 'Pago Inicial'
    $sql_inicial = "SELECT COALESCE(SUM(monto_cuota), 0) as total_inicial 
                    FROM pagos_contrato 
                    WHERE ID_contrato = '$id' AND tipo_pago = 'Pago Inicial'";
    $res_inicial = mysqli_query($conn, $sql_inicial);
    $data_inicial = mysqli_fetch_assoc($res_inicial);
    $total_pagado_inicial = $data_inicial['total_inicial'];

    // 4. ¿Ya se completó el pago inicial?
    $pago_inicial_completo = ($total_pagado_inicial >= $monto_inicial_requerido);

    // 5. Saldo del pago inicial pendiente
    $saldo_inicial_pendiente = max(0, $monto_inicial_requerido - $total_pagado_inicial);

    // 6. Obtener la suma de cuotas normales y la última cuota registrada
    $sql_pagos = "SELECT COALESCE(SUM(monto_cuota), 0) as total_pagado, 
                         COALESCE(MAX(numero_cuota), 0) as ultima_cuota 
                  FROM pagos_contrato 
                  WHERE ID_contrato = '$id' AND tipo_pago = 'Cuota'";
    $res_pagos = mysqli_query($conn, $sql_pagos);
    $data_pagos = mysqli_fetch_assoc($res_pagos);
    
    $total_pagado_cuotas = $data_pagos['total_pagado'];
    $ultima_cuota = $data_pagos['ultima_cuota'];

    // 7. Saldo total pendiente (monto_total - todo lo pagado)
    $saldo_pendiente = $monto_total - $total_pagado_inicial - $total_pagado_cuotas;
    $siguiente_cuota = $ultima_cuota + 1;

    // 8. Calcular meses y cuota fija (a partir de 3 meses después del inicio del contrato)
    $date_start_grace = new DateTime($fecha_inicio);
    $date_start_grace->modify('+3 months');
    $date_end = new DateTime($fecha_fin);

    if ($date_start_grace >= $date_end) {
        $total_meses = 1; // Fallback por seguridad
    } else {
        $interval = $date_start_grace->diff($date_end);
        $total_meses = ($interval->y * 12) + $interval->m;
        if ($total_meses <= 0) $total_meses = 1;
    }
    
    $monto_restante_base = $monto_total - $monto_inicial_requerido;
    $cuota_fija = 0;
    if ($total_meses > 0) {
        $cuota_fija = $monto_restante_base / $total_meses;
    }

    // 9. Devolvemos toda la info como JSON
    echo json_encode([
        'saldo'                   => number_format($saldo_pendiente, 2, '.', ''),
        'proxima_cuota'           => $siguiente_cuota,
        'pago_inicial_requerido'  => number_format($monto_inicial_requerido, 2, '.', ''),
        'pago_inicial_pagado'     => number_format($total_pagado_inicial, 2, '.', ''),
        'pago_inicial_pendiente'  => number_format($saldo_inicial_pendiente, 2, '.', ''),
        'pago_inicial_completo'   => $pago_inicial_completo,
        'total_meses'             => $total_meses,
        'cuota_fija'              => number_format($cuota_fija, 2, '.', ''),
        'ultima_cuota'            => $ultima_cuota
    ]);
}
?>