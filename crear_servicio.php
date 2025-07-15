<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nuevo Servicio de Grúa</title>
  <link rel="stylesheet" href="style.css">
  <script>
    function generarVistaPrevia() {
      const nombre = document.querySelector('[name="nombre_cliente"]').value;
      const telefono = document.querySelector('[name="telefono_cliente"]').value;
      const placa = document.querySelector('[name="placa"]').value;
      const ciudad = document.querySelector('[name="ciudad"]').value;
      const origen = document.querySelector('[name="origen"]').value;
      const destino = document.querySelector('[name="destino"]').value;
      const valor = document.querySelector('[name="valor_servicio"]').value;
      const conductor = document.querySelector('[name="conductor_info"]').selectedOptions[0].text;
      const direccionCompleta = origen + ', ' + ciudad;
      const linkMaps = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(direccionCompleta)}`;
      const linkWaze = `https://waze.com/ul?q=${encodeURIComponent(direccionCompleta)}`;

      document.getElementById('vista_previa').innerHTML = `
        <h3>Vista previa del mensaje</h3>
        <div style="background:#f0f0f0;padding:10px;border-radius:5px">
          🆕 NUEVO SERVICIO ASIGNADO<br>
          📍 <strong>Origen:</strong> ${origen}, ${ciudad}<br>
          <a href="${linkMaps}" target="_blank">🗺 Ver en Google Maps</a> | <a href="${linkWaze}" target="_blank">📍 Ver en Waze</a><br>
          🚗 <strong>Destino:</strong> ${destino}<br>

          🔑 <strong>Placa:</strong> ${placa}<br>
          💰 <strong>Valor:</strong> $${valor}<br>
          👤 <strong>Cliente:</strong> ${nombre} – ${telefono}<br>
          👨‍👷 <strong>Conductor:</strong> ${conductor}
        </div>`;
    }
  </script>
</head>
<body>
  <div class="formulario-container">
    <header style="display:flex;justify-content:space-between;align-items:center;">
      <h2>Registrar Nuevo Servicio</h2>
      <a href="logout.php" style="text-decoration:none;color:#fff;background:#dc3545;padding:5px 10px;border-radius:4px;">Cerrar sesión</a>
    </header>
    <form method="POST" action="" enctype="multipart/form-data" oninput="generarVistaPrevia()">
      <label>Nombre del Cliente</label>
      <input type="text" name="nombre_cliente" required>

      <label>Teléfono del Cliente</label>
      <input type="tel" name="telefono_cliente" required>

      <label>Placa del Vehículo</label>
      <input type="text" name="placa" required>

      <label>Ciudad</label>
      <input type="text" name="ciudad" value="Bogotá" required>

      <label>Ubicación de Recogida (Origen)</label>
      <textarea name="origen" required></textarea>

      <label>Destino</label>
      <textarea name="destino" required></textarea>

      <label>Valor del Servicio ($)</label>
      <input type="number" step="0.01" name="valor_servicio" required>

      <label>Conductor Asignado</label>
      <select name="conductor_info" required>
        <option value="">-- Selecciona un conductor --</option>
        <option value="SSX640|574134327303">Guillermo (SSX640)</option>
        <option value="WPS276|573214595644">Julian (WPS276)</option>
        <option value="andrea|573219086768">Andrea</option>
      </select>

      <button type="submit" name="guardar">Registrar Servicio</button>
    </form>

    <div id="vista_previa" style="margin-top:20px;"></div>

<?php
if (isset($_POST['guardar'])) {
  $nombre = $_POST['nombre_cliente'];
  $telefono = $_POST['telefono_cliente'];
  $placa = $_POST['placa'];
  $ciudad = $_POST['ciudad'];
  $origen = $_POST['origen'];
  $destino = $_POST['destino'];
  $valor = $_POST['valor_servicio'];
  $conductor_seleccionado = explode('|', $_POST['conductor_info']);
  $conductor = $conductor_seleccionado[0];
  $telefono_conductor = $conductor_seleccionado[1];

  $estado = 'pendiente de liquidar';
$stmt = $conn->prepare("INSERT INTO servicios (
    nombre_cliente, telefono_cliente, placa, origen, destino, valor_servicio, conductor_asignado, telefono_conductor, estado
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssss", $nombre, $telefono, $placa, $origen, $destino, $valor, $conductor, $telefono_conductor, $estado);

  if ($stmt->execute()) {
    $nuevo_id = $conn->insert_id;
    $direccionCompleta = $origen . ', ' . $ciudad;
    $linkMaps = "https://www.google.com/maps/search/?api=1&query=" . urlencode($direccionCompleta);
    $linkWaze = "https://waze.com/ul?q=" . urlencode($direccionCompleta);

    $whatsapp = urlencode("🆕 NUEVO SERVICIO ASIGNADO
📍 Origen: $origen, $ciudad
📍 Google Maps: $linkMaps
📍 Waze: $linkWaze
🚗 Destino: $destino
🔑 Placa: $placa
💰 Valor: $$valor
👤 Cliente: $nombre – $telefono
📥 Responde aquí:
https://www.gruasbogota24horas.co/gruas/responder_servicio.php?id=$nuevo_id");

    echo "<p class='exito'>✅ Servicio registrado correctamente</p>";
    echo "<p><strong>📋 Copia y envía esto por WhatsApp al conductor:</strong></p>";
    echo "<textarea style='width:100%; height:180px;'>🆕 NUEVO SERVICIO ASIGNADO
📍 Origen: $origen, $ciudad
📍 Google Maps: $linkMaps
📍 Waze: $linkWaze
🚗 Destino: $destino
🔑 Placa: $placa
💰 Valor: $$valor
👤 Cliente: $nombre – $telefono
📥 Responde aquí:
https://www.gruasbogota24horas.co/gruas/responder_servicio.php?id=$nuevo_id</textarea>";
    echo "<p><a target='_blank' href='https://wa.me/$telefono_conductor?text=$whatsapp'>👉 Enviar por WhatsApp Web</a></p>";
  } else {
    echo "<p class='error'>❌ Error al guardar: " . $stmt->error . "</p>";
  }
}
?>

<?php if ($_SESSION['rol'] === 'supervisor'): ?>
  <a href="servicios_pendientes.php" style="background: #007bff; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">
    ✅ SERVICIO TERMINADO
  </a>
<?php endif; ?>

  </div>
</body>
</html>
