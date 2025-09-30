<?php
session_start();
include("conexion.php");
include("funciones.php");

// Verificar sesión (admin o usuario)
verificarSesion();

// Registrar abono
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar_abono'])) {
    $idfactura = $_POST['idfactura'];
    $monto_abono = $_POST['monto_abono'];
    $observaciones = $_POST['observaciones'];
    $usuario_registro = $_SESSION['usuario'];
    
    // Obtener datos de la factura
    $sql_factura = "SELECT total, saldo_pendiente FROM facturas WHERE idfactura = ?";
    $stmt = $conn->prepare($sql_factura);
    $stmt->bind_param("i", $idfactura);
    $stmt->execute();
    $factura = $stmt->get_result()->fetch_assoc();
    
    if($factura && $monto_abono > 0 && $monto_abono <= $factura['saldo_pendiente']) {
        // Registrar el abono
        $sql_abono = "INSERT INTO abonos (idfactura, monto_abono, usuario_registro, observaciones) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_abono);
        $stmt->bind_param("idss", $idfactura, $monto_abono, $usuario_registro, $observaciones);
        
        if($stmt->execute()) {
            // Actualizar saldo pendiente
            $nuevo_saldo = $factura['saldo_pendiente'] - $monto_abono;
            $nuevo_estado = $nuevo_saldo == 0 ? 'Pagado' : 'Parcial';
            
            $sql_update = "UPDATE facturas SET saldo_pendiente = ?, estado_pago = ? WHERE idfactura = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("dsi", $nuevo_saldo, $nuevo_estado, $idfactura);
            $stmt_update->execute();
            
            $mensaje = "✅ Abono registrado correctamente. Nuevo saldo: Q" . number_format($nuevo_saldo, 2);
        } else {
            $mensaje = "❌ Error al registrar el abono";
        }
    } else {
        $mensaje = "❌ Monto inválido o excede el saldo pendiente";
    }
}

// Obtener facturas con crédito
if(esAdmin()) {
    $sql = "SELECT * FROM facturas WHERE tipo_pago = 'Credito' ORDER BY estado_pago ASC, idfactura DESC";
    $resultado = $conn->query($sql);
} else {
    $usuario_actual = $_SESSION['usuario'];
    $sql = "SELECT * FROM facturas WHERE tipo_pago = 'Credito' AND usuario_registro = ? ORDER BY estado_pago ASC, idfactura DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario_actual);
    $stmt->execute();
    $resultado = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuentas por Cobrar</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="productos.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="abonos.css">
</head>
<body>
    <div class="container-productos">
        <div class="header-productos">
            <h1>💳 Cuentas por Cobrar</h1>
            <button onclick="window.location.href='<?php echo esAdmin() ? 'dashboard_admin.php' : 'dashboard.php'; ?>'" class="btn-volver">← Volver al Dashboard</button>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <!-- Estadísticas de créditos -->
        <div class="creditos-stats">
            <?php
            $sql_stats = esAdmin() 
                ? "SELECT COUNT(*) as total, SUM(saldo_pendiente) as total_saldo FROM facturas WHERE tipo_pago = 'Credito' AND estado_pago != 'Pagado'"
                : "SELECT COUNT(*) as total, SUM(saldo_pendiente) as total_saldo FROM facturas WHERE tipo_pago = 'Credito' AND estado_pago != 'Pagado' AND usuario_registro = '{$_SESSION['usuario']}'";
            $stats = $conn->query($sql_stats)->fetch_assoc();
            ?>
            <div class="stat-card-small">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Créditos Activos</p>
            </div>
            <div class="stat-card-small">
                <h3>Q<?php echo number_format($stats['total_saldo'] ?? 0, 2); ?></h3>

                <p>Saldo Total Pendiente</p>
            </div>
        </div>

        <!-- Tabla de Facturas a Crédito -->
        <div class="tabla-card">
            <h2>📋 Facturas a Crédito</h2>
            <div class="tabla-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No. Factura</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Saldo Pendiente</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultado->num_rows > 0): ?>
                            <?php while($row = $resultado->fetch_assoc()): ?>
                                <tr class="<?php echo $row['estado_pago'] == 'Pagado' ? 'factura-pagada' : ''; ?>">
                                    <td>
                                        <strong>#<?php echo str_pad($row['idfactura'], 6, '0', STR_PAD_LEFT); ?></strong>
                                    </td>
                                    <td>
                                        <strong><?php echo $row['nombre'] . ' ' . $row['apellido']; ?></strong><br>
                                        <small>NIT: <?php echo $row['nit']; ?></small>
                                    </td>
                                    <td><strong>Q<?php echo number_format($row['total'], 2); ?></strong></td>
                                    <td>
                                        <span class="saldo-<?php echo $row['estado_pago']; ?>">
                                            Q<?php echo number_format($row['saldo_pendiente'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-estado-<?php echo strtolower($row['estado_pago']); ?>">
                                            <?php 
                                            echo $row['estado_pago'] == 'Pagado' ? '✅ Pagado' : 
                                                ($row['estado_pago'] == 'Parcial' ? '⏳ Parcial' : '⚠️ Pendiente'); 
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($row['fecha_factura'])); ?></td>
                                    <td>
                                        <?php if($row['estado_pago'] != 'Pagado'): ?>
                                            <button onclick="abrirModalAbono(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="btn-abonar">
                                                💰 Abonar
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="verHistorialAbonos(<?php echo $row['idfactura']; ?>)" class="btn-historial-abonos">
                                            📜 Historial
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center;">No hay facturas a crédito</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para registrar abono -->
    <div id="modalAbono" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2>💰 Registrar Abono</h2>
            
            <div class="info-factura">
                <p><strong>Factura:</strong> <span id="modal-factura"></span></p>
                <p><strong>Cliente:</strong> <span id="modal-cliente"></span></p>
                <p><strong>Total Factura:</strong> <span id="modal-total"></span></p>
                <p><strong>Saldo Pendiente:</strong> <span id="modal-saldo" class="saldo-pendiente"></span></p>
            </div>

            <form method="POST" id="formAbono">
                <input type="hidden" name="registrar_abono" value="1">
                <input type="hidden" name="idfactura" id="input-idfactura">
                
                <div class="form-group">
                    <label>Monto del Abono (Q)</label>
                    <input type="number" step="0.01" name="monto_abono" id="input-monto" required min="0.01">
                    <small>Máximo: Q<span id="max-abono"></span></small>
                </div>
                
                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="observaciones" rows="3" placeholder="Opcional..."></textarea>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn-success">💾 Registrar Abono</button>
                    <button type="button" onclick="cerrarModal()" class="btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para historial de abonos -->
    <div id="modalHistorial" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModalHistorial()">&times;</span>
            <h2>📜 Historial de Abonos</h2>
            <div id="contenido-historial">
                Cargando...
            </div>
        </div>
    </div>

    <script>
        function abrirModalAbono(factura) {
            document.getElementById('modal-factura').textContent = '#' + String(factura.idfactura).padStart(6, '0');
            document.getElementById('modal-cliente').textContent = factura.nombre + ' ' + factura.apellido;
            document.getElementById('modal-total').textContent = 'Q' + parseFloat(factura.total).toFixed(2);
            document.getElementById('modal-saldo').textContent = 'Q' + parseFloat(factura.saldo_pendiente).toFixed(2);
            document.getElementById('max-abono').textContent = parseFloat(factura.saldo_pendiente).toFixed(2);
            
            document.getElementById('input-idfactura').value = factura.idfactura;
            document.getElementById('input-monto').max = factura.saldo_pendiente;
            
            document.getElementById('modalAbono').style.display = 'block';
        }

        function cerrarModal() {
            document.getElementById('modalAbono').style.display = 'none';
            document.getElementById('formAbono').reset();
        }

        function verHistorialAbonos(idfactura) {
            document.getElementById('modalHistorial').style.display = 'block';
            
            // Cargar historial con AJAX
            fetch('obtener_abonos.php?idfactura=' + idfactura)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('contenido-historial').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('contenido-historial').innerHTML = '<p>Error al cargar el historial</p>';
                });
        }

        function cerrarModalHistorial() {
            document.getElementById('modalHistorial').style.display = 'none';
        }

        // Cerrar modal al hacer click fuera
        window.onclick = function(event) {
            const modalAbono = document.getElementById('modalAbono');
            const modalHistorial = document.getElementById('modalHistorial');
            if (event.target == modalAbono) {
                cerrarModal();
            }
            if (event.target == modalHistorial) {
                cerrarModalHistorial();
            }
        }
    </script>
</body>
</html>