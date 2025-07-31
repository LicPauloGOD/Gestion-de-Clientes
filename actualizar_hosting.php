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
    

    // Obtener datos del formulario
    $cliente_id = (int)$_POST['cliente'];
    $dominio= $_POST['dominio'];
    $nom_host = 'cpanel.' . str_replace('cpanel.', '', $_POST['nom_host']);
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];
    $contrasena_segura =($contrasena); 
    $tipo_producto = $_POST['tipo_producto'];
    $producto =(int)$_POST['producto'] ??0;
    $costo_producto = (float)$_POST['costo_producto'];
    $id_forma_pago = (int)$_POST['id_forma_pago'];
    $fecha_contratacion = $_POST['fecha_contratacion'];
    $fecha_pago = $_POST['fecha_pago'];

        $ns1= $_POST['ns1'];
        $ns2= $_POST['ns2'];
        $ns3= $_POST['ns3'];
        $ns4= $_POST['ns4'];
        $ns5= $_POST['ns5'];
        $ns6= $_POST['ns6'];

    // Validar fecha
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_contratacion)) {
        throw new Exception('Formato de fecha de contratación inválido');
    }
    
    // Calcular fecha de pago (1 año después)
    // $fecha_pago = date('Y-m-d', strtotime($fecha_contratacion . ' +1 year'));
    
  
    
    // Campos opcionales con valores por defecto
    $url_pago = '';
    $url_acceso = '';
    $dns = '';
    $estado_producto = 1;
    $IVA = 0; // O ajusta según tu lógica
    $eliminado = 0;
    
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
                ns6 = ?,
                dns = ?,
                url_pago = ?,
                url_acceso = ?,
                estado_producto = ?,
                IVA = ?,
                eliminado = ?
            WHERE id_orden = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssidissssssssssssiiii",
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
            $cliente_id,
            $ns1,
            $ns2,
            $ns3,
            $ns4,
            $ns5,
            $ns6,
            $dns,
            $url_pago,
            $url_acceso,
            $estado_producto,
            $IVA,
            $eliminado,
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
