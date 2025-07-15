<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'conductor') {
  header("Location: login.php");
  exit;
}

$usuario = $_SESSION['usuario'];
$fecha = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pagos = $_POST['pago'];
  $porcentajes = $_POST['porcentaje'];

  foreach ($pagos as $idServicio => $formaPago) {
    $porcentaje = isset($porcentajes[$idServicio]) ? floatval($porcentajes[$idServicio]) : 20;

    // Guardar en tabla ingresos_conductor (si existe, sino puedes crearla)
    $stmt = $conn->prepare("INSERT INTO ingresos_conductor (servicio_id, conductor, forma_pago, porcentaje, fecha) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issds", $idServicio, $usuario, $formaPago, $porcentaje, $fecha);
    $stmt->execute();
    $stmt->close();

    // Actualizar estado del servicio
    $update = $conn->prepare("UPDATE servicios SET estado = 'liquidado' WHERE id = ?");
    $update->bind_param("i", $idServicio);
    $update->execute();
    $update->close();
  }

  header("Location: vista_previa_liquidacion.php?fecha=$fecha");
  exit;
}
?>
