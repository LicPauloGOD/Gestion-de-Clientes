<?php
include "conn.php";
header('Content-Type: application/json');
ini_set('display_errors', 1);  // Mostrar errores en pantalla
ini_set('display_startup_errors', 1);  // Mostrar errores de inicio
error_reporting(E_ALL);  // Reportar todos los tipos de errores

$pago_id = $_POST['pago_id'] ?? null;
$session_id = $_POST['session_id'] ?? null;
$session_url = $_POST['session_url'] ?? null;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require "PHPMailer/src/Exception.php";
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";

// Primero, obtener los datos del pago y el correo del cliente
$sql_datos = "SELECT p.monto, p.currency, p.concepto, c.correo 
              FROM pagos p
              JOIN clientes c ON p.id_clie = c.id
              WHERE p.id = ?";

$stmt_datos = $conn->prepare($sql_datos);
$stmt_datos->bind_param("i", $pago_id);
$stmt_datos->execute();
$result = $stmt_datos->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Pago no encontrado']);
    exit;
}

$datos_pago = $result->fetch_assoc();
$stmt_datos->close();

// Función para enviar correo (actualizada para incluir botón de pago)
function enviarCorreoPago($correo_destino, $asunto, $datos_pago, $session_url) {
    $monto_formateado = number_format($datos_pago['monto'], 2);
    $moneda = $datos_pago['currency'];
    $concepto = $datos_pago['concepto'];
    
    $titulo = "Envío de Enlace de Pago Pendiente y Datos de la Cuenta";
 $cuerpo = "
    <p>Estimado cliente:
    Nos alegra trabajar contigo. A continuación, te compartimos el enlace de pago correspondiente al servicio, con los siguientes detalles:</p>
    <p><strong>Concepto:</strong> $concepto</p>
    <p><strong>Monto:</strong> $moneda $monto_formateado</p>
    <p>Para completar tu pago, haz clic en el siguiente botón:</p>

    <div style='text-align: center; margin: 30px 0;'>
        <a href='$session_url' style='background-color: #6772E5; color: white; 
           padding: 12px 24px; text-decoration: none; border-radius: 4px; 
           font-weight: bold; display: inline-block;'>
           Realizar Pago
        </a>
    </div>

    <p>Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
    <small>$session_url</small></p>

    <hr style='margin: 30px 0;'>

    <h4>Opciones de pago:</h4>
    <p><strong>Transferencia bancaria</strong></p>
    <ul>
        <li><strong>Nombre del titular:</strong> Jose Antonio Martinez Karam</li>
        <li><strong>Número de cuenta:</strong> 60622161632 (Santander)</li>
        <li><strong>CLABE:</strong> 014225606221616325 (Santander)</li>
    </ul>
";

    
    $despedida = "Atentamente,<br>El equipo de C-onlineWeb";

    $mensaje = "
    <html>
    <head>
      <title>$asunto</title>
      <link href='https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap' rel='stylesheet'>
      <style>
        body {
          margin: 0;
          padding: 0;
          font-family: 'Montserrat', sans-serif;
          background-color: #f1f1f1;
        }
        .container {
          width: 90%;
          max-width: 650px;
          margin: 40px auto;
        }
        .inner {
          background-color: #ffffff;
          padding: 40px;
          border-radius: 12px;
          box-shadow: 0 4px 12px rgba(0,0,0,0.15);
          border: 2px solid #ccc;
        }
        .banner {
          text-align: center;
          margin-bottom: 30px;
        }
        .banner img {
          max-width: 180px;
        }
        h1 {
          font-size: 24px;
          color: #2c3e50;
          text-align: center;
          margin-bottom: 20px;
        }
        p, li {
          font-size: 16px;
          color: #2c3e50;
          line-height: 1.6;
        }
        ul {
          padding-left: 20px;
        }
        .footer {
          margin-top: 40px;
          text-align: center;
          font-size: 13px;
          color: gray;
          font-style: italic;
        }
        @media (max-width: 600px) {
          .inner {
            padding: 20px;
          }
          h1 {
            font-size: 20px;
          }
          p, li {
            font-size: 14px;
          }
        }
      </style>
    </head>
    <body>
      <div class='container'>
        <div class='inner'>
          <div class='banner'>
            <img src='https://adm.conlineweb.com/images/logo.png' alt='C-onlineWeb'>
          </div>
          <h1>$titulo</h1>
          $cuerpo
          <p style='margin-top: 40px;'>$despedida</p>
          <div class='footer'>
            Este mensaje ha sido enviado automáticamente. Por favor, no responda a este correo.
          </div>
        </div>
      </div>
    </body>
    </html>";

    $correoRemitente = "info@conlineweb.com";
    $nombreRemitente = "C-onlineWeb";
    
    // Crear instancia de PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->SMTPDebug = 0; // Cambiado a 0 para producción
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->Host = "smtp.gmail.com";
        $mail->Username = $correoRemitente;
        $mail->Password = "bwctvomkzretakmu"; 

        // Configuración del correo
        $mail->setFrom($correoRemitente, $nombreRemitente);
        $mail->addAddress($correo_destino); 
        $mail->Subject = $asunto;
        $mail->isHTML(true);
        $mail->Body = $mensaje;
        $mail->CharSet = 'UTF-8';

        // Envío del correo
        $enviado = $mail->send();
        
        if (!$enviado) {
            return [
                'success' => false,
                'error' => 'El correo no se pudo enviar, pero no se generó una excepción',
                'debug' => $mail->ErrorInfo
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Correo enviado correctamente'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Excepción al enviar el correo: ' . $e->getMessage(),
            'debug' => $mail->ErrorInfo ?? 'No hay información adicional de depuración'
        ];
    }
}

// Actualizar pago con datos de Stripe
$sql = "UPDATE pagos SET 
    session_id = ?,
    id_pago = ?
    WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $session_id, $session_id, $pago_id);

if ($stmt->execute()) {
    // Enviar correo al cliente
    $resultado_correo = enviarCorreoPago(
        $datos_pago['correo'],
        "Pago Pendiente - " . $datos_pago['concepto'],
        $datos_pago,
        $session_url
    );
    $correo_status = $resultado_correo['success'] ? 'Correo enviado' : 'Correo NO enviado';
    $correo_error = $resultado_correo['success'] ? null : ($resultado_correo['error'] ?? '');
    echo json_encode([
        'success' => true,
        'message' => 'Pago actualizado. ' . $correo_status,
        'correo_error' => $correo_error
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>