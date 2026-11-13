<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/persona_helper.php';

if (isset($_GET['cedula'])) {
    $cedula = mysqli_real_escape_string($conn, $_GET['cedula']);

    // La cédula es la entrada; se resuelve el id_persona interno.
    $id_persona = buscarIdPersonaPorCedula($conn, $cedula);

    if ($id_persona === null) {
        echo '<div class="alert alert-warning text-center">No se encontró contrato activo para este núcleo familiar.</div>';
        $conn->close();
        exit();
    }

    // Consulta que suma el consumo de todo el núcleo familiar (Tanto por examen específico como por categoría)
    $sql = "SELECT 
                p.nombre_plan,
                p.monto_cobertura,
                cp.ID_contrato AS ID_contrato_parent,
                COALESCE(ex.nombre_examen, cat.nombre_categoria) AS nombre_item,
                cp_comp.cantidad_maxima,
                (
                    SELECT COUNT(*)
                    FROM citas_examenes ce
                    INNER JOIN citas c ON ce.id_cita = c.id_cita
                    INNER JOIN examenes e_inner ON ce.id_examen = e_inner.ID_examen
                    WHERE (
                        (cp_comp.ID_examen_componentes IS NOT NULL AND ce.id_examen = cp_comp.ID_examen_componentes)
                        OR
                        (cp_comp.id_categoria_componente IS NOT NULL AND e_inner.id_categoria = cp_comp.id_categoria_componente)
                    )
                    AND c.estado_pago = 'Deducida de Póliza'
                    AND c.id_persona IN (
                        SELECT a2.id_persona FROM afiliados a2 WHERE a2.ID = a.ID
                        UNION
                        SELECT b2.id_persona FROM beneficiarios b2 WHERE b2.id_afiliado = a.ID
                    )
                ) AS consumo_total_grupo
            FROM afiliados a
            INNER JOIN contrato_plan cp ON a.id_persona = cp.id_persona
            INNER JOIN planes p ON cp.ID_planes_contrato = p.ID_planes
            INNER JOIN componentes_planes cp_comp ON p.ID_planes = cp_comp.ID_planes_componentes
            LEFT JOIN examenes ex ON cp_comp.ID_examen_componentes = ex.ID_examen
            LEFT JOIN categorias_examenes cat ON cp_comp.id_categoria_componente = cat.id_categoria
            WHERE a.id_persona = ? AND cp.estado_contrato = 'Activo'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_persona);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $id_contrato = $data[0]['ID_contrato_parent'];
        $cobertura_total = $data[0]['monto_cobertura'];
        
        // Calcular el consumo acumulado en dinero
        $sql_dinero = "SELECT SUM(monto_descontado) as total_gastado FROM consumo_plan WHERE ID_contrato_plan = ?";
        $stmt_dinero = $conn->prepare($sql_dinero);
        $stmt_dinero->bind_param("i", $id_contrato);
        $stmt_dinero->execute();
        $res_dinero = $stmt_dinero->get_result();
        $total_gastado = $res_dinero->fetch_assoc()['total_gastado'] ?? 0;
        $saldo_disponible = $cobertura_total - $total_gastado;

        echo '<input type="hidden" id="nombre_plan_db" value="' . $data[0]['nombre_plan'] . '">';

        // DISEÑO DEL RESUMEN DE COBERTURA
        echo '
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white text-center p-3 shadow-sm">
                    <h6>Monto de Póliza</h6>
                    <h3 class="fw-bold">$' . number_format($cobertura_total, 2) . '</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark text-center p-3 shadow-sm">
                    <h6>Consumo de Seguro</h6>
                    <h3 class="fw-bold">$' . number_format($total_gastado, 2) . '</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white text-center p-3 shadow-sm">
                    <h6>Saldo Disponible</h6>
                    <h3 class="fw-bold">$' . number_format($saldo_disponible, 2) . '</h3>
                </div>
            </div>
        </div>';

        echo '<div class="table-responsive">
                <h5 class="fw-bold mb-3"><i class="fas fa-list-ul me-2"></i>Límites de Exámenes por Cantidad</h5>
                <table class="table table-hover table-bordered border-dark">
                    <thead class="table-dark">
                        <tr>
                            <th>Examen Incluido</th>
                            <th>Límite (Familiar)</th>
                            <th>Consumo Grupo</th>
                            <th>Disponibles</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($data as $row) {
            $cantidad_limite = $row['cantidad_maxima'];
            $consumo = $row['consumo_total_grupo'];
            
            // Si el límite es NULL o vacío, se considera ILIMITADO
            if (is_null($cantidad_limite) || $cantidad_limite === "" || $cantidad_limite === "NULL") {
                $limite_display = '<span class="badge bg-secondary">Ilimitado</span>';
                $disponibles_display = '<span class="badge bg-success">Ilimitado</span>';
                $clase = 'text-success fw-bold';
            } else {
                $disponibles = intval($cantidad_limite) - $consumo;
                $limite_display = $cantidad_limite;
                $disponibles_display = $disponibles;
                $clase = ($disponibles <= 0) ? 'table-danger text-danger fw-bold' : 'text-success fw-bold';
            }

            echo "<tr>
                    <td>{$row['nombre_item']}</td>
                    <td class='text-center'>{$limite_display}</td>
                    <td class='text-center'>{$consumo}</td>
                    <td class='text-center {$clase}'>{$disponibles_display}</td>
                  </tr>";
        }
        echo '</tbody></table></div>';
        
        // SEGUNDA TABLA: HISTORIAL DE CONSUMOS (EXTERNOS/PÓLIZA)
        $sql_historial = "SELECT 
                            p.nombre AS nombre_per, p.apellido AS apellido_per, p.cedula,
                            c.ID_examen_plan,
                            COALESCE(e.nombre_examen, c.nombre_estudio_externo, 'Servicio/Consulta') AS nombre_servicio,
                            c.monto_descontado,
                            c.fecha_consumo
                          FROM consumo_plan c
                          LEFT JOIN examenes e ON c.ID_examen_plan = e.ID_examen
                          LEFT JOIN persona p ON c.id_persona = p.id_persona
                          WHERE c.ID_contrato_plan = ? 
                          ORDER BY c.fecha_consumo DESC";
                          
        $stmt_hist = $conn->prepare($sql_historial);
        $stmt_hist->bind_param("i", $id_contrato);
        $stmt_hist->execute();
        $res_hist = $stmt_hist->get_result();
        
        echo '<div class="table-responsive mt-5">
                <h5 class="fw-bold mb-3"><i class="fas fa-file-invoice-dollar me-2"></i>Historial de Gastos del Plan</h5>
                <table class="table table-hover table-striped border">
                    <strong class="text-danger ps-2 mb-2 d-block">* Los montos a continuación reflejan lo que se ha ido descontando del saldo de Cobertura.</strong>
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Paciente (C.I.)</th>
                            <th>Servicio / Examen</th>
                            <th>Tipo</th>
                            <th>Monto Descontado ($)</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        if ($res_hist->num_rows > 0) {
            while ($row_hist = $res_hist->fetch_assoc()) {
                $fecha_formateada = date('d-m-Y h:i A', strtotime($row_hist['fecha_consumo']));
                $nombre_completo = "{$row_hist['nombre_per']} {$row_hist['apellido_per']}";
                $monto_desc = number_format($row_hist['monto_descontado'], 2);
                
                // Determinar si fue en la institución o externo
                if (!empty($row_hist['ID_examen_plan'])) {
                    $badge_tipo = '<span class="badge bg-primary">Institución</span>';
                } else {
                    $badge_tipo = '<span class="badge bg-warning text-dark">Externo</span>';
                }
                
                echo "<tr>
                        <td class='text-center'>{$fecha_formateada}</td>
                        <td>{$nombre_completo} <br><small class='text-muted'>V-{$row_hist['cedula']}</small></td>
                        <td>{$row_hist['nombre_servicio']}</td>
                        <td class='text-center'>{$badge_tipo}</td>
                        <td class='text-center fw-bold text-danger'>-{$monto_desc} $</td>
                      </tr>";
            }
        } else {
            echo '<tr><td colspan="5" class="text-center text-muted py-3">No hay registros de consumos o cobros de póliza para este plan aún.</td></tr>';
        }
        
        echo '</tbody></table></div>';
        $stmt_hist->close();
    } else {
        echo '<div class="alert alert-warning text-center">No se encontró contrato activo para este núcleo familiar.</div>';
    }
    $stmt->close();
}
$conn->close();
?>