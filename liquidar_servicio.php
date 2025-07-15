<?php
include 'db.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Obtener lista de conductores
$conductores = $conn->query("SELECT DISTINCT conductor_asignado FROM servicios WHERE conductor_asignado IS NOT NULL");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Liquidación de Servicios</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { font-family: 'Segoe UI'; background: #f4f4f4; padding: 30px; }
    .formulario { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    table { width: 100%; margin-top: 20px; border-collapse: collapse; }
    th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
    th { background: #343a40; color: white; }
    .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
    .btn:hover { background: #0056b3; }
  </style>
</head>
<body>

<div class="formulario">
  <h2>Liquidación de Servicios</h2>
  <form method="POST" action="generar_pdf_liquidacion.php">
    <label>Conductor:</label>
    <select name="conductor" required>
      <option value="">Seleccione un conductor</option>
      <?php while ($c = $conductores->fetch_assoc()): ?>
        <option value="<?= $c['conductor_asignado'] ?>"><?= strtoupper($c['conductor_asignado']) ?></option>
      <?php endwhile; ?>
    </select>

    <br><br>

    <label>Fecha Desde:</label>
    <input type="date" name="desde" required>

    <label>Fecha Hasta:</label>
    <input type="date" name="hasta" required>

    <br><br>

    <label>Porcentaje de comisión (%):</label>
    <input type="number" name="porcentaje" value="20" min="0" max="100" required>

    <label>Descuentos ($):</label>
    <input type="number" name="descuentos" value="0" min="0">

    <br><br>
    <button type="submit" class="btn">Generar Liquidación PDF</button>
  </form>
</div>

</body>
</html>
