<?php
session_start();
include("conexion.php");
include("funciones.php");

// Verificar que sea administrador
verificarAdmin();

// Obtener facturas
$sql = "SELECT * FROM facturas ORDER BY idfactura DESC";
$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Facturas</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="productos.css">
    <link rel="stylesheet" href="abonos.css">
</head>
<body>
    <div class="container-productos">
        <div class="header-productos">
            <h1>📋 Historial de Facturas</h1>
            <button onclick="window.location.href='dashboard_admin.php'" class="btn-volver">← Volver al Dashboard</button>
        </div>

        <div class="tabla-card">
            <div class="tabla-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No. Factura</th>
                            <th>NIT</th>
                            <th>Cliente</th>
                            <th>Productos</th>
                            <th>Total</th>
                            <th>Tipo Pago</th>
                            <th>Estado</th>
                            <th>Saldo</th>
                            <th>Registrado por</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultado->num_rows > 0): ?>
                            <?php while($row = $resultado->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo str_pad($row['idfactura'], 6, '0', STR_PAD_LEFT); ?></strong>
                                    </td>
                                    <td><?php echo $row['nit']; ?></td>
                                    <td><?php echo $row['nombre'] . ' ' . $row['apellido']; ?></td>
                                    <td>
                                        <div class="productos-detalle">
                                            <?php echo $row['productos']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>Q <?php echo number_format($row['total'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge-<?php echo strtolower($row['tipo_pago']); ?>">
                                            <?php echo $row['tipo_pago'] == 'Contado' ? '💵 Contado' : '💳 Crédito'; ?>
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
                                    <td>
                                        <?php if($row['tipo_pago'] == 'Credito'): ?>
                                            <span class="saldo-<?php echo $row['estado_pago']; ?>">
                                                Q<?php echo number_format($row['saldo_pendiente'], 2); ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['usuario_registro']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_factura'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" style="text-align:center;">No hay facturas registradas</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>