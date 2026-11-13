
<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_contrato = intval($_POST['id_contrato']);
    $id_persona = intval($_POST['id_persona']);
    $items = json_decode($_POST['items'], true);

    if (empty($id_contrato) || empty($id_persona) || empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
        exit;
    }

    // 1. Obtener ID_plan del contrato
    $sql_plan = "SELECT ID_planes_contrato FROM contrato_plan WHERE ID_contrato = '$id_contrato'";
    $res_plan = mysqli_query($conn, $sql_plan);
    $id_plan = mysqli_fetch_assoc($res_plan)['ID_planes_contrato'];

    mysqli_begin_transaction($conn);

    try {
        foreach ($items as $item) {
            $nombre = mysqli_real_escape_string($conn, $item['nombre']);
            $id_cat = intval($item['id_categoria']);
            $costo = floatval($item['costo']);

            // Validar límites en el servidor (Seguridad)
            $sql_limite = "SELECT monto_maximo FROM componentes_planes 
                           WHERE ID_planes_componentes = '$id_plan' AND id_categoria_componente = '$id_cat'";
            $res_limite = mysqli_query($conn, $sql_limite);
            $limite_max = mysqli_fetch_assoc($res_limite)['monto_maximo'] ?? 0;

            $sql_consumo = "SELECT SUM(monto_descontado) as consumido 
                            FROM consumo_plan 
                            WHERE ID_contrato_plan = '$id_contrato' 
                            AND (
                                ID_examen_plan IN (SELECT ID_examen FROM examenes WHERE id_categoria = '$id_cat')
                                OR id_categoria_externa = '$id_cat'
                            )";
            $res_consumo = mysqli_query($conn, $sql_consumo);
            $consumido_actual = mysqli_fetch_assoc($res_consumo)['consumido'] ?? 0;

            if (($consumido_actual + $costo) > $limite_max) {
                throw new Exception("Límite excedido para la categoría en el examen: " . $nombre);
            }

            // Insertar consumo externo
            $sql_insert = "INSERT INTO consumo_plan (ID_contrato_plan, id_cita, id_persona, ID_examen_plan, nombre_estudio_externo, id_categoria_externa, monto_descontado) 
                           VALUES ('$id_contrato', NULL, '$id_persona', NULL, '$nombre', '$id_cat', '$costo')";
            
            if (!mysqli_query($conn, $sql_insert)) {
                throw new Exception("Error al insertar consumo: " . mysqli_error($conn));
            }
        }

        mysqli_commit($conn);
        echo json_encode(['success' => true, 'message' => 'Consumo externo registrado y deducido con éxito']);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
