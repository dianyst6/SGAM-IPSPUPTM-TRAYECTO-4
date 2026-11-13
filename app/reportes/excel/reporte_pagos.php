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

// Obtener el tipo de pago (contrato o externo)
$tipo_pago = $_GET['tipo_pago'] ?? 'contrato';

$titulo_base = "Reporte de Pagos";
$titulo = ($tipo_pago == 'contrato') ? "$titulo_base de Contratos" : "$titulo_base Externos";

$condicion_fecha_contrato = "";
$condicion_fecha_externo = "";

if (isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])) {
    $fecha_inicio = $conn->real_escape_string($_GET['fecha_inicio']);
    $fecha_fin = $conn->real_escape_string($_GET['fecha_fin']);
    $condicion_fecha_contrato = "WHERE pc.fecha_pago BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    $condicion_fecha_externo = "WHERE p.fecha_pago BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    $titulo .= " - Del " . date('d/m/Y', strtotime($fecha_inicio)) . " al " . date('d/m/Y', strtotime($fecha_fin));
}

// Los pagos externos corresponden solo a Comunidad UPTM (personas sin rol).
$filtro_rol_externo = "a_u.id_persona IS NULL AND b_u.id_persona IS NULL";
$where_externo = $condicion_fecha_externo === ''
    ? "WHERE $filtro_rol_externo"
    : $condicion_fecha_externo . " AND $filtro_rol_externo";

// Crear un nuevo Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// --- Insertar Logos ---
$drawing = new Drawing();
$drawing->setName('Logo IPSPUPTM');
$drawing->setDescription('Logo IPSPUPTM');
$drawing->setPath('C:/xampp/htdocs/IPSPUPTM/recursos/img/IPSPUPTMlogo.png'); // Ruta del logo
$drawing->setHeight(50);
$drawing->setCoordinates('A1');
$drawing->setWorksheet($sheet);

$drawing2 = new Drawing();
$drawing2->setName('Logo UPTM');
$drawing2->setDescription('Logo UPTM');
$drawing2->setPath('C:/xampp/htdocs/IPSPUPTM/recursos/img/UPTM_logo.png'); // Ruta del logo
$drawing2->setHeight(50);
$drawing2->setCoordinates('H1'); // Se ajustará dinámicamente si es necesario
$drawing2->setWorksheet($sheet);

// --- Título y Subtítulo ---
$titulo_completo = 'Instituto de Previsión Social de los Profesores de la UPTM';
$sheet->setCellValue('A3', $titulo_completo);
$sheet->setCellValue('A4', $titulo);
$sheet->mergeCells('A3:H3');
$sheet->mergeCells('A4:H4');

$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);

// --- Fecha de Emisión ---
$fecha_emision = date('d-m-Y');
$sheet->setCellValue('A5', 'Emitido: ' . $fecha_emision);
$sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('A5')->getFont()->setSize(10);

$headerStyle = [
    'font' => ['bold' => true],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FFFFFF00'], // Amarillo
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ],
];

$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
];

if ($tipo_pago == 'contrato') {
    $sql = "SELECT
                p.cedula                                    AS cedula,
                CONCAT(p.apellido, ' ', p.nombre)          AS nombre_afiliado,
                YEAR(pc.fecha_pago)                        AS anio,
                MONTH(pc.fecha_pago)                       AS mes,
                SUM(pc.monto_cuota)                        AS monto_mes
            FROM pagos_contrato pc
            INNER JOIN contrato_plan cp ON pc.ID_contrato = cp.ID_contrato
            INNER JOIN persona p       ON p.id_persona = cp.id_persona
            $condicion_fecha_contrato
            GROUP BY p.cedula, p.apellido, p.nombre, YEAR(pc.fecha_pago), MONTH(pc.fecha_pago)
            ORDER BY p.apellido, p.nombre, anio, mes";

    $resultado = $conn->query($sql);

    $pagos_por_afiliado = [];
    $nombres_afiliados = [];
    $meses_set = [];
    $cedulas_orden = [];

    if ($resultado) {
        while ($row = $resultado->fetch_assoc()) {
            $ced = $row['cedula'];
            $key = $row['anio'] . '-' . str_pad($row['mes'], 2, '0', STR_PAD_LEFT);

            if (!isset($nombres_afiliados[$ced])) {
                $nombres_afiliados[$ced] = $row['nombre_afiliado'];
                $cedulas_orden[] = $ced;
            }
            $pagos_por_afiliado[$ced][$key] = (float) $row['monto_mes'];
            $meses_set[$key] = ['anio' => $row['anio'], 'mes' => (int) $row['mes']];
        }
    }

    ksort($meses_set);
    $meses_activos = array_values($meses_set);

    $mesesNombres = [
        1 => 'Ene',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Abr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ago',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dic'
    ];

    $headerRow = ['Cédula', 'Apellidos y Nombres'];
    foreach ($meses_activos as $m) {
        $headerRow[] = $mesesNombres[$m['mes']] . ' ' . substr($m['anio'], 2, 2);
    }
    $headerRow[] = 'Total';

    $sheet->fromArray($headerRow, null, 'A7');

    $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headerRow));
    $sheet->getStyle('A7:' . $lastCol . '7')->applyFromArray($headerStyle);
    $drawing2->setCoordinates($lastCol . '1');

    $fila = 8;
    $totalGeneral = 0.0;
    $totalPorMes = array_fill(0, count($meses_activos), 0.0);

    foreach ($cedulas_orden as $cedula) {
        $sheet->setCellValue('A' . $fila, $cedula);
        $sheet->setCellValue('B' . $fila, $nombres_afiliados[$cedula]);

        $totalAfiliado = 0.0;
        $colIdx = 3; // Columna C

        foreach ($meses_activos as $idx => $m) {
            $key = $m['anio'] . '-' . str_pad($m['mes'], 2, '0', STR_PAD_LEFT);
            $monto = $pagos_por_afiliado[$cedula][$key] ?? 0.0;

            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($colLetter . $fila, $monto);
            if ($monto > 0) {
                $sheet->getStyle($colLetter . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
            } else {
                $sheet->setCellValue($colLetter . $fila, '-');
                $sheet->getStyle($colLetter . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            $totalAfiliado += $monto;
            $totalPorMes[$idx] += $monto;
            $colIdx++;
        }

        $totalGeneral += $totalAfiliado;
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue($colLetter . $fila, $totalAfiliado);
        $sheet->getStyle($colLetter . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle($colLetter . $fila)->getFont()->setBold(true);

        $fila++;
    }

    if (count($cedulas_orden) > 0) {
        // Totales por mes
        $sheet->setCellValue('B' . $fila, 'TOTAL');
        $sheet->getStyle('B' . $fila)->getFont()->setBold(true);
        $sheet->getStyle('B' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $colIdx = 3;
        foreach ($totalPorMes as $idx => $t) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($colLetter . $fila, $t);
            $sheet->getStyle($colLetter . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle($colLetter . $fila)->getFont()->setBold(true);
            $colIdx++;
        }

        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue($colLetter . $fila, $totalGeneral);
        $sheet->getStyle($colLetter . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle($colLetter . $fila)->getFont()->setBold(true);

        $sheet->getStyle('A8:' . $lastCol . $fila)->applyFromArray($dataStyle);
    } else {
        $sheet->setCellValue('A8', 'No hay registros de pagos de contratos para el periodo seleccionado.');
        $sheet->mergeCells('A8:' . $lastCol . '8');
    }

    for ($i = 1; $i <= count($headerRow); $i++) {
        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
} else {
    // Pagos Externos
    $sql = "SELECT
                p.fecha_pago,
                p.monto_final              AS monto,
                e.nombre_especialidad      AS referencia,
                p.metodo_pago,
                CONCAT(u.nombre, ' ', u.apellido) AS nombre_paciente
            FROM pagos_externos p
            INNER JOIN citas c         ON p.id_cita = c.id_cita
            INNER JOIN persona u       ON u.id_persona = c.id_persona
            INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
            LEFT JOIN afiliados a_u    ON a_u.id_persona = u.id_persona
            LEFT JOIN beneficiarios b_u ON b_u.id_persona = u.id_persona
            $where_externo
            ORDER BY p.fecha_pago DESC";

    $resultado = $conn->query($sql);

    $headerRow = ['Fecha', 'Paciente', 'Especialidad', 'Monto', 'Método'];
    $sheet->fromArray($headerRow, null, 'A7');
    $sheet->getStyle('A7:E7')->applyFromArray($headerStyle);
    $drawing2->setCoordinates('E1');

    $fila = 8;
    if ($resultado && $resultado->num_rows > 0) {
        while ($row = $resultado->fetch_assoc()) {
            $sheet->setCellValue('A' . $fila, date('d-m-Y', strtotime($row['fecha_pago'])));
            $sheet->setCellValue('B' . $fila, $row['nombre_paciente']);
            $sheet->setCellValue('C' . $fila, $row['referencia']);
            $sheet->setCellValue('D' . $fila, $row['monto']);
            $sheet->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue('E' . $fila, $row['metodo_pago']);
            $fila++;
        }
        $sheet->getStyle('A8:E' . ($fila - 1))->applyFromArray($dataStyle);
    } else {
        $sheet->setCellValue('A8', 'No hay registros de pagos externos para el periodo seleccionado.');
        $sheet->mergeCells('A8:E8');
    }

    for ($col = 'A'; $col <= 'E'; $col++) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// Crear el archivo Excel y forzar la descarga
$writer = new Xlsx($spreadsheet);
$filename = 'reporte_pagos_' . $tipo_pago . '_' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
?>