<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
require_once 'C:/xampp/htdocs/IPSPUPTM/vendor/autoload.php'; // Autoload de Composer
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Section\Header;
use PhpOffice\PhpWord\SimpleType\Jc; // Importar la clase para la alineación

$titulo = "Reporte de Afiliados";

// Ruta de los logos
$logo_ipspuptm = 'C:/xampp/htdocs/IPSPUPTM/recursos/img/IPSPUPTMlogo.png';
$logo_uptm = 'C:/xampp/htdocs/IPSPUPTM/recursos/img/UPTM_logo.png';

// Consulta para obtener todos los afiliados
$sql = "SELECT  
            p.cedula,  
            p.nombre,  
            p.apellido,  
            p.fechanacimiento,  
            p.genero,  
            p.telefono,  
            p.correo,  
            p.ocupacion,  
            a.created_at,  
            a.updated_at  
        FROM  
            afiliados a  
        JOIN  
            persona p ON a.cedula = p.cedula";

$resultado = $conn->query($sql);

if (!$resultado) {
    echo "Error en la consulta de afiliados: " . $conn->error;
    exit;
}

// Crear el documento Word
$phpWord = new PhpWord();
$section = $phpWord->addSection();

//Encabezado
$header = $section->addHeader();

// Verificar si los archivos de los logos existen
if (file_exists($logo_ipspuptm)) {
    $header->addImage($logo_ipspuptm, ['width' => 60, 'height' => 60, 'alignment' => Jc::START]);
} else {
    $header->addText('¡Error! No se encontró el logo de IPSPUPTM.', ['color' => 'FF0000']); // Mensaje de error
}

$header->addText(' ', [], ['alignment' => Jc::CENTER]); // Espacio entre logos y título

if (file_exists($logo_uptm)) {
    $header->addImage($logo_uptm, ['width' => 60, 'height' => 60, 'alignment' => Jc::END]);
} else {
    $header->addText('¡Error! No se encontró el logo de UPTM.', ['color' => 'FF0000']); // Mensaje de error
}

// Agregar título y subtítulo
$section->addText('Instituto de Previsión Social de los profesores de la Universidad Politécnica Territorial del Estado Mérida Kléber Ramirez', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
$section->addText($titulo, ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER]);
$section->addTextBreak(2); // Espacio en blanco

// Crear tabla con encabezados
$table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 50]);
$table->addRow();
$table->addCell(2000)->addText('Cédula', ['bold' => true]);
$table->addCell(3000)->addText('Nombre', ['bold' => true]);
$table->addCell(3000)->addText('Apellido', ['bold' => true]);
$table->addCell(2000)->addText('F. Nacimiento', ['bold' => true]);
$table->addCell(2000)->addText('Género', ['bold' => true]);
$table->addCell(2000)->addText('Teléfono', ['bold' => true]);
$table->addCell(3000)->addText('Correo', ['bold' => true]);
$table->addCell(2000)->addText('Ocupación', ['bold' => true]);
$table->addCell(3000)->addText('Fecha Registro', ['bold' => true]);
$table->addCell(3000)->addText('Última Actualización', ['bold' => true]);

// Agregar filas con datos del query
while ($row = $resultado->fetch_assoc()) {
    $table->addRow();
    $table->addCell(2000)->addText($row['cedula']);
    $table->addCell(3000)->addText($row['nombre']);
    $table->addCell(3000)->addText($row['apellido']);
    $table->addCell(2000)->addText(date('d-m-Y', strtotime($row['fechanacimiento'])));
    $table->addCell(2000)->addText($row['genero']);
    $table->addCell(2000)->addText($row['telefono']);
    $table->addCell(3000)->addText($row['correo']);
    $table->addCell(2000)->addText($row['ocupacion']);
    $table->addCell(3000)->addText(date('d-m-Y H:i:s', strtotime($row['created_at'])));
    $table->addCell(3000)->addText(date('d-m-Y H:i:s', strtotime($row['updated_at'])));
}

// Guardar y descargar el archivo Word
$filename = 'reporte_afiliados_' . date('Ymd') . '.docx';
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$phpWord->save('php://output');
?>
