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
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Font;

// ─── Parámetro opcional: filtrar por afiliado ─────────────────────────────────
$cedula_filtro = isset($_GET['cedula']) ? $conn->real_escape_string($_GET['cedula']) : null;

$logo_ipspuptm = 'C:/xampp/htdocs/IPSPUPTM/recursos/img/IPSPUPTMlogo.png';
$logo_uptm = 'C:/xampp/htdocs/IPSPUPTM/recursos/img/UPTM_logo.png';

// ─── Consulta afiliados con contrato activo ───────────────────────────────────
$where_afil = $cedula_filtro ? "AND a.cedula = '$cedula_filtro'" : "";

$sql_afiliados = "SELECT
                    a.cedula,
                    CONCAT(per.apellido, ' ', per.nombre) AS nombre_afiliado,
                    p.nombre_plan,
                    p.monto_cobertura,
                    cp.ID_contrato
                  FROM afiliados a
                  INNER JOIN contrato_plan cp ON a.id_persona = cp.id_persona
                  INNER JOIN planes p         ON cp.ID_planes_contrato = p.ID_planes
                  INNER JOIN persona per      ON a.id_persona = per.id_persona
                  WHERE cp.estado_contrato = 'Activo'
                  $where_afil
                  ORDER BY per.apellido, per.nombre";

$res_afil = $conn->query($sql_afiliados);

$afiliados = [];
if ($res_afil) {
    while ($row = $res_afil->fetch_assoc()) {
        // Gastado
        $sql_g = "SELECT COALESCE(SUM(monto_descontado),0) AS total FROM consumo_plan WHERE ID_contrato_plan = ?";
        $st = $conn->prepare($sql_g);
        $st->bind_param("i", $row['ID_contrato']);
        $st->execute();
        $gastado = (float) $st->get_result()->fetch_assoc()['total'];
        $st->close();

        // Historial
        $sql_h = "SELECT
                    per2.nombre AS nombre_per, per2.apellido AS apellido_per, per2.cedula,
                    c.ID_examen_plan,
                    COALESCE(e.nombre_examen, c.nombre_estudio_externo, 'Servicio/Consulta') AS nombre_servicio,
                    c.monto_descontado,
                    c.fecha_consumo
                  FROM consumo_plan c
                  LEFT JOIN examenes e   ON c.ID_examen_plan = e.ID_examen
                  LEFT JOIN persona per2 ON c.id_persona = per2.id_persona
                  WHERE c.ID_contrato_plan = ?
                  ORDER BY c.fecha_consumo DESC";
        $st2 = $conn->prepare($sql_h);
        $st2->bind_param("i", $row['ID_contrato']);
        $st2->execute();
        $registros = $st2->get_result()->fetch_all(MYSQLI_ASSOC);
        $st2->close();

        $afiliados[] = [
            'cedula' => $row['cedula'],
            'nombre_afiliado' => $row['nombre_afiliado'],
            'nombre_plan' => $row['nombre_plan'],
            'cobertura' => (float) $row['monto_cobertura'],
            'gastado' => $gastado,
            'saldo' => (float) $row['monto_cobertura'] - $gastado,
            'registros' => $registros,
        ];
    }
}

// ─── Crear Spreadsheet ────────────────────────────────────────────────────────
$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0); // Eliminar hoja por defecto

// Paleta de colores
$COLOR_HEADER_BG = '0D2E74'; // Azul oscuro institucional
$COLOR_HEADER_FG = 'FFFFFF';
$COLOR_POLIZA_BG = 'DCE6FF';
$COLOR_POLIZA_FG = '0D2E74';
$COLOR_CONSUMO_BG = 'FFF3CD';
$COLOR_CONSUMO_FG = '7C5000';
$COLOR_SALDO_BG = 'C8F0DC';
$COLOR_SALDO_FG = '006432';
$COLOR_INSTITUCION = '0D2E74';
$COLOR_EXTERNO = 'CC9900';
$COLOR_MONTO_FG = 'B40000';
$COLOR_TOTAL_BG = '0D2E74';
$COLOR_ALT_ROW = 'F0F5FC';

foreach ($afiliados as $afil_idx => $afil) {
    // Nombre de pestaña: cédula truncada
    $sheetName = 'V-' . $afil['cedula'];
    $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $sheetName);
    $spreadsheet->addSheet($sheet);

    // ── Logos ────────────────────────────────────────────────────────────────
    if (file_exists($logo_ipspuptm)) {
        $d1 = new Drawing();
        $d1->setName('Logo IPSP');
        $d1->setPath($logo_ipspuptm);
        $d1->setHeight(40);
        $d1->setCoordinates('A1');
        $d1->setWorksheet($sheet);
    }
    if (file_exists($logo_uptm)) {
        $d2 = new Drawing();
        $d2->setName('Logo UPTM');
        $d2->setPath($logo_uptm);
        $d2->setHeight(40);
        $d2->setCoordinates('G1');
        $d2->setWorksheet($sheet);
    }

    // ── Encabezado institucional ─────────────────────────────────────────────
    $sheet->mergeCells('A3:G3');
    $sheet->setCellValue('A3', 'Instituto de Previsión Social de los Profesores de la Universidad Politécnica Territorial Kléber Ramírez del Estado Mérida');
    $sheet->getStyle('A3')->applyFromArray([
        'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF' . $COLOR_HEADER_BG]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
    ]);
    $sheet->getRowDimension(3)->setRowHeight(28);

    $sheet->mergeCells('A4:G4');
    $sheet->setCellValue('A4', 'Historial de Gastos del Plan');
    $sheet->getStyle('A4')->applyFromArray([
        'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF' . $COLOR_HEADER_BG]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);

    $sheet->setCellValue('A5', 'Emitido: ' . date('d-m-Y'));
    $sheet->getStyle('A5')->getFont()->setItalic(true)->setSize(9);

    // ── Datos del afiliado ───────────────────────────────────────────────────
    $sheet->mergeCells('A6:G6');
    $sheet->setCellValue('A6', 'Afiliado: ' . $afil['nombre_afiliado'] . '   |   Cédula: V-' . $afil['cedula'] . '   |   Plan: ' . $afil['nombre_plan']);
    $sheet->getStyle('A6')->applyFromArray([
        'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF' . $COLOR_HEADER_FG]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $COLOR_HEADER_BG]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getRowDimension(6)->setRowHeight(18);

    // ── Tarjetas resumen de cobertura ────────────────────────────────────────
    // Fila 7: etiquetas
    $sheet->setCellValue('B7', 'Monto de Póliza');
    $sheet->setCellValue('D7', 'Consumo de Seguro');
    $sheet->setCellValue('F7', 'Saldo Disponible');
    foreach (['B7', 'D7', 'F7'] as $cell) {
        $sheet->getStyle($cell)->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF' . $COLOR_HEADER_FG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }
    $sheet->getStyle('B7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF' . $COLOR_HEADER_BG);
    $sheet->getStyle('D7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCC9900');
    $sheet->getStyle('F7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF006432');

    // Fila 8: valores
    $sheet->setCellValue('B8', '$' . number_format($afil['cobertura'], 2));
    $sheet->setCellValue('D8', '$' . number_format($afil['gastado'], 2));
    $sheet->setCellValue('F8', '$' . number_format($afil['saldo'], 2));

    $sheet->getStyle('B8')->applyFromArray([
        'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF' . $COLOR_POLIZA_FG]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $COLOR_POLIZA_BG]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getStyle('D8')->applyFromArray([
        'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF' . $COLOR_CONSUMO_FG]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $COLOR_CONSUMO_BG]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getStyle('F8')->applyFromArray([
        'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF' . $COLOR_SALDO_FG]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $COLOR_SALDO_BG]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);

    // ── Nota ─────────────────────────────────────────────────────────────────
    $sheet->mergeCells('A9:G9');
    $sheet->setCellValue('A9', '* Los montos reflejan lo que se ha descontando del saldo de Cobertura.');
    $sheet->getStyle('A9')->applyFromArray([
        'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FFB40000']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
    ]);

    // ── Encabezados de la tabla de historial ─────────────────────────────────
    $headerRow = ['Fecha', 'Paciente', 'C.I.', 'Servicio / Examen', 'Tipo', 'Monto Descontado ($)'];
    $sheet->fromArray($headerRow, null, 'A11');

    $colsHeader = ['A11', 'B11', 'C11', 'D11', 'E11', 'F11'];
    $sheet->getStyle('A11:F11')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['argb' => 'FF' . $COLOR_HEADER_FG]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $COLOR_HEADER_BG]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF888888']]],
    ]);

    // ── Datos de historial ────────────────────────────────────────────────────
    $fila = 12;

    if (empty($afil['registros'])) {
        $sheet->mergeCells("A{$fila}:F{$fila}");
        $sheet->setCellValue("A{$fila}", 'No hay registros de consumos o cobros de póliza para este plan aún.');
        $sheet->getStyle("A{$fila}")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['argb' => 'FF777777']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $fila++;
    } else {
        $totalMonto = 0.0;
        $isAlt = false;

        foreach ($afil['registros'] as $r) {
            $fecha = date('d-m-Y H:i', strtotime($r['fecha_consumo']));
            $paciente = $r['nombre_per'] . ' ' . $r['apellido_per'];
            $ci = 'V-' . $r['cedula'];
            $servicio = $r['nombre_servicio'];
            $tipo = !empty($r['ID_examen_plan']) ? 'Institución' : 'Externo';
            $monto = (float) $r['monto_descontado'];
            $totalMonto += $monto;

            $sheet->setCellValue("A{$fila}", $fecha);
            $sheet->setCellValue("B{$fila}", $paciente);
            $sheet->setCellValue("C{$fila}", $ci);
            $sheet->setCellValue("D{$fila}", $servicio);
            $sheet->setCellValue("E{$fila}", $tipo);
            $sheet->setCellValue("F{$fila}", -$monto); // Negativo para mostrar descuento

            // Fila alternada
            $rowBg = $isAlt ? 'FFEFF5FF' : 'FFFFFFFF';
            $sheet->getStyle("A{$fila}:F{$fila}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $rowBg]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Color especial en "Tipo"
            if ($tipo === 'Institución') {
                $sheet->getStyle("E{$fila}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF' . $COLOR_INSTITUCION]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            } else {
                $sheet->getStyle("E{$fila}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF' . $COLOR_EXTERNO]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }

            // Monto en rojo, negrita
            $sheet->getStyle("F{$fila}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FF' . $COLOR_MONTO_FG]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'numberFormat' => ['formatCode' => '"$"#,##0.00_-'],
            ]);

            // Centro para fecha y cedula
            $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $isAlt = !$isAlt;
            $fila++;
        }

        // ── Fila de total ─────────────────────────────────────────────────────
        $sheet->mergeCells("A{$fila}:E{$fila}");
        $sheet->setCellValue("A{$fila}", 'TOTAL DESCONTADO');
        $sheet->setCellValue("F{$fila}", -$totalMonto);
        $sheet->getStyle("A{$fila}:F{$fila}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $COLOR_TOTAL_BG]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("F{$fila}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFC0C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            'numberFormat' => ['formatCode' => '"$"#,##0.00_-'],
        ]);
        $fila++;
    }

    // ── Ajustar anchos de columna ─────────────────────────────────────────────
    $sheet->getColumnDimension('A')->setWidth(20);  // Fecha
    $sheet->getColumnDimension('B')->setWidth(26);  // Paciente
    $sheet->getColumnDimension('C')->setWidth(14);  // C.I.
    $sheet->getColumnDimension('D')->setWidth(32);  // Servicio
    $sheet->getColumnDimension('E')->setWidth(14);  // Tipo
    $sheet->getColumnDimension('F')->setWidth(20);  // Monto
    $sheet->getColumnDimension('G')->setWidth(5);
    $sheet->getRowDimension(8)->setRowHeight(20);
}

// ─── Descargar ────────────────────────────────────────────────────────────────
$filename = 'historial_gastos_plan_' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
?>