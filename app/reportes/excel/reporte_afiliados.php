<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
require_once 'C:/xampp/htdocs/IPSPUPTM/vendor/autoload.php'; // Autoload de Composer
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$titulo = "Reporte de Afiliados";

// Ruta de los logos
$logo_ipspuptm = 'C:/xampp/htdocs/IPSPUPTM/recursos/img/IPSPUPTMlogo.png'; // Asegúrate de que la ruta sea correcta
$logo_uptm = 'C:/xampp/htdocs/IPSPUPTM/recursos/img/UPTM_logo.png';     // Asegúrate de que la ruta sea correcta

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

// Crear un nuevo Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// --- Insertar Logos ---
// Logo IPSPUPTM en la esquina superior izquierda
$drawing = new Drawing();
$drawing->setName('Logo IPSPUPTM');
$drawing->setDescription('Logo del Instituto de Previsión Social de los Profesores de la Universidad Politécnica Territorial del Estado Mérida Kléber Ramirez');
$drawing->setPath($logo_ipspuptm);
$drawing->setHeight(40); // Ajusta el tamaño según sea necesario
$drawing->setCoordinates('A1'); // Celda donde se insertará la esquina superior izquierda
$drawing->setWorksheet($sheet);

// Logo UPTM en la esquina superior derecha
$drawing2 = new Drawing();
$drawing2->setName('Logo UPTM');
$drawing2->setDescription('Logo de la Universidad Politécnica Territorial del Estado Mérida Kléber Ramirez');
$drawing2->setPath($logo_uptm);
$drawing2->setHeight(40); // Ajusta el tamaño según sea necesario
$drawing2->setCoordinates('H1'); // Celda donde se insertará la esquina superior derecha (ajusta la columna según el número de columnas de tus datos)
$drawing2->setWorksheet($sheet);

// --- Título y Subtítulo ---
$sheet->setCellValue('A3', 'Instituto de Previsión Social de los Profesores de la Universidad Politécnica Territorial del Estado Mérida Kléber Ramirez');
$sheet->setCellValue('A4', 'Reporte de Afiliados');
$sheet->mergeCells('A3:H3'); // Ajusta el rango de celdas para el título
$sheet->mergeCells('A4:H4'); // Ajusta el rango de celdas para el subtítulo

// Estilos para el título y subtítulo
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);

// --- Fecha de Emisión ---
$fecha_emision = date('d-m-Y');
$sheet->setCellValue('A5', 'Emitido: ' . $fecha_emision); // Colocar "Emitido" y la fecha
$sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('A5')->getFont()->setSize(10);

// --- Encabezados de la tabla ---
$headerRow = ['Cédula', 'Nombre', 'Apellido', 'F. Nacimiento', 'Género', 'Teléfono', 'Correo', 'Ocupación', 'Fecha Registro', 'Última Actualización'];
$sheet->fromArray($headerRow, null, 'A7'); // Colocar los encabezados en la fila 7

// Estilo para los encabezados
$headerStyle = [
    'font' => ['bold' => true],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FFFFFF00'], // Amarillo
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'], // Negro
        ],
    ],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER], //Centrar
];
$sheet->getStyle('A7:J7')->applyFromArray($headerStyle); // Aplica el estilo a la fila de encabezados (A7 a J7)

// --- Datos de la tabla ---
$fila = 8; // Comenzar en la fila 8 para los datos
while ($row = $resultado->fetch_assoc()) {
    $sheet->setCellValue('A' . $fila, $row['cedula']);
    $sheet->setCellValue('B' . $fila, $row['nombre']);
    $sheet->setCellValue('C' . $fila, $row['apellido']);
    $sheet->setCellValue('D' . $fila, date('d-m-Y', strtotime($row['fechanacimiento'])));
    $sheet->setCellValue('E' . $fila, $row['genero']);
    $sheet->setCellValue('F' . $fila, $row['telefono']);
    $sheet->setCellValue('G' . $fila, $row['correo']);
    $sheet->setCellValue('H' . $fila, $row['ocupacion']);
    $sheet->setCellValue('I' . $fila, date('d-m-Y H:i:s', strtotime($row['created_at'])));
    $sheet->setCellValue('J' . $fila, date('d-m-Y H:i:s', strtotime($row['updated_at'])));
    $fila++;
}

// Aplicar bordes a los datos
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'], // Negro
        ],
    ],
];
$sheet->getStyle('A8:J' . ($fila - 1))->applyFromArray($dataStyle); // Aplica bordes a los datos (desde A8 hasta la última fila)

// Ajustar automáticamente el ancho de las columnas
for ($col = 'A'; $col <= 'J'; $col++) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Crear el archivo Excel y forzar la descarga
$writer = new Xlsx($spreadsheet);
$filename = 'reporte_afiliados_' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
?>
