<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'C:/xampp/htdocs/IPSPUPTM/assets/fpdf.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

$tipo_reporte = isset($_GET['tipo_reporte']) ? $_GET['tipo_reporte'] : 'semanal';

$titulo_base = "Reporte de Especialidades Más Solicitadas";
$condicion_fecha = "";

if ($tipo_reporte === 'personalizado' && isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])) {
    $fecha_inicio = $conn->real_escape_string($_GET['fecha_inicio']);
    $fecha_fin = $conn->real_escape_string($_GET['fecha_fin']);
    $condicion_fecha = "c.fecha_cita BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    $titulo = "$titulo_base - Del " . date('d/m/Y', strtotime($fecha_inicio)) . " al " . date('d/m/Y', strtotime($fecha_fin));
} else {
    switch ($tipo_reporte) {
        case 'semanal':   $interval = 'INTERVAL 1 WEEK';  $titulo = "$titulo_base - Semanal";   break;
        case 'quincenal': $interval = 'INTERVAL 2 WEEK';  $titulo = "$titulo_base - Quincenal"; break;
        case 'mensual':   $interval = 'INTERVAL 1 MONTH'; $titulo = "$titulo_base - Mensual";   break;
        default:          $interval = 'INTERVAL 1 WEEK';  $titulo = "$titulo_base - Semanal";   break;
    }
    $condicion_fecha = "c.fecha_cita >= DATE_SUB(CURDATE(), $interval)";
}

class PDF_Especialidades extends FPDF {
    protected $titulo;

    function __construct($titulo) {
        parent::__construct('P', 'mm', 'Letter');
        $this->titulo = $titulo;
    }

    function Header() {
        $logo_ipsp = 'C:/xampp/htdocs/IPSPUPTM/recursos/img/IPSPUPTMlogo.png';
        $logo_uptm = 'C:/xampp/htdocs/IPSPUPTM/recursos/img/UPTM_logo.png';
        if (file_exists($logo_ipsp)) $this->Image($logo_ipsp, 10, 4, 25);
        if (file_exists($logo_uptm)) $this->Image($logo_uptm, 175, 4, 25);

        $this->SetFont('Arial', 'B', 11);
        $this->SetY(10);
        $this->SetTextColor(51, 51, 51);
        $this->SetX(40);
        $this->MultiCell(135, 5, utf8_decode("Instituto de Previsión Social de los Profesores de la\nUniversidad Politécnica Territorial Kléber Ramirez del Estado Mérida"), 0, 'C');
        $this->SetFont('Arial', 'B', 13);
        $this->SetX(40);
        $this->Cell(135, 8, utf8_decode($this->titulo), 0, 1, 'C');
        
        $this->SetY(33);
        $this->SetDrawColor(6, 41, 116);
        $this->SetLineWidth(1.5); // Línea gruesa y evidente
        $this->Line(10, $this->GetY(), $this->GetPageWidth() - 10, $this->GetY());
        $this->Ln(6);
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 8, utf8_decode('Fecha de Emisión: ' . date('d-m-Y')), 0, 0, 'C');
        $this->Ln(5);
        $this->Cell(0, 8, 'Página ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Consulta
$sql = "SELECT e.nombre_especialidad, COUNT(c.id_especialidad) AS total_solicitudes
        FROM especialidades e
        JOIN citas c ON e.id_especialidad = c.id_especialidad
        WHERE $condicion_fecha
        GROUP BY e.nombre_especialidad
        ORDER BY total_solicitudes DESC";
$resultado = $conn->query($sql);

if (ob_get_length()) ob_end_clean();

$pdf = new PDF_Especialidades($titulo);
$pdf->AliasNbPages();
$pdf->AddPage();

// Cabecera de tabla
$pdf->SetFillColor(6, 41, 116);
$pdf->SetTextColor(255);
$pdf->SetFont('Arial', 'B', 10);
$w = [120, 70]; // Total = 190
$pageWidth = $pdf->GetPageWidth();
$tableWidth = array_sum($w);
$marginLeft = ($pageWidth - $tableWidth) / 2;

$pdf->SetX($marginLeft);
$headers = ['Especialidad', 'Total Solicitudes'];
foreach ($headers as $i => $h)
    $pdf->Cell($w[$i], 9, utf8_decode($h), 0, 0, 'C', true);
$pdf->Ln();

$pdf->SetFillColor(235, 242, 250);
$pdf->SetTextColor(30, 30, 30);
$pdf->SetDrawColor(150, 150, 150);
$pdf->SetLineWidth(0.3);
$pdf->SetFont('Arial', '', 10);
$fill = false;
if ($resultado) {
    while ($row = $resultado->fetch_assoc()) {
        $pdf->SetX($marginLeft);
        $pdf->Cell($w[0], 8, utf8_decode($row['nombre_especialidad']), 'B', 0, 'L', $fill);
        $pdf->Cell($w[1], 8, $row['total_solicitudes'], 'B', 0, 'C', $fill);
        $pdf->Ln();
        $fill = !$fill;
    }
}

$pdf->Output('D', 'reporte_especialidades_' . $tipo_reporte . '_' . date('Ymd') . '.pdf');
?>