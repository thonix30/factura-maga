<?php
session_start();
if (isset($_SESSION['usuario'])) {
    header("Location: dashboard.php, estilos.css");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Factura</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo-container">
                <img src="LOGO MAGA.png" alt="Logo Facturación">
            </div>
            <h2>Sistema de Factura</h2>
        </div>

        <div class="login-body">
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <div class="input-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" id="usuario" name="usuario" required placeholder="Ingrese su usuario">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="password" name="password" required placeholder="Ingrese su contraseña">
                    </div>
                </div>

                <button type="submit" class="btn-login">Iniciar Sesión</button>

                <div class="footer-text">
                    © 2025 Sistema MAGA
                </div>
            </form>
        </div>
    </div>
</body>
</html>