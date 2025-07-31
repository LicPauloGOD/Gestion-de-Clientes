<?php
// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Registrar errores
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/hosting_error.log');

// Función para enviar respuesta JSON
function sendJsonResponse($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Incluir conexión
include "conn.php";

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Verificar si es edición o creación
    $modo_edicion = isset($_POST['modo_edicion']) && $_POST['modo_edicion'] == '1';
    
    // Validar campos obligatorios
    if ($modo_edicion) {
        // En modo edición, verificar que al menos tenga cliente (original o nuevo)
        if (empty($_POST['cliente']) && empty($_POST['cliente_id'])) {
            throw new Exception('Se requiere un cliente válido');
        }
    } else {
        $required_fields = ['cliente'];
    }

    // Obtner datos del formulario
    // Manejar cliente_id dependiendo del modo
    if ($modo_edicion) {
        // En modo edición, verificar si se está cambiando el cliente
        if (isset($_POST['cliente']) && !empty($_POST['cliente'])) {
            // Se está cambiando el cliente (checkbox marcado)
            $cliente_id = (int)$_POST['cliente'];
        } else {
            // No se está cambiando el cliente (usar el original)
            $cliente_id = (int)$_POST['cliente_id'];
        }
    } else {
        // En modo normal, usar el campo cliente
        $cliente_id = (int)$_POST['cliente'];
    }
    
    // Validar que el cliente_id sea válido
    if (!$cliente_id || $cliente_id <= 0) {
        throw new Exception('ID de cliente inválido');
    }
    
$dominio = $_POST['dominio'] ?? '';
$producto = isset($_POST['producto']) ? (int)$_POST['producto'] : 0;
$id_forma_pago = isset($_POST['id_forma_pago']) ? (int)$_POST['id_forma_pago'] : 0;    
$nom_host = 'cpanel.' . str_replace('cpanel.', '', $_POST['nom_host']);
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];
    $contrasena_segura =($contrasena); 
    $tipo_producto = $_POST['tipo_producto'];
    $costo_producto = (float)$_POST['costo_producto'];
    $fecha_contratacion = $_POST['fecha_contratacion'];
    $fecha_pago = $_POST['fecha_pago'];

        $ns1= $_POST['ns1'];
        $ns2= $_POST['ns2'];
        $ns3= $_POST['ns3'];
        $ns4= $_POST['ns4'];
        $ns5= $_POST['ns5'];
        $ns6= $_POST['ns6'];

    $url_pago = '';
    $url_acceso = '';
    $dns = '';
    $estado_producto = 1;
    
    if ($modo_edicion) {
        // Modo edición - Actualizar registro
        $id_orden = (int)$_POST['id_orden'];
        
        $sql = "UPDATE hosting SET 
                dominio = ?,
                nom_host = ?,
                usuario = ?,
                contrasena_normal = ?,
                contrasena = ?,
                tipo_producto = ?,
                producto = ?,
                costo_producto = ?,
                id_forma_pago = ?,
                fecha_contratacion = ?,
                fecha_pago = ?,
                cliente_id = ?,
                ns1 = ?,
                 ns2 = ?,
                  ns3 = ?,
                   ns4 = ?,
                    ns5 = ?,
                     ns6 = ?
                WHERE id_orden = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssidisssssssssi",
            $dominio,
            $nom_host,
            $usuario,
            $contrasena,
            $contrasena,
            $tipo_producto,
            $producto,
            $costo_producto,
            $id_forma_pago,
            $fecha_contratacion,
            $fecha_pago,
            // $IVA,
            $cliente_id,
            $ns1,
            $ns2,
            $ns3,
            $ns4,
            $ns5,
            $ns6,
            $id_orden
        );
        
        $action = 'actualizado';
        $redirect = 'detalle_cliente.php?id=' . $cliente_id;
    } else {
        // Modo creación - Insertar nuevo registro      
          $sql = "INSERT INTO hosting (
                dominio, nom_host, usuario,contrasena_normal, contrasena, tipo_producto, producto,
                costo_producto, id_forma_pago, dns, url_pago, url_acceso,
                ns1, ns2, ns3, ns4, ns5, ns6, fecha_contratacion, fecha_pago,
                estado_producto, cliente_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssisdissssssssssii",
            $dominio,
            $nom_host,
            $usuario,
            $contrasena,
            $contrasena,
            $tipo_producto,
            $producto,
            $costo_producto,
            $id_forma_pago,
            $dns,
            $url_pago,
            $url_acceso,
            $ns1,
            $ns2,
            $ns3,
            $ns4,
            $ns5,
            $ns6,
            $fecha_contratacion,
            $fecha_pago,
            $estado_producto,
            $cliente_id
        );
        
        $action = 'creado';
        $redirect = 'formulario_hosting.php';
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Error al guardar en la base de datos: ' . $stmt->error);
    }
    
    // Respuesta exitosa
    sendJsonResponse([
        'success' => true,
        'message' => 'Hosting ' . $action . ' correctamente',
        'redirect' => $redirect
    ]);
    
} catch (Exception $e) {
    // Respuesta de error
    sendJsonResponse([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>