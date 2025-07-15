<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'CONDUCTOR') {
  header("Location: login.php");
  exit;
}

include 'db.php';

$placa = $_GET['placa'] ?? '';
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';

// Contar total
$sql_count = "SELECT COUNT(*) FROM servicios WHERE estado = 'finalizado'";
if (!empty($placa)) $sql_count .= " AND placa LIKE '%$placa%'";
if (!empty($desde)) $sql_count .= " AND fecha_finalizacion >= '$desde'";
if (!empty($hasta)) $sql_count .= " AND fecha_finalizacion <= '$hasta'";
$total_resultado = $conn->query($sql_count)->fetch_row()[0];
$total_paginas = ceil($total_resultado / $por_pagina);


// Paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $por_pagina;


$sql = "SELECT * FROM servicios WHERE estado = 'finalizado'";

// Filtro adicional si es conductor
if ($_SESSION['rol'] === 'conductor') {
    $usuario = $_SESSION['usuario'];
    $sql .= " AND conductor_asignado = '$usuario'";
}

$params = [];
$types = "";

// Condicionales dinámicas
if (!empty($placa)) {
  $sql .= " AND placa LIKE ?";
  $params[] = "%$placa%";
  $types .= "s";
}
if (!empty($desde)) {
  $sql .= " AND fecha_finalizacion >= ?";
  $params[] = $desde;
  $types .= "s";
}
if (!empty($hasta)) {
  $sql .= " AND fecha_finalizacion <= ?";
  $params[] = $hasta;
  $types .= "s";
}

$sql .= " ORDER BY fecha_finalizacion DESC LIMIT $offset, $por_pagina";
$stmt = $conn->prepare($sql);
if ($types) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Historial de Servicios</title>
  <style>
    body {
      background: #f1f1f1;
      font-family: Arial, sans-serif;
    }
    .historial {
      max-width: 1200px;
      margin: 40px auto;
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #007bff;
    }
    .cerrar-sesion {
      text-align: right;
      margin-bottom: 20px;
    }
    .cerrar-sesion a {
      text-decoration: none;
      color: #dc3545;
      font-weight: bold;
    }
    form {
      display: flex;
      gap: 15px;
      margin-bottom: 20px;
      justify-content: center;
      flex-wrap: wrap;
    }
    input[type="text"], input[type="date"] {
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }
    button {
      background: #007bff;
      color: white;
      padding: 8px 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    th, td {
      padding: 12px;
      border-bottom: 1px solid #ddd;
      text-align: left;
      vertical-align: middle;
    }
    th {
      background: #007bff;
      color: white;
    }
    tr:hover {
      background-color: #f9f9f9;
    }
    img {
      max-height: 60px;
      border-radius: 6px;
    }
    .total {
      font-weight: bold;
      color: green;
    }
    .paginacion {
  margin-top: 20px;
  text-align: center;
}
.paginacion a {
  padding: 8px 12px;
  margin: 2px;
  background-color: #eee;
  border-radius: 4px;
  text-decoration: none;
}
.paginacion a.activa {
  background-color: #007bff;
  color: white;
  font-weight: bold;
}

  </style>
</head>
<body>
  <div class="historial">
    <div class="cerrar-sesion">
      <a href="logout.php">🚪 Cerrar sesión</a>
    </div>

    <h2>📋 Historial de Servicios Finalizados</h2>

    <form method="GET">
      <input type="text" name="placa" placeholder="Buscar por placa..." value="<?= htmlspecialchars($placa) ?>">
      <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>">
      <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
      <button type="submit">🔍 Filtrar</button>
    </form>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Placa</th>
          <th>Fecha</th>
          <th>Observaciones</th>
          <th>Foto</th>
          <th>Total Gastos</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($fila = $resultado->fetch_assoc()): ?>
          <tr>
            <td><?= $fila['id'] ?></td>
            <td><?= $fila['placa'] ?></td>
            <td><?= $fila['fecha_finalizacion'] ?></td>
            <td><?= $fila['observaciones'] ?></td>
            <td>
              <?php if ($fila['foto_evidencia']): ?>
                <img src="fotos/evidencias/<?= htmlspecialchars($fila['foto_evidencia']) ?>" alt="Evidencia">
              <?php else: ?>
                ❌
              <?php endif; ?>
            </td>
            <td class="total">
              $<?= number_format($fila['acpm'] + $fila['peaje'] + $fila['parqueadero'] + $fila['otros_gastos'], 0, ',', '.') ?>
            </td>
            <td><?= ucfirst($fila['estado']) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <div class="paginacion">
  <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
    <a href="?pagina=<?= $i ?>" class="<?= $i === $pagina_actual ? 'activa' : '' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>

  </div>
</body>
</html>
