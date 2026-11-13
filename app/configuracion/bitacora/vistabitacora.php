<div class="cont-general" style="width: 100%; flex-grow: 1;"> <!-- Ajustando estilo inline para abarcar toda la sección -->

<?php
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php'; 
include 'C:/xampp/htdocs/IPSPUPTM/config/alertify.php'; 

// --- LÓGICA DE PAGINACIÓN ---
$rowsPerPage = 10; // Número de registros por página
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage < 1) $currentPage = 1;
$offset = ($currentPage - 1) * $rowsPerPage;

// Obtener el total de registros para calcular páginas
$totalResult = $conn->query("SELECT COUNT(*) AS total FROM bitacora");
$totalRows = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $rowsPerPage);

// Consulta con LIMIT y OFFSET
$sql = "SELECT * FROM bitacora ORDER BY fecha DESC LIMIT $rowsPerPage OFFSET $offset";
$result = $conn->query($sql);

echo "<div class='card shadow-lg'>"; // Añadido para mantener la consistencia con el estilo de las otras secciones
echo "<div class='card-body mt-5 m-3'> <!-- Cambiado a container-fluid para usar todo el espacio -->
        <h1 class='text-center mb-4'>Bitácora de Actividades</h1>
        <p class='text-muted text-center'>Se encarga de registrar todos los movimientos realizados en el sistema para mantener una documentación. Puedes descargar el registro de dichos movimientos en PDF.</p>
        <div class='d-flex justify-content-between mb-3'>
            <button id='deleteButton' class='btn btn-danger btn-sm'>
                <i class='fas fa-trash-alt me-1'></i>Eliminar registros
            </button>
            <button id='downloadButton' class='btn btn-primary btn-sm'>
                <i class='fas fa-download me-1'></i>Descargar PDF
            </button>
        </div>";

if ($result->num_rows > 0) {
    echo "<div class='table-responsive'>
            <table class='table table-striped table-hover table-sm'>
                <thead class='table-dark text-center'>
                    <tr>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['usuario']) . "</td>
                <td>" . htmlspecialchars($row['accion']) . "</td>
                <td>" . htmlspecialchars($row['descripcion']) . "</td>
                <td class='text-center'>" . date('d/m/Y H:i:s', strtotime($row['fecha'])) . "</td>
            </tr>";
    }

    echo "</tbody>
            </table>
          </div>";

    // --- CONTROLES DE PAGINACIÓN ---
    echo "<nav aria-label='Navegación de bitácora' class='mt-4'>
            <ul class='pagination justify-content-center pagination-sm'>";
    
    // Botón Anterior
    $prevClass = ($currentPage <= 1) ? 'disabled' : '';
    echo "<li class='page-item $prevClass'>
            <a class='page-link' href='?vista=bitacora&page=" . ($currentPage - 1) . "'>Anterior</a>
          </li>";

    // Números de página
    for ($i = 1; $i <= $totalPages; $i++) {
        $activeClass = ($i == $currentPage) ? 'active' : '';
        echo "<li class='page-item $activeClass'>
                <a class='page-link' href='?vista=bitacora&page=$i'>$i</a>
              </li>";
    }

    // Botón Siguiente
    $nextClass = ($currentPage >= $totalPages) ? 'disabled' : '';
    echo "<li class='page-item $nextClass'>
            <a class='page-link' href='?vista=bitacora&page=" . ($currentPage + 1) . "'>Siguiente</a>
          </li>";

    echo "</ul>
          </nav>";

} else {
    echo "<div class='alert alert-info text-center'>No hay registros en la bitácora</div>";
}
echo "</div>";
echo "</div>"; // Cierre de la tarjeta

?>

</div>

<script src="/IPSPUPTM/assets/js/bitacora.js"></script>