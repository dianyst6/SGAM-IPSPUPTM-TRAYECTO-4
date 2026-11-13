<?php
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

// --- LÓGICA DE PAGINACIÓN ---
$rowsPerPage = 10;
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage < 1) $currentPage = 1;
$offset = ($currentPage - 1) * $rowsPerPage;

// SQL BASE: todas las citas con póliza (afiliado o beneficiario), con su tipo y contrato.
$sql_base = "
    SELECT 
        c.id_cita, 
        p.cedula,
        CONCAT(p.nombre, ' ', p.apellido) AS nombre_paciente,
        CASE 
            WHEN a.ID IS NOT NULL THEN 'Afiliado'
            WHEN b.id_beneficiario IS NOT NULL THEN 'Beneficiario'
            ELSE 'Comunidad UPTM'
        END AS tipo,
        pl.nombre_plan,
        pl.monto_cobertura,
        cp_con.ID_contrato,
        c.fecha_cita,
        c.estado_pago,
        e.nombre_especialidad,
        e.descuento AS descuento_especialidad,
        (SELECT SUM(precio_historico) FROM citas_examenes WHERE id_cita = c.id_cita) AS costo_total
    FROM citas c
    INNER JOIN persona p ON p.id_persona = c.id_persona
    LEFT JOIN afiliados a     ON a.id_persona = p.id_persona
    LEFT JOIN beneficiarios b ON b.id_persona = p.id_persona
    INNER JOIN afiliados a_tit ON a_tit.ID = COALESCE(a.ID, b.id_afiliado)
    INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
    INNER JOIN contrato_plan cp_con ON a_tit.id_persona = cp_con.id_persona
    INNER JOIN planes pl ON cp_con.ID_planes_contrato = pl.ID_planes
    WHERE c.estado_pago = 'Por Pagar' AND cp_con.estado_contrato = 'Activo' AND c.estado != 'cancelada'
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
                            $id_contrato = $row['ID_contrato'];
                            $cobertura = $row['monto_cobertura'];
                            
                            // Calcular consumo actual
                            $sql_cons = "SELECT SUM(monto_descontado) AS consumido FROM consumo_plan WHERE ID_contrato_plan = '$id_contrato'";
                            $res_cons = mysqli_query($conn, $sql_cons);
                            $consumido = mysqli_fetch_assoc($res_cons)['consumido'] ?? 0;
                            $saldo_disponible = $cobertura - $consumido;
                            $costo_cita = $row['costo_total'] ?? 0;
                            $puede_pagar = ($saldo_disponible >= $costo_cita);
                        ?>
                        <tr>
                            <td class="text-start">
                                <strong><?php echo htmlspecialchars($row['nombre_paciente']); ?></strong><br>
                                <small class="text-muted">C.I: <?php echo htmlspecialchars($row['cedula']); ?></small>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo $row['tipo']; ?></span></td>
                            <td><?php echo htmlspecialchars($row['nombre_plan']); ?></td>
                            <td class="fw-bold text-danger">$<?php echo number_format($costo_cita, 2); ?></td>
                            <td class="fw-bold <?php echo $puede_pagar ? 'text-success' : 'text-danger'; ?>">
                                $<?php echo number_format($saldo_disponible, 2); ?>
                            </td>
                            <td>
                                <?php if ($puede_pagar): ?>
                                <button type="button" class="btn btn-success btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalPagoPoliza"
                                        data-bs-idcita="<?php echo $row['id_cita']; ?>"
                                        data-bs-idcontrato="<?php echo $row['ID_contrato']; ?>"
                                        data-bs-monto="<?php echo $costo_cita; ?>"
                                        data-bs-nombre="<?php echo $row['nombre_paciente']; ?>"
                                        data-bs-especialidad="<?php echo $row['nombre_especialidad']; ?>"
                                        data-bs-descuento="<?php echo $row['descuento_especialidad']; ?>">
                                    <i class="fas fa-file-invoice-dollar me-1"></i> Descontar de Póliza
                                </button>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Saldo Insuficiente</span>
                                    <button class="btn btn-outline-primary btn-sm mt-1">Pagar Diferencia</button>
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
