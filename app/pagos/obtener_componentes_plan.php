<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

header('Content-Type: application/json');

$id_plan = isset($_GET['id_plan']) ? intval($_GET['id_plan']) : 0;

if ($id_plan <= 0) {
    echo json_encode(['examenes' => [], 'categorias' => []]);
    exit;
}

// Arreglos para enviar al Frontend
$respuesta = [
    'examenes' => [],
    'categorias' => []
];

// Consulta única a la tabla componentes_planes
$sql = "SELECT ID_examen_componentes, id_categoria_componente, cantidad_maxima, monto_maximo 
        FROM componentes_planes 
        WHERE ID_planes_componentes = $id_plan";

$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Si tiene ID_examen_componentes (no es NULL), pertenece a los exámenes individuales
        if (!empty($row['ID_examen_componentes'])) {
            $respuesta['examenes'][] = [
                'id_examen' => $row['ID_examen_componentes'],
                'cantidad'  => $row['cantidad_maxima']
            ];
        }
        
        // Si tiene id_categoria_componente (no es NULL), pertenece a los límites globales
        if (!empty($row['id_categoria_componente'])) {
            $respuesta['categorias'][] = [
                'id_categoria' => $row['id_categoria_componente'],
                'cantidad'     => $row['cantidad_maxima'],
                'monto'        => $row['monto_maximo']
            ];
        }
    }
}

// Simular un pequeño retardo de medio segundo para que la animación se note y no parpadee (Opcional, puedes borrarlo)
usleep(500000); 

echo json_encode($respuesta);
?>