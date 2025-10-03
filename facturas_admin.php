<?php
session_start();
include("conexion.php");
include("funciones.php");

// Verificar que sea administrador
verificarAdmin();

// Procesar búsqueda
$where = "WHERE 1=1";
$busqueda = "";
$fecha_inicio = "";
$fecha_fin = "";
$tipo_pago_filtro = "";
$estado_filtro = "";

if(isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
    $busqueda = $conn->real_escape_string($_GET['busqueda']);
    $where .= " AND (idfactura LIKE '%$busqueda%' 
                OR nit LIKE '%$busqueda%' 
                OR nombre LIKE '%$busqueda%' 
                OR apellido LIKE '%$busqueda%'
                OR productos LIKE '%$busqueda%')";
}

if(isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio'])) {
    $fecha_inicio = $conn->real_escape_string($_GET['fecha_inicio']);
    $where .= " AND DATE(fecha_factura) >= '$fecha_inicio'";
}

if(isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin'])) {
    $fecha_fin = $conn->real_escape_string($_GET['fecha_fin']);
    $where .= " AND DATE(fecha_factura) <= '$fecha_fin'";
}

if(isset($_GET['tipo_pago']) && !empty($_GET['tipo_pago'])) {
    $tipo_pago_filtro = $conn->real_escape_string($_GET['tipo_pago']);
    $where .= " AND tipo_pago = '$tipo_pago_filtro'";
}

if(isset($_GET['estado']) && !empty($_GET['estado'])) {
    $estado_filtro = $conn->real_escape_string($_GET['estado']);
    $where .= " AND estado_pago = '$estado_filtro'";
}

// Obtener facturas con filtros
$sql = "SELECT * FROM facturas $where ORDER BY idfactura DESC";
$resultado = $conn->query($sql);

// Calcular estadísticas
$sql_stats = "SELECT 
                COUNT(*) as total_facturas,
                SUM(total) as suma_total,
                SUM(CASE WHEN tipo_pago = 'Credito' THEN saldo_pendiente ELSE 0 END) as total_pendiente
              FROM facturas $where";
$stats = $conn->query($sql_stats)->fetch_assoc();
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
    <style>
        .buscador-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filtros-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .campo-filtro {
            display: flex;
            flex-direction: column;
        }
        
        .campo-filtro label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .campo-filtro input,
        .campo-filtro select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .botones-filtro {
            display: flex;
            gap: 10px;
        }
        
        .btn-buscar {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-limpiar {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-buscar:hover {
            background: #45a049;
        }
        
        .btn-limpiar:hover {
            background: #5a6268;
        }
        
        .estadisticas {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            flex: 1;
            text-align: center;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .valor {
            color: #333;
            font-size: 24px;
            font-weight: bold;
        }
        
        .stat-card.total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-card.ventas {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .stat-card.pendiente {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }
        
        .stat-card.total h3,
        .stat-card.ventas h3,
        .stat-card.pendiente h3 {
            color: white;
        }
        
        .btn-reporte {
            background: #2196F3;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
        }
        
        .btn-reporte:hover {
            background: #1976D2;
        }
        
        .acciones-cell {
            display: flex;
            gap: 5px;
        }
        
        @media print {
            .buscador-container,
            .btn-volver,
            .acciones-cell {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container-productos">
        <div class="header-productos">
            <h1>📋 Historial de Facturas</h1>
            <button onclick="window.location.href='dashboard_admin.php'" class="btn-volver">← Volver al Dashboard</button>
        </div>

        <!-- Buscador y Filtros -->
        <div class="buscador-container">
            <h3>🔍 Buscar y Filtrar</h3>
            <form method="GET" action="" class="filtros-form">
                <div class="campo-filtro">
                    <label for="busqueda">Buscar:</label>
                    <input type="text" name="busqueda" id="busqueda" 
                           placeholder="No. Factura, NIT, Cliente..." 
                           value="<?php echo htmlspecialchars($busqueda); ?>">
                </div>
                
                <div class="campo-filtro">
                    <label for="fecha_inicio">Desde:</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" 
                           value="<?php echo $fecha_inicio; ?>">
                </div>
                
                <div class="campo-filtro">
                    <label for="fecha_fin">Hasta:</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" 
                           value="<?php echo $fecha_fin; ?>">
                </div>
                
                <div class="campo-filtro">
                    <label for="tipo_pago">Tipo de Pago:</label>
                    <select name="tipo_pago" id="tipo_pago">
                        <option value="">Todos</option>
                        <option value="Contado" <?php echo $tipo_pago_filtro == 'Contado' ? 'selected' : ''; ?>>Contado</option>
                        <option value="Credito" <?php echo $tipo_pago_filtro == 'Credito' ? 'selected' : ''; ?>>Crédito</option>
                    </select>
                </div>
                
                <div class="campo-filtro">
                    <label for="estado">Estado:</label>
                    <select name="estado" id="estado">
                        <option value="">Todos</option>
                        <option value="Pagado" <?php echo $estado_filtro == 'Pagado' ? 'selected' : ''; ?>>Pagado</option>
                        <option value="Parcial" <?php echo $estado_filtro == 'Parcial' ? 'selected' : ''; ?>>Parcial</option>
                        <option value="Pendiente" <?php echo $estado_filtro == 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    </select>
                </div>
                
                <div class="botones-filtro">
                    <button type="submit" class="btn-buscar">🔍 Buscar</button>
                    <button type="button" class="btn-limpiar" onclick="window.location.href='facturas_admin.php'">🔄 Limpiar</button>
                </div>
            </form>
        </div>

        <!-- Estadísticas -->
        <div class="estadisticas">
            <div class="stat-card total">
                <h3>📊 Total Facturas</h3>
                <div class="valor"><?php echo number_format($stats['total_facturas']); ?></div>
            </div>
            <div class="stat-card ventas">
                <h3>💰 Total Ventas</h3>
                <div class="valor">Q<?php echo number_format($stats['suma_total'], 2); ?></div>
            </div>
            <div class="stat-card pendiente">
                <h3>⚠️ Total Pendiente</h3>
                <div class="valor">Q<?php echo number_format($stats['total_pendiente'], 2); ?></div>
            </div>
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
                            <th>Acciones</th>
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
                                    <td class="acciones-cell">
                                        <button onclick="verReporte(<?php echo $row['idfactura']; ?>)" 
                                                class="btn-reporte" title="Ver Reporte">
                                            📄 Ver
                                        </button>
                                        <button onclick="imprimirReporte(<?php echo $row['idfactura']; ?>)" 
                                                class="btn-reporte" title="Imprimir">
                                            🖨️ Imprimir
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" style="text-align:center;">No se encontraron facturas con los criterios especificados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function verReporte(id) {
            window.open('reporte_factura.php?id=' + id, '_blank', 'width=800,height=600');
        }
        
        function imprimirReporte(id) {
            var ventana = window.open('reporte_factura.php?id=' + id + '&print=1', '_blank', 'width=800,height=600');
            ventana.onload = function() {
                ventana.print();
            }
        }
    </script>
</body>
</html>