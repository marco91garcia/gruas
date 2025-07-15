<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'conductor') {
  header("Location: login.php");
  exit;
}

$usuario = $_SESSION['usuario'];
$fechaHoy = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_gasto'])) {
    $tipo_gasto = $_POST['tipo_gasto'];
    $valor = $_POST['valor'];
    $motivo = $_POST['motivo'] ?? '';
    $soporte = $_FILES['soporte']['name'] ?? null;

    if ($soporte) {
        $ruta_destino = __DIR__ . '/fotos/gastos/' . basename($soporte);
        move_uploaded_file($_FILES['soporte']['tmp_name'], $ruta_destino);
    }

    $stmt = $conn->prepare("INSERT INTO gastos_conductor (conductor_asignado, fecha, tipo_gasto, valor, motivo, soporte) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $usuario, $fechaHoy, $tipo_gasto, $valor, $motivo, $soporte);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('✅ Gasto registrado correctamente.'); window.location.href='gestion-conductor.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrar Gasto</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      padding: 20px;
      background: #f7f9fa;
    }
    .card {
      background: white;
      padding: 20px;
      border-radius: 15px;
      max-width: 500px;
      margin: auto;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: #333;
    }
    form {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    input, select, textarea {
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 16px;
    }
    button {
      background: #007bff;
      color: white;
      font-weight: bold;
      padding: 14px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
    }
    button:hover {
      background: #0056b3;
    }
    .tabla-gastos {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
  font-size: 15px;
}
.tabla-gastos th, .tabla-gastos td {
  border: 1px solid #ccc;
  padding: 8px;
  text-align: center;
}
.tabla-gastos th {
  background-color: #e9ecef;
}
@media (max-width: 600px) {
  .tabla-gastos {
    font-size: 13px;
  }
}

  </style>
</head>
<body>
  <div class="card">
    <h2>🧾 Registrar Gasto</h2>
    <form method="POST" enctype="multipart/form-data">
      <select name="tipo_gasto" required>
        <option value="">Seleccione tipo de gasto</option>
        <option value="ACPM">ACPM</option>
        <option value="Parqueadero">Parqueadero</option>
        <option value="Cambio de Aceite">Cambio de Aceite</option>
        <option value="Llantas">Llantas</option>
        <option value="Otros">Otros</option>
      </select>
      <input type="number" name="valor" placeholder="Valor del gasto" required>
      <textarea name="motivo" placeholder="Motivo (opcional)"></textarea>
      <input type="file" name="soporte">
      <button type="submit" name="guardar_gasto">Guardar Gasto</button>
    </form>
    <?php
// CONSULTAR GASTOS YA REGISTRADOS HOY
$stmt = $conn->prepare("SELECT tipo_gasto, valor, motivo, soporte FROM gastos_conductor WHERE conductor_asignado = ? AND fecha = ?");
$stmt->bind_param("ss", $usuario, $fechaHoy);
$stmt->execute();
$resultado = $stmt->get_result();
if ($resultado->num_rows > 0):
?>
<div class="card">
  <h3>📋 Gastos Registrados Hoy</h3>
  <table class="tabla-gastos">
    <thead>
      <tr>
        <th>Tipo</th>
        <th>Valor</th>
        <th>Motivo</th>
        <th>Soporte</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($g = $resultado->fetch_assoc()): ?>
      <tr>
        <td><?php echo ucfirst($g['tipo_gasto']); ?></td>
        <td>$<?php echo number_format($g['valor']); ?></td>
        <td><?php echo $g['motivo'] ?: '—'; ?></td>
        <td>
          <?php if ($g['soporte']): ?>
            <a href="fotos/gastos/<?php echo $g['soporte']; ?>" target="_blank">📎 Ver</a>
          <?php else: ?>
            Sin archivo
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php endif; $stmt->close(); ?>

  </div>
</body>
</html>
