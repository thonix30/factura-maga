<?php
session_start();
include("conexion.php");
include("funciones.php");

// Verificar sesión (permite tanto admin como usuario)
verificarSesion();

// Si es admin, redirigir a su dashboard
if (esAdmin()) {
    header("Location: dashboard_admin.php");
    exit();
}

// Obtener productos
$sql_productos = "SELECT * FROM productos ORDER BY producto";
$resultado_productos = $conn->query($sql_productos);

// Guardar factura
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nit = $_POST['nit'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $tipo_pago = $_POST['tipo_pago'];
    
    $productos_seleccionados = [];
    $total_factura = 0;
    
    if(isset($_POST['producto_id']) && is_array($_POST['producto_id'])) {
        foreach($_POST['producto_id'] as $key => $id_prod) {
            if(!empty($id_prod) && !empty($_POST['cantidad'][$key])) {
                $cantidad = intval($_POST['cantidad'][$key]);
                
                $sql_prod = "SELECT producto, precio FROM productos WHERE idproducto = ?";
                $stmt_prod = $conn->prepare($sql_prod);
                $stmt_prod->bind_param("i", $id_prod);
                $stmt_prod->execute();
                $result_prod = $stmt_prod->get_result();
                $prod_info = $result_prod->fetch_assoc();
                
                if($prod_info) {
                    $subtotal = $prod_info['precio'] * $cantidad;
                    $total_factura += $subtotal;
                    $productos_seleccionados[] = $cantidad . "x " . $prod_info['producto'] . " (Q" . number_format($subtotal, 2) . ")";
                }
            }
        }
    }
    
    if(count($productos_seleccionados) > 0) {
        $productos_texto = implode(", ", $productos_seleccionados);
        
        $sql = "INSERT INTO facturas (nit, nombre, apellido, productos, total, tipo_pago) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssds", $nit, $nombre, $apellido, $productos_texto, $total_factura, $tipo_pago);

        if ($stmt->execute()) {
            $mensaje = "✅ Factura registrada correctamente - Total: Q" . number_format($total_factura, 2);
            $numero_factura = $conn->insert_id;
        } else {
            $mensaje = "❌ Error: " . $conn->error;
        }
    } else {
        $mensaje = "❌ Debe seleccionar al menos un producto";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Facturación</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="dashboard-header">
        <h1>🧾 Sistema de Factura MAGA - Usuario</h1>
        <div class="user-info">
            <span>Bienvenido, <?php echo getNombreUsuario(); ?></span>
        </div>
        <div class="header-buttons">
            <button onclick="window.location.href='cuentas_cobrar.php'" class="btn-historial">💳 Cuentas por Cobrar</button>
            <button onclick="window.location.href='facturas_usuario.php'" class="btn-historial">📋 Mis Facturas</button>
            <a href="logout.php" class="btn-logout">🚪 Cerrar Sesión</a>
        </div>
    </div>

    <div class="container-dashboard">
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-exito"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <div class="factura-card">
            <h2>📝 Nueva Factura</h2>
            
            <form method="POST" id="formFactura">
                <div class="cliente-info">
                    <h3>Información del Cliente</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>NIT</label>
                            <input type="text" name="nit" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Apellido</label>
                            <input type="text" name="apellido" required>
                        </div>
                    </div>
                </div>

                <div class="productos-section">
                    <h3>Productos</h3>
                    <div id="productosLista">
                        <div class="producto-item">
                            <select name="producto_id[]" class="select-producto" onchange="calcularTotal()" required>
                                <option value="">Seleccionar producto</option>
                                <?php 
                                if($resultado_productos->num_rows > 0) {
                                    $resultado_productos->data_seek(0);
                                    while($row = $resultado_productos->fetch_assoc()) {
                                        echo "<option value='{$row['idproducto']}' data-precio='{$row['precio']}'>";
                                        echo $row['producto'] . " - Q" . number_format($row['precio'], 2);
                                        echo "</option>";
                                    }
                                }
                                ?>
                            </select>
                            <input type="number" name="cantidad[]" min="1" value="1" placeholder="Cantidad" class="input-cantidad" onchange="calcularTotal()" required>
                            <button type="button" class="btn-eliminar" onclick="eliminarProducto(this)" style="display:none;">✖</button>
                        </div>
                    </div>
                    <button type="button" class="btn-agregar" onclick="agregarProducto()">➕ Agregar otro producto</button>
                </div>

                <div class="total-section">
                    <div class="total-display" id="totalPreview">
                        Total: Q0.00
                    </div>
                </div>

                <div class="pago-section">
                    <h3>Tipo de Pago</h3>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="tipo_pago" value="Contado" checked required>
                            <span>💵 Contado</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="tipo_pago" value="Credito" required>
                            <span>💳 Crédito</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-guardar">💾 Guardar Factura</button>
            </form>
        </div>
    </div>

    <script>
        function agregarProducto() {
            const container = document.getElementById('productosLista');
            const nuevoProducto = document.createElement('div');
            nuevoProducto.className = 'producto-item';
            nuevoProducto.innerHTML = `
                <select name="producto_id[]" class="select-producto" onchange="calcularTotal()" required>
                    <option value="">Seleccionar producto</option>
                    <?php 
                    if($resultado_productos->num_rows > 0) {
                        $resultado_productos->data_seek(0);
                        while($row = $resultado_productos->fetch_assoc()) {
                            echo "<option value='{$row['idproducto']}' data-precio='{$row['precio']}'>";
                            echo $row['producto'] . " - Q" . number_format($row['precio'], 2);
                            echo "</option>";
                        }
                    }
                    ?>
                </select>
                <input type="number" name="cantidad[]" min="1" value="1" placeholder="Cantidad" class="input-cantidad" onchange="calcularTotal()" required>
                <button type="button" class="btn-eliminar" onclick="eliminarProducto(this)">✖</button>
            `;
            container.appendChild(nuevoProducto);
            actualizarBotonesEliminar();
        }

        function eliminarProducto(btn) {
            btn.closest('.producto-item').remove();
            actualizarBotonesEliminar();
            calcularTotal();
        }

        function actualizarBotonesEliminar() {
            const items = document.querySelectorAll('.producto-item');
            items.forEach(item => {
                const btnEliminar = item.querySelector('.btn-eliminar');
                btnEliminar.style.display = items.length > 1 ? 'block' : 'none';
            });
        }

        function calcularTotal() {
            let total = 0;
            const items = document.querySelectorAll('.producto-item');
            
            items.forEach(item => {
                const select = item.querySelector('.select-producto');
                const cantidad = item.querySelector('.input-cantidad').value;
                
                if(select.value && cantidad) {
                    const precio = parseFloat(select.options[select.selectedIndex].dataset.precio);
                    total += precio * parseInt(cantidad);
                }
            });
            
            document.getElementById('totalPreview').textContent = 'Total: Q' + total.toFixed(2);
        }

        document.addEventListener('DOMContentLoaded', calcularTotal);
    </script>
</body>
</html>