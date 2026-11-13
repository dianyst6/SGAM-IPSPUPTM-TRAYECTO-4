<?php

function registrarEnBitacora ($conn,
    $usuario, $accion, $descripcion) {	
        $sql_bitacora = "INSERT INTO bitacora (usuario, accion, descripcion, fecha) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql_bitacora);
        $stmt->bind_param("sss", $usuario, $accion, $descripcion);

        if (!$stmt->execute()) {
            echo "Error al registrar en bitácora: " . $stmt->error;
                } else {
            $stmt -> close();

}
 } 

 ?>