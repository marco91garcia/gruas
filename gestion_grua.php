<?php
session_start();
include 'db.php';

if (isset($_POST['registro_aceite'])) {
  $usuarioCambio = $_POST['usuario'];
  $km_actual = intval($_POST['km_actual']);
  $intervalo = ($_POST['intervalo'] === 'otro') ? intval($_POST['intervalo_otro']) : intval($_POST['intervalo']);
  $obs = $_POST['observacion'] ?? '';
  $fecha = date('Y-m-d');

  $stmt = $conn->prepare("INSERT INTO cambio_aceite (usuario, fecha, km_actual, km_intervalo, observacion) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("ssiis", $usuarioCambio, $fecha, $km_actual, $intervalo, $obs);
  $stmt->execute();
  $stmt->close();

  echo "<script>alert('✅ Cambio de aceite registrado'); window.location.href='gestion_grua.php?placa=$usuarioCambio&fecha=$fecha';</script>";
  exit;
}


if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'supervisor')) {
  header("Location: login.php");
  exit;
}

$usuarioActual = $_SESSION['usuario'];
$placaSeleccionada = $_GET['placa'] ?? '';
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$mesActual = date('Y-m');

// Obtener placas desde usuarios
$placas = [];
$res = $conn->query("SELECT usuario FROM usuarios WHERE rol = 'conductor'");
while ($row = $res->fetch_assoc()) {
  $placas[] = $row['usuario'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Grúa</title>
  <style>
    body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
    .card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    select, input, button { padding: 10px; border-radius: 5px; border: 1px solid #ccc; margin-top: 10px; }
    table { width: 100%; margin-top: 10px; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
  </style>
</head>
<body>

<div class="card">
  <h2>🚚 Gestión de Grúa</h2>
  <form method="GET">
    <label>Seleccionar Usuario:</label>
    <select name="placa" required>
      <option value="">-- Selecciona --</option>
      <?php foreach ($placas as $p): ?>
        <option value="<?= $p ?>" <?= $placaSeleccionada === $p ? 'selected' : '' ?>><?= $p ?></option>
      <?php endforeach; ?>
    </select>
    <label>Fecha:</label>
    <input type="date" name="fecha" value="<?= $fecha ?>">
    <button type="submit">Filtrar</button>
  </form>
  <?php
// Obtener datos del último cambio de aceite
$stmt = $conn->prepare("SELECT fecha, km_actual, km_intervalo, observacion FROM cambio_aceite WHERE usuario = ? ORDER BY fecha DESC LIMIT 1");
$stmt->bind_param("s", $placaSeleccionada); // ahora placaSeleccionada contiene el usuario
$stmt->execute();
$stmt->bind_result($fechaAceite, $kmAceite, $intervaloAceite, $obsAceite);
$tieneCambio = $stmt->fetch();
$stmt->close();

$proximoCambio = $tieneCambio ? $kmAceite + $intervaloAceite : null;
$alertaAceite = ($tieneCambio && isset($kmHoy) && $kmHoy >= $proximoCambio);
?>

<div class="card">
  <h3>🛢 Registro y Estado - Cambio de Aceite</h3>
  <?php if ($tieneCambio): ?>
    <p><strong>Último cambio:</strong> <?= $fechaAceite ?> – <?= number_format($kmAceite) ?> km</p>
    <p><strong>Intervalo:</strong> Cada <?= number_format($intervaloAceite) ?> km</p>
    <p><strong>Próximo cambio:</strong> <?= number_format($proximoCambio) ?> km</p>
    <?php if ($alertaAceite): ?>
      <p style="color:red; font-weight:bold;">⚠️ Ya se superó el kilometraje. Requiere mantenimiento.</p>
    <?php endif; ?>
    <?php if ($obsAceite): ?>
      <p><em>📝 <?= $obsAceite ?></em></p>
    <?php endif; ?>
  <?php else: ?>
    <p>No se han registrado cambios de aceite aún.</p>
  <?php endif; ?>

  <!-- Formulario de Registro -->
  <form method="POST">
    <input type="hidden" name="registro_aceite" value="1">
    <input type="hidden" name="usuario" value="<?= $placaSeleccionada ?>">
    <label><strong>Kilometraje actual:</strong></label>
    <input type="number" name="km_actual" value="<?= $kmHoy ?? '' ?>" required>

    <label><strong>Intervalo de cambio (km):</strong></label>
    <select name="intervalo" required onchange="document.getElementById('otro_km').style.display = this.value === 'otro' ? 'block' : 'none'">
      <option value="5000">Cada 5.000 km</option>
      <option value="10000">Cada 10.000 km</option>
      <option value="12000">Cada 12.000 km</option>
      <option value="otro">Otro...</option>
    </select>

    <div id="otro_km" style="display:none;">
      <label>Especifique otro intervalo (km):</label>
      <input type="number" name="intervalo_otro" placeholder="Ej: 8000">
    </div>

    <label>Observación (opcional):</label>
    <textarea name="observacion" rows="2"></textarea>

    <button type="submit">💾 Guardar Registro</button>
  </form>
</div>

</div>

<?php
// Obtener datos del último cambio de aceite
$stmt = $conn->prepare("SELECT fecha, km_actual, km_intervalo, observacion FROM cambio_aceite WHERE usuario = ? ORDER BY fecha DESC LIMIT 1");
$stmt->bind_param("s", $placaSeleccionada); // ahora placaSeleccionada contiene el usuario
$stmt->execute();
$stmt->bind_result($fechaAceite, $kmAceite, $intervaloAceite, $obsAceite);
$tieneCambio = $stmt->fetch();
$stmt->close();

$proximoCambio = $tieneCambio ? $kmAceite + $intervaloAceite : null;
$alertaAceite = ($tieneCambio && isset($kmHoy) && $kmHoy >= $proximoCambio);
?>

<div class="card">
  <h3>🛢 Registro y Estado - Cambio de Aceite</h3>
  <?php if ($tieneCambio): ?>
    <p><strong>Último cambio:</strong> <?= $fechaAceite ?> – <?= number_format($kmAceite) ?> km</p>
    <p><strong>Intervalo:</strong> Cada <?= number_format($intervaloAceite) ?> km</p>
    <p><strong>Próximo cambio:</strong> <?= number_format($proximoCambio) ?> km</p>
    <?php if ($alertaAceite): ?>
      <p style="color:red; font-weight:bold;">⚠️ Ya se superó el kilometraje. Requiere mantenimiento.</p>
    <?php endif; ?>
    <?php if ($obsAceite): ?>
      <p><em>📝 <?= $obsAceite ?></em></p>
    <?php endif; ?>
  <?php else: ?>
    <p>No se han registrado cambios de aceite aún.</p>
  <?php endif; ?>

 


<?php
if ($placaSeleccionada):
  // Kilometraje diario
  $stmt = $conn->prepare("SELECT kilometraje_inicio FROM mantenimiento_kilometraje WHERE conductor_asignado = ? AND fecha = ?");
  $stmt->bind_param("ss", $placaSeleccionada, $fecha);
  $stmt->execute();
  $stmt->bind_result($kmHoy);
  $stmt->fetch();
  $stmt->close();

  echo "<div class='card'>
          <h3>📍 Kilometraje Diario</h3>
          <p><strong>Usuario:</strong> $placaSeleccionada</p>
          <p><strong>Fecha:</strong> $fecha</p>
          <p><strong>Kilometraje:</strong> " . ($kmHoy ?? 'No registrado') . "</p>
        </div>";

  // Gastos del día
  $stmt = $conn->prepare("SELECT tipo_gasto, valor, motivo, soporte FROM gastos_conductor WHERE conductor_asignado = ? AND fecha = ?");
  $stmt->bind_param("ss", $placaSeleccionada, $fecha);
  $stmt->execute();
  $result = $stmt->get_result();
  echo "<div class='card'><h3>💸 Gastos del Día</h3>";
  if ($result->num_rows > 0) {
    echo "<table><tr><th>Tipo</th><th>Valor</th><th>Motivo</th><th>Soporte</th></tr>";
    while ($g = $result->fetch_assoc()) {
      echo "<tr><td>{$g['tipo_gasto']}</td><td>$" . number_format($g['valor']) . "</td><td>{$g['motivo']}</td><td>" . ($g['soporte'] ? '📎' : 'N/A') . "</td></tr>";
    }
    echo "</table>";
  } else {
    echo "<p>No hay gastos registrados.</p>";
  }
  echo "</div>";

  

  // Gastos del mes
  $stmt = $conn->prepare("SELECT tipo_gasto, SUM(valor) as total FROM gastos_conductor WHERE conductor_asignado = ? AND fecha LIKE CONCAT(?, '%') GROUP BY tipo_gasto");
  $stmt->bind_param("ss", $placaSeleccionada, $mesActual);
  $stmt->execute();
  $res = $stmt->get_result();
  echo "<div class='card'><h3>📊 Gastos del Mes</h3>";
  if ($res->num_rows > 0) {
    echo "<table><tr><th>Tipo</th><th>Total</th></tr>";
    while ($r = $res->fetch_assoc()) {
      echo "<tr><td>{$r['tipo_gasto']}</td><td>$" . number_format($r['total']) . "</td></tr>";
    }
    echo "</table>";
  } else {
    echo "<p>No hay gastos este mes.</p>";
  }
  echo "</div>";


  
endif;
?>

</body>
</html>
