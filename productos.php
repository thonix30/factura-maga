<?php
session_start();
include("conexion.php");
include("funciones.php");

// Verificar que sea administrador
verificarAdmin();

// Agregar producto
if (isset($_POST['agregar'])) {
    $producto = $_POST['producto'];
    $precio = $_POST['precio'];
    
    $sql = "INSERT INTO productos (producto, precio) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sd", $producto, $precio);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Producto agregado correctamente";
    } else {
        $mensaje = "❌ Error al agregar producto";
    }
}

// Eliminar producto
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $sql = "DELETE FROM productos WHERE idproducto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Producto eliminado correctamente";
    } else {
        $mensaje = "❌ Error al eliminar producto";
    }
}

// Modificar producto
if (isset($_POST['modificar'])) {
    $id = $_POST['idproducto'];
    $producto = $_POST['producto'];
    $precio = $_POST['precio'];
    
    $sql = "UPDATE productos SET producto = ?, precio = ? WHERE idproducto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdi", $producto, $precio, $id);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Producto modificado correctamente";
    } else {
        $mensaje = "❌ Error al modificar producto";
    }
}

// Obtener productos
$sql = "SELECT * FROM productos ORDER BY idproducto DESC";
$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="productos.css">
</head>
<body>
    <div class="container-productos">
        <div class="header-productos">
            <h1>📦 Gestión de Productos</h1>
            <button onclick="window.location.href='dashboard_admin.php'" class="btn-volver">← Volver al Dashboard</button>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <!-- Formulario Agregar/Modificar -->
        <div class="form-card">
            <h2 id="titulo-form">➕ Agregar Nuevo Producto</h2>
            <form method="POST" id="formProducto">
                <input type="hidden" name="idproducto" id="idproducto">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre del Producto</label>
                        <input type="text" name="producto" id="producto" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Precio (Q)</label>
                        <input type="number" step="0.01" name="precio" id="precio" required>
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" name="agregar" id="btnAgregar" class="btn-success">Agregar Producto</button>
                    <button type="submit" name="modificar" id="btnModificar" class="btn-warning" style="display:none;">Modificar Producto</button>
                    <button type="button" onclick="cancelarEdicion()" id="btnCancelar" class="btn-secondary" style="display:none;">Cancelar</button>
                </div>
            </form>
        </div>

        <!-- Tabla de Productos -->
        <div class="tabla-card">
            <h2>📋 Lista de Productos</h2>
            <div class="tabla-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultado->num_rows > 0): ?>
                            <?php while($row = $resultado->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['idproducto']; ?></td>
                                    <td><?php echo $row['producto']; ?></td>
                                    <td>Q <?php echo number_format($row['precio'], 2); ?></td>
                                    <td>
                                        <button onclick='editarProducto(<?php echo json_encode($row); ?>)' class="btn-edit">✏️ Editar</button>
                                        <a href="?eliminar=<?php echo $row['idproducto']; ?>" 
                                           onclick="return confirm('¿Está seguro de eliminar este producto?')" 
                                           class="btn-delete">🗑️ Eliminar</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center;">No hay productos registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function editarProducto(producto) {
            document.getElementById('idproducto').value = producto.idproducto;
            document.getElementById('producto').value = producto.producto;
            document.getElementById('precio').value = producto.precio;
            
            document.getElementById('titulo-form').textContent = '✏️ Modificar Producto';
            document.getElementById('btnAgregar').style.display = 'none';
            document.getElementById('btnModificar').style.display = 'block';
            document.getElementById('btnCancelar').style.display = 'block';
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function cancelarEdicion() {
            document.getElementById('formProducto').reset();
            document.getElementById('idproducto').value = '';
            document.getElementById('titulo-form').textContent = '➕ Agregar Nuevo Producto';
            document.getElementById('btnAgregar').style.display = 'block';
            document.getElementById('btnModificar').style.display = 'none';
            document.getElementById('btnCancelar').style.display = 'none';
        }
    </script>
</body>
</html>