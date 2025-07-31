<?php
include "conn.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require "PHPMailer/src/Exception.php";
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";

$pago_id = $_POST['pago_id'];
$is_resend = isset($_POST['resend']) ? $_POST['resend'] : 0;

function enviarCorreoPago($correo_destino, $asunto, $datos_pago, $session_url) {
    $monto_formateado = number_format($datos_pago['monto'], 2);
    $moneda = $datos_pago['currency'];
    $concepto = $datos_pago['concepto'];
    $fecha_limite_pago = $datos_pago['fecha_limite_pago'];
    
    $titulo = "Recordatorio de Pago Pendiente - $concepto";
    $cuerpo = "
    <p>🔔 Estimado cliente,</p>
    <p>Queremos recordarte que tienes un pago pendiente por realizar correspondiente al proyecto que estamos desarrollando contigo. Agradecemos tu pronta atención para evitar cualquier retraso en la continuidad de los servicios.</p>

    <p><strong>🔍 Detalles del Pago Pendiente</strong></p>
    <ul>
        <li><strong>Concepto:</strong> $concepto</li>
        <li><strong>Monto:</strong> $moneda $monto_formateado</li>
        <li><strong>Fecha límite sugerida:</strong> $fecha_limite_pago</li>
    </ul>

    <p><strong>Formas de Pago:</strong></p>
    <p>✅ <strong>Pago con tarjeta (Stripe)</strong></p>
    <p>Puedes completar tu pago de forma rápida y segura a través de nuestra pasarela de pagos. Haz clic en el botón a continuación:</p>

    <div style='text-align: center; margin: 30px 0;'>
        <a href='$session_url' style='background-color: #6772E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;'>
           Realizar Pago
        </a>
    </div>

    <p>Si el botón anterior no funciona, copia y pega el siguiente enlace en tu navegador:<br>
    <small>$session_url</small></p>

    <p><strong>🏦 Transferencia bancaria</strong></p>
    <ul>
        <li><strong>Nombre del titular:</strong> Jose Antonio Martinez Karam</li>
        <li><strong>Banco:</strong> Santander</li>
        <li><strong>Número de cuenta:</strong> 60622161632</li>
        <li><strong>CLABE interbancaria:</strong> 014225606221616325</li>
        <li><strong>Referencia sugerida:</strong> $concepto – [Tu nombre o empresa]</li>
    </ul>

    <hr style='margin: 30px 0;'>

    <p><strong>📲 Confirmación de Pago:</strong></p>
    <p>Una vez realizado el pago, por favor envía el comprobante vía WhatsApp al número 477 118 1285 para confirmar la renovación y validar tu transacción.</p>
    
    <p>Si ya realizaste el pago, por favor omite este mensaje. Quedamos atentos a cualquier duda o comentario.</p>";

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

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->Host = "smtp.gmail.com";
        $mail->Username = $correoRemitente;
        $mail->Password = "bwctvomkzretakmu";

        $mail->setFrom($correoRemitente, $nombreRemitente);
        $mail->addAddress($correo_destino);
        $mail->Subject = $asunto;
        $mail->isHTML(true);
        $mail->Body = $mensaje;
        $mail->CharSet = 'UTF-8';

        $mail->send();
        return ['success' => true, 'message' => 'Correo enviado correctamente'];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Error al enviar el correo: ' . $e->getMessage(),
            'debug' => $mail->ErrorInfo
        ];
    }
}

if ($is_resend) {
    $sql = "SELECT p.monto, p.currency, p.concepto, c.correo, c.nombre_contacto
            FROM pagos p
            JOIN clientes c ON p.id_clie = c.id
            WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pago_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment_data = $result->fetch_assoc();

    if (!$payment_data) {
        echo json_encode(['success' => false, 'error' => 'Pago no encontrado']);
        exit;
    }

    $data = [
        'id' => $pago_id,
        'nombre' => $payment_data['nombre_contacto'],
        'costo' => $payment_data['monto'],
        'moneda' => $payment_data['currency'],
        'concepto' => $payment_data['concepto'],
        'correo' => $payment_data['correo']
    ];

    $ch = curl_init('https://adm.conlineweb.com/procesar_pago.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Error en la conexión con el servidor de pagos',
            'curl_error' => curl_error($ch)
        ]);
        curl_close($ch);
        exit;
    }

    curl_close($ch);
    $payment_result = json_decode($response, true);
    $session_url = $payment_result['session_url'];
    $session_id = $payment_result['session_id'];

    $update_sql = "UPDATE pagos SET session_id = ?, id_pago = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssi", $session_id, $session_id, $pago_id);
    $update_stmt->execute();
}

$sql_datos = "SELECT p.monto, p.currency, p.concepto, c.correo, p.fecha_limite_pago 
              FROM pagos p
              JOIN clientes c ON p.id_clie = c.id
              WHERE p.id = ?";
$stmt_datos = $conn->prepare($sql_datos);
$stmt_datos->bind_param("i", $pago_id);
$stmt_datos->execute();
$result = $stmt_datos->get_result();
$datos_pago = $result->fetch_assoc();

if (!$datos_pago) {
    echo json_encode(['success' => false, 'error' => 'Datos de pago no encontrados']);
    exit;
}

$resultado_correo = enviarCorreoPago(
    $datos_pago['correo'],
    "Pago Pendiente - " . $datos_pago['concepto'],
    $datos_pago,
    $session_url
);

echo json_encode([
    'success' => true,
    'email_result' => $resultado_correo,
    'session_url' => $session_url,
    'session_id' => $session_id
]);
$conn->close();
