<?php if (isset($_GET['finalizado']) && $_GET['finalizado'] == 1): ?>
  <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
    ✅ Servicio finalizado correctamente.
  </div>
<?php endif; ?>


<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
  header("Location: login.php");
  exit;
}
$usuario = $_SESSION['usuario'];
if ($_SESSION['rol'] === 'supervisor') {
if ($_SESSION['rol'] === 'supervisor') {
  $sql = "SELECT id, placa, origen, destino, conductor_asignado, estado, fecha_creacion FROM servicios WHERE estado = 'pendiente'";
  
  if (isset($_GET['filtro_por']) && isset($_GET['valor']) && $_GET['valor'] !== '') {
    $filtro = $_GET['filtro_por'];
    $valor = "%{$_GET['valor']}%";
    
    if ($filtro === 'placa') {
      $sql .= " AND placa LIKE ?";
    } elseif ($filtro === 'conductor') {
      $sql .= " AND conductor_asignado LIKE ?";
    }

    $sql .= " ORDER BY fecha_creacion DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $valor);
  } else {
    $sql .= " ORDER BY fecha_creacion DESC";
    $stmt = $conn->prepare($sql);
  }
} else {
  $stmt = $conn->prepare("SELECT id, placa, origen, destino, conductor_asignado, estado, fecha_creacion FROM servicios WHERE estado = 'pendiente' AND conductor_asignado = ? ORDER BY fecha_creacion DESC");
  $stmt->bind_param("s", $usuario);
}

} else {
  $stmt = $conn->prepare("SELECT id, placa, origen, destino, conductor_asignado, estado, fecha_creacion FROM servicios WHERE estado = 'pendiente' AND conductor_asignado = ?");
  $stmt->bind_param("s", $usuario);
}
$stmt->execute();
$resultado = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Servicios Pendientes</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f2f2f2;
      margin: 0;
      padding: 0;
    }

    header {
      background: #007bff;
      color: white;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: cen"POST") ;
    }

    .logout {
      color: white;
      text-decoration: none;
      font-weight: bold;
    }

    .logout:hover {
      text-decoration: underline;
    }

    .container {
      padding: 30px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 10px;
      overflow: hidden;
      animation: fadeIn 1s ease-in-out;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }

    th, td {
      padding: 15px;
      border-bottom: 1px solid #ddd;
      text-align: cen"POST";
    }

    th {
      background-color: #343a40;
      color: white;
    }

    tr:hover {
      background-color: #f1f1f1;
    }

    .boton {
      padding: 8px 12px;
      background-color: #28a745;
      color: white;
      border-radius: 5px;
      text-decoration: none;
      font-weight: bold;
    }

    .boton:hover {
      background-color: #218838;
    }

    @keyframes fadeIn {
      from {opacity: 0;}
      to {opacity: 1;}
    }
  </style>
</head>
<body>

<header>
  <h2><i class="fas fa-truck"></i> Servicios Pendientes</h2>
  <a class="logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
</header>

<div class="container">

  <table>
    <thead><?php if (isset($_GET['finalizado']) && $_GET['finalizado'] == 1): ?>
  <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: bold;">
    ✅ Servicio finalizado correctamente
  </div>
<?php endif; ?>

      <tr>
        <th>ID</th>
        <th>Placa</th>
        <th>Origen</th>
        <th>Destino</th>
        <th>Estado</th>
        <th>Conductor</th>
        <th>Fecha</th>
        <th>Acción</th>
        
      </tr>
    </thead>
    <tbody>
      <?php if ($_SESSION['rol'] === 'supervisor'): ?>
<div class="card">
  <form method="GET">
    <label>Filtrar por:</label>
    <select name="filtro_por">
      <option value="">-- Todos --</option>
      <option value="placa">Placa</option>
      <option value="conductor">Conductor</option>
    </select>
    <input type="text" name="valor" placeholder="Ingrese placa o conductor">
    <button type="submit">🔍 Filtrar</button>
  </form>
</div>
<?php endif; ?>

      <?php while ($row = $resultado->fetch_assoc()): ?>

        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= $row['placa'] ?></td>
          <td><?= $row['origen'] ?></td>
          <td><?= $row['destino'] ?></td>
          <td><i class="fas fa-clock"></i> <?= ucfirst($row['estado']) ?></td>
          <td><?php echo $row['conductor_asignado']; ?></td>
          <td><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y H:i', strtotime($row['fecha_creacion'])) ?></td>
         <td>
  <?php if ($_SESSION['rol'] === 'central' || $_SESSION['rol'] === 'supervisor'): ?>
    <a href="finalizar_servicio.php?id=<?php echo $row['id']; ?>" 
       onclick="return confirm('¿Estás seguro de finalizar este servicio?')" 
       style="background: darkorange; padding: 5px 10px; border-radius: 5px; color: white; text-decoration: none;">
      ✔ Servicio Terminado
    </a>
  <?php else: ?>
    <!-- Puedes dejar acciones para el conductor si es necesario -->
    N/A
  <?php endif; ?>
</td>

          

        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <a href="historial_servicios.php" style="background: #28a745; padding: 10px 15px; color: white; border-radius: 5px; text-decoration: none;">
  📜 Ver Historial
</a>

</div>

</body>
</html>
