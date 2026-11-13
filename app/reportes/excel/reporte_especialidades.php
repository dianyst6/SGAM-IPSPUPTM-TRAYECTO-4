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

// Parámetros: se espera recibir el tipo de reporte vía GET
$tipo_reporte = isset($_GET['tipo_reporte']) ? $_GET['tipo_reporte'] : 'semanal';
$titulo_base = "Reporte de Especialidades Más Solicitadas";
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

$query = "
    SELECT
        e.nombre_especialidad,
        COUNT(c.id_especialidad) AS total_solicitudes
    FROM especialidades e
    JOIN citas c ON e.id_especialidad = c.id_especialidad
    WHERE $condicion_fecha
    GROUP BY e.nombre_especialidad
    ORDER BY total_solicitudes DESC
";

$resultado = $conn->query($query);

if (!$resultado) {
    echo "Error en la consulta de especialidades: " . $conn->error;
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
$drawing->setPath('C:/xampp/htdocs/IPSPUPTM/recursos/img/IPSPUPTMlogo.png'); // Ruta del logo
$drawing->setHeight(40);
$drawing->setCoordinates('A1');
$drawing->setWorksheet($sheet);

// Logo UPTM en la esquina superior derecha
$drawing2 = new Drawing();
$drawing2->setName('Logo UPTM');
$drawing2->setDescription('Logo de la Universidad Politécnica Territorial del Estado Mérida Kléber Ramirez');
$drawing2->setPath('C:/xampp/htdocs/IPSPUPTM/recursos/img/UPTM_logo.png'); // Ruta del logo
$drawing2->setHeight(40);
$drawing2->setCoordinates('H1'); // Ajustar según el número de columnas
$drawing2->setWorksheet($sheet);

// --- Título y Subtítulo ---
$sheet->setCellValue('A3', 'Instituto de Previsión Social de los Profesores de la UPTM');
$sheet->setCellValue('A4', $titulo); // Usar el título dinámico
$sheet->mergeCells('A3:H3'); // Combinar celdas para el título
$sheet->mergeCells('A4:H4'); // Combinar celdas para el subtítulo

$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);

// Establecer el ancho de la columna A para que quepa el título completo
$sheet->getColumnDimension('A')->setWidth(50); // Ajustar según sea necesario

// --- Fecha de Emisión ---
$fecha_emision = date('d-m-Y');
$sheet->setCellValue('A5', 'Emitido: ' . $fecha_emision);
$sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('A5')->getFont()->setSize(10);

// --- Encabezados de la tabla ---
$headerRow = ['Especialidad', 'Total Solicitudes'];
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
$sheet->getStyle('A7:B7')->applyFromArray($headerStyle);

// --- Datos de la tabla ---
$fila = 8;
while ($row = $resultado->fetch_assoc()) {
    $sheet->setCellValue('A' . $fila, $row['nombre_especialidad']);
    $sheet->setCellValue('B' . $fila, $row['total_solicitudes']);
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
$sheet->getStyle('A8:B' . ($fila - 1))->applyFromArray($dataStyle);

for ($col = 'A'; $col <= 'B'; $col++) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Crear el archivo Excel y forzar descarga
$writer = new Xlsx($spreadsheet);
$filename = 'reporte_especialidades_' . $tipo_reporte . '_' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
?>
