<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php';

// Verificar acceso de conductor
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'conductor') {
  header("Location: login.php");
  exit;
}

$usuario = $_SESSION['usuario'];
$fechaHoy = date('Y-m-d');

// Registrar kilometraje si no ha sido ingresado
$yaIngresoKm = false;
$valorKmHoy = 0;
$stmt = $conn->prepare("SELECT kilometraje_inicio FROM mantenimiento_kilometraje WHERE conductor_asignado = ? AND fecha = ?");
$stmt->bind_param("ss", $usuario, $fechaHoy);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->bind_result($valorKmHoy);
    $stmt->fetch();
    $yaIngresoKm = true;
}
$stmt->close();

if (isset($_POST['km_inicio']) && !$yaIngresoKm) {
    $km = intval($_POST['km_inicio']);
    $insert = $conn->prepare("INSERT INTO mantenimiento_kilometraje (conductor_asignado, fecha, kilometraje_inicio) VALUES (?, ?, ?)");
    $insert->bind_param("ssi", $usuario, $fechaHoy, $km);
    $insert->execute();
    $insert->close();
    $yaIngresoKm = true;
    $valorKmHoy = $km;
}

// Procesar liquidación de servicios
if (isset($_POST['liquidar_servicios']) && isset($_POST['forma_pago']) && is_array($_POST['forma_pago'])) {
  foreach ($_POST['forma_pago'] as $id => $forma) {
    $porcentaje = isset($_POST['porcentaje'][$id]) ? intval($_POST['porcentaje'][$id]) : 20;
    if (!empty($forma)) {
      $stmt = $conn->prepare("UPDATE servicios SET forma_pago = ?, porcentaje_conductor = ?, estado = 'liquidado' WHERE id = ? AND conductor_asignado = ?");
      $stmt->bind_param("siis", $forma, $porcentaje, $id, $usuario);
      $stmt->execute();
      $stmt->close();

      $stmt2 = $conn->prepare("INSERT INTO ingresos_conductor (servicio_id, conductor_asignado, forma_pago, porcentaje_conductor, fecha) VALUES (?, ?, ?, ?, ?)");
      $stmt2->bind_param("issis", $id, $usuario, $forma, $porcentaje, $fechaHoy);
      $stmt2->execute();
      $stmt2->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión Conductor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
    .card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2, h3 { text-align: center; }
    input, select, button, textarea {
      width: 100%; padding: 10px; margin-top: 10px; border: 1px solid #ccc; border-radius: 5px;
    }
    button { background: #28a745; color: white; font-weight: bold; }
    .logout { background: #343a40; color: white; text-decoration: none; padding: 10px 20px; display: inline-block; border-radius: 5px; margin-top: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
    .alert { color: red; font-weight: bold; text-align: center; margin-top: 10px; }
  </style>
</head>
<body>

<h2>Panel del Conductor - <?php echo strtoupper($usuario); ?></h2>
<a href="logout.php" class="logout">Cerrar sesión</a>

<?php if (!$yaIngresoKm): ?>
  <div class="card">
    <h3>1️⃣ Registrar Kilometraje Inicial</h3>
    <form method="POST">
      <input type="number" name="km_inicio" id="km_inicio" placeholder="Kilometraje de inicio" required>
      <table id="tablaVista" style="display:none; margin-top:10px;">
        <tr><th>Fecha</th><th>Kilometraje</th></tr>
        <tr><td><?php echo $fechaHoy; ?></td><td id="valor_km_mostrado"></td></tr>
      </table>
      <button type="submit" id="btnEnviar" style="display:none;">🚗 ENVIAR</button>
    </form>
  </div>
  <script>
    document.getElementById('km_inicio').addEventListener('input', function() {
      const val = this.value;
      if (val) {
        document.getElementById('valor_km_mostrado').innerText = val;
        document.getElementById('tablaVista').style.display = 'table';
        document.getElementById('btnEnviar').style.display = 'inline-block';
      } else {
        document.getElementById('tablaVista').style.display = 'none';
        document.getElementById('btnEnviar').style.display = 'none';
      }
    });
  </script>
<?php else: ?>
  <div class="card">
    <h3>✅ Kilometraje ya registrado</h3>
    <p><strong>Fecha:</strong> <?php echo $fechaHoy; ?></p>
    <p><strong>Kilometraje:</strong> <?php echo $valorKmHoy; ?> km</p>
  </div>

  <!-- Mostrar servicios pendientes de liquidar -->
  <div class="card">
    <h3>💼 Registrar Ingresos del Día</h3>
    <form method="POST">
      <input type="hidden" name="liquidar_servicios" value="1">
      <table>
        <tr><th>Placa</th><th>Valor</th><th>Forma de Pago</th><th>% Comisión</th></tr>
        <?php
        $query = $conn->prepare("SELECT id, placa, valor_servicio FROM servicios WHERE estado = 'pendiente de liquidar' AND conductor_asignado = ? AND DATE(fecha_creacion) = ?");
        $query->bind_param("ss", $usuario, $fechaHoy);
        $query->execute();
        $result = $query->get_result();
        while ($row = $result->fetch_assoc()):
        ?>
        <tr>
          <td><?php echo $row['placa']; ?></td>
          <td>$<?php echo number_format($row['valor_servicio']); ?></td>
          <td>
            <select name="forma_pago[<?php echo $row['id']; ?>]" required>
              <option value="">Seleccione</option>
              <option value="Efectivo">Efectivo</option>
              <option value="Nequi">Nequi 3217005069</option>
              <option value="Tarjeta">Tarjeta de Crédito</option>
              <option value="Pendiente">Pendiente</option>
              <option value="Otros">Otros</option>
            </select>
          </td>
          <td>
            <input type="number" name="porcentaje[<?php echo $row['id']; ?>]" value="20" min="0" max="100" required> %
          </td>
        </tr>
        <?php endwhile; $query->close(); ?>
      </table>
      <button type="submit" id="btnLiquidar" name="liquidar_servicios">✔️ Liquidar Servicios</button>
      <?php if (isset($_POST['liquidar_servicios']) && empty($_POST['forma_pago'])): ?>
  <div class="alert">⚠️ No hay servicios disponibles para liquidar.</div>
<?php endif; ?>

    </form>
  </div>

  <!-- Mostrar servicios ya liquidados -->
  <div class="card">
    <h3>📋 Servicios Liquidados Hoy</h3>
    <table>
      <tr><th>Placa</th><th>Valor</th><th>Forma de Pago</th><th>% Comisión</th><th>Total a Pagar</th></tr>
      <?php
      $query = $conn->prepare("SELECT placa, valor_servicio, forma_pago, porcentaje_conductor FROM servicios WHERE estado = 'liquidado' AND conductor_asignado = ? AND DATE(fecha_creacion) = ?");
      $query->bind_param("ss", $usuario, $fechaHoy);
      $query->execute();
      $result = $query->get_result();
      $total_pagar = 0;
      while ($row = $result->fetch_assoc()):
        $comision = round($row['valor_servicio'] * ($row['porcentaje_conductor'] / 100));
        $total_pagar += $comision;
      ?>
        <tr>
          <td><?php echo $row['placa']; ?></td>
          <td>$<?php echo number_format($row['valor_servicio']); ?></td>
          <td><?php echo $row['forma_pago']; ?></td>
          <td><?php echo $row['porcentaje_conductor']; ?>%</td>
          <td>$<?php echo number_format($comision); ?></td>
        </tr>
      <?php endwhile; $query->close(); ?>
      <tr>
        <td colspan="4"><strong>Total a pagar al conductor:</strong></td>
        <td><strong>$<?php echo number_format($total_pagar); ?></strong></td>
      </tr>
    </table>
    <?php if ($total_pagar < 50000): ?>
      <div class="alert">⚠️ Atención: El total a pagar es inferior al mínimo ($50,000).</div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php
// Procesar formulario de gastos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_gasto'])) {
    $tipo_gasto = $_POST['tipo_gasto'];
    $valor = floatval($_POST['valor']);
    $motivo = $_POST['motivo'] ?? '';
    $soporte = $_FILES['soporte']['name'] ?? null;

    // Subir imagen
    if ($soporte) {
        $ruta_destino = __DIR__ . '/fotos/gastos/' . basename($soporte);
        move_uploaded_file($_FILES['soporte']['tmp_name'], $ruta_destino);
    }

    // Insertar en base de datos
    $stmt = $conn->prepare("INSERT INTO gastos_conductor (conductor_asignado, fecha, tipo_gasto, valor, motivo, soporte) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $usuario, $fechaHoy, $tipo_gasto, $valor, $motivo, $soporte);
    $stmt->execute();
    $stmt->close();
}
if (isset($_POST['guardar_ingresos'])) {
  foreach ($_POST['pago'] as $id => $forma_pago) {
    $porcentaje = $_POST['porcentaje_conductor'][$id];
    $update = $conn->prepare("UPDATE servicios SET forma_pago = ?, porcentaje_conductor = ?, estado = 'liquidado' WHERE id = ? AND conductor_asignado = ?");
    $update->bind_param("sdis", $forma_pago, $porcentaje, $id, $usuario);
    $update->execute();
    $update->close();
  }

  // Redirige al generador de PDF directamente
  header("Location: generar_pdf_liquidacion.php?fecha=$fechaHoy");
  exit;
}

?>



<div class="card">
  <h3>💸 Registrar Gastos del Conductor</h3>
  <form method="POST" enctype="multipart/form-data">
    <label>Tipo de Gasto:</label>
    <select name="tipo_gasto" required>
      <option value="">Seleccionar</option>
      <option value="acpm">ACPM</option>
      <option value="peaje">Peaje</option>
      <option value="parqueadero">Parqueadero</option>
      <option value="otros">Otros</option>
    </select>

    <label>Valor:</label>
    <input type="number" name="valor" required min="0">

    <label>Motivo (si es otro gasto):</label>
    <textarea name="motivo" placeholder="Opcional si el gasto es 'otros'"></textarea>

    <label>Soporte (imagen):</label>
    <input type="file" name="soporte" accept="image/*">

    <button type="submit" name="guardar_gasto">Guardar Gasto</button>
  </form>
</div>

<?php
$totalGastos = 0;
$stmt = $conn->prepare("SELECT tipo_gasto, valor, motivo, soporte FROM gastos_conductor WHERE conductor_asignado = ? AND fecha = ?");
$stmt->bind_param("ss", $usuario, $fechaHoy);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<?php if ($resultado->num_rows > 0): ?>
  <div class="card">
    <h3>📋 Gastos Registrados Hoy</h3>
    <table style="width:100%; border-collapse:collapse; font-size: 14px;">
      <thead>
        <tr style="background:#f0f0f0;">
          <th style="padding:10px;">Tipo</th>
          <th>Valor</th>
          <th>Motivo</th>
          <th>Soporte</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $resultado->fetch_assoc()):
          $totalGastos += $row['valor'];
        ?>
          <tr>
            <td style="padding:10px;"><?php echo ucfirst($row['tipo_gasto']); ?></td>
            <td>$<?php echo number_format($row['valor']); ?></td>
            <td><?php echo $row['motivo'] ?: '-'; ?></td>
            <td>
              <?php if ($row['soporte']): ?>
                <a href="fotos/gastos/<?php echo $row['soporte']; ?>" target="_blank">📷 Ver</a>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <h4 style="margin-top:15px; text-align:right;">🧾 Total Gastos: <span style="color:red;">$<?php echo number_format($totalGastos); ?></span></h4>
  </div>
<?php else: ?>
  <div class="card">
    <p style="text-align:center;">❌ Aún no has registrado gastos hoy.</p>
  </div>
<?php endif; ?>


<?php if (isset($_GET['liquidado']) && $_GET['liquidado'] == 1): ?>
  <div style="text-align:center; margin-top: 20px;">
    <a href="generar_pdf_liquidacion.php?fecha=<?php echo $fechaHoy; ?>" 
       style="
          display: inline-block;
          padding: 12px 30px;
          background-color: #dc3545;
          color: white;
          font-weight: bold;
          text-decoration: none;
          border-radius: 30px;
          font-size: 16px;
          box-shadow: 0 4px 6px rgba(0,0,0,0.1);
          transition: background-color 0.3s ease;
       ">
      📄 Descargar PDF
    </a>
  </div>
<?php endif; ?>


<a href="generar_pdf_liquidacion.php?fecha=<?php echo $fechaHoy; ?>" target="_blank" class="btn-descargar-pdf">
  📄 Descargar PDF nuevamente
</a>

<script>
document.getElementById("btnLiquidar").addEventListener("click", function(e) {
  const selects = document.querySelectorAll("select[name^='forma_pago']");
  let faltantes = 0;

  selects.forEach(select => {
    if (!select.value) {
      faltantes++;
      select.style.border = "2px solid red";
    } else {
      select.style.border = "";
    }
  });

  if (faltantes > 0) {
    e.preventDefault(); // Evita que se envíe el formulario
    alert("Debes seleccionar la forma de pago para todos los servicios.");
  }
});
</script>

</body>
</html>
