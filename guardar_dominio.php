<?php
// 🔧 Configuración estricta de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// 🔐 Función para enviar respuesta JSON limpia
function sendJsonResponse($data) {
    // Limpiar cualquier salida previa
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 🔍 Función para logging personalizado
function logError($message, $data = null) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $logMessage .= " - Data: " . print_r($data, true);
    }
    error_log($logMessage . "\n", 3, __DIR__ . '/dominio_debug.log');
}

// 🛡️ Manejo de errores fatal
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logError("Fatal Error", $error);
        sendJsonResponse([
            'success' => false,
            'message' => 'Error interno del servidor. Revisa los logs.'
        ]);
    }
});

ob_start(); // Iniciar buffer

try {
    // 🔗 Incluir conexión con manejo de errores
    if (!file_exists('conn.php')) {
        throw new Exception('Archivo de conexión no encontrado');
    }
    
    include 'conn.php';
    
    // Verificar conexión
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos: ' . ($conn->connect_error ?? 'Conexión no establecida'));
    }
    
    logError("Inicio de proceso - Método: " . $_SERVER["REQUEST_METHOD"]);
    
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception('Método no permitido. Solo se acepta POST.');
    }
    
    // 🔍 Log de datos recibidos
    logError("Datos POST recibidos", $_POST);
    
    // Verificar si es modo edición
    $modo_edicion = isset($_POST['modo_edicion']) && $_POST['modo_edicion'] == '1';
    $id_dominio = $modo_edicion ? intval($_POST['id_dominio']) : null;
    
    // 📋 Validar campos obligatorios básicos
    if ($modo_edicion) {
        // En modo edición, verificar que al menos tenga cliente (original o nuevo)
        if (empty($_POST['cliente']) && empty($_POST['cliente_id'])) {
            throw new Exception('Se requiere un cliente válido');
        }
        $required_fields = [];
    } else {
        $required_fields = ['cliente', 'proveedor', 'url_dominio', 'url_admin', 'costo_dominio', 'id_forma_pago', 'fecha_contratacion'];
    }
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    if (!$modo_edicion && !isset($_POST['registrado'])) {
        $missing_fields[] = 'registrado';
    }
    if (!empty($missing_fields)) {
        logError("Campos faltantes", $missing_fields);
        throw new Exception('Faltan campos obligatorios: ' . implode(', ', $missing_fields));
    }
    
    // 🧹 Sanitizar y validar datos
    // Manejar cliente_id dependiendo del modo
    if ($modo_edicion) {
        // En modo edición, verificar si se está cambiando el cliente
        if (isset($_POST['cliente']) && !empty($_POST['cliente'])) {
            // Se está cambiando el cliente (checkbox marcado)
            $cliente_id = filter_var($_POST['cliente'], FILTER_VALIDATE_INT);
        } else {
            // No se está cambiando el cliente (usar el original)
            $cliente_id = filter_var($_POST['cliente_id'], FILTER_VALIDATE_INT);
        }
    } else {
        // En modo normal, usar el campo cliente
        $cliente_id = filter_var($_POST['cliente'], FILTER_VALIDATE_INT);
    }
    
    if ($cliente_id === false) {
        throw new Exception('ID de cliente inválido');
    }

    if (!$modo_edicion || ($modo_edicion && isset($_POST['costo_dominio']) && $_POST['costo_dominio'] !== '')) {
        $costo_dominio = filter_var($_POST['costo_dominio'], FILTER_VALIDATE_FLOAT);
        if ($costo_dominio === false) {
            throw new Exception('Costo de dominio inválido');
        }
    } else {
        $costo_dominio = null;
    }
    
    $id_forma_pago = filter_var($_POST['id_forma_pago'], FILTER_VALIDATE_INT);
    if ($id_forma_pago === false) {
        throw new Exception('Forma de pago inválida');
    }
    
    $registrado = filter_var($_POST['registrado'], FILTER_VALIDATE_INT);
    if ($registrado === false || !in_array($registrado, [0, 1])) {
        throw new Exception('Estado de registro inválido');
    }
    
    // Asignar variables con sanitización
    $proveedor = trim($_POST['proveedor']);
    $url_dominio = trim($_POST['url_dominio']);
    $url_admin = trim($_POST['url_admin']);
    
    // Validar fecha de contratación
    $fecha_contratacion = $_POST['fecha_contratacion'];
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_contratacion)) {
        throw new Exception('Formato de fecha de contratación inválido');
    }
    
    // Usar fecha_pago del formulario si existe, sino calcular
    if (!empty($_POST['fecha_pago'])) {
        $fecha_pago = $_POST['fecha_pago'];
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_pago)) {
            throw new Exception('Formato de fecha de pago inválido');
        }
    } else {
        // Calcular fecha de pago automáticamente (1 año después)
        $fecha_contratacion_dt = new DateTime($fecha_contratacion);
        $fecha_pago_dt = clone $fecha_contratacion_dt;
        $fecha_pago_dt->modify('+1 year');
        $fecha_pago = $fecha_pago_dt->format('Y-m-d');
    }

    // Variables opcionales - pueden ser NULL
    $usuario_admin = isset($_POST['usuario_admin']) ? trim($_POST['usuario_admin']) : '';
    $contrasena_admin = isset($_POST['contrasena_admin']) ? trim($_POST['contrasena_admin']) : '';
    $url_cpanel = isset($_POST['url_cpanel']) ? trim($_POST['url_cpanel']) : '';
    $ns1 = isset($_POST['ns1']) ? trim($_POST['ns1']) : '';
    $ns2 = isset($_POST['ns2']) ? trim($_POST['ns2']) : '';
    $ns3 = isset($_POST['ns3']) ? trim($_POST['ns3']) : '';
    $ns4 = isset($_POST['ns4']) ? trim($_POST['ns4']) : '';
    $ns5 = isset($_POST['ns5']) ? trim($_POST['ns5']) : '';
    $ns6 = isset($_POST['ns6']) ? trim($_POST['ns6']) : '';
    
    // Estado del dominio por defecto
    $estado_dominio = 1; // 1 = Activo
    
    // 🔍 Log de datos procesados
    logError("Datos procesados correctamente", [
        'modo_edicion' => $modo_edicion,
        'id_dominio' => $id_dominio,
        'cliente_id' => $cliente_id,
        'proveedor' => $proveedor,
        'costo' => $costo_dominio
    ]);
    
    if ($modo_edicion && $id_dominio) {
        // 💾 Modo EDICIÓN - Actualizar dominio existente
        $sql = "UPDATE dominios SET 
                cliente_id = ?,
                proveedor = ?,
                url_dominio = ?,
                url_admin = ?,
                usuario = ?,
                contrasena = ?,
                contrasena_normal = ?,
                url_cpanel = ?,
                ns1 = ?,
                ns2 = ?,
                ns3 = ?,
                ns4 = ?,
                ns5 = ?,
                ns6 = ?,
                costo_dominio = ?,
                id_forma_pago = ?,
                fecha_contratacion = ?,
                fecha_pago = ?,
                estado_dominio = ?,
                registrado = ?
                WHERE id_dominio = ?";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Error al preparar consulta de actualización: ' . $conn->error);
        }
        
        $bind_result = $stmt->bind_param(
            "isssssssssssssdissiii",
            $cliente_id, $proveedor, $url_dominio, $url_admin, 
            $usuario_admin, $contrasena_admin, $contrasena_admin, 
            $url_cpanel, $ns1, $ns2, $ns3, $ns4, $ns5, $ns6,
            $costo_dominio, $id_forma_pago, $fecha_contratacion, 
            $fecha_pago, $estado_dominio, $registrado, $id_dominio
        );
        
        $action = 'actualizado';
    } else {
        // 💾 Modo CREACIÓN - Insertar nuevo dominio
        $sql = "INSERT INTO dominios (
                cliente_id, proveedor, url_dominio, url_admin, usuario, 
                contrasena, contrasena_normal, url_cpanel, ns1, ns2, ns3, ns4, ns5, ns6, 
                costo_dominio, id_forma_pago, fecha_contratacion, fecha_pago, estado_dominio, registrado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Error al preparar consulta de inserción: ' . $conn->error);
        }
        
        $bind_result = $stmt->bind_param(
            "isssssssssssssdissii",
            $cliente_id, $proveedor, $url_dominio, $url_admin, 
            $usuario_admin, $contrasena_admin, $contrasena_admin, 
            $url_cpanel, $ns1, $ns2, $ns3, $ns4, $ns5, $ns6,
            $costo_dominio, $id_forma_pago, $fecha_contratacion, 
            $fecha_pago, $estado_dominio, $registrado
        );
        
        $action = 'registrado';
    }
    
    if (!$bind_result) {
        throw new Exception('Error al vincular parámetros: ' . $stmt->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Error al ejecutar consulta: ' . $stmt->error);
    }
    
    if ($modo_edicion) {
        $affected_rows = $stmt->affected_rows;
        logError("Dominio actualizado exitosamente", [
            'id_dominio' => $id_dominio,
            'affected_rows' => $affected_rows
        ]);
        $domain_id = $id_dominio;
    } else {
        $insert_id = $conn->insert_id;
        logError("Dominio registrado exitosamente", ['insert_id' => $insert_id]);
        $domain_id = $insert_id;
    }
    
    $stmt->close();
    
    // ✅ Respuesta exitosa
    sendJsonResponse([
        'success' => true,
        'message' => 'El dominio se ha ' . $action . ' correctamente.',
        'action' => $action,
        'domain_id' => $domain_id,
        'redirect' => $modo_edicion ? 'detalle_cliente.php?id=' . $cliente_id : 'formulario_dominio.php'
    ]);
    
} catch (Exception $e) {
    logError("Error capturado: " . $e->getMessage());
    
    // 🚨 Respuesta de error
    sendJsonResponse([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $modo_edicion ? 'DOMAIN_UPDATE_ERROR' : 'DOMAIN_REGISTRATION_ERROR'
    ]);
} finally {
    if (isset($conn)) $conn->close();
}
?>