<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'C:/xampp/htdocs/IPSPUPTM/assets/fpdf.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

$titulo = "Reporte de Afiliados";

class PDF_Afiliados extends FPDF {
    protected $titulo;

    function __construct($titulo) {
        parent::__construct('L', 'mm', 'Letter'); // Landscape
        $this->titulo = $titulo;
    }

    function Header() {
        $logo_ipsp = 'C:/xampp/htdocs/IPSPUPTM/recursos/img/IPSPUPTMlogo.png';
        $logo_uptm = 'C:/xampp/htdocs/IPSPUPTM/recursos/img/UPTM_logo.png';
        if (file_exists($logo_ipsp))  $this->Image($logo_ipsp, 10, 4, 25);
        if (file_exists($logo_uptm))  $this->Image($logo_uptm, 245, 4, 25);

        $this->SetFont('Arial', 'B', 11);
        $this->SetY(12);
        $this->SetTextColor(51, 51, 51);
        $this->SetX(45);
        $this->MultiCell(200, 5, utf8_decode("Instituto de Previsión Social de los Profesores de la\nUniversidad Politécnica Territorial Kléber Ramirez del Estado Mérida"), 0, 'C');
        $this->SetFont('Arial', 'B', 13);
        $this->SetX(45);
        $this->Cell(200, 8, utf8_decode($this->titulo), 0, 1, 'C');
        
        $this->SetY(33);
        $this->SetDrawColor(6, 41, 116);
        $this->SetLineWidth(1.5);
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
$sql = "SELECT p.cedula, p.nombre, p.apellido, p.fechanacimiento, p.genero,
               p.telefono, p.correo, p.ocupacion, a.created_at
        FROM afiliados a JOIN persona p ON a.cedula = p.cedula";
$resultado = $conn->query($sql);

if (ob_get_length()) ob_end_clean();

$pdf = new PDF_Afiliados($titulo);
$pdf->AliasNbPages();
$pdf->AddPage();

// Cabecera de tabla
$pdf->SetFillColor(6, 41, 116);
$pdf->SetTextColor(255);
$pdf->SetFont('Arial', 'B', 8);
$w = [20, 30, 30, 25, 20, 25, 50, 25, 30]; // Sum: 255
$pageWidth = $pdf->GetPageWidth();
$tableWidth = array_sum($w);
$marginLeft = ($pageWidth - $tableWidth) / 2;

$pdf->SetX($marginLeft);
$headers = ['Cédula', 'Nombre', 'Apellido', 'F. Nacimiento', 'Género', 'Teléfono', 'Correo', 'Ocupación', 'Fecha Registro'];
foreach ($headers as $i => $h)
    $pdf->Cell($w[$i], 9, utf8_decode($h), 0, 0, 'C', true);
$pdf->Ln();

$pdf->SetFillColor(235, 242, 250);
$pdf->SetTextColor(30, 30, 30);
$pdf->SetDrawColor(150, 150, 150);
$pdf->SetLineWidth(0.3);
$pdf->SetFont('Arial', '', 8);
$fill = false;
if ($resultado) {
    while ($row = $resultado->fetch_assoc()) {
        $pdf->SetX($marginLeft);
        $pdf->Cell($w[0], 8, $row['cedula'], 'B', 0, 'C', $fill);
        $nombre = utf8_decode(mb_strimwidth($row['nombre'], 0, 18, "...", 'UTF-8'));
        $pdf->Cell($w[1], 8, $nombre, 'B', 0, 'L', $fill);
        $apellido = utf8_decode(mb_strimwidth($row['apellido'], 0, 18, "...", 'UTF-8'));
        $pdf->Cell($w[2], 8, $apellido, 'B', 0, 'L', $fill);
        $pdf->Cell($w[3], 8, date('d-m-Y', strtotime($row['fechanacimiento'])), 'B', 0, 'C', $fill);
        $pdf->Cell($w[4], 8, utf8_decode($row['genero']), 'B', 0, 'C', $fill);
        $pdf->Cell($w[5], 8, $row['telefono'], 'B', 0, 'C', $fill);
        $correo = mb_strimwidth($row['correo'], 0, 25, "...");
        $pdf->Cell($w[6], 8, $correo, 'B', 0, 'L', $fill);
        $ocupacion = utf8_decode(mb_strimwidth($row['ocupacion'], 0, 15, "...", 'UTF-8'));
        $pdf->Cell($w[7], 8, $ocupacion, 'B', 0, 'L', $fill);
        $pdf->Cell($w[8], 8, date('d-m-Y', strtotime($row['created_at'])), 'B', 0, 'C', $fill);
        $pdf->Ln();
        $fill = !$fill;
    }
}

$pdf->Output('D', 'reporte_afiliados_' . date('Ymd') . '.pdf');
?>