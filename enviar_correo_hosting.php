<?php
ob_start();
header('Content-Type: application/json');
include "conn.php";


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require "./PHPMailer/src/Exception.php";
require "./PHPMailer/src/PHPMailer.php";
require "./PHPMailer/src/SMTP.php";



// Obtener el ID del cliente desde GET
$idCliente = isset($_GET["id"]) && $_GET["id"] !== '' ? intval($_GET["id"]) : null;

if (!$idCliente) {
    echo json_encode(["success" => false, "message" => "ID de cliente no proporcionado"]);
    exit();
}

// Obtener datos del cliente
try {
    $sqlCliente = "SELECT * FROM clientes WHERE id = ?";
    $stmt = $conn->prepare($sqlCliente);
    $stmt->bind_param("i", $idCliente);
    $stmt->execute();
    $resultCliente = $stmt->get_result();
    $cliente = $resultCliente->fetch_assoc();

    if (!$cliente) {
        echo json_encode(["success" => false, "message" => "Cliente no encontrado"]);
        exit();
    }

    // Obtener dominio asociado
    $sqlDominio = "SELECT * FROM hosting WHERE cliente_id = ?";
    $stmt = $conn->prepare($sqlDominio);
    $stmt->bind_param("i", $idCliente);
    $stmt->execute();
    $resultDominio = $stmt->get_result();
    $dominio = $resultDominio->fetch_assoc();

    if (!$dominio) {
        echo json_encode(["success" => false, "message" => "Dominio no encontrado para el cliente"]);
        exit();
    }

    // Preparar datos
    $correo_destino = $cliente["correo"] ?? '';
    if (empty($correo_destino)) {
        echo json_encode(["success" => false, "message" => "No hay dirección de correo para el cliente"]);
        exit();
    }

    $nombre_cliente = $cliente["nombre_contacto"] ?? 'Cliente';
    $url_dominio = $dominio["url_dominio"] ?? 'dominio no especificado';
    $fecha_pago = (!empty($dominio["fecha_pago"]) && $dominio["fecha_pago"] !== '0000-00-00') 
        ? date("d M Y", strtotime($dominio["fecha_pago"])) 
        : "sin fecha registrada";
        
        
        
        
    // Contenido del correo
    $asunto = "Recordatorio de renovacion de dominio: $url_dominio";
    $titulo = "Renovación de dominio";
// Aquí defines el botón justo antes de construir el cuerpo:
$boton_pago = "<p style='text-align:center; margin: 15px 0;'>
  <a href='$urlStripe' style='display: inline-block; background-color: #27ae60; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
    Pagar renovación ahora
  </a>
</p>";

// Construyes el cuerpo incluyendo el botón, sin imprimirlo después:
$cuerpo = "Estimado/a $nombre_cliente,<br><br>
Le recordamos que el dominio <strong>$url_dominio</strong> tiene como fecha de renovación el <strong>$fecha_pago</strong>.<br><br>
Por favor, considere renovarlo a tiempo para evitar interrupciones en su servicio.<br><br>



<h4>💳 Opciones de pago:</h4>
<p><strong>Transferencia bancaria</strong></p>
<ul>
  <li><strong>Nombre del titular:</strong> Jose Antonio Martinez Karam</li>
  <li><strong>Número de cuenta:</strong> 60622161632 (Santander)</li>
  <li><strong>CLABE:</strong> 014225606221616325 (Santander)</li>
  $boton_pago
</ul>
<hr>
<p>Una vez realizado el pago, por favor envía el comprobante vía WhatsApp al número <strong>477 118 1285</strong> para confirmar la renovación.</p>
<p>Si tienes alguna pregunta o necesitas asistencia, no dudes en contactarnos. Estaremos encantados de ayudarte.</p>";
    $despedida = "Gracias por su atención.<br>Atentamente, <br><strong>Equipo de soporte técnico</strong>";
    
    
    
    // Preparar datos del cliente y dominio
$correo_destino = $cliente["correo"] ?? '';
if (empty($correo_destino)) {
    echo json_encode(["success" => false, "message" => "No hay dirección de correo para el cliente"]);
    exit();
}

$monedasMap = [
  1 => 'mxn',
  2 => 'usd'
];


$nombre_cliente = $cliente["nombre_contacto"] ?? 'Cliente';
$url_dominio = $dominio["url_dominio"] ?? 'dominio no especificado';
$fecha_pago = (!empty($dominio["fecha_pago"]) && $dominio["fecha_pago"] !== '0000-00-00') 
    ? date("d M Y", strtotime($dominio["fecha_pago"])) 
    : "sin fecha registrada";
$costoDominio = isset($dominio["costo_dominio"]) ? $dominio["costo_dominio"] : 1200;
$idFormaPago = isset($dominio["id_forma_pago"]) ? intval($dominio["id_forma_pago"]) : 1;
$codigoMoneda = $monedasMap[$idFormaPago] ?? 'mxn';  // default a 'mxn' si no existe





// ✅ Preparar datos para la solicitud de pago (usa los nombres requeridos)
$data = [
    "id" => $idCliente,
    "nombre" => $nombre_cliente,
    "correo" => $correo_destino,
    "moneda" => $codigoMoneda,
    "costo" => $costoDominio,
    "concepto" => "Renovación de dominio: $url_dominio"
];

$ch = curl_init('https://adm.conlineweb.com/procesar_pago.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
curl_close($ch);

$payment_result = json_decode($response, true);

if (!isset($payment_result['success']) || !$payment_result['success']) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al crear la sesión de pago con Stripe',
        'error' => $payment_result['error'] ?? 'Error desconocido',
        'raw_response' => $response
    ]);
    exit;
}

// ✅ Ahora que ya existe, puedes usar $urlStripe
$urlStripe = $payment_result['session_url'];
$boton_pago = "<p style='text-align:center; margin: 15px 0;'>
  <a href='$urlStripe' style='display: inline-block; background-color: #27ae60; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
    Pagar renovación ahora - $ $costoDominio $codigoMoneda
  </a>
</p>";






    // Configuración de PHPMailer con el estilo del código que funciona
    $mail = new PHPMailer(true);
     $mensaje = "
        <html>
        <head>
          <title>" . htmlspecialchars($asunto) . "</title>
          <style>
            body {
              margin: 0;
              padding: 0;
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
              font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
              min-height: 100vh;
            }
            .bg {
              width: 100%;
              padding: 60px 20px;
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .container {
              width: 90%;
              max-width: 650px;
              margin: 0 auto;
              background: rgba(255, 255, 255, 0.95);
              backdrop-filter: blur(10px);
              border-radius: 20px;
              box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
              overflow: hidden;
              border: 1px solid rgba(255, 255, 255, 0.2);
            }
            .header {
              background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
              color: #ffffff;
              padding: 40px 40px 30px 40px;
              text-align: center;
              position: relative;
            }
            .header::before {
              content: '';
              position: absolute;
              top: 0;
              left: 0;
              right: 0;
              height: 4px;
              background: linear-gradient(90deg, #3498db, #e74c3c, #f39c12, #2ecc71);
            }
            .header h1 {
              margin: 0;
              font-size: 28px;
              font-weight: 300;
              letter-spacing: 1px;
              text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            }
            .card-container {
              padding: 50px 40px;
              background: #ffffff;
              position: relative;
            }
            .card-container::before {
              content: '';
              position: absolute;
              top: 0;
              left: 50%;
              transform: translateX(-50%);
              width: 60px;
              height: 4px;
              background: linear-gradient(90deg, #667eea, #764ba2);
              border-radius: 2px;
            }
            p {
              margin: 20px 0;
              font-size: 16px;
              line-height: 1.8;
              color: #2c3e50;
              text-align: justify;
            }
            .greeting {
              font-size: 18px;
              color: #34495e;
              margin-bottom: 25px;
            }
            .signature {
              margin-top: 40px;
              padding-top: 25px;
              border-top: 2px solid #ecf0f1;
              font-style: italic;
              color: #7f8c8d;
            }
            .footer {
              padding: 30px 40px;
              background: linear-gradient(135deg, #bdc3c7 0%, #95a5a6 100%);
              font-size: 13px;
              color: #2c3e50;
              text-align: center;
              font-weight: 500;
              letter-spacing: 0.5px;
            }
            .decorative-line {
              width: 100px;
              height: 2px;
              background: linear-gradient(90deg, #667eea, #764ba2);
              margin: 30px auto;
              border-radius: 1px;
            }
            
            .banner {
              width: 100%;
              max-height: 150px;
              /*background-color: #f5f5f5;*/
              padding: 30px;
              display: flex;
              justify-content: center;
              align-items: center;
            }
            
            /* Responsive */
            @media (max-width: 600px) {
              .bg {
                padding: 30px 15px;
              }
              .container {
                width: 95%;
              }
              .header, .card-container, .footer {
                padding-left: 25px;
                padding-right: 25px;
              }
              .header h1 {
                font-size: 24px;
              }
              p {
                font-size: 15px;
              }
            }
          </style>
        </head>
        <body>
          <div class='bg'>
            <div class='container'>
              <div class='banner'>
                <img src='https://adm.conlineweb.com/images/logo.png' alt='Company Banner'>
              </div>
              <div class='header'>
                <h1>" . htmlspecialchars($titulo) . "</h1>
              </div>
              <div class='card-container'>
  <div class='greeting'>
    $cuerpo
  </div>
  <div class='decorative-line'></div>

  <div class='signature'>
    $despedida
  </div>
</div>

              <div class='footer'>
                Este mensaje ha sido enviado automáticamente. Por favor, no responda a este correo.
              </div>
            </div>
          </div>
        </body>
        </html>
        ";
    
    
     // Encabezados para enviar el correo como HTML
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";

    // Configuración SMTP (igual que en tu código funcional)
    $mail_enviado = false;
    $correoRemitente = "info@conlineweb.com";
    $nombreRemitente = "InfoConlineweb";
    
    try {
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'starttls'; // Cambiado de 'starttls' a 'tls'
        $mail->Port = 587;
        $mail->Host = "smtp.gmail.com";
        $mail->Username = $correoRemitente;
        $mail->Password = "bwctvomkzretakmu";

        $mail->setFrom($correoRemitente, $nombreRemitente);
        $mail->addAddress($correo_destino, $nombre_cliente);

        $mail->Subject = $asunto;
        $mail->isHTML(true);
        $mail->Body = $mensaje;

        $mail->send();
        ob_end_clean();
        echo json_encode(["success" => true, "message" => "✅ Correo enviado a $correo_destino"]);
        exit();
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(["success" => false, "message" => "❌ Error al enviar el correo: " . $mail->ErrorInfo]);
        exit();

    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "❌ Error en el proceso: " . $e->getMessage()]);
}