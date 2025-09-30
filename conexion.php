<?php
// Configuración de conexión a la base de datos
$servidor = "localhost";
$usuario = "root";
$password = "";
$base_datos = "sistema_factura";

// Crear conexión
$conn = new mysqli($servidor, $usuario, $password, $base_datos);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Establecer charset UTF-8
$conn->set_charset("utf8");
?>