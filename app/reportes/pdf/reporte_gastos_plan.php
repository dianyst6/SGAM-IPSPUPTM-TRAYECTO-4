<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'C:/xampp/htdocs/IPSPUPTM/assets/fpdf.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

// ─── Parámetro opcional: filtrar por cédula de afiliado ───────────────────────
$cedula_filtro = isset($_GET['cedula']) ? $conn->real_escape_string($_GET['cedula']) : null;

$titulo = "Historial de Gastos del Plan";
if ($cedula_filtro) {
    $titulo .= " - Afiliado V-" . $cedula_filtro;
}

// ─── Clase PDF ────────────────────────────────────────────────────────────────
class PDF extends FPDF
{
    protected $titulo;
    protected $logo_ipspuptm;
    protected $logo_uptm;

    function __construct($titulo, $orientation = 'P', $unit = 'mm', $size = 'Letter')
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
            $this->Image($this->logo_uptm, 175, 4, 22);
        }

        $this->SetFont('Arial', 'B', 11);
        $this->SetY(6);
        $this->SetTextColor(51, 51, 51);
        $this->SetX(35);
        $this->MultiCell(142, 5, utf8_decode(
            "Instituto de Previsión Social de los Profesores de la\n" .
            "Universidad Politécnica Territorial Kléber Ramírez del Estado Mérida"
        ), 0, 'C');

        $this->SetFont('Arial', 'B', 13);
        $this->SetX(35);
        $this->Cell(142, 7, utf8_decode($this->titulo), 0, 1, 'C');

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

    // ── Bloque de resumen de cobertura por afiliado ───────────────────────────
    function ResumenCobertura($nombre_afiliado, $cedula, $nombre_plan, $cobertura, $gastado, $saldo)
    {
        $pageW  = $this->GetPageWidth();
        $startX = 10;
        $w      = $pageW - 20;

        // Nombre afiliado + plan
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(6, 41, 116);
        $this->SetTextColor(255, 255, 255);
        $this->SetX($startX);
        $this->Cell($w, 8, utf8_decode("Afiliado: $nombre_afiliado  |  Cédula: V-$cedula  |  Plan: $nombre_plan"), 0, 1, 'C', true);
        $this->Ln(2);

        // Tres tarjetas de resumen
        $wCard = ($w - 6) / 3;

        // Monto Póliza
        $this->SetFillColor(6, 41, 116);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->SetX($startX);
        $this->Cell($wCard, 7, utf8_decode('Monto de Póliza'), 0, 0, 'C', true);
        $this->SetX($this->GetX() + 3);
        // Consumo
        $this->SetFillColor(204, 153, 0);
        $this->Cell($wCard, 7, utf8_decode('Consumo de Seguro'), 0, 0, 'C', true);
        $this->SetX($this->GetX() + 3);
        // Saldo
        $this->SetFillColor(0, 128, 64);
        $this->Cell($wCard, 7, utf8_decode('Saldo Disponible'), 0, 1, 'C', true);

        // Valores
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(220, 230, 255);
        $this->SetTextColor(6, 41, 116);
        $this->SetX($startX);
        $this->Cell($wCard, 9, utf8_decode('$' . number_format($cobertura, 2)), 0, 0, 'C', true);
        $this->SetX($this->GetX() + 3);
        $this->SetFillColor(255, 243, 205);
        $this->SetTextColor(120, 80, 0);
        $this->Cell($wCard, 9, utf8_decode('$' . number_format($gastado, 2)), 0, 0, 'C', true);
        $this->SetX($this->GetX() + 3);
        $this->SetFillColor(200, 240, 220);
        $this->SetTextColor(0, 100, 50);
        $this->Cell($wCard, 9, utf8_decode('$' . number_format($saldo, 2)), 0, 1, 'C', true);
        $this->Ln(4);
    }

    // ── Tabla de historial de gastos ─────────────────────────────────────────
    function TablaHistorial($registros)
    {
        $pageW  = $this->GetPageWidth();
        $startX = 10;

        // Anchos: Fecha | Paciente | Servicio/Examen | Tipo | Monto
        $w = [38, 42, 60, 22, 28]; // Sum = 190 (para Letter portrait con márgenes 10)

        // Encabezado
        $this->SetFillColor(6, 41, 116);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->SetLineWidth(0.3);
        $this->SetDrawColor(180, 180, 180);

        $headers = ['Fecha', 'Paciente (C.I.)', 'Servicio / Examen', 'Tipo', 'Monto Desc. ($)'];
        $this->SetX($startX);
        foreach ($headers as $i => $h) {
            $this->Cell($w[$i], 9, utf8_decode($h), 1, 0, 'C', true);
        }
        $this->Ln();

        // Filas
        $this->SetTextColor(30, 30, 30);
        $this->SetFont('Arial', '', 8);
        $fill = false;

        $totalGastado = 0.0;

        foreach ($registros as $r) {
            $bgColor = $fill ? [240, 245, 252] : [255, 255, 255];
            $this->SetFillColor($bgColor[0], $bgColor[1], $bgColor[2]);

            $fecha    = date('d-m-Y H:i', strtotime($r['fecha_consumo']));
            $paciente = utf8_decode(mb_strimwidth($r['nombre_per'] . ' ' . $r['apellido_per'], 0, 22, '..', 'UTF-8'));
            $ci       = 'V-' . $r['cedula'];
            $servicio = utf8_decode(mb_strimwidth($r['nombre_servicio'], 0, 35, '..', 'UTF-8'));
            $tipo     = !empty($r['ID_examen_plan']) ? 'Institución' : 'Externo';
            $monto    = (float)$r['monto_descontado'];
            $totalGastado += $monto;

            // Calcular altura de fila (nombre puede necesitar sub-línea para cedula)
            $this->SetX($startX);
            $this->Cell($w[0], 8, $fecha, 1, 0, 'C', true);

            // Paciente: nombre + cedula en la misma celda con salto manual si hay espacio
            $yBefore = $this->GetY();
            $xBefore = $this->GetX();
            $this->MultiCell($w[1], 4, $paciente . "\n" . $ci, 1, 'L', true);
            $yAfter = $this->GetY();
            $rowH   = max(8, $yAfter - $yBefore);

            // Volver a la misma Y para las celdas restantes
            $this->SetXY($xBefore + $w[1], $yBefore);
            $this->Cell($w[2], $rowH, $servicio, 1, 0, 'L', true);

            // Color tipo
            if ($tipo === 'Institución') {
                $this->SetFillColor(6, 41, 116);
                $this->SetTextColor(255, 255, 255);
            } else {
                $this->SetFillColor(204, 153, 0);
                $this->SetTextColor(80, 50, 0);
            }
            $this->Cell($w[3], $rowH, utf8_decode($tipo), 1, 0, 'C', true);

            // Monto en rojo
            $this->SetFillColor($bgColor[0], $bgColor[1], $bgColor[2]);
            $this->SetTextColor(180, 0, 0);
            $this->SetFont('Arial', 'B', 8);
            $this->Cell($w[4], $rowH, '-' . number_format($monto, 2), 1, 1, 'R', true);

            // Restaurar
            $this->SetFont('Arial', '', 8);
            $this->SetTextColor(30, 30, 30);
            $fill = !$fill;
        }

        // Fila de total
        $totalW = array_sum($w) - $w[4];
        $this->SetFillColor(6, 41, 116);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->SetX($startX);
        $this->Cell($totalW, 9, utf8_decode('TOTAL DESCONTADO'), 1, 0, 'R', true);
        $this->SetFillColor(200, 10, 10);
        $this->Cell($w[4], 9, '-' . number_format($totalGastado, 2), 1, 1, 'R', true);
    }
}

// ─── Consulta de afiliados ────────────────────────────────────────────────────
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

// ─── Construir datos agrupados por afiliado ───────────────────────────────────
$afiliados = [];
if ($res_afil) {
    while ($row = $res_afil->fetch_assoc()) {
        $ced = $row['cedula'];

        // Total gastado en dinero
        $sql_g = "SELECT COALESCE(SUM(monto_descontado),0) AS total FROM consumo_plan WHERE ID_contrato_plan = ?";
        $st = $conn->prepare($sql_g);
        $st->bind_param("i", $row['ID_contrato']);
        $st->execute();
        $gastado = (float)$st->get_result()->fetch_assoc()['total'];
        $st->close();

        // Historial
        $sql_h = "SELECT
                    per2.nombre AS nombre_per, per2.apellido AS apellido_per, per2.cedula,
                    c.ID_examen_plan,
                    COALESCE(e.nombre_examen, c.nombre_estudio_externo, 'Servicio/Consulta') AS nombre_servicio,
                    c.monto_descontado,
                    c.fecha_consumo
                  FROM consumo_plan c
                  LEFT JOIN examenes e  ON c.ID_examen_plan = e.ID_examen
                  LEFT JOIN persona per2 ON c.id_persona = per2.id_persona
                  WHERE c.ID_contrato_plan = ?
                  ORDER BY c.fecha_consumo DESC";
        $st2 = $conn->prepare($sql_h);
        $st2->bind_param("i", $row['ID_contrato']);
        $st2->execute();
        $registros = $st2->get_result()->fetch_all(MYSQLI_ASSOC);
        $st2->close();

        $afiliados[] = [
            'cedula'          => $ced,
            'nombre_afiliado' => $row['nombre_afiliado'],
            'nombre_plan'     => $row['nombre_plan'],
            'cobertura'       => (float)$row['monto_cobertura'],
            'gastado'         => $gastado,
            'saldo'           => (float)$row['monto_cobertura'] - $gastado,
            'registros'       => $registros,
        ];
    }
}

// ─── Generar PDF ──────────────────────────────────────────────────────────────
if (ob_get_length()) ob_end_clean();

$pdf = new PDF($titulo, 'P');
$pdf->AliasNbPages();
$pdf->SetFont('Arial', '', 9);

if (empty($afiliados)) {
    $pdf->AddPage();
    $pdf->SetTextColor(30, 30, 30);
    $pdf->Cell(0, 10, utf8_decode('No se encontraron afiliados con contrato activo.'), 0, 1, 'C');
} else {
    foreach ($afiliados as $afil) {
        $pdf->AddPage();
        $pdf->ResumenCobertura(
            $afil['nombre_afiliado'],
            $afil['cedula'],
            $afil['nombre_plan'],
            $afil['cobertura'],
            $afil['gastado'],
            $afil['saldo']
        );

        // Nota aclaratoria
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(180, 0, 0);
        $pdf->Cell(0, 6, utf8_decode('* Los montos reflejan lo que se ha descontado del saldo de Cobertura.'), 0, 1, 'L');
        $pdf->SetTextColor(30, 30, 30);
        $pdf->Ln(1);

        if (empty($afil['registros'])) {
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(0, 8, utf8_decode('No hay registros de consumos o cobros de póliza para este plan aún.'), 0, 1, 'C');
        } else {
            $pdf->TablaHistorial($afil['registros']);
        }
    }
}

$filename = 'historial_gastos_plan_' . date('Ymd') . '.pdf';
$pdf->Output('D', $filename);
?>
