<?php
session_start();
include("conexion.php");
include("funciones.php");

// Verificar que sea administrador
verificarAdmin();

// Agregar usuario
if (isset($_POST['agregar'])) {
    $usuario = $_POST['usuario'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nombre = $_POST['nombre'];
    $rol = $_POST['rol'];
    
    $sql = "INSERT INTO usuarios (usuario, password, nombre, rol) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $usuario, $password, $nombre, $rol);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Usuario agregado correctamente";
    } else {
        $mensaje = "❌ Error al agregar usuario: " . $conn->error;
    }
}


// Modificar usuario
if (isset($_POST['modificar'])) {
    $id = $_POST['id'];
    $usuario = $_POST['usuario'];
    $nombre = $_POST['nombre'];
    $rol = $_POST['rol'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Si se proporciona nueva contraseña
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET usuario = ?, password = ?, nombre = ?, rol = ?, activo = ? WHERE idusuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", $usuario, $password, $nombre, $rol, $activo, $id);
    } else {
        $sql = "UPDATE usuarios SET usuario = ?, nombre = ?, rol = ?, activo = ? WHERE idusuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", $usuario, $nombre, $rol, $activo, $id);
    }
    
    if ($stmt->execute()) {
        $mensaje = "✅ Usuario modificado correctamente";
    } else {
        $mensaje = "❌ Error al modificar usuario: " . $conn->error;
    }
}

// Obtener usuarios
$sql = "SELECT * FROM usuarios ORDER BY idusuario DESC";
$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="productos.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="container-productos">
        <div class="header-productos">
            <h1>👥 Gestión de Usuarios</h1>
            <button onclick="window.location.href='dashboard_admin.php'" class="btn-volver">← Volver al Dashboard</button>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <!-- Formulario Agregar/Modificar -->
        <div class="form-card">
            <h2 id="titulo-form">➕ Agregar Nuevo Usuario</h2>
            <form method="POST" id="formUsuario">
                <input type="hidden" name="id" id="userId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Usuario</label>
                        <input type="text" name="usuario" id="usuario" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Contraseña <span id="password-note"></span></label>
                        <input type="password" name="password" id="password">
                    </div>
                    
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre" id="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Rol</label>
                        <select name="rol" id="rol" required>
                            <option value="usuario">👤 Usuario</option>
                            <option value="administrador">👑 Administrador</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="activo" id="activo" checked>
                            Usuario Activo
                        </label>
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" name="agregar" id="btnAgregar" class="btn-success">Agregar Usuario</button>
                    <button type="submit" name="modificar" id="btnModificar" class="btn-warning" style="display:none;">Modificar Usuario</button>
                    <button type="button" onclick="cancelarEdicion()" id="btnCancelar" class="btn-secondary" style="display:none;">Cancelar</button>
                </div>
            </form>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="tabla-card">
            <h2>📋 Lista de Usuarios</h2>
            <div class="tabla-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Nombre</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultado->num_rows > 0): ?>
                            <?php while($row = $resultado->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['idusuario']; ?></td>
                                    <td><?php echo $row['usuario']; ?></td>
                                    <td><?php echo $row['nombre']; ?></td>
                                    <td>
                                        <span class="badge-<?php echo $row['rol']; ?>">
                                            <?php echo $row['rol'] == 'administrador' ? '👑 Admin' : '👤 Usuario'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-<?php echo $row['activo'] ? 'activo' : 'inactivo'; ?>">
                                            <?php echo $row['activo'] ? '✅ Activo' : '❌ Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($row['fecha_registro'])); ?></td>
                                    <td>
                                        <button onclick='editarUsuario(<?php echo json_encode($row); ?>)' class="btn-edit">✏️ Editar</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center;">No hay usuarios registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function editarUsuario(usuario) {
            document.getElementById('userId').value = usuario.id;
            document.getElementById('usuario').value = usuario.usuario;
            document.getElementById('nombre').value = usuario.nombre;
            document.getElementById('rol').value = usuario.rol;
            document.getElementById('activo').checked = usuario.activo == 1;
            
            document.getElementById('titulo-form').textContent = '✏️ Modificar Usuario';
            document.getElementById('password-note').textContent = '(dejar vacío para mantener la actual)';
            document.getElementById('password').required = false;
            document.getElementById('btnAgregar').style.display = 'none';
            document.getElementById('btnModificar').style.display = 'block';
            document.getElementById('btnCancelar').style.display = 'block';
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function cancelarEdicion() {
            document.getElementById('formUsuario').reset();
            document.getElementById('userId').value = '';
            document.getElementById('titulo-form').textContent = '➕ Agregar Nuevo Usuario';
            document.getElementById('password-note').textContent = '';
            document.getElementById('password').required = true;
            document.getElementById('btnAgregar').style.display = 'block';
            document.getElementById('btnModificar').style.display = 'none';
            document.getElementById('btnCancelar').style.display = 'none';
            document.getElementById('activo').checked = true;
        }
    </script>
</body>
</html>