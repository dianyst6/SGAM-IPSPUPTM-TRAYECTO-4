<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre_plan']);
    $precio = $_POST['precio'];
    $monto_cobertura = $_POST['monto_cobertura'];
    $desc = mysqli_real_escape_string($conn, $_POST['descripcion']);

    // 1. Insertar el Plan principal
    $sql_plan = "INSERT INTO planes (nombre_plan, precio, monto_cobertura, descripcion) VALUES ('$nombre', '$precio', '$monto_cobertura', '$desc')";
    
    if (mysqli_query($conn, $sql_plan)) {
        $id_plan_nuevo = mysqli_insert_id($conn); // Obtenemos el ID del plan que se acaba de crear

        // 2. Procesar Exámenes Individuales
        $examenes = isset($_POST['id_examen']) ? $_POST['id_examen'] : [];
        $cantidades_ex = isset($_POST['cantidad_examen']) ? $_POST['cantidad_examen'] : [];

        for ($i = 0; $i < count($examenes); $i++) {
            $id_ex = $examenes[$i];
            $cant = !empty($cantidades_ex[$i]) ? intval($cantidades_ex[$i]) : "NULL";

            if (!empty($id_ex)) {
                $sql_comp = "INSERT INTO componentes_planes (ID_planes_componentes, ID_examen_componentes, id_categoria_componente, cantidad_maxima) 
                             VALUES ('$id_plan_nuevo', '$id_ex', NULL, $cant)";
                mysqli_query($conn, $sql_comp); 
            }
        }

        // 3. Procesar Límites por Categoría
        $categorias = isset($_POST['id_categoria_comp']) ? $_POST['id_categoria_comp'] : [];
        $cantidades_cat = isset($_POST['cantidad_categoria']) ? $_POST['cantidad_categoria'] : [];
        $montos_cat = isset($_POST['monto_categoria']) ? $_POST['monto_categoria'] : [];

        for ($j = 0; $j < count($categorias); $j++) {
            $id_cat = $categorias[$j];
            $cant_c = !empty($cantidades_cat[$j]) ? intval($cantidades_cat[$j]) : "NULL";
            $monto_c = !empty($montos_cat[$j]) ? floatval($montos_cat[$j]) : 0;

            if (!empty($id_cat)) {
                $sql_comp_cat = "INSERT INTO componentes_planes (ID_planes_componentes, ID_examen_componentes, id_categoria_componente, cantidad_maxima, monto_maximo) 
                                 VALUES ('$id_plan_nuevo', NULL, '$id_cat', $cant_c, '$monto_c')";
                mysqli_query($conn, $sql_comp_cat); 
            }
        }
        
        echo "<script>alert('Plan y componentes registrados con éxito'); window.location.href='/IPSPUPTM/home.php?vista=gestionplanes';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>