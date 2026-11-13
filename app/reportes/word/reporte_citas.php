<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
  
require_once 'C:/xampp/htdocs/IPSPUPTM/vendor/autoload.php'; // Autoload de Composer  
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';  

use PhpOffice\PhpWord\PhpWord;  
use PhpOffice\PhpWord\IOFactory;  

// Obtener el tipo de reporte desde un parámetro GET  
$tipo_reporte = $_GET['tipo_reporte'] ?? 'semanal'; 

$titulo_base = "Reporte de Citas";  
$condicion_fecha = "";  
$titulo = '';  

if ($tipo_reporte === 'personalizado' && isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])) {
    $fecha_inicio = $conn->real_escape_string($_GET['fecha_inicio']);
    $fecha_fin = $conn->real_escape_string($_GET['fecha_fin']);
    $condicion_fecha = "c.fecha_cita BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    $titulo = "$titulo_base - Del " . date('d/m/Y', strtotime($fecha_inicio)) . " al " . date('d/m/Y', strtotime($fecha_fin));
} else {
    switch ($tipo_reporte) {  
        case 'semanal':  
            $interval = 'INTERVAL 1 WEEK';  
            $titulo = $titulo_base . " - Semanal";  
            break;  
        case 'quincenal':  
            $interval = 'INTERVAL 2 WEEK';  
            $titulo = $titulo_base . " - Quincenal";  
            break;  
        case 'mensual':  
            $interval = 'INTERVAL 1 MONTH';  
            $titulo = $titulo_base . " - Mensual";  
            break;  
        default:  
            $interval = 'INTERVAL 1 WEEK';  
            $titulo = $titulo_base . " - Semanal";  
            break;  
    }  
    $condicion_fecha = "c.fecha_cita >= DATE_SUB(CURDATE(), $interval)";
}  

$query_citas = "  
    SELECT  
        c.id_cita,  
        c.fecha_cita,  
        c.descripcion,  
        e.nombre_especialidad,  
        CASE  
            WHEN a.ID IS NOT NULL THEN 'Afiliado'  
            WHEN b.id_beneficiario IS NOT NULL THEN 'Beneficiario'  
            ELSE 'Comunidad UPTM'  
        END AS tipo_paciente,  
        CONCAT(p.nombre,' ',p.apellido) AS nombre_paciente,  
        p.cedula AS cedula_paciente  
    FROM citas c  
    INNER JOIN persona p ON p.id_persona = c.id_persona  
    LEFT JOIN afiliados a     ON a.id_persona = p.id_persona  
    LEFT JOIN beneficiarios b ON b.id_persona = p.id_persona  
    LEFT JOIN especialidades e ON c.id_especialidad = e.id_especialidad  
    WHERE $condicion_fecha  
    ORDER BY c.fecha_cita DESC  
";  

$resultado = $conn->query($query_citas);  

if (!$resultado) {  
    echo "Error en la consulta de citas: " . $conn->error;  
    exit;  
}  

// Crear el documento Word
$phpWord = new PhpWord();
$section = $phpWord->addSection();  

// Agregar título y subtítulo
$section->addText('Instituto de Previsión Social de los profesores de la Universidad Politécnica Territorial del Estado Mérida Kléber Ramirez', ['bold' => true, 'size' => 14], ['alignment' => 'center']);
$section->addText($titulo, ['bold' => true, 'size' => 12], ['alignment' => 'center']);
$section->addTextBreak(2); // Espacio en blanco

// Crear tabla con encabezados
$table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 50]);
$table->addRow();
$table->addCell(2000)->addText('Fecha', ['bold' => true]);
$table->addCell(3000)->addText('Paciente', ['bold' => true]);
$table->addCell(2000)->addText('Cédula', ['bold' => true]);
$table->addCell(2000)->addText('Tipo', ['bold' => true]);
$table->addCell(3000)->addText('Especialidad', ['bold' => true]);
$table->addCell(5000)->addText('Descripción', ['bold' => true]);

// Agregar filas con datos
while ($row = $resultado->fetch_assoc()) {
    $table->addRow();
    $table->addCell(2000)->addText(date('d-m-Y', strtotime($row['fecha_cita'])));
    $table->addCell(3000)->addText($row['nombre_paciente']);
    $table->addCell(2000)->addText($row['cedula_paciente']);
    $table->addCell(2000)->addText($row['tipo_paciente']);
    $table->addCell(3000)->addText($row['nombre_especialidad']);
    $table->addCell(5000)->addText($row['descripcion']);
}

// Guardar el archivo Word
$filename = 'reporte_citas_' . $tipo_reporte . '_' . date('Ymd') . '.docx';
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$phpWord->save('php://output');
?>
