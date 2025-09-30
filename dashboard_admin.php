<?php
session_start();
include("conexion.php");
include("funciones.php");

// Verificar que sea administrador
verificarAdmin();

// Obtener estadísticas
$sql_productos = "SELECT COUNT(*) as total_productos FROM productos";
$result_productos = $conn->query($sql_productos);
$total_productos = $result_productos->fetch_assoc()['total_productos'];

$sql_facturas = "SELECT 
    COUNT(*) as total_facturas, 
    COALESCE(SUM(total), 0) as total_ventas, 
    COALESCE(SUM(saldo_pendiente), 0) as total_pendiente 
FROM facturas";
$result_facturas = $conn->query($sql_facturas);
$datos_facturas = $result_facturas->fetch_assoc();

$sql_usuarios = "SELECT COUNT(*) as total_usuarios FROM usuarios WHERE activo = 1";
$result_usuarios = $conn->query($sql_usuarios);
$total_usuarios = $result_usuarios->fetch_assoc()['total_usuarios'];

// Obtener productos para facturación
$sql_productos_list = "SELECT * FROM productos ORDER BY producto";
$resultado_productos = $conn->query($sql_productos_list);

// Procesar factura si se envía
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_factura'])) {
    $nit = $_POST['nit'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $tipo_pago = $_POST['tipo_pago'];
    $usuario_registro = $_SESSION['usuario'];
    
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
        
        if($tipo_pago == 'Contado') {
            $saldo_pendiente = 0;
            $estado_pago = 'Pagado';
        } else {
            $saldo_pendiente = $total_factura;
            $estado_pago = 'Pendiente';
        }
        
        $sql = "INSERT INTO facturas (nit, nombre, apellido, productos, total, saldo_pendiente, tipo_pago, estado_pago, usuario_registro) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssddsss", $nit, $nombre, $apellido, $productos_texto, $total_factura, $saldo_pendiente, $tipo_pago, $estado_pago, $usuario_registro);

        if ($stmt->execute()) {
            $mensaje = "✅ Factura registrada correctamente - Total: Q" . number_format($total_factura, 2);
            if($tipo_pago == 'Credito') {
                $mensaje .= " - Saldo pendiente: Q" . number_format($saldo_pendiente, 2);
            }
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
    <title>Dashboard Administrador - Sistema de Factura</title>
    <link rel="stylesheet" href="dashboard-responsive.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- PANEL DE ADMINISTRACIÓN PRINCIPAL -->
        <header class="admin-panel">
            <div class="container">
                <div class="panel-header">
                    <div class="panel-title">
                        <h1>👑 Panel de Administración MAGA</h1>
                        <p class="panel-subtitle">Sistema Factura</p>
                    </div>
                    <div class="user-info">
                        <div class="user-avatar">👤</div>
                        <div class="user-details">
                            <span class="user-name"><?php echo getNombreUsuario(); ?></span>
                            <span class="user-role"><?php echo ucfirst(getRolUsuario()); ?></span>
                        </div>
                    </div>
                </div>

                <!-- MENÚ DE NAVEGACIÓN -->
                <nav class="admin-navigation">
                    <div class="nav-grid">
                        <a href="usuarios.php" class="nav-card">
                            <div class="nav-icon">👥</div>
                            <div class="nav-info">
                                <h3>Usuarios</h3>
                                <p>Gestionar usuarios</p>
                            </div>
                        </a>

                        <a href="productos.php" class="nav-card">
                            <div class="nav-icon">📦</div>
                            <div class="nav-info">
                                <h3>Productos</h3>
                                <p>Catálogo de productos</p>
                            </div>
                        </a>

                        <a href="facturas_admin.php" class="nav-card">
                            <div class="nav-icon">📋</div>
                            <div class="nav-info">
                                <h3>Facturas</h3>
                                <p>Ver todas las facturas</p>
                            </div>
                        </a>

                        <a href="cuentas_cobrar.php" class="nav-card">
                            <div class="nav-icon">💳</div>
                            <div class="nav-info">
                                <h3>Cuentas por Cobrar</h3>
                                <p>Gestión de cobros</p>
                            </div>
                        </a>

                        <a href="reportes.php" class="nav-card">
                            <div class="nav-icon">📊</div>
                            <div class="nav-info">
                                <h3>Reportes</h3>
                                <p>Análisis y estadísticas</p>
                            </div>
                        </a>

                        <a href="logout.php" class="nav-card nav-logout">
                            <div class="nav-icon">🚪</div>
                            <div class="nav-info">
                                <h3>Cerrar Sesión</h3>
                                <p>Salir del sistema</p>
                            </div>
                        </a>
                    </div>
                </nav>
            </div>
        </header>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content">
            <div class="container">
                <!-- ESTADÍSTICAS GENERALES -->
                <section class="stats-section">
                    <h2 class="section-title">📊 Estadísticas Generales</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon products-icon">📦</div>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo $total_productos; ?></span>
                                <span class="stat-label">Productos</span>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon invoices-icon">🧾</div>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo $datos_facturas['total_facturas']; ?></span>
                                <span class="stat-label">Facturas</span>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon sales-icon">💰</div>
                            <div class="stat-content">
                                <span class="stat-value">Q<?php echo number_format($datos_facturas['total_ventas'], 2); ?></span>
                                <span class="stat-label">Ventas Totales</span>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon pending-icon">⏳</div>
                            <div class="stat-content">
                                <span class="stat-value">Q<?php echo number_format($datos_facturas['total_pendiente'], 2); ?></span>
                                <span class="stat-label">Saldo Pendiente</span>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon users-icon">👤</div>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo $total_usuarios; ?></span>
                                <span class="stat-label">Usuarios Activos</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- FORMULARIO DE NUEVA FACTURA -->
                <section class="invoice-section">
                    <?php if (!empty($mensaje)): ?>
                        <div class="alert <?php echo strpos($mensaje, '✅') !== false ? 'alert-success' : 'alert-error'; ?>">
                            <?php echo $mensaje; ?>
                        </div>
                    <?php endif; ?>

                    <div class="invoice-card">
                        <div class="invoice-header">
                            <h2>📝 Nueva Factura</h2>
                            <span class="invoice-date">Fecha: <?php echo date('d/m/Y'); ?></span>
                        </div>

                        <form method="POST" id="formFactura" class="invoice-form">
                            <input type="hidden" name="crear_factura" value="1">

                            <!-- Información del Cliente -->
                            <fieldset class="form-section">
                                <legend>Información del Cliente</legend>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="nit">NIT</label>
                                        <input type="text" id="nit" name="nit" placeholder="123456789" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="nombre">Nombre</label>
                                        <input type="text" id="nombre" name="nombre" placeholder="Nombre del cliente" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="apellido">Apellido</label>
                                        <input type="text" id="apellido" name="apellido" placeholder="Apellido del cliente" required>
                                    </div>
                                </div>
                            </fieldset>

                            <!-- Productos -->
                            <fieldset class="form-section">
                                <legend>Productos</legend>
                                <div id="productosLista" class="products-container">
                                    <div class="product-item">
                                        <div class="product-select-group">
                                            <label>Producto</label>
                                            <select name="producto_id[]" class="select-producto" onchange="calcularTotal()" required>
                                                <option value="">Seleccionar...</option>
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
                                        </div>
                                        <div class="product-qty-group">
                                            <label>Cantidad</label>
                                            <input type="number" name="cantidad[]" min="1" value="1" class="input-cantidad" onchange="calcularTotal()" required>
                                        </div>
                                        <button type="button" class="btn-remove" onclick="eliminarProducto(this)" style="display:none;">✖</button>
                                    </div>
                                </div>
                                <button type="button" class="btn-add-product" onclick="agregarProducto()">
                                    ➕ Agregar otro producto
                                </button>
                            </fieldset>

                            <!-- Resumen y Pago -->
                            <div class="payment-section">
                                <div class="total-box">
                                    <span class="total-label">Total a Pagar:</span>
                                    <span class="total-amount" id="totalPreview">Q0.00</span>
                                </div>

                                <fieldset class="form-section payment-methods">
                                    <legend>Método de Pago</legend>
                                    <div class="payment-options">
                                        <label class="payment-option">
                                            <input type="radio" name="tipo_pago" value="Contado" checked>
                                            <div class="option-card">
                                                <span class="option-icon">💵</span>
                                                <span class="option-label">Contado</span>
                                            </div>
                                        </label>
                                        <label class="payment-option">
                                            <input type="radio" name="tipo_pago" value="Credito">
                                            <div class="option-card">
                                                <span class="option-icon">💳</span>
                                                <span class="option-label">Crédito</span>
                                            </div>
                                        </label>
                                    </div>
                                </fieldset>

                                <button type="submit" class="btn-submit">
                                    💾 Guardar Factura
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        function agregarProducto() {
            const container = document.getElementById('productosLista');
            const nuevoProducto = document.createElement('div');
            nuevoProducto.className = 'product-item';
            nuevoProducto.innerHTML = `
                <div class="product-select-group">
                    <label>Producto</label>
                    <select name="producto_id[]" class="select-producto" onchange="calcularTotal()" required>
                        <option value="">Seleccionar...</option>
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
                </div>
                <div class="product-qty-group">
                    <label>Cantidad</label>
                    <input type="number" name="cantidad[]" min="1" value="1" class="input-cantidad" onchange="calcularTotal()" required>
                </div>
                <button type="button" class="btn-remove" onclick="eliminarProducto(this)">✖</button>
            `;
            container.appendChild(nuevoProducto);
            actualizarBotonesEliminar();
        }

        function eliminarProducto(btn) {
            btn.closest('.product-item').remove();
            actualizarBotonesEliminar();
            calcularTotal();
        }

        function actualizarBotonesEliminar() {
            const items = document.querySelectorAll('.product-item');
            items.forEach(item => {
                const btnEliminar = item.querySelector('.btn-remove');
                btnEliminar.style.display = items.length > 1 ? 'block' : 'none';
            });
        }

        function calcularTotal() {
            let total = 0;
            const items = document.querySelectorAll('.product-item');
            
            items.forEach(item => {
                const select = item.querySelector('.select-producto');
                const cantidad = item.querySelector('.input-cantidad').value;
                
                if(select.value && cantidad) {
                    const precio = parseFloat(select.options[select.selectedIndex].dataset.precio);
                    total += precio * parseInt(cantidad);
                }
            });
            
            document.getElementById('totalPreview').textContent = 'Q' + total.toFixed(2);
        }

        document.addEventListener('DOMContentLoaded', function() {
            calcularTotal();
            
            // Animación de entrada
            const cards = document.querySelectorAll('.nav-card, .stat-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.05}s`;
            });
        });
    </script>
</body>
</html>