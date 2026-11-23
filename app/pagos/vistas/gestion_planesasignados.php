<?php
require_once 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
require_once 'C:/xampp/htdocs/IPSPUPTM/config/plan_helper.php';

$rowsPerPage = 15;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $rowsPerPage;

// Calcular total de páginas
$totalQuery = "SELECT COUNT(*) as total FROM contrato_plan";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRows = mysqli_fetch_assoc($totalResult)['total'] ?? 0;
$totalPages = (int) ceil($totalRows / $rowsPerPage);

$hoy = planHoy();
?>
<style>
.pagination .page-link {
    color: #062974;
    border-color: #dee2e6;
}
.pagination .page-link:hover {
    color: #002750;
    background-color: #e9ecef;
    border-color: #dee2e6;
}
.pagination .page-item.active .page-link {
    background-color: #062974;
    border-color: #062974;
    color: white;
}
.pagination .page-item.disabled .page-link {
    color: #6c757d;
}
.detalle-cuotas table {
    font-size: .82rem;
}
</style>
<div class="mt-3 m-3">
    <div class="row align-items-center mb-4">
        <!-- Botón Volver Atrás (Izquierda) -->
        <div class="col-auto col-md-3 text-start">
            <a href="/IPSPUPTM/home.php?vista=gestionplanes" class="btn" style="color: white; background-color: #002750; border: none; border-radius: 8px; padding: 8px 16px;"> 
                <i class="fas fa-arrow-left me-1"></i> Volver atrás
            </a>
        </div>
        
        <!-- Título Centrado (Centro) -->
        <div class="col col-md-6 text-center">
            <h1 class="fw-bold mb-0" style="color: #062974; font-size: 2.25rem;">Gestión de Planes Asignados</h1>
            <hr class="mx-auto mt-2 mb-0" style="width: 50px; height: 3px; background-color: #062974; opacity: 1;">
        </div>
        
        <!-- Espaciador derecho para mantener simetría -->
        <div class="col-auto col-md-3 text-end">
            <a href="/IPSPUPTM/home.php?vista=gestionpagoscontrato" class="btn btn-primary" style="border-radius: 8px; padding: 8px 16px;">
                <i class="fas fa-plus-circle me-1"></i> Registrar Pago
            </a>
        </div>
    </div>
    <br>
    <div class="table-responsive">
        <table class="table table-sm table-striped table-hover mx-auto align-middle" id="tablaContratos" width="100%"
            cellspacing="0">
            <thead class="table-dark">
                <tr>
                    <th>Afiliado</th>
                    <th>Plan</th>
                    <th class="text-end">Monto Total</th>
                    <th>Inicial (30 %)</th>
                    <th>Cuotas</th>
                    <th>Cobertura</th>
                    <th>Vigencia</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $sql = "SELECT cp.*, per.nombre, per.apellido, per.cedula, pl.nombre_plan
                            FROM contrato_plan cp
                            INNER JOIN persona per ON cp.id_persona = per.id_persona
                            INNER JOIN planes pl ON cp.ID_planes_contrato = pl.ID_planes
                            ORDER BY cp.anio DESC, cp.fecha_inicio DESC, per.nombre ASC
                            LIMIT $rowsPerPage OFFSET $offset";

                    $resultado = mysqli_query($conn, $sql);

                    if ($resultado && mysqli_num_rows($resultado) > 0) {
                        while ($row = mysqli_fetch_assoc($resultado)) {
                            $idContrato = (int) $row['ID_contrato'];
                            $resumen = contratoResumen($conn, $row);
                            $cobertura = contratoValidarCobertura($conn, (int) $row['id_persona']);

                            $estadoContrato = (string) ($row['estado_contrato'] ?? '');
                            $vigenteHoy = (bool) $cobertura['vigente'];

                            // Estado real: combina fechas, inicial y mora, no solo estado_contrato.
                            if ($estadoContrato === 'Inactivo') {
                                $badgeColor = 'bg-secondary';
                                $estadoTexto = 'Inactivo';
                            } elseif (!$resumen['inicial_completo']) {
                                $badgeColor = 'bg-warning text-dark';
                                $estadoTexto = 'Inicial pendiente';
                            } elseif (!$vigenteHoy) {
                                if ($hoy < $resumen['cobertura_inicio']) {
                                    $badgeColor = 'bg-info text-dark';
                                    $estadoTexto = 'Por iniciar';
                                } else {
                                    $badgeColor = 'bg-warning text-dark';
                                    $estadoTexto = 'Vencido';
                                }
                            } elseif ($resumen['en_mora']) {
                                $badgeColor = 'bg-danger';
                                $estadoTexto = 'En mora';
                            } else {
                                $badgeColor = 'bg-success';
                                $estadoTexto = 'Cobertura activa';
                            }

                            $inicialPct = $resumen['monto_inicial'] > 0
                                ? min(100, (int) round($resumen['inicial_pagado'] / $resumen['monto_inicial'] * 100))
                                : 0;
                    ?>
                <tr>
                    <td>
                        <span class="fw-bold"><?php echo htmlspecialchars(trim($row['nombre'] . ' ' . $row['apellido'])); ?></span><br>
                        <small class="text-muted">C.I: <?php echo htmlspecialchars($row['cedula']); ?></small>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['nombre_plan']); ?><br>
                        <small class="text-muted">Año <?php echo (int) $resumen['anio']; ?></small>
                    </td>
                    <td class="text-end fw-bold">$ <?php echo number_format($resumen['monto_total'], 2); ?></td>
                    <td style="min-width: 130px;">
                        <div class="progress" style="height: 7px;">
                            <div class="progress-bar bg-info" role="progressbar"
                                style="width: <?php echo $inicialPct; ?>%"
                                aria-valuenow="<?php echo $inicialPct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">
                            $ <?php echo number_format($resumen['inicial_pagado'], 2); ?>
                            / $ <?php echo number_format($resumen['monto_inicial'], 2); ?>
                        </small>
                    </td>
                    <td style="min-width: 110px;">
                        <span class="badge bg-secondary" title="Cuotas pagadas">
                            <?php echo (int) $resumen['cuotas_pagadas']; ?>/<?php echo (int) $resumen['cuotas_total']; ?>
                        </span>
                        <?php if ($resumen['cuotas_vencidas'] > 0): ?>
                            <br><small class="<?php echo $resumen['en_mora'] ? 'text-danger fw-bold' : 'text-warning'; ?>">
                                <?php echo (int) $resumen['cuotas_vencidas']; ?> vencida(s)
                            </small>
                        <?php endif; ?>
                    </td>
                    <td style="min-width: 130px;">
                        <small class="text-muted d-block">
                            Consumido: $ <?php echo number_format($resumen['consumido'], 2); ?>
                        </small>
                        <span class="fw-bold <?php echo $resumen['disponible'] > 0 ? 'text-success' : 'text-danger'; ?>">
                            $ <?php echo number_format($resumen['disponible'], 2); ?>
                        </span>
                        <small class="text-muted">de $ <?php echo number_format($resumen['cobertura'], 2); ?></small>
                    </td>
                    <td>
                        <small>Desde: <?php echo date('d/m/Y', strtotime($row['fecha_inicio'])); ?></small><br>
                        <small>Hasta: <?php echo date('d/m/Y', strtotime($row['fecha_fin'])); ?></small><br>
                        <small class="text-muted">Cobertura: <?php echo date('d/m/Y', strtotime($resumen['cobertura_inicio'])); ?></small>
                    </td>
                    <td>
                        <span class="badge <?php echo $badgeColor; ?>"><?php echo $estadoTexto; ?></span>
                        <?php if ($resumen['motivo'] !== 'Cobertura activa.'): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($resumen['motivo']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse"
                            data-bs-target="#cuotas-<?php echo $idContrato; ?>" aria-expanded="false"
                            title="Ver cuotas">
                            <i class="fas fa-list-ol"></i>
                        </button>
                        <a href="/IPSPUPTM/home.php?vista=gestionpagoscontrato" class="btn btn-success btn-sm"
                            title="Registrar pago">
                            <i class="fas fa-money-bill-wave"></i>
                        </a>
                    </td>
                </tr>
                <tr class="collapse detalle-cuotas" id="cuotas-<?php echo $idContrato; ?>">
                    <td colspan="9" class="bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Cuotas del contrato #<?php echo $idContrato; ?></strong>
                            <small class="text-muted">
                                Próxima cuota:
                                <?php if ($resumen['proxima_cuota']): ?>
                                    $ <?php echo number_format($resumen['proxima_cuota']['monto'], 2); ?>
                                    con vencimiento <?php echo date('d/m/Y', strtotime($resumen['proxima_cuota']['fecha_vencimiento'])); ?>
                                <?php else: ?>
                                    ninguna
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered bg-white mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>N°</th>
                                        <th>Vencimiento</th>
                                        <th class="text-end">Monto</th>
                                        <th class="text-end">Pagado</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resumen['cuotas'] as $cuota): ?>
                                        <?php
                                            $saldoCuota = round((float) $cuota['monto'] - (float) $cuota['monto_pagado'], 2);
                                            if ($saldoCuota <= 0.009) {
                                                $badgeCuota = '<span class="badge bg-success">Pagada</span>';
                                            } elseif ($cuota['fecha_vencimiento'] < $hoy) {
                                                $badgeCuota = '<span class="badge bg-danger">Vencida</span>';
                                            } else {
                                                $badgeCuota = '<span class="badge bg-secondary">Pendiente</span>';
                                            }
                                        ?>
                                    <tr>
                                        <td><?php echo (int) $cuota['numero_cuota']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($cuota['fecha_vencimiento'])); ?></td>
                                        <td class="text-end">$ <?php echo number_format($cuota['monto'], 2); ?></td>
                                        <td class="text-end">$ <?php echo number_format($cuota['monto_pagado'], 2); ?></td>
                                        <td><?php echo $badgeCuota; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($resumen['cuotas'])): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Sin cuotas generadas.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                <?php
                        }
                    } else {
                        echo '<tr><td colspan="9" class="text-center text-muted py-4">No hay contratos registrados.</td></tr>';
                    }
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Navegación de planes asignados" class="mt-4">
        <ul class="pagination justify-content-center pagination-sm">
            <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?vista=gestionplanesasignados&page=<?= $currentPage - 1 ?>">Anterior</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                <a class="page-link" href="?vista=gestionplanesasignados&page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?vista=gestionplanesasignados&page=<?= $currentPage + 1 ?>">Siguiente</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>
