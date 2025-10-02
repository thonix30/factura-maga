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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_factura'])) {
    $nit = $_POST['nit'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    
    // Capturar correctamente el tipo de pago
    $tipo_pago = isset($_POST['tipo_pago']) ? $_POST['tipo_pago'] : 'Contado';
    
    // Debug para verificar el valor recibido
    error_log("Tipo de pago recibido en dashboard.php: " . $tipo_pago);
    
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
        
        // IMPORTANTE: Determinar saldo y estado según el tipo de pago
        if($tipo_pago == 'Contado') {
            $saldo_pendiente = 0;
            $estado_pago = 'Pagado';
        } else if($tipo_pago == 'Credito') {
            $saldo_pendiente = $total_factura;
            $estado_pago = 'Pendiente';
        } else {
            // Por defecto si hay algún error
            $tipo_pago = 'Contado';
            $saldo_pendiente = 0;
            $estado_pago = 'Pagado';
        }
        
        // IMPORTANTE: Incluir TODOS los campos necesarios en el INSERT
        $sql = "INSERT INTO facturas (nit, nombre, apellido, productos, total, saldo_pendiente, tipo_pago, estado_pago, usuario_registro) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssddsss", $nit, $nombre, $apellido, $productos_texto, $total_factura, $saldo_pendiente, $tipo_pago, $estado_pago, $usuario_registro);

        if ($stmt->execute()) {
            $numero_factura = $conn->insert_id;
            $mensaje = "✅ Factura #" . str_pad($numero_factura, 6, '0', STR_PAD_LEFT) . " registrada correctamente";
            $mensaje .= "<br>Total: Q" . number_format($total_factura, 2);
            $mensaje .= "<br>Tipo de pago: <strong>" . $tipo_pago . "</strong>";
            if($tipo_pago == 'Credito') {
                $mensaje .= "<br>Saldo pendiente: <strong style='color:#ff9800;'>Q" . number_format($saldo_pendiente, 2) . "</strong>";
            }
        } else {
            $mensaje = "❌ Error al guardar: " . $conn->error;
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
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* Estilos adicionales para mejorar los radio buttons */
        .radio-group {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 20px 0;
        }
        
        .radio-label {
            display: flex;
            align-items: center;
            padding: 15px 30px;
            border: 2px solid #ddd;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            position: relative;
        }
        
        .radio-label:hover {
            border-color: #4CAF50;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .radio-label input[type="radio"] {
            margin-right: 10px;
            cursor: pointer;
        }
        
        .radio-label input[type="radio"]:checked {
            accent-color: #4CAF50;
        }
        
        .radio-label:has(input:checked) {
            background: linear-gradient(to right, #f0fff0, white);
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }
        
        .radio-label span {
            font-size: 16px;
            font-weight: 500;
        }
        
        /* Indicador de tipo de pago seleccionado */
        .tipo-pago-indicator {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            background: #4CAF50;
            color: white;
            font-size: 14px;
            font-weight: bold;
            margin-left: 10px;
            transition: all 0.3s ease;
        }
        
        .tipo-pago-indicator.credito {
            background: #ff9800;
        }
        
        .mensaje-exito {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .total-display {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .debug-info {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #333;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 9999;
            display: none;
        }
        
        .debug-info.show {
            display: block;
        }
        
        /* Mejorar visual de los productos */
        .producto-item {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
            align-items: center;
        }
        
        .producto-item:hover {
            background: #f0f0f0;
        }
        
        .select-producto {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .input-cantidad {
            width: 100px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        
        .btn-eliminar {
            background: #f44336;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-eliminar:hover {
            background: #d32f2f;
            transform: scale(1.1);
        }
    </style>
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
            
            <form method="POST" id="formFactura" onsubmit="return validarFormulario()">
                <input type="hidden" name="crear_factura" value="1">
                
                <div class="cliente-info">
                    <h3>Información del Cliente</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>NIT</label>
                            <input type="text" name="nit" id="nit" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre" id="nombre" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Apellido</label>
                            <input type="text" name="apellido" id="apellido" required>
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
                                        echo htmlspecialchars($row['producto']) . " - Q" . number_format($row['precio'], 2);
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
                    <h3>
                        Tipo de Pago 
                        <span id="tipoPagoIndicator" class="tipo-pago-indicator">Contado</span>
                    </h3>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="tipo_pago" value="Contado" checked onchange="actualizarTipoPago()">
                            <span>💵 Contado</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="tipo_pago" value="Credito" onchange="actualizarTipoPago()">
                            <span>💳 Crédito</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-guardar">💾 Guardar Factura</button>
            </form>
        </div>
    </div>

    <!-- Debug info (oculto por defecto) -->
    <div id="debugInfo" class="debug-info">
        Tipo de pago: <span id="debugTipoPago">Contado</span>
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
                            $producto_escaped = addslashes($row['producto']);
                            echo "<option value='{$row['idproducto']}' data-precio='{$row['precio']}'>";
                            echo "{$producto_escaped} - Q" . number_format($row['precio'], 2);
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

        function actualizarTipoPago() {
            const tipoPago = document.querySelector('input[name="tipo_pago"]:checked').value;
            const indicator = document.getElementById('tipoPagoIndicator');
            
            indicator.textContent = tipoPago;
            if(tipoPago === 'Credito') {
                indicator.classList.add('credito');
            } else {
                indicator.classList.remove('credito');
            }
            
            // Debug
            document.getElementById('debugTipoPago').textContent = tipoPago;
            console.log('Tipo de pago seleccionado:', tipoPago);
        }

        function validarFormulario() {
            const tipoPago = document.querySelector('input[name="tipo_pago"]:checked');
            
            if (!tipoPago) {
                alert('Por favor seleccione un tipo de pago');
                return false;
            }
            
            // Confirmación visual
            const total = document.getElementById('totalPreview').textContent;
            const mensaje = `¿Confirma crear la factura?\n${total}\nTipo de pago: ${tipoPago.value}`;
            
            if(!confirm(mensaje)) {
                return false;
            }
            
            console.log('Enviando formulario con tipo de pago:', tipoPago.value);
            return true;
        }

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            calcularTotal();
            actualizarTipoPago();
            
            // Listener adicional para asegurar funcionamiento
            document.querySelectorAll('input[name="tipo_pago"]').forEach(radio => {
                radio.addEventListener('click', function() {
                    console.log('Radio clicked:', this.value);
                    actualizarTipoPago();
                });
            });
            
            // Mostrar debug con tecla D
            document.addEventListener('keypress', function(e) {
                if(e.key === 'd' || e.key === 'D') {
                    document.getElementById('debugInfo').classList.toggle('show');
                }
            });
        });

        // Log antes de enviar
        document.getElementById('formFactura').addEventListener('submit', function(e) {
            const formData = new FormData(this);
            console.log('Datos del formulario:');
            for(let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
        });
    </script>
</body>
</html>