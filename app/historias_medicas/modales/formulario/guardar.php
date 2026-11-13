<?php  
require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';  
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';  
session_start(); 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Captura de datos básicos
    $tipo_paciente = $_POST['tipo_paciente'] ?? null;
    $ci_medico = $_POST['ci_medico'] ?? null;
    $motivo = $_POST['motivo_consulta'] ?? null;
    
    // Obtener la cédula dependiendo del tipo de paciente seleccionado
    if ($tipo_paciente === 'interno') {
        $ci_paciente_raw = $_POST['ci_paciente'] ?? $_POST['cedula_paciente'] ?? null;
    } else {
        $ci_paciente_raw = $_POST['cedula_ext'] ?? null;
    }

    if (!empty($ci_paciente_raw) && !empty($tipo_paciente) && !empty($ci_medico) && !empty($motivo)) {  
        
        try {
            // Limpieza de Cédulas (solo números)
            $ci_paciente = intval(preg_replace('/[^0-9]/', '', $ci_paciente_raw));
            $ci_medico = intval($ci_medico);
            
            // --- LÓGICA DE DETECCIÓN AUTOMÁTICA DE TIPO ---
            // Si el formulario envía "interno", verificamos la tabla real
            if ($tipo_paciente === 'interno') {
                // 1. ¿Es Afiliado?
                $checkAfil = $conn->prepare("SELECT cedula FROM afiliados WHERE cedula = ?");
                $checkAfil->bind_param("i", $ci_paciente);
                $checkAfil->execute();
                if ($checkAfil->get_result()->num_rows > 0) {
                    $tipo_paciente = 'afiliado';
                } else {
                    // 2. ¿Es Beneficiario?
                    $checkBenef = $conn->prepare("SELECT cedula FROM beneficiarios WHERE cedula = ?");
                    $checkBenef->bind_param("i", $ci_paciente);
                    $checkBenef->execute();
                    if ($checkBenef->get_result()->num_rows > 0) {
                        $tipo_paciente = 'beneficiario';
                    }
                }
                $checkAfil->close();
            }

            // Datos adicionales según tu estructura de tabla
            $fecha = date('Y-m-d');
            $fecha_nac = $_POST['fecha_nacimiento'] ?? '';
            $edad = intval($_POST['edad'] ?? 0);
            $direccion = htmlspecialchars($_POST['direccion'] ?? '');
            $enfermedad = htmlspecialchars($_POST['enfermedad_actual'] ?? '');
            $ant_fam = htmlspecialchars($_POST['antecedentes_familiares'] ?? '');
            $ant_per = htmlspecialchars($_POST['antecedentes_personales'] ?? '');
            $info = htmlspecialchars($_POST['info_adicional'] ?? null);

            // Inserción final en historias_medicas
            $sql = "INSERT INTO historias_medicas (
                        ci_paciente, tipo_paciente, ci_medico, fecha, 
                        fecha_nacimiento, edad, direccion, motivo_consulta, 
                        enfermedad_actual, antecedentes_familiares, 
                        antecedentes_personales, info_adicional
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "isisisssssss", 
                $ci_paciente, $tipo_paciente, $ci_medico, $fecha,
                $fecha_nac, $edad, $direccion, $motivo,
                $enfermedad, $ant_fam, $ant_per, $info
            );

            if ($stmt->execute()) {
                $usuario = $_SESSION['username'] ?? 'Sistema'; 
                registrarenBitacora($conn, $usuario, "Registro de Historia", "Paciente CI: $ci_paciente ($tipo_paciente)");
                echo json_encode(['success' => true, 'message' => "Historia guardada correctamente como $tipo_paciente."]);
            } else {
                throw new Exception("Error en BD: " . $stmt->error);
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {  
        echo json_encode(['success' => false, 'message' => "Faltan datos obligatorios."]);
    } 
}
$conn->close();
?>