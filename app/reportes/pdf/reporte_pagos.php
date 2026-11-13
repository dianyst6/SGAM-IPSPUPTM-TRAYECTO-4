<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
ob_start(); // Prevenir cualquier salida accidental que corrompa el PDF
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'C:/xampp/htdocs/IPSPUPTM/assets/fpdf.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

// Obtener el tipo de pago (contrato o externo)
$tipo_pago = $_GET['tipo_pago'] ?? 'contrato';

$titulo_base = "Reporte de Pagos";
$titulo = ($tipo_pago == 'contrato') ? "$titulo_base de Contratos" : "$titulo_base Externos";

$condicion_fecha_contrato = "";
$condicion_fecha_externo = "";

$fecha_inicio_raw = null;
$fecha_fin_raw = null;

if (isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])) {
    $fecha_inicio_raw = $_GET['fecha_inicio'];
    $fecha_fin_raw = $_GET['fecha_fin'];
    $fecha_inicio = $conn->real_escape_string($fecha_inicio_raw);
    $fecha_fin = $conn->real_escape_string($fecha_fin_raw);
    $condicion_fecha_contrato = "WHERE pc.fecha_pago BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    $condicion_fecha_externo = "WHERE p.fecha_pago  BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    $titulo .= " - Del " . date('d/m/Y', strtotime($fecha_inicio)) . " al " . date('d/m/Y', strtotime($fecha_fin));
}

// Los pagos externos corresponden solo a Comunidad UPTM (personas sin rol).
$filtro_rol_externo = "a_u.id_persona IS NULL AND b_u.id_persona IS NULL";
$where_externo = $condicion_fecha_externo === ''
    ? "WHERE $filtro_rol_externo"
    : $condicion_fecha_externo . " AND $filtro_rol_externo";

// ─────────────────────────────────────────────────────────────────────────────
// CLASE PDF ORIENTACIÓN LANDSCAPE (APAISADA)
// ─────────────────────────────────────────────────────────────────────────────
class PDF extends FPDF
{
    protected $titulo;
    protected $logo_ipspuptm;
    protected $logo_uptm;

    function __construct($titulo, $orientation = 'L', $unit = 'mm', $size = 'Letter')
    {
        parent::__construct($orientation, $unit, $size);
        $this->titulo = $titulo;
        $this->logo_ipspuptm = 'C:/xampp/htdocs/IPSPUPTM/recursos/img/IPSPUPTMlogo.png';
        $this->logo_uptm = 'C:/xampp/htdocs/IPSPUPTM/recursos/img/UPTM_logo.png';
    }

    function Header()
    {
        if (file_exists($this->logo_ipspuptm)) {
            $this->Image($this->logo_ipspuptm, 10, 4, 22);
        }
        if (file_exists($this->logo_uptm)) {
            $this->Image($this->logo_uptm, 245, 4, 22);
        }

        $this->SetFont('Arial', 'B', 11);
        $this->SetY(6);
        $this->SetTextColor(51, 51, 51);
        $this->SetX(35);
        $this->MultiCell(198, 5, utf8_decode(
            "Instituto de Previsión Social de los Profesores de la\n" .
            "Universidad Politécnica Territorial Kléber Ramírez del Estado Mérida"
        ), 0, 'C');

        $this->SetFont('Arial', 'B', 13);
        $this->SetX(35);
        $this->Cell(198, 7, utf8_decode($this->titulo), 0, 1, 'C');

        $this->SetDrawColor(6, 41, 116);
        $this->SetLineWidth(1.2);
        $this->Line(10, $this->GetY() + 1, $this->GetPageWidth() - 10, $this->GetY() + 1);
        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 8, utf8_decode('Fecha de Emisión: ' . date('d-m-Y')), 0, 0, 'C');
        $this->Ln(5);
        $this->Cell(0, 8, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TABLA MATRICIAL: afiliados × meses
    // ─────────────────────────────────────────────────────────────────────────
    function TablaContratos($meses_activos, $pagos_por_afiliado, $nombres_afiliados, $cedulas_afiliados)
    {

        $nMeses = count($meses_activos);

        // ── Anchos de columna ───────────────────────────────────────────────
        $wCedula = 22;
        $wNombre = 52;
        $wTotal = 22;

        // Ancho disponible para las columnas de mes
        $pageW = $this->GetPageWidth();
        $margenes = 10 + 10; // margen izq + der
        $fixedW = $wCedula + $wNombre + $wTotal;
        $available = $pageW - $margenes - $fixedW;

        // Ancho de cada mes (mínimo 16, máximo 28)
        $wMes = ($nMeses > 0) ? max(16, min(28, floor($available / $nMeses))) : 20;

        // Ancho total de la tabla
        $tableW = $wCedula + $wNombre + ($wMes * $nMeses) + $wTotal;
        $startX = ($pageW - $tableW) / 2;

        // Nombres cortos de meses en español
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

        // ── ENCABEZADO ──────────────────────────────────────────────────────
        $this->SetFillColor(6, 41, 116);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->SetLineWidth(0.3);
        $this->SetDrawColor(200, 200, 200);

        $this->SetX($startX);
        $this->Cell($wCedula, 9, utf8_decode('Cédula'), 1, 0, 'C', true);
        $this->Cell($wNombre, 9, utf8_decode('Apellidos y Nombres'), 1, 0, 'C', true);
        foreach ($meses_activos as $m) {
            $label = $mesesNombres[$m['mes']] . ' ' . substr($m['anio'], 2, 2);
            $this->Cell($wMes, 9, $label, 1, 0, 'C', true);
        }
        $this->Cell($wTotal, 9, 'Total', 1, 1, 'C', true);

        // ── FILAS DE AFILIADOS ───────────────────────────────────────────────
        $this->SetTextColor(30, 30, 30);
        $this->SetFont('Arial', '', 7.5);

        // Acumuladores por mes y total general
        $totalPorMes = array_fill(0, $nMeses, 0.0);
        $totalGeneral = 0.0;
        $fill = false;

        foreach ($cedulas_afiliados as $cedula) {
            // Altura de fila dinámica (nombre puede necesitar 2 líneas)
            $nombre = utf8_decode($nombres_afiliados[$cedula]);
            $maxChars = floor($wNombre / 1.8); // aprox chars que caben
            if (strlen($nombre) > $maxChars) {
                $nombre = substr($nombre, 0, $maxChars - 2) . '..';
            }

            $bgColor = $fill ? [240, 245, 252] : [255, 255, 255];
            $this->SetFillColor($bgColor[0], $bgColor[1], $bgColor[2]);

            $this->SetX($startX);
            $this->Cell($wCedula, 8, number_format($cedula, 0, '', '.'), 1, 0, 'C', true);
            $this->Cell($wNombre, 8, $nombre, 1, 0, 'L', true);

            $totalAfiliado = 0.0;
            foreach ($meses_activos as $idx => $m) {
                $key = $m['anio'] . '-' . str_pad($m['mes'], 2, '0', STR_PAD_LEFT);
                $monto = $pagos_por_afiliado[$cedula][$key] ?? 0.0;
                $totalAfiliado += $monto;
                $totalPorMes[$idx] += $monto;

                if ($monto > 0) {
                    $cell_text = number_format($monto, 2);
                } else {
                    $cell_text = '-';
                }
                $this->Cell($wMes, 8, $cell_text, 1, 0, 'R', true);
            }

            $totalGeneral += $totalAfiliado;
            // Celda de total del afiliado (resaltada)
            $this->SetFillColor(200, 220, 255);
            $this->SetFont('Arial', 'B', 7.5);
            $this->Cell($wTotal, 8, number_format($totalAfiliado, 2), 1, 1, 'R', true);
            $this->SetFont('Arial', '', 7.5);

            // Restaurar color de fila
            $this->SetFillColor($bgColor[0], $bgColor[1], $bgColor[2]);
            $fill = !$fill;
        }

        // ── FILA DE TOTALES POR MES ──────────────────────────────────────────
        $this->SetFillColor(6, 41, 116);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);

        $this->SetX($startX);
        $this->Cell($wCedula + $wNombre, 9, utf8_decode('TOTAL'), 1, 0, 'C', true);
        foreach ($totalPorMes as $t) {
            $this->Cell($wMes, 9, number_format($t, 2), 1, 0, 'R', true);
        }
        $this->Cell($wTotal, 9, number_format($totalGeneral, 2), 1, 1, 'R', true);

        // ── TOTAL DE AFILIADOS ───────────────────────────────────────────────
        $this->Ln(4);
        $this->SetFillColor(235, 240, 255);
        $this->SetTextColor(6, 41, 116);
        $this->SetFont('Arial', 'B', 9);
        $this->SetX($startX);
        $totalAfiliados = count($cedulas_afiliados);
        $this->Cell(
            $wCedula + $wNombre + ($wMes * $nMeses) + $wTotal,
            9,
            utf8_decode("Total de Afiliados: $totalAfiliados"),
            1,
            1,
            'C',
            true
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TABLA SIMPLE PARA PAGOS EXTERNOS (sin cambios)
    // ─────────────────────────────────────────────────────────────────────────
    function TablaExternos($header, $data)
    {
        $w = [25, 65, 55, 30, 30]; // Sum: 205

        $pageWidth = $this->GetPageWidth();
        $tableWidth = array_sum($w);
        $marginLeft = ($pageWidth - $tableWidth) / 2;

        $this->SetFillColor(6, 41, 116);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 9);
        $this->SetLineWidth(0.3);
        $this->SetDrawColor(0);

        $this->SetX($marginLeft);
        foreach ($header as $i => $h) {
            $this->Cell($w[$i], 9, utf8_decode($h), 1, 0, 'C', true);
        }
        $this->Ln();

        $this->SetTextColor(30, 30, 30);
        $this->SetFont('Arial', '', 8);
        $fill = false;
        foreach ($data as $row) {
            $this->SetFillColor($fill ? 235 : 255, $fill ? 242 : 255, $fill ? 250 : 255);
            $this->SetX($marginLeft);
            $this->Cell($w[0], 8, date('d-m-Y', strtotime($row['fecha'])), 1, 0, 'C', $fill);
            $this->Cell($w[1], 8, utf8_decode(mb_strimwidth($row['paciente'], 0, 35, '..', 'UTF-8')), 1, 0, 'L', $fill);
            $this->Cell($w[2], 8, utf8_decode(mb_strimwidth($row['referencia'], 0, 28, '..', 'UTF-8')), 1, 0, 'L', $fill);
            $this->Cell($w[3], 8, number_format($row['monto'], 2) . ' Bs', 1, 0, 'R', $fill);
            $this->Cell($w[4], 8, utf8_decode($row['metodo']), 1, 1, 'C', $fill);
            $fill = !$fill;
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// CONSULTAS Y CONSTRUCCIÓN DE DATOS
// ─────────────────────────────────────────────────────────────────────────────

if ($tipo_pago == 'contrato') {

    // 1. Obtener todos los pagos del periodo
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

    // Estructuras de datos
    $pagos_por_afiliado = []; // [cedula][YYYY-MM] = monto
    $nombres_afiliados = []; // [cedula] = nombre
    $meses_set = []; // set de meses únicos (YYYY-MM)
    $cedulas_orden = []; // orden de aparición de afiliados

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

    // Ordenar meses cronológicamente
    ksort($meses_set);
    $meses_activos = array_values($meses_set);

    // Limpiar buffer y generar PDF
    if (ob_get_length())
        ob_end_clean();

    $pdf = new PDF($titulo, 'L');
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 9);

    if (empty($cedulas_orden)) {
        $pdf->SetTextColor(30, 30, 30);
        $pdf->Cell(0, 10, utf8_decode('No hay registros de pagos de contratos para el periodo seleccionado.'), 0, 1, 'C');
    } else {
        $pdf->TablaContratos($meses_activos, $pagos_por_afiliado, $nombres_afiliados, $cedulas_orden);
    }

} else {
    // ── PAGOS EXTERNOS ────────────────────────────────────────────────────────
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

    $header = ['Fecha', 'Paciente', 'Especialidad', 'Monto', 'Método'];
    $data_ext = [];
    $resultado = $conn->query($sql);
    if ($resultado) {
        while ($row = $resultado->fetch_assoc()) {
            $data_ext[] = [
                'fecha' => $row['fecha_pago'],
                'paciente' => $row['nombre_paciente'],
                'referencia' => $row['referencia'],
                'monto' => $row['monto'],
                'metodo' => $row['metodo_pago'],
            ];
        }
    }

    if (ob_get_length())
        ob_end_clean();

    $pdf = new PDF($titulo, 'L');
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 9);

    if (empty($data_ext)) {
        $pdf->Cell(0, 10, utf8_decode('No hay registros de pagos externos para el periodo seleccionado.'), 0, 1, 'C');
    } else {
        $pdf->TablaExternos($header, $data_ext);
    }
}

$filename = 'reporte_pagos_' . $tipo_pago . '_' . date('Ymd') . '.pdf';
$pdf->Output('D', $filename);
?>