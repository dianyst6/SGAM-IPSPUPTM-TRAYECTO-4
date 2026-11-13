<?php
require_once 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

$rowsPerPage = 15;
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($currentPage - 1) * $rowsPerPage;

// Calcular total de páginas
$totalQuery = "SELECT COUNT(*) as total FROM contrato_plan";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRows = mysqli_fetch_assoc($totalResult)['total'];
$totalPages = ceil($totalRows / $rowsPerPage);
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
        <div class="col-auto col-md-3 text-end"></div>
    </div>
    <br>
    <div class="table-responsive">
        <table class="table table-sm table-striped table-hover mx-auto" id="tablaContratos" width="100%"
            cellspacing="0">
            <thead class="table-dark">
                <tr>
                    <th>Afiliado</th>
                    <th>Plan</th>
                    <th>Monto Total</th>
                    <th>Frecuencia</th>
                    <th>Día Pago</th>
                    <th>Vigencia</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    require_once 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

                    $sql = "SELECT cp.*, per.nombre, per.apellido, pl.nombre_plan 
                            FROM contrato_plan cp
                            INNER JOIN persona per ON cp.id_persona = per.id_persona
                            INNER JOIN planes pl ON cp.ID_planes_contrato = pl.ID_planes
                            ORDER BY cp.fecha_inicio DESC
                            LIMIT $rowsPerPage OFFSET $offset";

                    $resultado = mysqli_query($conn, $sql);

                    while ($row = mysqli_fetch_assoc($resultado)) {
                        // Lógica para color del badge de estado
                        $badgeColor = ($row['estado_contrato'] == 'Activo') ? 'bg-success' : 'bg-danger';
                        if ($row['estado_contrato'] == 'Vencido') $badgeColor = 'bg-warning text-dark';
                    ?>
                <tr>
                    <td><?php echo $row['nombre'] . " " . $row['apellido']; ?> <br>
                        <small class="text-muted">ID Persona: <?php echo $row['id_persona']; ?></small>
                    </td>
                    <td><?php echo $row['nombre_plan']; ?></td>
                    <td class="fw-bold">$ <?php echo number_format($row['monto_total'], 2); ?></td>
                    <td><?php echo $row['frecuencia_pago']; ?></td>
                    <td class="text-center"><?php echo $row['dia_pago_mensual']; ?></td>
                    <td>
                        <small>Desde: <?php echo date('d/m/Y', strtotime($row['fecha_inicio'])); ?></small><br>
                        <small>Hasta: <?php echo date('d/m/Y', strtotime($row['fecha_fin'])); ?></small>
                    </td>
                    <td>
                        <span class="badge <?php echo $badgeColor; ?>">
                            <?php echo $row['estado_contrato']; ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                           
    
                        </div>
                    </td>
                </tr>
                <?php } ?>
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