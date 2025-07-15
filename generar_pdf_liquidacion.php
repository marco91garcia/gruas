<?php
require('fpdf/fpdf.php');
include 'db.php';
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'conductor') {
  header("Location: login.php");
  exit;
}

$usuario = $_SESSION['usuario'];
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

// Obtener servicios liquidados ese día
$stmt = $conn->prepare("SELECT s.placa, s.valor_servicio, s.porcentaje_conductor 
                        FROM servicios s 
                        WHERE s.conductor_asignado = ? AND DATE(s.fecha_creacion) = ? AND s.estado = 'liquidado'");
$stmt->bind_param("ss", $usuario, $fecha);
$stmt->execute();
$result = $stmt->get_result();

$servicios = [];
$totalServicios = 0;
$totalComision = 0;

while ($row = $result->fetch_assoc()) {
    $comision = ($row['valor_servicio'] * $row['porcentaje_conductor']) / 100;
    $servicios[] = [
        'placa' => $row['placa'],
        'valor' => $row['valor_servicio'],
        'porcentaje' => $row['porcentaje_conductor'],
        'comision' => $comision
    ];
    $totalServicios += $row['valor_servicio'];
    $totalComision += $comision;
}

// Obtener total de gastos
$gastosQuery = $conn->prepare("SELECT SUM(valor) AS total_gastos FROM gastos_conductor WHERE conductor_asignado = ? AND fecha = ?");
$gastosQuery->bind_param("ss", $usuario, $fecha);
$gastosQuery->execute();
$gastosQuery->bind_result($totalGastos);
$gastosQuery->fetch();
$gastosQuery->close();

$totalGastos = $totalGastos ?? 0;
$totalConsignar = $totalServicios - $totalComision - $totalGastos;

// Crear PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode("Liquidación de Servicios"), 0, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, utf8_decode("Conductor: ") . strtoupper($usuario), 0, 1);
$pdf->Cell(0, 10, utf8_decode("Fecha: ") . $fecha, 0, 1);

$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 10, 'Placa', 1);
$pdf->Cell(40, 10, 'Valor', 1);
$pdf->Cell(40, 10, '%', 1);
$pdf->Cell(60, 10, 'Comisión', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 12);
foreach ($servicios as $s) {
    $pdf->Cell(40, 10, $s['placa'], 1);
    $pdf->Cell(40, 10, '$' . number_format($s['valor']), 1);
    $pdf->Cell(40, 10, $s['porcentaje'] . '%', 1);
    $pdf->Cell(60, 10, '$' . number_format($s['comision']), 1);
    $pdf->Ln();
}

$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, utf8_decode("Total Servicios: $") . number_format($totalServicios), 0, 1);
$pdf->Cell(0, 10, utf8_decode("Total Comisión: $") . number_format($totalComision), 0, 1);
$pdf->Cell(0, 10, utf8_decode("Total Gastos: $") . number_format($totalGastos), 0, 1);
$pdf->Cell(0, 10, utf8_decode("Valor a Consignar: $") . number_format($totalConsignar), 0, 1);

$pdf->Output('I', 'liquidacion_' . $usuario . '_' . $fecha . '.pdf');
exit;
