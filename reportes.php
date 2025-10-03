<?php
session_start();
include("conexion.php");
include("funciones.php");

// Verificar que sea administrador
verificarAdmin();

// Obtener fechas de filtro
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// Reporte 1: Ventas por periodo
$sql_ventas = "SELECT 
    COUNT(*) as total_facturas,
    SUM(total) as total_ventas,
    SUM(CASE WHEN tipo_pago = 'Contado' THEN total ELSE 0 END) as ventas_contado,
    SUM(CASE WHEN tipo_pago = 'Credito' THEN total ELSE 0 END) as ventas_credito,
    SUM(saldo_pendiente) as saldo_pendiente
    FROM facturas 
    WHERE DATE(fecha_factura) BETWEEN ? AND ?";
$stmt = $conn->prepare($sql_ventas);
$stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt->execute();
$ventas = $stmt->get_result()->fetch_assoc();

// Reporte 2: Productos más vendidos
$sql_productos = "SELECT 
    p.producto,
    p.precio,
    COUNT(*) as veces_vendido
    FROM facturas f
    INNER JOIN productos p ON f.productos LIKE CONCAT('%', p.producto, '%')
    WHERE DATE(f.fecha_factura) BETWEEN ? AND ?
    GROUP BY p.idproducto
    ORDER BY veces_vendido DESC
    LIMIT 10";
$stmt_prod = $conn->prepare($sql_productos);
$stmt_prod->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt_prod->execute();
$productos_vendidos = $stmt_prod->get_result();

// Reporte 3: Clientes con más compras
$sql_clientes = "SELECT 
    nombre,
    apellido,
    nit,
    COUNT(*) as total_compras,
    SUM(total) as total_gastado,
    SUM(saldo_pendiente) as saldo_pendiente
    FROM facturas
    WHERE DATE(fecha_factura) BETWEEN ? AND ?
    GROUP BY nit
    ORDER BY total_compras DESC
    LIMIT 10";
$stmt_cli = $conn->prepare($sql_clientes);
$stmt_cli->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt_cli->execute();
$mejores_clientes = $stmt_cli->get_result();

// Reporte 4: Facturas pendientes de pago
$sql_pendientes = "SELECT 
    idfactura,
    nit,
    nombre,
    apellido,
    total,
    saldo_pendiente,
    fecha_factura,
    DATEDIFF(CURDATE(), fecha_factura) as dias_vencidos
    FROM facturas
    WHERE tipo_pago = 'Credito' AND estado_pago != 'Pagado'
    ORDER BY fecha_factura ASC";
$facturas_pendientes = $conn->query($sql_pendientes);

// Reporte 5: Ventas por usuario
$sql_usuarios = "SELECT 
    usuario_registro,
    COUNT(*) as total_facturas,
    SUM(total) as total_vendido
    FROM facturas
    WHERE DATE(fecha_factura) BETWEEN ? AND ?
    GROUP BY usuario_registro
    ORDER BY total_vendido DESC";
$stmt_usr = $conn->prepare($sql_usuarios);
$stmt_usr->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt_usr->execute();
$ventas_usuarios = $stmt_usr->get_result();

// Reporte 6: Estadísticas de abonos
$sql_abonos = "SELECT 
    COUNT(*) as total_abonos,
    SUM(monto_abono) as total_abonado,
    AVG(monto_abono) as promedio_abono
    FROM abonos
    WHERE DATE(fecha_abono) BETWEEN ? AND ?";
$stmt_abn = $conn->prepare($sql_abonos);
$stmt_abn->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt_abn->execute();
$stats_abonos = $stmt_abn->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes del Sistema</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="productos.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="reportes.css">
</head>
<body>
    <div class="container-productos">
        <div class="header-productos">
            <h1>📊 Reportes y Estadísticas</h1>
            <button onclick="window.location.href='dashboard_admin.php'" class="btn-volver">← Volver al Dashboard</button>
        </div>

        <!-- Filtros de fecha -->
        <div class="filtros-card">
            <h3>🔍 Filtrar por Periodo</h3>
            <form method="GET" class="form-filtros">
                <div class="filtro-group">
                    <label>Fecha Inicio:</label>
                    <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>" required>
                </div>
                <div class="filtro-group">
                    <label>Fecha Fin:</label>
                    <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>" required>
                </div>
                <button type="submit" class="btn-filtrar">Generar Reporte</button>
                <button type="button" onclick="imprimirReporte()" class="btn-imprimir">🖨️ Imprimir</button>
            </form>
        </div>

        <div class="periodo-info">
            <p>📅 Periodo: <strong><?php echo date('d/m/Y', strtotime($fecha_inicio)); ?></strong> al <strong><?php echo date('d/m/Y', strtotime($fecha_fin)); ?></strong></p>
        </div>

        <!-- Reporte 1: Resumen de Ventas -->
        <div class="reporte-card">
            <h2>💰 Resumen de Ventas</h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-icon">🧾</div>
                    <div class="stat-content">
                        <h3><?php echo $ventas['total_facturas']; ?></h3>
                        <p>Total Facturas</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">💵</div>
                    <div class="stat-content">
                        <h3>Q<?php echo number_format($ventas['total_ventas'], 2); ?></h3>
                        <p>Ventas Totales</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">💸</div>
                    <div class="stat-content">
                        <h3>Q<?php echo number_format($ventas['ventas_contado'], 2); ?></h3>
                        <p>Ventas al Contado</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">💳</div>
                    <div class="stat-content">
                        <h3>Q<?php echo number_format($ventas['ventas_credito'], 2); ?></h3>
                        <p>Ventas a Crédito</p>
                    </div>
                </div>
                <div class="stat-box stat-warning">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-content">
                        <h3>Q<?php echo number_format($ventas['saldo_pendiente'], 2); ?></h3>
                        <p>Saldo Pendiente</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3>Q<?php echo number_format($ventas['ventas_contado'] + ($ventas['ventas_credito'] - $ventas['saldo_pendiente']), 2); ?></h3>
                        <p>Total Cobrado</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reporte 2: Productos más vendidos -->
        <div class="reporte-card">
            <h2>🏆 Top 10 Productos Más Vendidos</h2>
            <div class="tabla-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Posición</th>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Veces Vendido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($productos_vendidos->num_rows > 0): ?>
                            <?php $pos = 1; while($prod = $productos_vendidos->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        if($pos == 1) echo "🥇";
                                        elseif($pos == 2) echo "🥈";
                                        elseif($pos == 3) echo "🥉";
                                        else echo $pos;
                                        ?>
                                    </td>
                                    <td><strong><?php echo $prod['producto']; ?></strong></td>
                                    <td>Q<?php echo number_format($prod['precio'], 2); ?></td>
                                    <td><?php echo $prod['veces_vendido']; ?></td>
                                </tr>
                            <?php $pos++; endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No hay datos</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reporte 3: Mejores Clientes -->
        <div class="reporte-card">
            <h2>⭐ Top 10 Mejores Clientes</h2>
            <div class="tabla-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Posición</th>
                            <th>Cliente</th>
                            <th>NIT</th>
                            <th>Total Compras</th>
                            <th>Total Gastado</th>
                            <th>Saldo Pendiente</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($mejores_clientes->num_rows > 0): ?>
                            <?php $pos = 1; while($cli = $mejores_clientes->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        if($pos == 1) echo "👑";
                                        elseif($pos == 2) echo "🥈";
                                        elseif($pos == 3) echo "🥉";
                                        else echo $pos;
                                        ?>
                                    </td>
                                    <td><strong><?php echo $cli['nombre'] . ' ' . $cli['apellido']; ?></strong></td>
                                    <td><?php echo $cli['nit']; ?></td>
                                    <td><?php echo $cli['total_compras']; ?></td>
                                    <td>Q<?php echo number_format($cli['total_gastado'], 2); ?></td>
                                    <td>
                                        <span class="<?php echo $cli['saldo_pendiente'] > 0 ? 'saldo-pendiente' : 'saldo-pagado'; ?>">
                                            Q<?php echo number_format($cli['saldo_pendiente'], 2); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php $pos++; endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;">No hay datos</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reporte 4: Facturas Pendientes -->
        <div class="reporte-card reporte-alerta">
            <h2>⚠️ Facturas Pendientes de Pago</h2>
            <div class="tabla-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No. Factura</th>
                            <th>Cliente</th>
                            <th>NIT</th>
                            <th>Total</th>
                            <th>Saldo Pendiente</th>
                            <th>Fecha Factura</th>
                            <th>Días Vencidos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($facturas_pendientes->num_rows > 0): ?>
                            <?php while($fac = $facturas_pendientes->fetch_assoc()): ?>
                                <tr class="<?php echo $fac['dias_vencidos'] > 30 ? 'row-danger' : ''; ?>">
                                    <td><strong>#<?php echo str_pad($fac['idfactura'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                    <td><?php echo $fac['nombre'] . ' ' . $fac['apellido']; ?></td>
                                    <td><?php echo $fac['nit']; ?></td>
                                    <td>Q<?php echo number_format($fac['total'], 2); ?></td>
                                    <td>
                                        <strong class="saldo-pendiente">Q<?php echo number_format($fac['saldo_pendiente'], 2); ?></strong>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($fac['fecha_factura'])); ?></td>
                                    <td>
                                        <span class="badge-dias-<?php echo $fac['dias_vencidos'] > 30 ? 'alto' : ($fac['dias_vencidos'] > 15 ? 'medio' : 'bajo'); ?>">
                                            <?php echo $fac['dias_vencidos']; ?> días
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center;">✅ No hay facturas pendientes</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reporte 5: Ventas por Usuario -->
        <!-- <div class="reporte-card">
            <h2>👥 Ventas por Usuario</h2>
            <div class="tabla-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Total Facturas</th>
                            <th>Total Vendido</th>
                            <th>Promedio por Factura</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($ventas_usuarios->num_rows > 0): ?>
                            <?php while($usr = $ventas_usuarios->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo $usr['usuario_registro']; ?></strong></td>
                                    <td><?php echo $usr['total_facturas']; ?></td>
                                    <td>Q<?php echo number_format($usr['total_vendido'], 2); ?></td>
                                    <td>Q<?php echo number_format($usr['total_vendido'] / $usr['total_facturas'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No hay datos</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div> -->

        <!-- Reporte 6: Estadísticas de Abonos -->
        <div class="reporte-card">
            <h2>💸 Estadísticas de Abonos</h2>
            <div class="stats-grid-small">
                <div class="stat-box-small">
                    <h3><?php echo $stats_abonos['total_abonos']; ?></h3>
                    <p>Total Abonos Registrados</p>
                </div>
                <div class="stat-box-small">
                    <h3>Q<?php echo number_format($stats_abonos['total_abonado'], 2); ?></h3>
                    <p>Total Abonado</p>
                </div>
                <div class="stat-box-small">
                    <h3>Q<?php echo number_format($stats_abonos['promedio_abono'], 2); ?></h3>
                    <p>Promedio por Abono</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function imprimirReporte() {
            window.print();
        }
    </script>
</body>
</html>