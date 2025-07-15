<?php
require('fpdf/fpdf.php');

// Datos de ejemplo (puedes reemplazar por datos de tu base de datos)
$conductor = "Julian Perez";
$placa = "WPS276";
$fecha = date("d/m/Y");
$servicios = [
    ['descripcion' => 'Servicio Bogotá → Soacha', 'placa' => 'WPS276', 'valor' => 120000],
    ['descripcion' => 'Servicio Chía → Kennedy', 'placa' => 'WPS276', 'valor' => 100000],
];
$porcentaje = 20; // % de comisión
$otros_descuentos = 15000;

$total_servicios = array_sum(array_column($servicios, 'valor'));
$total_comision = $total_servicios * ($porcentaje / 100);
$valor_a_pagar = $total_comision - $otros_descuentos;

// Crear PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, utf8_decode("Cuenta de Cobro - S.O.S. GRÚAS"), 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, utf8_decode("Conductor: $conductor   |   Placa: $placa   |   Fecha: $fecha"), 0, 1);

$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(100, 10, utf8_decode("Descripción del servicio"), 1);
$pdf->Cell(40, 10, "Placa", 1);
$pdf->Cell(40, 10, "Valor ($)", 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 12);
foreach ($servicios as $s) {
    $pdf->Cell(100, 10, utf8_decode($s['descripcion']), 1);
    $pdf->Cell(40, 10, $s['placa'], 1);
    $pdf->Cell(40, 10, number_format($s['valor'], 0), 1);
    $pdf->Ln();
}

$pdf->Ln(5);
$pdf->Cell(180, 10, "Total Servicios: $" . number_format($total_servicios, 0), 0, 1);
$pdf->Cell(180, 10, "Comisión del $porcentaje%: $" . number_format($total_comision, 0), 0, 1);
$pdf->Cell(180, 10, "Otros descuentos: $" . number_format($otros_descuentos, 0), 0, 1);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(180, 10, "TOTAL A PAGAR: $" . number_format($valor_a_pagar, 0), 0, 1);

$pdf->Output();
    