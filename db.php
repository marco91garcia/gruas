<?php
$host = "localhost";
$usuario = "root";
$clave = "";
$base_datos = "dgruasbd7_gruas_movil";

$conn = new mysqli($host, $usuario, $clave, $base_datos);

if ($conn->connect_error) {
  die("Conexion fallida: " . $conn->connect_error);
}
?>
