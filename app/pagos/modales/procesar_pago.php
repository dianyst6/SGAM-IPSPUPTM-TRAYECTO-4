<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
// 1. Incluimos la conexión (Ajusta la ruta si es necesario)
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

// Verificar que los datos vengan por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2. Recibimos los datos de los formularios mediante sus 'name'
    $id_contrato  = $_POST['id_contrato'];
    $monto_cuota  = $_POST['monto_cuota'];
    $fecha_pago   = $_POST['fecha_pago'];
    $numero_cuota = $_POST['numero_cuota'];
    $metodo_pago  = $_POST['metodo_pago'];
    $tipo_pago    = $_POST['tipo_pago'] ?? 'Cuota';

    // 3. Validamos que los campos esenciales no estén vacíos
    if (!empty($id_contrato) && !empty($monto_cuota)) {
        
        $sql = "INSERT INTO pagos_contrato (ID_contrato, monto_cuota, fecha_pago, numero_cuota, metodo_pago, tipo_pago) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            $exito = true;
            
            if ($tipo_pago === 'Abonos') {
                $cuotas = $_POST['cuotas_abono'] ?? [];
                $cantidad_cuotas = count($cuotas);
                
                if ($cantidad_cuotas > 0) {
                    // Dividimos el monto total entre las cuotas seleccionadas
                    $monto_individual = $monto_cuota / $cantidad_cuotas;
                    $tipo_registro = 'Cuota'; // En la BD se registran como cuotas individuales
                    
                    foreach ($cuotas as $num_cuota) {
                        mysqli_stmt_bind_param($stmt, "idsiss", $id_contrato, $monto_individual, $fecha_pago, $num_cuota, $metodo_pago, $tipo_registro);
                        if (!mysqli_stmt_execute($stmt)) {
                            $exito = false;
                            echo "Error al ejecutar la consulta para cuota $num_cuota: " . mysqli_error($conn);
                            break;
                        }
                    }
                } else {
                    $exito = false;
                    echo "<script>alert('Debe seleccionar al menos una cuota.'); window.history.back();</script>";
                    exit();
                }
            } else {
                // Pago Inicial o Cuota Normal
                mysqli_stmt_bind_param($stmt, "idsiss", $id_contrato, $monto_cuota, $fecha_pago, $numero_cuota, $metodo_pago, $tipo_pago);
                if (!mysqli_stmt_execute($stmt)) {
                    $exito = false;
                    echo "Error al ejecutar la consulta: " . mysqli_error($conn);
                }
            }

            // 5. Redireccionar si todo fue exitoso
            if ($exito) {
                $_SESSION['flash_msg'] = "Pago registrado con éxito";
                $_SESSION['flash_type'] = "success";
                header("Location: /IPSPUPTM/home.php?vista=gestionpagoscontrato");
                exit();
            }

            mysqli_stmt_close($stmt);
        } else {
            echo "Error al preparar la consulta: " . mysqli_error($conn);
        }

    } else {
        echo "<script>
                alert('Error: Todos los campos son obligatorios.');
                window.history.back();
              </script>";
    }
}

// Cerramos la conexión
mysqli_close($conn);
?>