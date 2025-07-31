<?php
include "conn.php";

header('Content-Type: application/json');

// Obtener datos del POST
$cliente_id = $_POST['cliente_id'];
$monto = $_POST['monto'];
$currency = $_POST['currency'];
$concepto = $_POST['concepto'];

// Calcular fecha límite (3 días después de hoy)
$fecha_limite = date('Y-m-d', strtotime('+3 days'));

// Insertar pago en la base de datos
$sql = "INSERT INTO pagos (
    id_clie, id_servicio, fecha, hora, 
    fecha_pago, hora_pago, monto, currency, 
    concepto, forma_pago, estatus, id_pago, 
    session_id, id_cuenta, tipo_servicio, fecha_limite_pago, manual
) VALUES (?, 0, CURDATE(), CURTIME(), 
    '0000-00-00', '00:00:00', ?, ?, 
    ?, 0, 0, '', 
    '', 0, 0, ?, 1)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("idsss", $cliente_id, $monto, $currency, $concepto, $fecha_limite);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Pago agregado correctamente',
        'pago_id' => $conn->insert_id
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar el pago: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>