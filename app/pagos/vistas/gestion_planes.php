<?php
include_once 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

$rowsPerPage = 2;
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($currentPage - 1) * $rowsPerPage;

// Calcular total de páginas
$totalQuery = "SELECT COUNT(*) as total FROM planes";
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
<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold text-center" style="color: #062974;">Gestionar planes salud</h1>
            <hr class="mx-auto" style="width: 50px; height: 3px; background-color: #062974;">
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Botón 1 -->
        <div class="col-md-4">
            <a href="/IPSPUPTM/home.php?vista=agregarplan" class="btn text-white w-100 py-3 shadow-sm d-flex align-items-center justify-content-center" 
               style="background-color: #062974; border-radius: 10px;">
                <i class="fa-solid fa-plus me-2"></i><strong>Crear plan Salud</strong>
            </a>
        </div>

        <!-- Botón 2 -->
        <div class="col-md-4">
            <a href="/IPSPUPTM/home.php?vista=gestionplanesasignados" class="btn text-white w-100 py-3 shadow-sm d-flex align-items-center justify-content-center" 
               style="background-color: #062974; border-radius: 10px;">
                <i class="fa-solid fa-book-medical me-2"></i><strong>Gestionar planes asignados</strong>
            </a>
        </div>

        <!-- Botón 3 -->
        <div class="col-md-4">
            <a href="/IPSPUPTM/home.php?vista=gestionexamenes" class="btn text-white w-100 py-3 shadow-sm d-flex align-items-center justify-content-center" 
               style="background-color: #062974; border-radius: 10px;">
                <i class="fa-solid fa-microscope me-2"></i><strong>Gestionar Exámenes y Precios</strong>
            </a>
        </div>
    </div>

    <!-- Lista de Planes -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow border-0">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h4 class="fw-bold mb-0"><i class="fa-solid fa-list-check me-2"></i>Visualizar planes de pagos</h4>
                </div>

                <div class="card-body p-4">
                    <?php
                    include_once 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
                    
                    // Conteo total absoluto para el estado del sistema al final
                    $res_todos = mysqli_query($conn, "SELECT * FROM planes");
                    $total_activos = mysqli_num_rows($res_todos);

                    // 1. Consultar planes registrados con paginación
                    $sql_planes = "SELECT * FROM planes ORDER BY ID_planes DESC LIMIT $rowsPerPage OFFSET $offset";
                    $res_planes = mysqli_query($conn, $sql_planes);

    if (mysqli_num_rows($res_planes) > 0) {
        while ($plan = mysqli_fetch_assoc($res_planes)) {
            $id_plan = $plan['ID_planes'];
            
            // 2. Consultar los componentes (exámenes) asociados a este plan específico
            $sql_comp = "SELECT e.nombre_examen, cp.cantidad_maxima 
                         FROM componentes_planes cp
                         INNER JOIN examenes e ON cp.ID_examen_componentes = e.ID_examen
                         WHERE cp.ID_planes_componentes = '$id_plan'";
            $res_comp = mysqli_query($conn, $sql_comp);
            
            // 3. Consultar límites por categoría
            $sql_cat = "SELECT c.nombre_categoria, cp.cantidad_maxima, cp.monto_maximo 
                        FROM componentes_planes cp
                        INNER JOIN categorias_examenes c ON cp.id_categoria_componente = c.id_categoria
                        WHERE cp.ID_planes_componentes = '$id_plan'";
            $res_cat = mysqli_query($conn, $sql_cat);
            ?>

                    <div class="p-3 mb-4 border-start border-4 rounded shadow-sm bg-white"
                        style="border-color: #062974 !important;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                             <div class="ms-2">
                                 <h5 class="mb-0 fw-bold" style="color: #062974;"><?php echo $plan['nombre_plan']; ?>
                                 </h5>
                                 <small class="text-muted d-block"><?php echo $plan['descripcion']; ?></small>
                                 <span class="badge bg-info text-dark mt-1">Cobertura Póliza: $<?php echo number_format($plan['monto_cobertura'], 2); ?></span>
                             </div>
                            <a href="/IPSPUPTM/home.php?vista=editarplan&id=<?php echo $id_plan; ?>" class="btn btn-warning fw-bold d-flex align-items-center shadow-sm" style="border-radius: 8px; padding: 6px 14px; font-size: 0.9rem; color: #212529;">
                                <i class="fa-solid fa-pen me-2"></i> Editar Plan
                            </a>
                        </div>

                        <div class="ms-2 mt-2">
                            <h6 class="fw-bold small text-uppercase text-secondary">Cobertura del Plan:</h6>
                            <ul class="list-unstyled mb-0">
                                <?php 
                        if (mysqli_num_rows($res_comp) > 0) {
                            while ($comp = mysqli_fetch_assoc($res_comp)) {
                                echo "<li class='small'><i class='fa-solid fa-check-circle text-info me-2'></i>";
                                echo $comp['nombre_examen'] . " (Máx: " . $comp['cantidad_maxima'] . ")";
                                echo "</li>";
                            }
                        }
                        
                        if (mysqli_num_rows($res_cat) > 0) {
                            while ($cat = mysqli_fetch_assoc($res_cat)) {
                                $limit_desc = "";
                                if ($cat['cantidad_maxima'] > 0) $limit_desc .= "Máx: " . $cat['cantidad_maxima'] . " usos";
                                if ($cat['monto_maximo'] > 0) $limit_desc .= ($limit_desc ? " / " : "") . "Cobertura: $" . number_format($cat['monto_maximo'], 2);
                                
                                echo "<li class='small'><i class='fa-solid fa-tags text-success me-2'></i>";
                                echo "<strong>" . $cat['nombre_categoria'] . "</strong> (" . ($limit_desc ?: "Sin límites") . ")";
                                echo "</li>";
                            }
                        }

                        if (mysqli_num_rows($res_comp) == 0 && mysqli_num_rows($res_cat) == 0) {
                            echo "<li class='small text-muted italic'>Este plan no tiene coberturas específicas asociadas.</li>";
                        }
                        ?>
                            </ul>
                        </div>

                        <div class="text-end mt-2">
                            <span class="badge rounded-pill" style="background-color: #062974;">Precio:
                                $<?php echo number_format($plan['precio'], 2); ?></span>
                        </div>
                    </div>

                    <?php
        }
    } else {
        echo "<div class='text-center py-4 text-muted'>No hay planes registrados actualmente.</div>";
    }
    ?>

                    <!-- Paginación -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Navegación de planes" class="mt-4">
                        <ul class="pagination justify-content-center pagination-sm">
                            <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?vista=gestionplanes&page=<?= $currentPage - 1 ?>">Anterior</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                                <a class="page-link" href="?vista=gestionplanes&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?vista=gestionplanes&page=<?= $currentPage + 1 ?>">Siguiente</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mt-4 p-4 bg-light rounded border-0">
                        <h5 class="mb-0 text-secondary">Estado del Sistema:</h5>
                        <h6 class="mb-0 fw-bold" style="color: #062974;">Planes Activos:
                            <?php echo $total_activos; ?></h6>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="/IPSPUPTM/assets/js/accionescitas.js"></script>