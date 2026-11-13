<?php
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

if (isset($_GET['id_especialidad'])) {
    $id_esp = mysqli_real_escape_string($conn, $_GET['id_especialidad']);
    $tipo_pac = isset($_GET['tipo_pac']) ? mysqli_real_escape_string($conn, $_GET['tipo_pac']) : 'externo';
    $id_pac = isset($_GET['id_paciente']) ? mysqli_real_escape_string($conn, $_GET['id_paciente']) : '';

    $examenes = [];

    // Lógica para paciente interno (Afiliado/Beneficiario)
    if ($tipo_pac === 'interno' && !empty($id_pac)) {
        // Obtenemos el titular de la póliza a partir de la persona seleccionada.
        $sql_perfil = "
            SELECT 'afiliado' as tipo_vinc, a.ID as id_afiliado_titular, cp.ID_planes_contrato
            FROM afiliados a
            JOIN contrato_plan cp ON a.id_persona = cp.id_persona
            WHERE a.id_persona = '$id_pac' AND cp.estado_contrato = 'Activo'
            UNION
            SELECT 'beneficiario' as tipo_vinc, a.ID as id_afiliado_titular, cp.ID_planes_contrato
            FROM beneficiarios b
            JOIN afiliados a ON b.id_afiliado = a.ID
            JOIN contrato_plan cp ON a.id_persona = cp.id_persona
            WHERE b.id_persona = '$id_pac' AND cp.estado_contrato = 'Activo'
        ";
        $res_perfil = mysqli_query($conn, $sql_perfil);
        $perfil = mysqli_fetch_assoc($res_perfil);

        if ($perfil) {
            $id_plan = $perfil['ID_planes_contrato'];
            $id_afiliado_titular = $perfil['id_afiliado_titular'];

            $sql_examenes = "
                SELECT 
                    e.ID_examen, 
                    e.nombre_examen, 
                    e.precio, 
                    cp_comp.cantidad_maxima,
                    (
                        SELECT COUNT(*)
                        FROM citas_examenes ce
                        INNER JOIN citas c ON ce.id_cita = c.id_cita
                        INNER JOIN examenes e_inner ON ce.id_examen = e_inner.ID_examen
                        WHERE (
                            (cp_comp.ID_examen_componentes IS NOT NULL AND ce.id_examen = cp_comp.ID_examen_componentes)
                            OR
                            (cp_comp.id_categoria_componente IS NOT NULL AND e_inner.id_categoria = cp_comp.id_categoria_componente)
                        )
                        AND c.estado_pago = 'Deducida de Póliza'
                        AND c.id_persona IN (
                            SELECT a2.id_persona FROM afiliados a2 WHERE a2.ID = '$id_afiliado_titular'
                            UNION
                            SELECT b2.id_persona FROM beneficiarios b2 WHERE b2.id_afiliado = '$id_afiliado_titular'
                        )
                    ) AS consumido
                FROM examenes e
                LEFT JOIN componentes_planes cp_comp ON (cp_comp.ID_planes_componentes = '$id_plan' AND (cp_comp.ID_examen_componentes = e.ID_examen OR cp_comp.id_categoria_componente = e.id_categoria))
                WHERE e.ID_especialidad_examenes = '$id_esp' AND e.estado = 'activo'
                GROUP BY e.ID_examen
            ";

            $res_ex = mysqli_query($conn, $sql_examenes);
            while ($row = mysqli_fetch_assoc($res_ex)) {
                $maximo = $row['cantidad_maxima'];
                $consumido = intval($row['consumido']);

                if (is_null($maximo) || $maximo === "" || $maximo === "NULL") {
                    $row['disponibles'] = 'ilimitado';
                    $row['is_disabled'] = false;
                } else {
                    $restan = intval($maximo) - $consumido;
                    $row['disponibles'] = $restan;
                    $row['is_disabled'] = ($restan <= 0);
                }
                $examenes[] = $row;
            }
        } else {
            // Si el paciente interno no tiene plan activo, mostrar todo bloqueado o como sin cupo.
            $sql = "SELECT ID_examen, nombre_examen, precio FROM examenes WHERE ID_especialidad_examenes = '$id_esp' AND estado = 'activo'";
            $res = mysqli_query($conn, $sql);
            while ($row = mysqli_fetch_assoc($res)) {
                $row['disponibles'] = 0;
                $row['is_disabled'] = true; // No tiene plan, no puede consumir gratis
                $examenes[] = $row;
            }
        }
    } else {
        // Lógica para paciente externo (Sin plan, no importa el límite)
        $sql = "SELECT ID_examen, nombre_examen, precio FROM examenes WHERE ID_especialidad_examenes = '$id_esp' AND estado = 'activo'";
        $res = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($res)) {
            $row['disponibles'] = 'ilimitado';
            $row['is_disabled'] = false;
            $examenes[] = $row;
        }
    }

    echo json_encode($examenes);
}
?>
