<?php
include("conexion.php");

// Mostrar errores de mysqli como excepciones
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

echo "<h2>🔧 Configuración Inicial del Sistema</h2>";

try {
    // Credenciales iniciales
    $usuarios = [
        [
            "usuario" => "admin",
            "password" => "admin123",
            "nombre"  => "Administrador del Sistema",
            "rol"     => "administrador"
        ],
        [
            "usuario" => "usuario",
            "password" => "user123",
            "nombre"  => "Usuario Normal",
            "rol"     => "usuario"
        ]
    ];

    // Limpiar usuarios existentes
    $conn->query("DELETE FROM usuarios");

    // Preparar SQL
    $sql = "INSERT INTO usuarios (usuario, password, nombre, rol) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    $todo_ok = true;

    foreach ($usuarios as $u) {
        $usuario = $u["usuario"];
        $password_hash = password_hash($u["password"], PASSWORD_DEFAULT);
        $nombre = $u["nombre"];
        $rol = $u["rol"];

        $stmt->bind_param("ssss", $usuario, $password_hash, $nombre, $rol);
        if (!$stmt->execute()) {
            $todo_ok = false;
        }
    }

} catch (Exception $e) {
    $todo_ok = false;
    $error_msg = $e->getMessage();
}

?>

<!DOCTYPE html>

<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 700px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #f1aeb5;
        }
        .credentials {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #0084ff;
        }
        .credentials h3 {
            margin-top: 0;
            color: #0056b3;
        }
        .login-btn {
            display: inline-block;
            margin: 15px 10px 0 0;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .login-btn:hover {
            background: #5568d3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($todo_ok): ?>
            <div class="success">
                ✅ <strong>Sistema configurado correctamente</strong><br>
                Los usuarios han sido creados exitosamente.
            </div>

```
        <div class="credentials">
            <h3>🔑 Credenciales de Acceso</h3>
            
            <table>
                <thead>
                    <tr>
                        <th>Tipo de Usuario</th>
                        <th>Usuario</th>
                        <th>Contraseña</th>
                        <th>Permisos</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>👑 Administrador</strong></td>
                        <td><code>admin</code></td>
                        <td><code>admin123</code></td>
                        <td>Acceso completo al sistema</td>
                    </tr>
                    <tr>
                        <td><strong>👤 Usuario Normal</strong></td>
                        <td><code>usuario</code></td>
                        <td><code>user123</code></td>
                        <td>Solo crear facturas</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="text-align: center;">
            <h3>🚀 ¡Sistema Listo para Usar!</h3>
            <p>Ahora puedes iniciar sesión con cualquiera de las cuentas:</p>
            <a href="index.php" class="login-btn">🔐 Ir al Login</a>
        </div>

        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-top: 20px; border: 1px solid #ffeaa7;">
            <strong>📋 Funcionalidades del Sistema:</strong>
            <ul>
                <li><strong>Administrador:</strong> Gestionar usuarios, productos y ver todas las facturas</li>
                <li><strong>Usuario:</strong> Solo crear facturas y ver sus propias facturas</li>
                <li><strong>Seguridad:</strong> Control de roles y sesiones</li>
                <li><strong>Diseño:</strong> Interfaz moderna y responsive</li>
            </ul>
        </div>

    <?php else: ?>
        <div class="error">
            ❌ <strong>Error al configurar el sistema</strong><br>
            <?php echo isset($error_msg) ? $error_msg : "Error desconocido"; ?>
        </div>
    <?php endif; ?>
</div>
```

</body>
</html>
