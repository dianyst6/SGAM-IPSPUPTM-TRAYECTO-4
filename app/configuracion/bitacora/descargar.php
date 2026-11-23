<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1]);
  
require 'C:/xampp/htdocs/IPSPUPTM/assets/fpdf.php';  
require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';  

class PDF_Table extends FPDF {
    var $widths;
    var $aligns;

    function SetWidths($w) {
        $this->widths = $w;
    }

    function SetAligns($a) {
        $this->aligns = $a;
    }

    function Row($data) {
        $nb = 0;
        for($i=0; $i<count($data); $i++)
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        $h = 5 * $nb;
        $this->CheckPageBreak($h);
        for($i=0; $i<count($data); $i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x, $y, $w, $h);
            $this->MultiCell($w, 5, $data[$i], 0, $a);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    function CheckPageBreak($h) {
        if($this->GetY() + $h > $this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if($nb > 0 and $s[$nb-1] == "\n") $nb--;
        $sep = -1;
        $i = 0; $j = 0; $l = 0; $nl = 1;
        while($i < $nb) {
            $c = $s[$i];
            if($c == "\n") {
                $i++; $sep = -1; $j = $i; $l = 0; $nl++;
                continue;
            }
            if($c == ' ') $sep = $i;
            $l += $cw[$c];
            if($l > $wmax) {
                if($sep == -1) {
                    if($i == $j) $i++;
                } else $i = $sep + 1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else $i++;
        }
        return $nl;
    }

    function Header() {
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, utf8_decode('IPSP - Bitácora de Actividades'), 0, 1, 'C');
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, utf8_decode('Fecha de reporte: ' . date('d/m/Y H:i:s')), 0, 1, 'R');
        $this->Ln(5);
        
        $this->SetFillColor(6, 41, 116); // Azul institucional
        $this->SetTextColor(255);
        $this->SetDrawColor(200);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', 10);
        
        $this->Cell(35, 7, 'Usuario', 1, 0, 'C', true);
        $this->Cell(45, 7, utf8_decode('Acción'), 1, 0, 'C', true);
        $this->Cell(80, 7, utf8_decode('Descripción'), 1, 0, 'C', true);
        $this->Cell(30, 7, 'Fecha', 1, 1, 'C', true);
        
        $this->SetTextColor(0);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Configuración y Generación
$pdf = new PDF_Table();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetWidths(array(35, 45, 80, 30));
$pdf->SetAligns(array('L', 'L', 'L', 'C'));
$pdf->SetFont('Arial', '', 9);

// Obtener datos
$sql = "SELECT usuario, accion, descripcion, fecha FROM bitacora ORDER BY fecha DESC";  
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pdf->Row(array(
            utf8_decode($row['usuario']),
            utf8_decode($row['accion']),
            utf8_decode($row['descripcion']),
            $row['fecha']
        ));
    }
} else {
    $pdf->Cell(190, 10, utf8_decode('No hay registros disponibles.'), 1, 1, 'C');
}

// Salida
$pdf->Output('D', 'bitacora_'.date('YmdHis').'.pdf');
$conn->close();
?>
  
