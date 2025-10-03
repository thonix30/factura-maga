<?php
session_start();
include("conexion.php");
include("funciones.php");

// Verificar que sea administrador
verificarAdmin();

// Obtener el ID de la factura
$id_factura = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_factura == 0) {
    die("Factura no encontrada");
}

// Obtener datos de la factura
$sql = "SELECT * FROM facturas WHERE idfactura = $id_factura";
$resultado = $conn->query($sql);

if ($resultado->num_rows == 0) {
    die("Factura no encontrada");
}

$factura = $resultado->fetch_assoc();

// Obtener historial de abonos si es crédito
$abonos = [];
if ($factura['tipo_pago'] == 'Credito') {
    $sql_abonos = "SELECT * FROM abonos WHERE idfactura = $id_factura ORDER BY fecha_abono ASC";
    $resultado_abonos = $conn->query($sql_abonos);
    while($abono = $resultado_abonos->fetch_assoc()) {
        $abonos[] = $abono;
    }
}

// Información de la empresa (puedes personalizarla)
$empresa_nombre = "MINISTERIO DE AGRICULTURA GANADERIA Y ALIMENTACION";
$empresa_direccion = "7A Avenida 12-90, Cdad. de Guatemala";
$empresa_telefono = "Tel: 2222-2222";
$empresa_nit = "NIT: 123456-7";
$empresa_email = "mga@gmail.com";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura #<?php echo str_pad($factura['idfactura'], 6, '0', STR_PAD_LEFT); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .factura-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .factura-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            position: relative;
        }
        
        .empresa-info {
            margin-bottom: 10px;
        }
        
        .empresa-nombre {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .empresa-detalles {
            font-size: 14px;
            line-height: 1.6;
        }
        
        .factura-numero {
            position: absolute;
            top: 70px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            padding: 20px 20px;
            border-radius: 25px;
            font-size: 15px;
            font-weight: bold;
        }
        
        .factura-body {
            padding: 30px;
        }
        
        .seccion {
            margin-bottom: 30px;
        }
        
        .seccion-titulo {
            font-size: 18px;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #764ba2;
        }
        
        .info-cliente {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .campo {
            margin-bottom: 10px;
        }
        
        .campo label {
            font-weight: bold;
            color: #666;
            display: inline-block;
            width: 120px;
        }
        
        .campo span {
            color: #333;
        }
        
        .tabla-productos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .tabla-productos th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            color: #666;
            border-bottom: 2px solid #dee2e6;
        }
        
        .tabla-productos td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .productos-lista {
            white-space: pre-line;
            line-height: 1.6;
        }
        
        .resumen-pago {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .linea-total {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        
        .linea-total.final {
            font-size: 20px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .estado-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .estado-pagado {
            background: #4CAF50;
            color: white;
        }
        
        .estado-parcial {
            background: #ff9800;
            color: white;
        }
        
        .estado-pendiente {
            background: #f44336;
            color: white;
        }
        
        .tabla-abonos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .tabla-abonos th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            color: #666;
            border-bottom: 2px solid #dee2e6;
        }
        
        .tabla-abonos td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .footer-factura {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        
        .firma-seccion {
            margin-top: 40px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 50px;
        }
        
        .firma-box {
            text-align: center;
        }
        
        .firma-linea {
            border-bottom: 1px solid #333;
            margin-bottom: 5px;
            height: 40px;
        }
        
        .firma-texto {
            font-size: 12px;
            color: #666;
        }
        
        @media print {
            body {
                background: white;
            }
            
            .factura-container {
                box-shadow: none;
                margin: 0;
            }
            
            .no-print {
                display: none;
            }
        }
        
        .botones-accion {
            text-align: center;
            padding: 20px;
            background: white;
            border-top: 1px solid #dee2e6;
        }
        
        .btn-print {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 0 10px;
        }
        
        .btn-close {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 0 10px;
        }
        
        .btn-print:hover {
            background: #45a049;
        }
        
        .btn-close:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="factura-container">
        <div class="factura-header">
            <div class="empresa-info">
                <div class="empresa-nombre"><?php echo $empresa_nombre; ?></div>
                <div class="empresa-detalles">
                    <?php echo $empresa_direccion; ?><br>
                    
                    <?php echo $empresa_telefono; ?> | <?php echo $empresa_email; ?><br>
                    <?php echo $empresa_nit; ?>
                </div>
            </div>
            <div class="factura-numero"><br>
                FACTURA #<?php echo str_pad($factura['idfactura'], 6, '0', STR_PAD_LEFT); ?>
            </div>
        </div>
        
        <div class="factura-body">
            <!-- Información del Cliente -->
            <div class="seccion">
                <div class="seccion-titulo">Información del Cliente</div>
                <div class="info-cliente">
                    <div class="campo">
                        <label>Cliente:</label>
                        <span><?php echo $factura['nombre'] . ' ' . $factura['apellido']; ?></span>
                    </div>
                    <div class="campo">
                        <label>NIT:</label>
                        <span><?php echo $factura['nit']; ?></span>
                    </div>
                    <div class="campo">
                        <label>Fecha:</label>
                        <span><?php echo date('d/m/Y H:i', strtotime($factura['fecha_factura'])); ?></span>
                    </div>
                    <div class="campo">
                        <label>Vendedor:</label>
                        <span><?php echo $factura['usuario_registro']; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Productos -->
            <div class="seccion">
                <div class="seccion-titulo">Detalle de Productos</div>
                <table class="tabla-productos">
                    <thead>
                        <tr>
                            <th>Descripción</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="productos-lista">
                                    <?php echo nl2br($factura['productos']); ?>
                                </div>
                            </td>
                            <td style="text-align: right; font-weight: bold;">
                                Q <?php echo number_format($factura['total'], 2); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Resumen de Pago -->
            <div class="seccion">
                <div class="seccion-titulo">Resumen de Pago</div>
                <div class="resumen-pago">
                    <div class="linea-total">
                        <span>Subtotal:</span>
                        <span>Q <?php echo number_format($factura['total'] / 1.12, 2); ?></span>
                    </div>
                    <div class="linea-total">
                        <span>IVA (12%):</span>
                        <span>Q <?php echo number_format($factura['total'] - ($factura['total'] / 1.12), 2); ?></span>
                    </div>
                    <div class="linea-total final">
                        <span>Total:</span>
                        <span>Q <?php echo number_format($factura['total'], 2); ?></span>
                    </div>
                    <div class="linea-total">
                        <span>Tipo de Pago:</span>
                        <span><?php echo $factura['tipo_pago']; ?></span>
                    </div>
                    <div class="linea-total">
                        <span>Estado:</span>
                        <span class="estado-badge estado-<?php echo strtolower($factura['estado_pago']); ?>">
                            <?php echo $factura['estado_pago']; ?>
                        </span>
                    </div>
                    <?php if($factura['tipo_pago'] == 'Credito'): ?>
                    <div class="linea-total">
                        <span>Saldo Pendiente:</span>
                        <span style="color: #f44336; font-weight: bold;">
                            Q <?php echo number_format($factura['saldo_pendiente'], 2); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Historial de Abonos -->
            <?php if($factura['tipo_pago'] == 'Credito' && count($abonos) > 0): ?>
            <div class="seccion">
                <div class="seccion-titulo">Historial de Abonos</div>
                <table class="tabla-abonos">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Método</th>
                            <th>Registrado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($abonos as $abono): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($abono['fecha_abono'])); ?></td>
                            <td>Q <?php echo number_format($abono['monto_abono'], 2); ?></td>
                            <td><?php echo $abono['metodo_pago'] ?? 'Efectivo'; ?></td>
                            <td><?php echo $abono['usuario_registro']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="font-weight: bold; background: #f8f9fa;">
                            <td>Total Abonado:</td>
                            <td colspan="3">
                                Q <?php echo number_format($factura['total'] - $factura['saldo_pendiente'], 2); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Sección de Firmas -->
            <div class="firma-seccion">
                <div class="firma-box">
                    <div class="firma-linea"></div>
                    <div class="firma-texto">Firma del Cliente</div>
                </div>
                <div class="firma-box">
                    <div class="firma-linea"></div>
                    <div class="firma-texto">Firma Autorizada</div>
                </div>
            </div>
        </div>
        
        <!-- <div class="footer-factura">
            <p><strong>¡Gracias por su compra!</strong></p>
            <p>Este documento es una representación impresa de la factura electrónica.</p>
            <p>Generado el <?php echo date('d/m/Y H:i:s'); ?></p>
        </div> -->
        
        <div class="botones-accion no-print">
            <button onclick="window.print()" class="btn-print">🖨️ Imprimir</button>
            <button onclick="window.close()" class="btn-close">❌ Cerrar</button>
        </div>
    </div>
    
    <?php if(isset($_GET['print']) && $_GET['print'] == 1): ?>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
    <?php endif; ?>
</body>
</html>