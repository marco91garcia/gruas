<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'conductor') {
  header("Location: login.php");
  exit;
}


include 'db.php';
?>


<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$id = $_GET['id'] ?? null;
if (!$id) die("ID de servicio no proporcionado.");

$consulta = $conn->query("SELECT * FROM servicios WHERE id = $id");
$servicio = $consulta->fetch_assoc();
if (!$servicio) die("Servicio no encontrado.");

if (isset($_POST['finalizar'])) {
  // Procesar foto
  $foto_nombre = null;
  if ($_FILES['foto']['error'] === 0) {
  $foto_nombre = "foto_" . time() . "_" . basename($_FILES["foto"]["name"]);
  $ruta_destino = "fotos/evidencias/" . $foto_nombre;


  if (!move_uploaded_file($_FILES["foto"]["tmp_name"], $ruta_destino)) {
    echo "<p class='error'>❌ Error al subir la foto.</p>";
    $foto_nombre = null;
  }
  
  $nombre_soporte = null;
if ($_FILES['soporte_acpm']['error'] === 0) {
  $nombre_soporte = "soporte_acpm_" . time() . "_" . basename($_FILES["soporte_acpm"]["name"]);
  move_uploaded_file($_FILES["soporte_acpm"]["tmp_name"], "fotos/soportes/" . $nombre_soporte);
}

}


  $acpm = $_POST['acpm'];
  $peaje = $_POST['peaje'];
  $parqueadero = $_POST['parqueadero'];
  $otros = $_POST['otros'];
  $observaciones = $_POST['observaciones'];
  
  $lectura_tacometro = $_POST['lectura_tacometro'];

$nombre_soporte = null;
if ($_FILES['soporte_acpm']['error'] === 0) {
  $nombre_soporte = "soporte_acpm_" . time() . "_" . basename($_FILES["soporte_acpm"]["name"]);
  move_uploaded_file($_FILES["soporte_acpm"]["tmp_name"], "fotos/soportes/" . $nombre_soporte);
}


 $stmt = $conn->prepare("UPDATE servicios SET acpm=?, peaje=?, parqueadero=?, otros_gastos=?, observaciones=?, foto_evidencia=?, soporte_acpm=?, lectura_tacometro=?, estado='finalizado', fecha_finalizacion=NOW() WHERE id=?");
 $stmt->bind_param("ddddssssi", $acpm, $peaje, $parqueadero, $otros, $observaciones, $foto_nombre, $nombre_soporte, $lectura_tacometro, $id);

  if ($stmt->execute()) {
    echo "<p class='exito'>✅ Servicio finalizado correctamente</p>";
  } else {
    echo "<p class='error'>❌ Error al finalizar: " . $conn->error . "</p>";
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Finalizar Servicio</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="formulario-container">
    <h2>Finalizar Servicio de <?php echo $servicio['placa']; ?></h2>
    <form method="POST" enctype="multipart/form-data">
      <label>Foto del vehículo cargado</label>
      <input type="file" name="foto" accept="image/*" required>

      <label>Gasto ACPM ($)</label>
      <input type="number" step="0.01" name="acpm" value="0">
      
        <label>Soporte ACPM (foto o recibo)</label>
      <input type="file" name="soporte_acpm" accept="image/*">
      
      <label>Lectura de Tacómetro (km o mi)</label>
      <input type="number" name="lectura_tacometro" step="1" required>
      
      <progress id="medidor" value="0" max="100000"></progress>
      <script>
      const input = document.querySelector('input[name="lectura_tacometro"]');    const progress = document.getElementById('medidor');
  input.addEventListener('input', () => {
    progress.value = input.value;
  });
     </script>

      <label>Peaje ($)</label>
      <input type="number" step="0.01" name="peaje" value="0">

      <label>Parqueadero ($)</label>
      <input type="number" step="0.01" name="parqueadero" value="0">

      <label>Otros Gastos ($)</label>
      <input type="number" step="0.01" name="otros" value="0">

      <label>Observaciones</label>
      <textarea name="observaciones" rows="4" placeholder="Anomalías, cambios, etc."></textarea>

      <button type="submit" name="finalizar">Finalizar Servicio</button>
    </form>
  </div>
</body>
</html>
