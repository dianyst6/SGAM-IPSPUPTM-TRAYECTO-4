<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
require_once 'C:/xampp/htdocs/IPSPUPTM/vendor/autoload.php'; // Incluye el autoloader de Composer
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

$titulo = "Reporte de Beneficiarios";

// Consulta para obtener todos los beneficiarios
$query_beneficiarios = "
    SELECT
        p.cedula,
        p.nombre,
        p.apellido,
        p.fechanacimiento,
        p.genero,
        p.telefono,
        p.correo,
        p.ocupacion,
        CONCAT(pa.nombre, ' ', pa.apellido) AS afiliado_nombre_completo,
        b.created_at
    FROM beneficiarios b
    JOIN persona p ON b.cedula = p.cedula
    JOIN afiliados a ON b.id_afiliado = a.id
    JOIN persona pa ON a.cedula = pa.cedula
    ORDER BY b.created_at DESC
";

$resultado = $conn->query($query_beneficiarios);

if (!$resultado) {
    echo "Error en la consulta de beneficiarios: " . $conn->error;
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
$table->addCell(2000)->addText('Cédula', ['bold' => true]);
$table->addCell(3000)->addText('Nombre', ['bold' => true]);
$table->addCell(3000)->addText('Apellido', ['bold' => true]);
$table->addCell(2000)->addText('F. Nacimiento', ['bold' => true]);
$table->addCell(2000)->addText('Género', ['bold' => true]);
$table->addCell(2000)->addText('Teléfono', ['bold' => true]);
$table->addCell(3000)->addText('Correo', ['bold' => true]);
$table->addCell(2000)->addText('Ocupación', ['bold' => true]);
$table->addCell(3000)->addText('Afiliado', ['bold' => true]);
$table->addCell(2000)->addText('Fecha Registro', ['bold' => true]);

// Agregar filas de datos
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
    $table->addCell(3000)->addText($row['afiliado_nombre_completo']);
    $table->addCell(2000)->addText(date('d-m-Y', strtotime($row['created_at'])));
}

// Guardar y descargar el archivo Word
$filename = 'reporte_beneficiarios_' . date('Ymd') . '.docx';
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$phpWord->save('php://output');
?>
