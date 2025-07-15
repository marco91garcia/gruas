<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario'])) {
    echo "Acceso no autorizado.";
    exit;
}

if (!isset($_GET['id'])) {
    echo "Solicitud no válida.";
    exit;
}

$id_servicio = intval($_GET['id']);

// Actualizar estado a "pendiente de liquidar"
$update = $conn->prepare("UPDATE servicios SET estado = 'pendiente de liquidar' WHERE id = ?");
$update->bind_param("i", $id_servicio);

if ($update->execute()) {
    // Registrar en historial
    $usuario = $_SESSION['usuario'];
    $estado = 'pendiente de liquidar';
    $historial = $conn->prepare("INSERT INTO historial_servicios (servicio_id, nuevo_estado, usuario) VALUES (?, ?, ?)");
    $historial->bind_param("iss", $id_servicio, $estado, $usuario);
    $historial->execute();

    header("Location: servicios_pendientes.php?finalizado=1");
    exit;
} else {
    echo "❌ Error al actualizar el estado del servicio.";
}
?>
