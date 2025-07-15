<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'conductor') {
  header("Location: login.php");
  exit;
}

$usuario = $_SESSION['usuario'];
$fecha = $_GET['fecha'] ?? date('Y-m-d');

// Obtener ingresos registrados
$stmt = $conn->prepare("SELECT s.placa, s.valor_servicio, i.forma_pago, i.porcentaje FROM ingresos_conductor i INNER JOIN servicios s ON i.servicio_id = s.id WHERE i.conductor = ? AND i.fecha = ?");
$stmt->bind_param("ss", $usuario, $fecha);
$stmt->execute();
$resultado = $stmt->get_result();

$total_servicios = 0;
$total_comision = 0;
$filas = [];

while ($row = $resultado->fetch_assoc()) {
  $comision = ($row['valor_servicio'] * $row['porcentaje']) / 100;
  $total_servicios += $row['valor_servicio'];
  $total_comision += $comision;
  $row['comision'] = $comision;
  $filas[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Vista previa de liquidación</title>
  <style>
    body { font-family: sans-serif; background: #f0f0f0; padding: 20px; }
    .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px #ccc; max-width: 800px; margin: auto; }
    h2 { text-align: center; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
    .total { font-weight: bold; background: #e0ffe0; }
    .descargar { margin-top: 20px; text-align: center; }
    .btn { padding: 10px 20px; background: green; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
  </style>
</head>
<body>
<div class="card">
  <h2>Vista previa de liquidación</h2>
  <table>
    <thead>
      <tr>
        <th>Placa</th>
        <th>Valor del Servicio</th>
        <th>Forma de Pago</th>
        <th>% Comisión</th>
        <th>Comisión</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($filas as $f): ?>
      <tr>
        <td><?php echo $f['placa']; ?></td>
        <td>$<?php echo number_format($f['valor_servicio'], 0); ?></td>
        <td><?php echo ucfirst($f['forma_pago']); ?></td>
        <td><?php echo $f['porcentaje']; ?>%</td>
        <td>$<?php echo number_format($f['comision'], 0); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr class="total">
        <td colspan="4">Total Servicios</td>
        <td>$<?php echo number_format($total_servicios, 0); ?></td>
      </tr>
      <tr class="total">
        <td colspan="4">Total Comisión</td>
        <td>$<?php echo number_format($total_comision, 0); ?></td>
      </tr>
    </tfoot>
  </table>

  <div class="descargar">
    <a class="btn" href="generar_pdf_liquidacion.php?fecha=<?php echo $fecha; ?>">Descargar PDF</a>
  </div>
</div>
</body>
</html>
