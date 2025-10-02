<?php
session_start();
include("conexion.php");
include("funciones.php");

// Verificar que sea administrador
verificarAdmin();

$mensajes = [];

// Verificar estructura de la tabla
$sql_estructura = "DESCRIBE facturas";
$resultado = $conn->query($sql_estructura);

$campos_requeridos = [
    'saldo_pendiente' => false,
    'estado_pago' => false,
    'usuario_registro' => false,
    'tipo_pago' => false
];

$mensajes[] = "<h3>🔍 Verificando estructura de la tabla facturas:</h3>";
while($campo = $resultado->fetch_assoc()) {
    if(isset($campos_requeridos[$campo['Field']])) {
        $campos_requeridos[$campo['Field']] = true;
        $mensajes[] = "✅ Campo '{$campo['Field']}' existe - Tipo: {$campo['Type']}, Default: {$campo['Default']}";
    }
}

// Verificar campos faltantes
foreach($campos_requeridos as $campo => $existe) {
    if(!$existe) {
        $mensajes[] = "❌ Campo '$campo' NO existe en la tabla";
        
        // Intentar agregar el campo
        switch($campo) {
            case 'saldo_pendiente':
                $sql_add = "ALTER TABLE facturas ADD COLUMN saldo_pendiente DECIMAL(10,2) DEFAULT 0 AFTER total";
                break;
            case 'estado_pago':
                $sql_add = "ALTER TABLE facturas ADD COLUMN estado_pago VARCHAR(20) DEFAULT 'Pendiente' AFTER tipo_pago";
                break;
            case 'usuario_registro':
                $sql_add = "ALTER TABLE facturas ADD COLUMN usuario_registro VARCHAR(100) AFTER estado_pago";
                break;
        }
        
        if(isset($sql_add)) {
            if($conn->query($sql_add)) {
                $mensajes[] = "✅ Campo '$campo' agregado exitosamente";
            } else {
                $mensajes[] = "❌ Error al agregar campo '$campo': " . $conn->error;
            }
        }
    }
}

// Verificar y corregir facturas con problemas
if(isset($_POST['corregir'])) {
    $mensajes[] = "<h3>🔧 Corrigiendo facturas existentes:</h3>";
    
    // Corregir facturas de crédito sin saldo pendiente
    $sql_corregir = "UPDATE facturas 
                     SET saldo_pendiente = total, 
                         estado_pago = 'Pendiente' 
                     WHERE tipo_pago = 'Credito' 
                     AND (saldo_pendiente = 0 OR saldo_pendiente IS NULL)
                     AND NOT EXISTS (SELECT 1 FROM abonos WHERE abonos.idfactura = facturas.idfactura)";
    
    if($conn->query($sql_corregir)) {
        $afectadas = $conn->affected_rows;
        $mensajes[] = "✅ Se corrigieron $afectadas facturas de crédito sin saldo pendiente";
    }
    
    // Corregir facturas de contado
    $sql_contado = "UPDATE facturas 
                    SET saldo_pendiente = 0, 
                        estado_pago = 'Pagado' 
                    WHERE tipo_pago = 'Contado' 
                    AND (saldo_pendiente > 0 OR estado_pago != 'Pagado')";
    
    if($conn->query($sql_contado)) {
        $afectadas = $conn->affected_rows;
        $mensajes[] = "✅ Se corrigieron $afectadas facturas de contado";
    }
    
    // Actualizar estado de facturas con abonos
    $sql_abonos = "UPDATE facturas f 
                   SET estado_pago = CASE 
                       WHEN saldo_pendiente = 0 THEN 'Pagado'
                       WHEN saldo_pendiente < total THEN 'Parcial'
                       ELSE 'Pendiente'
                   END
                   WHERE tipo_pago = 'Credito'";
    
    if($conn->query($sql_abonos)) {
        $afectadas = $conn->affected_rows;
        $mensajes[] = "✅ Se actualizó el estado de $afectadas facturas según sus abonos";
    }
}

// Estadísticas actuales
$sql_stats = "SELECT 
              tipo_pago,
              estado_pago,
              COUNT(*) as cantidad,
              SUM(total) as total,
              SUM(saldo_pendiente) as saldo
              FROM facturas 
              GROUP BY tipo_pago, estado_pago
              ORDER BY tipo_pago, estado_pago";

$resultado_stats = $conn->query($sql_stats);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación y Corrección de Base de Datos</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 32px;
        }
        
        h3 {
            color: #667eea;
            margin-top: 30px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .mensaje {
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .mensaje:nth-child(even) {
            background: #f9f9f9;
        }
        
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .stats-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        .stats-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .stats-table tr:hover {
            background: #f5f5f5;
        }
        
        .btn-corregir {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin: 20px 0;
            transition: all 0.3s;
        }
        
        .btn-corregir:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }
        
        .btn-volver {
            background: #6c757d;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }
        
        .btn-volver:hover {
            background: #5a6268;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .problema-detectado {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 10px;
            margin: 10px 0;
        }
        
        .sin-problemas {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 10px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Verificación y Corrección de Base de Datos</h1>
        
        <?php foreach($mensajes as $mensaje): ?>
            <div class="mensaje"><?php echo $mensaje; ?></div>
        <?php endforeach; ?>
        
        <h3>📊 Estadísticas Actuales de Facturas</h3>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Tipo de Pago</th>
                    <th>Estado</th>
                    <th>Cantidad</th>
                    <th>Total</th>
                    <th>Saldo Pendiente</th>
                </tr>
            </thead>
            <tbody>
                <?php while($stat = $resultado_stats->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo $stat['tipo_pago']; ?></strong></td>
                    <td><?php echo $stat['estado_pago']; ?></td>
                    <td><?php echo $stat['cantidad']; ?></td>
                    <td>Q<?php echo number_format($stat['total'], 2); ?></td>
                    <td>Q<?php echo number_format($stat['saldo'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <?php
        // Detectar problemas
        $sql_problemas = "SELECT COUNT(*) as problemas FROM facturas 
                         WHERE (tipo_pago = 'Credito' AND saldo_pendiente = 0 AND estado_pago != 'Pagado')
                         OR (tipo_pago = 'Contado' AND saldo_pendiente > 0)";
        $result_problemas = $conn->query($sql_problemas);
        $problemas = $result_problemas->fetch_assoc()['problemas'];
        
        if($problemas > 0):
        ?>
        <div class="problema-detectado">
            <h4>⚠️ Se detectaron <?php echo $problemas; ?> facturas con inconsistencias</h4>
            <p>Facturas de crédito sin saldo pendiente o facturas de contado con saldo pendiente.</p>
        </div>
        
        <form method="POST">
            <button type="submit" name="corregir" value="1" class="btn-corregir" 
                    onclick="return confirm('¿Está seguro de corregir las facturas con problemas?')">
                🔧 Corregir Facturas con Problemas
            </button>
        </form>
        <?php else: ?>
        <div class="sin-problemas">
            <h4>✅ No se detectaron problemas en las facturas</h4>
            <p>Todas las facturas tienen sus estados y saldos correctamente configurados.</p>
        </div>
        <?php endif; ?>
        
        <h3>🧪 Prueba de Inserción</h3>
        <div class="warning-box">
            <p>Para verificar que el sistema está funcionando correctamente:</p>
            <ol>
                <li>Vaya al dashboard y cree una factura de prueba con tipo "Crédito"</li>
                <li>Verifique en la lista de facturas que aparece con saldo pendiente</li>
                <li>Cree otra factura con tipo "Contado" y verifique que aparece como "Pagado"</li>
            </ol>
        </div>
        
        <a href="dashboard_admin.php" class="btn-volver">← Volver al Dashboard</a>
    </div>
</body>
</html>