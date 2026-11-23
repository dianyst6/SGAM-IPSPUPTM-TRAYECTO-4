<?php
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/plan_helper.php';

// --- LÓGICA DE PAGINACIÓN ---
$rowsPerPage = 10;
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage < 1) $currentPage = 1;
$offset = ($currentPage - 1) * $rowsPerPage;

// Citas por pagar de pacientes internos (afiliados o beneficiarios).
// La cobertura y el contrato se resuelven por fecha en PHP.
$sql_base = "
    SELECT
        c.id_cita,
        c.id_persona,
        p.cedula,
        CONCAT(p.nombre, ' ', p.apellido) AS nombre_paciente,
        CASE
            WHEN a.ID IS NOT NULL THEN 'Afiliado'
            WHEN b.id_beneficiario IS NOT NULL THEN 'Beneficiario'
            ELSE 'Comunidad UPTM'
        END AS tipo,
        c.fecha_cita,
        c.estado_pago,
        e.nombre_especialidad,
        e.descuento AS descuento_especialidad,
        (SELECT COALESCE(SUM(precio_historico), 0)
           FROM citas_examenes
          WHERE id_cita = c.id_cita) AS costo_total
    FROM citas c
    INNER JOIN persona p ON p.id_persona = c.id_persona
    LEFT JOIN afiliados a     ON a.id_persona = p.id_persona
    LEFT JOIN beneficiarios b ON b.id_persona = p.id_persona
    INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
    WHERE c.estado_pago = 'Por Pagar'
      AND c.estado != 'cancelada'
      AND (a.ID IS NOT NULL OR b.id_beneficiario IS NOT NULL)
";

// Obtener total de registros
$countResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM ($sql_base) AS t");
$totalRows = mysqli_fetch_assoc($countResult)['total'] ?? 0;
$totalPages = ceil($totalRows / $rowsPerPage);

// Obtener registros paginados
$sql_paginado = "$sql_base ORDER BY fecha_cita DESC LIMIT $rowsPerPage OFFSET $offset";
$res = mysqli_query($conn, $sql_paginado);
?>

<div class="card shadow-lg">
    <div class="card-body p-4"> 
        
        <div class="row align-items-center mb-4">
            <!-- Botón Volver Atrás (Izquierda) -->
            <div class="col-auto col-md-3 text-start">
                <button onclick="window.history.back();" class="btn" style="color: white; background-color: #002750; border: none; border-radius: 8px; padding: 8px 16px;"> 
                    <i class="fas fa-arrow-left me-1"></i> Volver atrás
                </button>
            </div>
            
            <!-- Título Centrado (Centro) -->
            <div class="col col-md-6 text-center">
                <h1 class="fw-bold mb-0" style="color: #062974; font-size: 2.25rem;">Cobro de Citas con Póliza</h1>
                <hr class="mx-auto mt-2 mb-0" style="width: 50px; height: 3px; background-color: #062974; opacity: 1;">
            </div>
            
            <!-- Botón Registrar Consumo (Derecha) -->
            <div class="col-auto col-md-3 text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalConsumoExterno" style="border-radius: 8px; padding: 8px 16px;">
                    <i class="fas fa-plus-circle me-1"></i> Registrar Consumo Externo
                </button>
            </div>
        </div>
        <h4 class="mb-3">Citas Pendientes de Cobro</h4>
        <div class="table-responsive mt-4">
            <table class="table table-striped table-hover shadow-sm">
                <thead class="table-dark">
                    <tr>
                        <th>Paciente</th>
                        <th>Tipo</th>
                        <th>Plan</th>
                        <th>Costo Cita</th>
                        <th>Saldo Póliza</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($res) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($res)):
                            $idPersona = (int) $row['id_persona'];
                            $fechaCitaDia = substr((string) $row['fecha_cita'], 0, 10);
                            $cobertura = contratoValidarCobertura($conn, $idPersona, $fechaCitaDia);
                            $resumen = $cobertura['resumen'] ?? [];
                            $contrato = $cobertura['contrato'] ?? null;

                            $coberturaOk = (bool) $cobertura['ok'];
                            $planNombre = $resumen['plan']['nombre_plan'] ?? 'Sin cobertura';
                            $anioPlan = $resumen['anio'] ?? '';
                            $saldoDisponible = $resumen['disponible'] ?? 0.0;
                            $costoCita = round((float) ($row['costo_total'] ?? 0), 2);
                            $cupo = ($coberturaOk && $contrato)
                                ? planValidarCupoCita(
                                    $conn,
                                    (int) $contrato['ID_contrato'],
                                    (int) $contrato['ID_planes_contrato'],
                                    (int) $row['id_cita']
                                )
                                : ['ok' => false, 'motivo' => ''];
                            $puedePagar = $coberturaOk && $costoCita > 0
                                && $saldoDisponible >= $costoCita && $cupo['ok'];
                            $motivo = $cobertura['motivo'];
                        ?>
                        <tr>
                            <td class="text-start">
                                <strong><?php echo htmlspecialchars($row['nombre_paciente']); ?></strong><br>
                                <small class="text-muted">C.I: <?php echo htmlspecialchars($row['cedula']); ?></small><br>
                                <small class="text-muted"><?php echo date('d/m/Y h:i A', strtotime($row['fecha_cita'])); ?></small>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo $row['tipo']; ?></span></td>
                            <td>
                                <?php echo htmlspecialchars($planNombre); ?>
                                <?php if ($anioPlan): ?>
                                    <br><small class="text-muted"><?php echo (int) $anioPlan; ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold text-danger">$<?php echo number_format($costoCita, 2); ?></td>
                            <td class="fw-bold <?php echo $puedePagar ? 'text-success' : 'text-danger'; ?>">
                                <?php if ($coberturaOk): ?>
                                    $<?php echo number_format($saldoDisponible, 2); ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$coberturaOk): ?>
                                    <span class="badge bg-warning text-dark">Sin cobertura</span>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($motivo); ?></small>
                                <?php elseif ($costoCita <= 0): ?>
                                    <span class="badge bg-warning text-dark">Sin exámenes</span>
                                <?php elseif (!$cupo['ok']): ?>
                                    <span class="badge bg-warning text-dark">Cupo agotado</span>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($cupo['motivo']); ?></small>
                                <?php elseif ($puedePagar): ?>
                                    <button type="button" class="btn btn-success btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalPagoPoliza"
                                            data-bs-idcita="<?php echo (int) $row['id_cita']; ?>"
                                            data-bs-idcontrato="<?php echo (int) $contrato['ID_contrato']; ?>"
                                            data-bs-monto="<?php echo $costoCita; ?>"
                                            data-bs-nombre="<?php echo htmlspecialchars($row['nombre_paciente']); ?>"
                                            data-bs-especialidad="<?php echo htmlspecialchars($row['nombre_especialidad']); ?>"
                                            data-bs-descuento="<?php echo (float) $row['descuento_especialidad']; ?>">
                                        <i class="fas fa-file-invoice-dollar me-1"></i> Descontar de Póliza
                                    </button>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Saldo Insuficiente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No hay citas pendientes de cobro para afiliados/beneficiarios.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Navegación de cobros" class="mt-4">
            <ul class="pagination justify-content-center pagination-sm">
                <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?vista=gestionpagoscitas&page=<?= $currentPage - 1 ?>">Anterior</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                    <a class="page-link" href="?vista=gestionpagoscitas&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?vista=gestionpagoscitas&page=<?= $currentPage + 1 ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

    </div>
</div>

<?php include 'C:/xampp/htdocs/IPSPUPTM/app/pagos/modales/modal_pago_poliza.php'; ?>
<?php include 'C:/xampp/htdocs/IPSPUPTM/app/pagos/modales/modal_consumo_externo.php'; ?>
