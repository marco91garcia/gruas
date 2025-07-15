<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}
include 'db.php';
?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel de Servicios</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { font-family: Arial; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; font-size: 14px; }
    th { background: #007bff; color: white; }
    .finalizado { background: #d4edda; }
    .pendiente { background: #fff3cd; }
    .contenedor { max-width: 95%; margin: 0 auto; padding: 20px; }
    h2 { text-align: center; }
    img { max-width: 100px; }
    form.filtros { margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; }
    form.filtros label { font-weight: bold; }
    form.filtros select, form.filtros input { padding: 5px; }
    button { padding: 6px 15px; background: #007bff; color: white; border: none; cursor: pointer; }
    button:hover { background: #0056b3; }
    .logout {
  background-color: #dc3545;
  color: white;
  padding: 10px 20px;
  text-decoration: none;
  border-radius: 6px;
  font-weight: bold;
  display: inline-block;
}

.logout:hover {
  background-color: #c82333;
}


  </style>
 

</head>
<body>
  <div class="contenedor">
    <h2>Panel de Servicios Registrados</h2>

    <!-- FORMULARIO DE FILTROS -->
    <form class="filtros" method="GET" action="">
      <label>Estado:
        <select name="estado">
          <option value="">Todos</option>
          <option value="pendiente" <?= ($_GET['estado'] ?? '') == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
          <option value="finalizado" <?= ($_GET['estado'] ?? '') == 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
        </select>
      </label>

      <label>Conductor:
        <select name="conductor">
          <option value="">Todos</option>
          <option value="guillermo" <?= ($_GET['conductor'] ?? '') == 'guillermo' ? 'selected' : '' ?>>Guillermo</option>
          <option value="julian" <?= ($_GET['conductor'] ?? '') == 'julian' ? 'selected' : '' ?>>Julian</option>
          <option value="andrea" <?= ($_GET['conductor'] ?? '') == 'andrea' ? 'selected' : '' ?>>Andrea</option>
        </select>
      </label>

      <label>Desde:
        <input type="date" name="desde" value="<?= $_GET['desde'] ?? '' ?>">
      </label>

      <label>Hasta:
        <input type="date" name="hasta" value="<?= $_GET['hasta'] ?? '' ?>">
      </label>

      <button type="submit">Filtrar</button>
    </form>
     
     <a class="logout" href="exportar_informe.php" style="background:#28a745; margin-left: 10px;"><i class="fas fa-download"></i> Descargar Informe</a>

</body>

    <!-- TABLA DE RESULTADOS -->
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Cliente</th>
          <th>Teléfono</th>
          <th>Placa</th>
          <th>Origen</th>
          <th>Destino</th>
          <th>Valor</th>
          <th>Conductor</th>
          <th>Gastos</th>
          <th>Estado</th>
          <th>Soporte ACPM</th>
          <th>Lectura KM</th>
          <th>Fecha</th>
          <th>Foto</th>
          <th>Observaciones</th>
        </tr>
        
      </thead>
    
      <tbody>
        <?php
        // Construcción dinámica del filtro
        $condiciones = [];
        if (!empty($_GET['estado'])) {
          $estado = $conn->real_escape_string($_GET['estado']);
          $condiciones[] = "estado = '$estado'";
        }
        if (!empty($_GET['conductor'])) {
          $conductor = $conn->real_escape_string($_GET['conductor']);
          $condiciones[] = "conductor_asignado = '$conductor'";
        }
        if (!empty($_GET['desde']) && !empty($_GET['hasta'])) {
          $desde = $_GET['desde'] . " 00:00:00";
          $hasta = $_GET['hasta'] . " 23:59:59";
          $condiciones[] = "fecha_creacion BETWEEN '$desde' AND '$hasta'";
        }

        $filtroSQL = "";
        if (!empty($condiciones)) {
          $filtroSQL = "WHERE " . implode(" AND ", $condiciones);
        }

        $sql = "SELECT * FROM servicios $filtroSQL ORDER BY fecha_creacion DESC";
        $result = $conn->query($sql);

        while ($row = $result->fetch_assoc()):
          $clase = ($row['estado'] === 'finalizado') ? 'finalizado' : 'pendiente';
          $gastos = $row['acpm'] + $row['peaje'] + $row['parqueadero'] + $row['otros_gastos'];
        ?>
        <tr class="<?= $clase ?>">
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($row['nombre_cliente']) ?></td>
          <td><?= htmlspecialchars($row['telefono_cliente']) ?></td>
          <td><?= htmlspecialchars($row['placa']) ?></td>
          <td><?= htmlspecialchars($row['origen']) ?></td>
          <td><?= htmlspecialchars($row['destino']) ?></td>
          <td>$<?= number_format($row['valor_servicio'], 0, ',', '.') ?></td>
          <td><?= htmlspecialchars($row['conductor_asignado']) ?></td>
          
          <td>$<?= number_format($gastos, 0, ',', '.') ?></td>
          <td><strong><?= ucfirst($row['estado']) ?></strong></td>
          <!-- Mostrar imagen de soporte ACPM -->
  <td>
    <?php if (!empty($row['soporte_acpm'])): ?>
      <img src="fotos/soportes/<?= $row['soporte_acpm'] ?>" alt="Soporte ACPM" width="100">
    <?php else: ?>
      No adjunto
    <?php endif; ?>
  </td>

  <!-- Mostrar lectura del tacómetro -->
  <td>
    <?= !empty($row['lectura_tacometro']) ? $row['lectura_tacometro'] . ' km' : 'No reportado' ?>
  </td>
          <td><?= $row['fecha_creacion'] ?></td>
          <td>
  <?php if ($row['foto_evidencia']): ?>
    <a href="https://www.gruasbogota24horas.co/gruas/fotos/evidencias/<?= $row['foto_evidencia'] ?>" target="_blank">
      <img src="https://www.gruasbogota24horas.co/gruas/fotos/evidencias/<?= $row['foto_evidencia'] ?>" alt="Evidencia" width="70">
    </a>
  <?php else: ?>
    —
  <?php endif; ?>
</td>

          <td><?= nl2br(htmlspecialchars($row['observaciones'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    
      <div style="text-align: center; margin-top: 20px;">
  <a href="logout.php" class="logout">Cerrar sesión</a>
</div>
>
  </div>
  
</html>
