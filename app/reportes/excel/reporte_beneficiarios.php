<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
require_once 'C:/xampp/htdocs/IPSPUPTM/vendor/autoload.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$titulo = "Reporte de Beneficiarios";

// Ruta de los logos
$logo_ipspuptm = 'C:/xampp/htdocs/IPSPUPTM/recursos/img/IPSPUPTMlogo.png';
$logo_uptm = 'C:/xampp/htdocs/IPSPUPTM/recursos/img/UPTM_logo.png';

// Consulta para obtener los beneficiarios
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

// Crear un nuevo Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// --- Insertar Logos ---
// Logo IPSPUPTM en la esquina superior izquierda
$drawing = new Drawing();
$drawing->setName('Logo IPSPUPTM');
$drawing->setDescription('Logo del Instituto de Previsión Social de los Profesores de la Universidad Politécnica Territorial del Estado Mérida Kléber Ramirez');
$drawing->setPath($logo_ipspuptm);
$drawing->setHeight(45); // Tamaño del logo ajustado a 50
$drawing->setCoordinates('A1');
$drawing->setWorksheet($sheet);

// Logo UPTM en la esquina superior derecha
$drawing2 = new Drawing();
$drawing2->setName('Logo UPTM');
$drawing2->setDescription('Logo de la Universidad Politécnica Territorial del Estado Mérida Kléber Ramirez');
$drawing2->setPath($logo_uptm);
$drawing2->setHeight(45); // Tamaño del logo ajustado a 50
$drawing2->setCoordinates('H1');
$drawing2->setWorksheet($sheet);

// --- Título y Subtítulo ---
$sheet->setCellValue('A3', 'Instituto de Previsión Social de los Profesores de la Universidad Politécnica Territorial del Estado Mérida Kléber Ramirez');
$sheet->setCellValue('A4', 'Reporte de Beneficiarios');
$sheet->mergeCells('A3:H3');
$sheet->mergeCells('A4:H4');

$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);

// Establecer el ancho de la columna A para que quepa el título completo
$sheet->getColumnDimension('A')->setWidth(50); // Puedes ajustar el valor de 50 según sea necesario

// --- Fecha de Emisión ---
$fecha_emision = date('d-m-Y H:i:s');
$sheet->setCellValue('A5', 'Emitido: ' . $fecha_emision);
$sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('A5')->getFont()->setSize(10);

// --- Encabezados de la tabla ---
$headerRow = ['Cédula', 'Nombre', 'Apellido', 'F. Nacimiento', 'Género', 'Teléfono', 'Correo', 'Ocupación', 'Afiliado', 'Fecha Registro'];
$sheet->fromArray($headerRow, null, 'A7');

$headerStyle = [
    'font' => ['bold' => true],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FFFFFF00'],
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
];
$sheet->getStyle('A7:J7')->applyFromArray($headerStyle);

// --- Datos de la tabla ---
$fila = 8;
while ($row = $resultado->fetch_assoc()) {
    $sheet->setCellValue('A' . $fila, $row['cedula']);
    $sheet->setCellValue('B' . $fila, $row['nombre']);
    $sheet->setCellValue('C' . $fila, $row['apellido']);
    $sheet->setCellValue('D' . $fila, date('d-m-Y', strtotime($row['fechanacimiento'])));
    $sheet->setCellValue('E' . $fila, $row['genero']);
    $sheet->setCellValue('F' . $fila, $row['telefono']);
    $sheet->setCellValue('G' . $fila, $row['correo']);
    $sheet->setCellValue('H' . $fila, $row['ocupacion']);
    $sheet->setCellValue('I' . $fila, $row['afiliado_nombre_completo']);
    $sheet->setCellValue('J' . $fila, date('d-m-Y H:i:s', strtotime($row['created_at'])));
    $fila++;
}

$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
];
$sheet->getStyle('A8:J' . ($fila - 1))->applyFromArray($dataStyle);

for ($col = 'A'; $col <= 'J'; $col++) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Crear el archivo Excel y forzar descarga
$writer = new Xlsx($spreadsheet);
$filename = 'reporte_beneficiarios_' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');

?>
