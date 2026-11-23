<?php
/**
 * config/plan_vista.php
 *
 * Renderizado del estado del plan de salud (cobertura, límites e historial).
 * Lo usan app/afiliados/consultar_consumo.php y
 * app/beneficiarios/consultar_consumo_beneficiario.php.
 *
 * Toda la lógica de negocio vive en plan_helper.php; aquí solo se presenta.
 */
require_once __DIR__ . '/plan_helper.php';

if (!function_exists('planVistaConsumo')) {
    /**
     * Devuelve el HTML con el resumen de cobertura, los límites del plan y,
     * opcionalmente, el historial de consumos del contrato familiar.
     *
     * @param mysqli $conn
     * @param int    $idPersona          Afiliado o beneficiario.
     * @param bool   $incluirHistorial   Mostrar la tabla de consumos.
     */
    function planVistaConsumo(mysqli $conn, int $idPersona, bool $incluirHistorial = true): string
    {
        $h = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
        $dinero = static fn($valor): string => number_format((float) $valor, 2);

        $idTitular = planPersonaTitular($conn, $idPersona);
        if ($idTitular === null) {
            return '<div class="alert alert-warning text-center">La persona no está registrada como afiliado ni como beneficiario.</div>';
        }

        $hoy = planHoy();
        $contrato = contratoVigente($conn, $idTitular, $hoy);
        $vigenteHoy = $contrato !== null;

        // Si hoy no hay contrato vigente, se muestra el más reciente para
        // informar su estado (por ejemplo, una inscripción que inicia el
        // próximo año) en lugar de ocultar la información.
        if ($contrato === null) {
            $stmt = $conn->prepare(
                "SELECT * FROM contrato_plan
                 WHERE id_persona = ?
                 ORDER BY anio DESC, fecha_inicio DESC
                 LIMIT 1"
            );
            if ($stmt) {
                $stmt->bind_param('i', $idTitular);
                $stmt->execute();
                $contrato = $stmt->get_result()->fetch_assoc() ?: null;
                $stmt->close();
            }
        }

        if ($contrato === null) {
            return '<div class="alert alert-warning text-center">No se encontró un contrato de salud registrado para este núcleo familiar.</div>';
        }

        $resumen   = contratoResumen($conn, $contrato);
        $plan      = $resumen['plan'] ?? [];
        $idContrato = (int) $contrato['ID_contrato'];
        $idPlan     = (int) $contrato['ID_planes_contrato'];

        $nombrePlan = $plan['nombre_plan'] ?? 'Sin plan';
        $estadoContrato = (string) ($contrato['estado_contrato'] ?? '');
        $coberturaActiva = $vigenteHoy && $resumen['cobertura_activa'];

        $esSolvente = !empty($resumen['solvente']);

        if ($estadoContrato === 'Inactivo') {
            $estadoBadge = '<span class="badge bg-secondary">Inactivo</span>';
        } elseif (!$resumen['inicial_completo']) {
            $estadoBadge = '<span class="badge bg-warning text-dark">Inicial pendiente</span>';
        } elseif (!$vigenteHoy) {
            $estadoBadge = '<span class="badge bg-secondary">No vigente</span>';
        } elseif ($resumen['en_mora']) {
            $estadoBadge = '<span class="badge bg-danger">Cobertura restringida</span>';
        } elseif ($esSolvente) {
            $estadoBadge = '<span class="badge bg-success"><i class="fas fa-circle-check me-1"></i>Cobertura activa · Solvente</span>';
        } else {
            $estadoBadge = '<span class="badge bg-success">Cobertura activa</span>';
        }

        if ($esSolvente && !$vigenteHoy) {
            $estadoBadge .= ' <span class="badge bg-success"><i class="fas fa-circle-check me-1"></i>Solvente</span>';
        }

        $inicialPct = $resumen['monto_inicial'] > 0
            ? min(100, (int) round($resumen['inicial_pagado'] / $resumen['monto_inicial'] * 100))
            : 0;

        $salida = '<input type="hidden" id="nombre_plan_db" value="' . $h($nombrePlan) . '">';

        // --- Resumen de cobertura ---
        $salida .= '
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card bg-primary text-white text-center p-3 shadow-sm h-100">
                    <h6 class="mb-1">Monto de Póliza</h6>
                    <h3 class="fw-bold mb-0">$' . $dinero($resumen['cobertura']) . '</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark text-center p-3 shadow-sm h-100">
                    <h6 class="mb-1">Consumo del Seguro</h6>
                    <h3 class="fw-bold mb-0">$' . $dinero($resumen['consumido']) . '</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white text-center p-3 shadow-sm h-100">
                    <h6 class="mb-1">Saldo Disponible</h6>
                    <h3 class="fw-bold mb-0">$' . $dinero($resumen['disponible']) . '</h3>
                </div>
            </div>
        </div>';

        // --- Ficha del contrato ---
        $salida .= '
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-3">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Plan contratado</small>
                        <span class="fw-bold">' . $h($nombrePlan) . ' (' . (int) $resumen['anio'] . ')</span><br>
                        ' . $estadoBadge . '
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Vigencia</small>
                        ' . date('d/m/Y', strtotime($contrato['fecha_inicio']))
                        . ' al ' . date('d/m/Y', strtotime($contrato['fecha_fin'])) . '<br>
                        <small class="text-muted">Cobertura desde: '
                        . date('d/m/Y', strtotime($resumen['cobertura_inicio'])) . '</small>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Pago inicial (30 %)</small>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-info" role="progressbar"
                                 style="width: ' . $inicialPct . '%;"
                                 aria-valuenow="' . $inicialPct . '" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">$' . $dinero($resumen['inicial_pagado'])
                        . ' de $' . $dinero($resumen['monto_inicial']) . '</small>
                    </div>
                </div>
                <hr class="my-3">
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Cuotas pagadas</small>
                        <span class="fw-bold">' . (int) $resumen['cuotas_pagadas'] . ' / ' . (int) $resumen['cuotas_total'] . '</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Cuotas vencidas</small>
                        <span class="fw-bold ' . ($resumen['en_mora'] ? 'text-danger' : 'text-success') . '">'
                        . (int) $resumen['cuotas_vencidas'] . '</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Deuda vencida</small>
                        <span class="fw-bold">$' . $dinero($resumen['deuda_vencida']) . '</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">' . ($esSolvente ? 'Estado de pago' : 'Próxima cuota') . '</small>
                        <span class="fw-bold' . ($esSolvente ? ' text-success' : '') . '">'
                        . ($esSolvente
                            ? 'Solvente'
                            : ($resumen['proxima_cuota']
                                ? '$' . $dinero($resumen['proxima_cuota']['monto'])
                                  . ' el ' . date('d/m/Y', strtotime($resumen['proxima_cuota']['fecha_vencimiento']))
                                : '—'))
                        . '</span>
                    </div>
                </div>
                ' . ($esSolvente
                    ? '<div class="alert alert-success mt-3 mb-0 py-2"><i class="fas fa-circle-check me-1"></i>'
                        . '<strong>Plan totalmente pagado.</strong> Contrato solvente: $'
                        . $dinero($resumen['pagado_total']) . ' de $' . $dinero($resumen['monto_total'])
                        . ' pagados, sin cuotas pendientes.</div>'
                    : '') . '
                ' . ($resumen['motivo'] !== 'Cobertura activa.'
                    ? '<div class="alert alert-' . ($coberturaActiva ? 'info' : 'warning') . ' mt-3 mb-0 py-2"><i class="fas fa-info-circle me-1"></i>'
                        . $h($resumen['motivo']) . '</div>'
                    : '') . '
            </div>
        </div>';

        // --- Límites del plan ---
        $sql = "SELECT comp.ID_componenteplan,
                       comp.ID_examen_componentes,
                       comp.id_categoria_componente,
                       comp.cantidad_maxima,
                       comp.monto_maximo,
                       ex.nombre_examen,
                       cat.nombre_categoria
                FROM componentes_planes comp
                LEFT JOIN examenes ex ON ex.ID_examen = comp.ID_examen_componentes
                LEFT JOIN categorias_examenes cat ON cat.id_categoria = comp.id_categoria_componente
                WHERE comp.ID_planes_componentes = ?
                ORDER BY (comp.id_categoria_componente IS NULL) ASC,
                         cat.nombre_categoria ASC,
                         ex.nombre_examen ASC";

        $filas = [];
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $idPlan);
            $stmt->execute();
            $resultado = $stmt->get_result();
            while ($fila = $resultado->fetch_assoc()) {
                $filas[] = $fila;
            }
            $stmt->close();
        }

        if ($filas === []) {
            $salida .= '<div class="alert alert-info text-center">El plan no tiene componentes de cobertura configurados.</div>';
        } else {
            $salida .= '
            <div class="table-responsive">
                <h5 class="fw-bold mb-3"><i class="fas fa-list-ul me-2"></i>Límites de Cobertura</h5>
                <p class="text-muted small mb-2">
                    <strong>Cantidad:</strong> servicios dentro de la institución (sin límite si no tiene cupo).
                    <strong>Monto:</strong> consumos externos contra el tope de la categoría.
                </p>
                <table class="table table-hover table-bordered border-dark align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Servicio / Categoría</th>
                            <th>Límite</th>
                            <th>Consumido</th>
                            <th>Disponible</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($filas as $fila) {
                if ($fila['ID_examen_componentes'] !== null) {
                    $tipoItem = 'Examen';
                    $nombreItem = $fila['nombre_examen'] ?? 'Examen';
                    $consumoCantidad = planConsumoExamen($conn, $idContrato, (int) $fila['ID_examen_componentes']);
                    $consumoMonto = 0.0;

                    $stmtMonto = $conn->prepare(
                        "SELECT COALESCE(SUM(monto_descontado), 0) AS total
                         FROM consumo_plan
                         WHERE ID_contrato_plan = ? AND ID_examen_plan = ?"
                    );
                    if ($stmtMonto) {
                        $idExamen = (int) $fila['ID_examen_componentes'];
                        $stmtMonto->bind_param('ii', $idContrato, $idExamen);
                        $stmtMonto->execute();
                        $consumoMonto = (float) ($stmtMonto->get_result()->fetch_assoc()['total'] ?? 0);
                        $stmtMonto->close();
                    }
                } else {
                    $tipoItem = 'Categoría';
                    $nombreItem = $fila['nombre_categoria'] ?? 'Categoría';
                    // Cantidad = servicios dentro de la institución.
                    // Monto = consumos externos contra el tope de la categoría.
                    $consumoInterno = planConsumoCategoria(
                        $conn,
                        $idContrato,
                        (int) $fila['id_categoria_componente'],
                        'interno'
                    );
                    $consumoExterno = planConsumoCategoria(
                        $conn,
                        $idContrato,
                        (int) $fila['id_categoria_componente'],
                        'externo'
                    );
                    $consumoCantidad = (int) $consumoInterno['cantidad'];
                    $consumoMonto = (float) $consumoExterno['monto'];
                }

                $limiteCantidad = $fila['cantidad_maxima'] !== null ? (int) $fila['cantidad_maxima'] : null;
                $limiteMonto = $fila['monto_maximo'] !== null ? (float) $fila['monto_maximo'] : null;

                if ($limiteCantidad === null) {
                    $limiteCantidadTxt = '<span class="badge bg-secondary">Sin límite</span>';
                    $disponibleCantidadTxt = '<span class="badge bg-success">Sin límite</span>';
                    $claseCantidad = 'text-success fw-bold';
                } else {
                    $disponibleCantidad = max(0, $limiteCantidad - $consumoCantidad);
                    $limiteCantidadTxt = (string) $limiteCantidad;
                    $disponibleCantidadTxt = (string) $disponibleCantidad;
                    $claseCantidad = $disponibleCantidad <= 0 ? 'table-danger text-danger fw-bold' : 'text-success fw-bold';
                }

                if ($tipoItem === 'Examen' && ($limiteMonto === null || $limiteMonto <= 0)) {
                    $limiteMontoTxt = '—';
                    $disponibleMontoTxt = '—';
                    $claseMonto = 'text-muted';
                } else {
                    $limiteMonto = $limiteMonto ?? 0.0;
                    $disponibleMonto = max(0, round($limiteMonto - $consumoMonto, 2));
                    $limiteMontoTxt = '$' . $dinero($limiteMonto);
                    $disponibleMontoTxt = '$' . $dinero($disponibleMonto);
                    $claseMonto = $disponibleMonto <= 0 ? 'table-danger text-danger fw-bold' : 'text-success fw-bold';
                }

                $badgeTipo = $tipoItem === 'Examen'
                    ? '<span class="badge bg-info text-dark">Examen</span>'
                    : '<span class="badge bg-primary">Categoría</span>';

                $salida .= '<tr>
                    <td><div class="fw-bold">' . $h($nombreItem) . '</div><small>' . $badgeTipo . '</small></td>
                    <td class="text-center">
                        <div><small class="text-muted">Cant.:</small> ' . $limiteCantidadTxt . '</div>
                        <div><small class="text-muted">Monto:</small> ' . $limiteMontoTxt . '</div>
                    </td>
                    <td class="text-center">
                        <div><small class="text-muted">Cant.:</small> ' . $consumoCantidad . '</div>
                        <div><small class="text-muted">Monto:</small> $' . $dinero($consumoMonto) . '</div>
                    </td>
                    <td class="text-center ' . $claseCantidad . '">
                        <div>' . $disponibleCantidadTxt . '</div>
                        <div class="' . $claseMonto . '">' . $disponibleMontoTxt . '</div>
                    </td>
                </tr>';
            }

            $salida .= '</tbody></table></div>';
        }

        // --- Historial de consumos ---
        if ($incluirHistorial) {
            $salida .= '
            <div class="table-responsive mt-5">
                <h5 class="fw-bold mb-3"><i class="fas fa-file-invoice-dollar me-2"></i>Historial de Gastos del Plan</h5>
                <strong class="text-danger ps-2 mb-2 d-block">* Los montos a continuación reflejan lo que se ha descontado del saldo de cobertura.</strong>
                <table class="table table-hover table-striped border">
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

            $stmtHist = $conn->prepare(
                "SELECT per.nombre, per.apellido, per.cedula,
                        cp.ID_examen_plan,
                        COALESCE(ex.nombre_examen, cp.nombre_estudio_externo, cat.nombre_categoria, 'Servicio / Consulta') AS nombre_servicio,
                        cp.monto_descontado,
                        cp.fecha_consumo
                 FROM consumo_plan cp
                 LEFT JOIN examenes ex ON ex.ID_examen = cp.ID_examen_plan
                 LEFT JOIN categorias_examenes cat ON cat.id_categoria = cp.id_categoria_externa
                 LEFT JOIN persona per ON per.id_persona = cp.id_persona
                 WHERE cp.ID_contrato_plan = ?
                 ORDER BY cp.fecha_consumo DESC, cp.ID_consumo DESC"
            );

            $hayHistorial = false;
            if ($stmtHist) {
                $stmtHist->bind_param('i', $idContrato);
                $stmtHist->execute();
                $resHist = $stmtHist->get_result();

                while ($filaHist = $resHist->fetch_assoc()) {
                    $hayHistorial = true;
                    $fechaHist = $filaHist['fecha_consumo']
                        ? date('d-m-Y h:i A', strtotime($filaHist['fecha_consumo']))
                        : '—';
                    $nombrePaciente = trim(($filaHist['nombre'] ?? '') . ' ' . ($filaHist['apellido'] ?? ''));
                    $badgeTipo = !empty($filaHist['ID_examen_plan'])
                        ? '<span class="badge bg-primary">Institución</span>'
                        : '<span class="badge bg-warning text-dark">Externo</span>';

                    $salida .= '<tr>
                        <td class="text-center text-nowrap">' . $h($fechaHist) . '</td>
                        <td>' . $h($nombrePaciente ?: 'N/D')
                        . '<br><small class="text-muted">V-' . $h($filaHist['cedula'] ?? '') . '</small></td>
                        <td>' . $h($filaHist['nombre_servicio'] ?? 'Servicio') . '</td>
                        <td class="text-center">' . $badgeTipo . '</td>
                        <td class="text-center fw-bold text-danger">-$' . $dinero($filaHist['monto_descontado']) . '</td>
                    </tr>';
                }

                $stmtHist->close();
            }

            if (!$hayHistorial) {
                $salida .= '<tr><td colspan="5" class="text-center text-muted py-3">No hay registros de consumos o cobros de póliza para este plan aún.</td></tr>';
            }

            $salida .= '</tbody></table></div>';
        }

        return $salida;
    }
}
