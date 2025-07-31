<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: https://cliente.conlineweb.com");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// Configuración de logging
$logFile = 'stripe_payments.log';
$logMessage = function($message) use ($logFile) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
};

// Registrar inicio del proceso
$logMessage("Iniciando procesamiento de pago");

try {
    // Incluir la librería de Stripe
    require_once('stripe-php/init.php');

    // Registrar que la librería se cargó
    $logMessage("Librería Stripe cargada");

    // Validar datos de entrada obligatorios
    $requiredFields = ['id', 'nombre', 'moneda', 'costo', 'concepto', 'correo'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        $errorMsg = "Campos requeridos faltantes: " . implode(', ', $missingFields);
        $logMessage("ERROR: $errorMsg");
        
        echo json_encode([
            'success' => false,
            'error' => $errorMsg,
            'missing_fields' => $missingFields
        ]);
        exit;
    }

    // Validar que el costo sea un número válido
    if (!is_numeric($_POST['costo']) || $_POST['costo'] <= 0) {
        $errorMsg = "El costo debe ser un número positivo";
        $logMessage("ERROR: $errorMsg. Valor recibido: " . $_POST['costo']);
        
        echo json_encode([
            'success' => false,
            'error' => $errorMsg,
            'received_value' => $_POST['costo']
        ]);
        exit;
    }

    // Registrar datos recibidos (sin información sensible)
    $logMessage("Datos recibidos: " . json_encode([
        'id' => $_POST['id'],
        'nombre' => $_POST['nombre'],
        'moneda' => $_POST['moneda'],
        'costo' => $_POST['costo'],
        'concepto' => $_POST['concepto'],
        'correo' => $_POST['correo']
    ]));

    // Establecer tu clave secreta de Stripe
   // \Stripe\Stripe::setApiKey('sk_test_51JDaK3GHIyztsA2RQBa83FnkXiOPtY9o4OPbSbd0B9A2K847iXpp88bTvCuXekxNrcj4BiP2JzCmBqd1j5NTC2EL009H4CjAmo');
    \Stripe\Stripe::setApiKey('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');

    // Incluir conexión a la base de datos
    require_once('conn.php');

    // Verificar si la columna session_id existe en la tabla pagos
    $result = $conn->query("SHOW COLUMNS FROM pagos LIKE 'session_id'");
    if ($result->num_rows == 0) {
        // La columna no existe, la creamos
        $conn->query("ALTER TABLE pagos ADD COLUMN session_id VARCHAR(255) NULL");
        $logMessage("Columna session_id creada en la tabla pagos");
    }

    // Convertir monto a centavos
    $monto_centavos = round($_POST['costo'] * 100);
    $logMessage("Monto en centavos: $monto_centavos");

    // Crear la sesión de Checkout en Stripe
    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $_POST['moneda'],
                        'product_data' => [
                            'name' => $_POST['concepto'],
                        ],
                        'unit_amount' => $monto_centavos,
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'customer_email' => $_POST['correo'],
            'success_url' => 'https://adm.conlineweb.com/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'https://adm.conlineweb.com/payment_cancel.php',
        ]);

        $logMessage("Sesión de Stripe creada exitosamente. Session ID: " . $session->id);

        // Actualizar el registro de pago con el session_id
        $stmt = $conn->prepare("UPDATE pagos SET session_id = ? WHERE id = ?");
        $stmt->bind_param("si", $session->id, $_POST['id']);
        
        if ($stmt->execute()) {
            $logMessage("Session ID guardado en la base de datos para el pago ID: " . $_POST['id']);
        } else {
            $logMessage("ERROR: No se pudo guardar el session_id en la base de datos: " . $conn->error);
            throw new Exception("Error al guardar session_id en la base de datos");
        }
        
        // Enviar la URL de la sesión de pago a la respuesta
        echo json_encode([
            'success' => true,
            'session_url' => $session->url,
            'session_id' => $session->id,
        ]);
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $errorMsg = "Error al crear sesión de Stripe: " . $e->getMessage();
        $logMessage("ERROR: $errorMsg");
        
        echo json_encode([
            'success' => false,
            'error' => $errorMsg,
            'error_type' => get_class($e),
            'stripe_error' => $e->getJsonBody()['error'] ?? null
        ]);
    }

} catch (Exception $e) {
    $errorMsg = "Error inesperado: " . $e->getMessage();
    $logMessage("ERROR GRAVE: $errorMsg");
    
    echo json_encode([
        'success' => false,
        'error' => $errorMsg,
        'error_type' => get_class($e),
        'trace' => $e->getTraceAsString()
    ]);
}
?>