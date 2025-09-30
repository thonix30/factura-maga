<?php
session_start();
include("conexion.php");
include("funciones.php");

verificarSesion();

$idfactura = isset($_GET['idfactura']) ? intval($_GET['idfactura']) : 0;

if($idfactura > 0) {
    // Obtener información de la factura
    $sql_factura = "SELECT * FROM facturas WHERE idfactura = ?";
    $stmt = $conn->prepare($sql_factura);
    $stmt->bind_param("i", $idfactura);
    $stmt->execute();
    $factura = $stmt->get_result()->fetch_assoc();
    
    if($factura) {
        echo "<div class='info-factura-historial'>";
        echo "<h3>Factura #" . str_pad($idfactura, 6, '0', STR_PAD_LEFT) . "</h3>";
        echo "<p><strong>Cliente:</strong> " . $factura['nombre'] . " " . $factura['apellido'] . "</p>";
        echo "<p><strong>Total:</strong> Q" . number_format($factura['total'], 2) . "</p>";
        echo "<p><strong>Saldo Pendiente:</strong> Q" . number_format($factura['saldo_pendiente'], 2) . "</p>";
        echo "</div>";
        
        // Obtener abonos
        $sql_abonos = "SELECT * FROM abonos WHERE idfactura = ? ORDER BY fecha_abono DESC";
        $stmt_abonos = $conn->prepare($sql_abonos);
        $stmt_abonos->bind_param("i", $idfactura);
        $stmt_abonos->execute();
        $resultado_abonos = $stmt_abonos->get_result();
        
        if($resultado_abonos->num_rows > 0) {
            echo "<div class='tabla-responsive'>";
            echo "<table class='tabla-abonos'>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>Fecha</th>";
            echo "<th>Monto</th>";
            echo "<th>Usuario</th>";
            echo "<th>Observaciones</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            
            $total_abonado = 0;
            while($abono = $resultado_abonos->fetch_assoc()) {
                $total_abonado += $abono['monto_abono'];
                echo "<tr>";
                echo "<td>" . date('d/m/Y H:i', strtotime($abono['fecha_abono'])) . "</td>";
                echo "<td><strong>Q" . number_format($abono['monto_abono'], 2) . "</strong></td>";
                echo "<td>" . $abono['usuario_registro'] . "</td>";
                echo "<td>" . ($abono['observaciones'] ? $abono['observaciones'] : '-') . "</td>";
                echo "</tr>";
            }
            
            echo "</tbody>";
            echo "<tfoot>";
            echo "<tr class='total-row'>";
            echo "<td colspan='1'><strong>Total Abonado:</strong></td>";
            echo "<td colspan='3'><strong>Q" . number_format($total_abonado, 2) . "</strong></td>";
            echo "</tr>";
            echo "</tfoot>";
            echo "</table>";
            echo "</div>";
        } else {
            echo "<p class='no-data'>No hay abonos registrados para esta factura.</p>";
        }
    } else {
        echo "<p class='error-message'>Factura no encontrada</p>";
    }
} else {
    echo "<p class='error-message'>ID de factura inválido</p>";
}
?>