<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   $usuario = trim($_POST['usuario']);
   $pass = trim($_POST['contrasena']);

   $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ?");
   $stmt->bind_param("s", $usuario);
   $stmt->execute();
   $resultado = $stmt->get_result();

   if ($fila = $resultado->fetch_assoc()) {
       if (password_verify($pass, $fila['contrasena'])) {
           $_SESSION['usuario'] = $usuario;
           $_SESSION['rol'] = $fila['rol'];

           // Redirige según el rol
           if ($fila['rol'] === 'admin') {
               header("Location: panel_admin.php");
           } elseif ($fila['rol'] === 'conductor') {
               header("Location: gestion-conductor.php");
           } elseif ($fila['rol'] === 'supervisor') {
               header("Location: crear_servicio.php");
           } else {
               header("Location: panel.php");
           }
           exit;
       }
   }

   $error = "Credenciales incorrectas.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - SOS GRÚAS</title>
  <style>
    body {
      font-family: Arial;
      background: #f8f8f8;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .login-container {
      background: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      max-width: 400px;
      width: 90%;
    }
    input {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      font-size: 16px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }
    button {
      width: 100%;
      padding: 12px;
      background: #007bff;
      color: white;
      border: none;
      font-size: 16px;
      border-radius: 6px;
      cursor: pointer;
    }
    button:hover {
      background: #0056b3;
    }
    .error {
      color: red;
      text-align: center;
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2>Acceso Administrativo</h2>

    <?php if (isset($error)): ?>
      <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">
      <label>Usuario</label>
      <input type="text" name="usuario" required>

      <label>Contraseña</label>
      <input type="password" name="contrasena" required>

      <button type="submit">Iniciar Sesión</button>
    </form>
  </div>
</body>
</html>
