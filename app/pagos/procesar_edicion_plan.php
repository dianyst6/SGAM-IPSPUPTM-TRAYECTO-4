
<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_plan = intval($_POST['id_plan']);
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre_plan']);
    $precio = $_POST['precio'];
    $monto_cobertura = $_POST['monto_cobertura'];
    $desc = mysqli_real_escape_string($conn, $_POST['descripcion']);

    // 1. Actualizar el Plan principal
    $sql_update_plan = "UPDATE planes SET 
                        nombre_plan = '$nombre', 
                        precio = '$precio', 
                        monto_cobertura = '$monto_cobertura', 
                        descripcion = '$desc' 
                        WHERE ID_planes = $id_plan";
    
    if (mysqli_query($conn, $sql_update_plan)) {
        
        // 2. Eliminar componentes antiguos para re-sincronizar
        // Nota: Esto es más simple que comparar qué cambió exactamente
        $sql_delete = "DELETE FROM componentes_planes WHERE ID_planes_componentes = $id_plan";
        mysqli_query($conn, $sql_delete);

        // 3. Procesar Exámenes Individuales
        $examenes = isset($_POST['id_examen']) ? $_POST['id_examen'] : [];
        $cantidades_ex = isset($_POST['cantidad_examen']) ? $_POST['cantidad_examen'] : [];

        for ($i = 0; $i < count($examenes); $i++) {
            $id_ex = $examenes[$i];
            $cant = !empty($cantidades_ex[$i]) ? intval($cantidades_ex[$i]) : "NULL";

            if (!empty($id_ex)) {
                $sql_comp = "INSERT INTO componentes_planes (ID_planes_componentes, ID_examen_componentes, id_categoria_componente, cantidad_maxima) 
                             VALUES ('$id_plan', '$id_ex', NULL, $cant)";
                mysqli_query($conn, $sql_comp); 
            }
        }

        // 4. Procesar Límites por Categoría
        $categorias = isset($_POST['id_categoria_comp']) ? $_POST['id_categoria_comp'] : [];
        $cantidades_cat = isset($_POST['cantidad_categoria']) ? $_POST['cantidad_categoria'] : [];
        $montos_cat = isset($_POST['monto_categoria']) ? $_POST['monto_categoria'] : [];

        for ($j = 0; $j < count($categorias); $j++) {
            $id_cat = $categorias[$j];
            $cant_c = !empty($cantidades_cat[$j]) ? intval($cantidades_cat[$j]) : "NULL";
            $monto_c = !empty($montos_cat[$j]) ? floatval($montos_cat[$j]) : 0;

            if (!empty($id_cat)) {
                $sql_comp_cat = "INSERT INTO componentes_planes (ID_planes_componentes, ID_examen_componentes, id_categoria_componente, cantidad_maxima, monto_maximo) 
                                 VALUES ('$id_plan', NULL, '$id_cat', $cant_c, '$monto_c')";
                mysqli_query($conn, $sql_comp_cat); 
            }
        }
        
        echo "<script>alert('Plan actualizado con éxito'); window.location.href='/IPSPUPTM/home.php?vista=gestionplanes';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
