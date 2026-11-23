<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
require('C:/xampp/htdocs/IPSPUPTM/assets/fpdf.php');

if (!isset($_GET['id'])) {
    die("ID de pago no especificado.");
}

$id_pago = (int) $_GET['id'];

$stmt = $conn->prepare(
    "SELECT pc.ID_pago, pc.fecha_pago, pc.monto_cuota, pc.numero_cuota,
            pc.metodo_pago, pc.tipo_pago, pc.tasa_bcv, pc.monto_bs,
            p.nombre, p.apellido, p.cedula,
            pl.nombre_plan, cp.anio
       FROM pagos_contrato pc
       INNER JOIN contrato_plan cp ON pc.ID_contrato = cp.ID_contrato
       INNER JOIN persona p ON cp.id_persona = p.id_persona
       INNER JOIN planes pl ON cp.ID_planes_contrato = pl.ID_planes
      WHERE pc.ID_pago = ?
      LIMIT 1"
);
$stmt->bind_param('i', $id_pago);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    die("Recibo no encontrado.");
}

$concepto = $row['tipo_pago'] === 'Pago Inicial'
    ? 'Pago Inicial (30 %)'
    : 'Cuota N° ' . $row['numero_cuota'];

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(6, 41, 116);
        $this->Cell(0, 10, utf8_decode('IPSP-UPTM'), 0, 1, 'C');

        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, utf8_decode('Recibo de Pago de Contrato'), 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetTextColor(0, 0, 0);

// Datos del recibo
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, utf8_decode('N° de Recibo:'), 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, str_pad($row['ID_pago'], 6, '0', STR_PAD_LEFT), 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Fecha de Pago:', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, date('d/m/Y', strtotime($row['fecha_pago'])), 0, 1);

$pdf->Ln(5);

// Paciente
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetFillColor(6, 41, 116);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(190, 10, utf8_decode('Datos del Paciente'), 1, 1, 'C', true);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, utf8_decode('Paciente:'), 1, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(140, 10, utf8_decode($row['nombre'] . ' ' . $row['apellido']), 1, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, utf8_decode('Cédula:'), 1, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(140, 10, $row['cedula'], 1, 1);

$pdf->Ln(5);

// Detalles del pago
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetFillColor(6, 41, 116);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(190, 10, utf8_decode('Detalles del Pago'), 1, 1, 'C', true);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Plan:', 1, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(140, 10, utf8_decode($row['nombre_plan'] . ' (' . $row['anio'] . ')'), 1, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, utf8_decode('Concepto:'), 1, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(140, 10, utf8_decode($concepto), 1, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, utf8_decode('Método de Pago:'), 1, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(140, 10, utf8_decode($row['metodo_pago']), 1, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Monto en Divisas:', 1, 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(0, 128, 0);
$pdf->Cell(140, 10, '$ ' . number_format($row['monto_cuota'], 2), 1, 1);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, utf8_decode('Tasa BCV:'), 1, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(140, 10, number_format($row['tasa_bcv'], 2) . ' Bs/$', 1, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Monto en Bolívares:', 1, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(140, 10, 'Bs ' . number_format($row['monto_bs'], 2), 1, 1);

$pdf->Ln(20);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, '___________________________________', 0, 1, 'C');
$pdf->Cell(0, 10, 'Firma / Sello IPSP-UPTM', 0, 1, 'C');

$pdf->Output('I', 'Recibo_Pago_' . $row['ID_pago'] . '.pdf');
?>
