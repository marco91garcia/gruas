<?php
require 'db.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=informe_servicios.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Placa', 'Origen', 'Destino', 'Estado', 'Fecha']);

$resultado = $conn->query("SELECT id, placa, origen, destino, estado, fecha_creacion FROM servicios");

while ($row = $resultado->fetch_assoc()) {
    fputcsv($output, $row);
}
fclose($output);
exit;
?>
