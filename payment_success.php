<?php
// 1. Configuración inicial y manejo de errores
require_once('stripe-php/init.php');
header('Content-Type: text/html; charset=UTF-8');
date_default_timezone_set('America/Mexico_City');
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: https://cliente.conlineweb.com");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/conn.php';

if (!isset($conn) || !$conn instanceof mysqli) {
    throw new Exception("No se pudo establecer conexión con la base de datos.");
}

while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Incluir PHPMailer
require "PHPMailer/src/Exception.php";
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";


// 3. Configuración de Stripe
Stripe\Stripe::setApiKey('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');
//Stripe\Stripe::setApiKey('sk_test_51JDaK3GHIyztsA2RQBa83FnkXiOPtY9o4OPbSbd0B9A2K847iXpp88bTvCuXekxNrcj4BiP2JzCmBqd1j5NTC2EL009H4CjAmo');

function sendEmail($to, $subject, $title, $body, $signature) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    // Configuración del remitente
    $correoRemitente = "info@conlineweb.com";
    $nombreRemitente = "Conlineweb";

    try {
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = "tls";
        $mail->Port = 587;
        $mail->Host = "smtp.gmail.com";
        $mail->Username = $correoRemitente;
        $mail->Password = "bwctvomkzretakmu";
        $mail->setFrom($correoRemitente, $nombreRemitente);
        $mail->addAddress($to);  


        $mail->CharSet = "UTF-8";
        $mail->Encoding = "base64";
        $mail->isHTML(true);
        $mail->Subject = $subject;

        // Opcional: activación de depuración para detectar problemas de SMTP
        /*
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP Debug ($level): $str");
        };
        */

        $htmlTemplate = "
        <html>
    <head>
      <title>$subject</title>
      <link href='https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap' rel='stylesheet'>
      <style>
        body {
          margin: 0;
          padding: 0;
          font-family: 'Montserrat', sans-serif;
          min-height: 100vh;
        }
        
        .container {
          background-color: #ECECEC;
          width: 90%;
          max-width: 650px;
          margin: 0 auto;
          overflow: hidden;
          padding: 20px; 
          border-radius: 12px; 
        }
        
        .banner {
  width: 100%;
  background-color: #f5f5f5;
  padding: 20px 0; /* solo arriba y abajo */
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 12px 12px 0 0; /* opcional: para empatar con .container */
}
        
        .banner img {
          max-height: 50%;
          max-width: 30%;
          object-fit: cover;
        }
        
        .header {
          padding: 40px 0px;
          text-align: center;
          position: relative;
        }
       
        .header h1 {
          margin: 0;
          font-size: 28px;
          font-weight: bold;
          text-align: left;
        }

        .card-container {
          position: relative;
        }
        
        p {
          margin: 20px 0;
          font-size: 16px;
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
          color: #7f8c8d;
        }
        
        .footer {
          padding: 30px 40px;
          font-size: 13px;
          color: gray;
          text-align: center;
          font-style: italic;
          letter-spacing: 0.5px;
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
            <h1>$title</h1>
          </div>
          <div class='card-container'>
            <div class='greeting'>
              $body
            </div>
            <div class='signature'>
              <p>$signature</p>
            </div>
          </div>
          <div class='footer'>
            Este mensaje ha sido enviado automaticamente. Por favor, no responda a este correo.
          </div>
        </div>
      </div>
    </body>
    </html>
    ";

        $mail->Body = $htmlTemplate;
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email Error: " . $e->getMessage());
        return false;
    }
}

// 5. Procesamiento del pago
try {
    if (!isset($_GET['session_id'])) {
        throw new Exception("No se proporcionó session_id");
    }

    $session_id = $_GET['session_id'];
    $session = \Stripe\Checkout\Session::retrieve($session_id);

    if ($session->payment_status !== 'paid') {
        throw new Exception("El pago no se completó correctamente");
    }

    $stmt = $conn->prepare("SELECT id,id_clie ,id_servicio ,tipo_servicio, estatus FROM pagos WHERE session_id = ?");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("No se encontró el pago con session_id: $session_id");
    }

    $pago = $result->fetch_assoc();

    if ($pago['estatus'] == 1) {
           echo '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gracias por tu pago</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: "Montserrat", sans-serif;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            color: #2c3e50;
            text-align: center;
        }
        .mensaje {
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
    </style>
    <script>
        setTimeout(() => {
            window.close();
        }, 5000);
    </script>
</head>
<body>
    <div class="mensaje">
        <h1>Tu transacción ha sido registrada exitosamente.</h1>
        <p>Este servicio ya se pagó anteriormente</p>
        <p>Esta ventana se cerrará automáticamente en unos segundos.</p>
    </div>
</body>
</html>';
exit;
    }
$serviceUpdate = null;
    $conn->begin_transaction();

    $update = $conn->prepare("UPDATE pagos SET forma_pago = 1, estatus = 1, fecha_pago = NOW() WHERE id = ?");
    $update->bind_param("i", $pago['id']);
    $update->execute();

    if ($pago['tipo_servicio'] == 1) {
        $serviceUpdate = $conn->prepare("UPDATE hosting SET fecha_pago = DATE_ADD(IFNULL(fecha_pago, NOW()), INTERVAL 1 YEAR), estado_producto = 1 WHERE id_orden = ?");
            $serviceUpdate->bind_param("i", $pago['id_servicio']);
    $serviceUpdate->execute();

    } elseif ($pago['tipo_servicio'] == 2) {
        $serviceUpdate = $conn->prepare("UPDATE dominios SET fecha_pago = DATE_ADD(IFNULL(fecha_pago, NOW()), INTERVAL 1 YEAR), estado_dominio = 1 WHERE id_dominio = ?");
            $serviceUpdate->bind_param("i", $pago['id_servicio']);
    $serviceUpdate->execute();

    } 
    // else {
    //    es pago manual
    // }


    $clientStmt = $conn->prepare("SELECT correo, nombre_contacto FROM clientes WHERE id = ?");
    $clientStmt->bind_param("i", $pago['id_clie']);
    $clientStmt->execute();
    $cliente = $clientStmt->get_result()->fetch_assoc();

    // if (!$cliente || empty($cliente['correo'])) {
    //     throw new Exception("No se encontró correo del cliente o está vacío");
    // }

    $conn->commit();

   // Enviar correo de confirmación
   $emailSent = sendEmail(
    $cliente['correo'],
    "Confirmación de Pago Recibido",
    "✅ Confirmación de Pago Recibido",
    "Estimado/a {$cliente['nombre_contacto']},<br><br>
    Te confirmamos que hemos recibido tu pago correspondiente al servicio, el cual se encuentra correctamente registrado y validado.<br><br>
    📄 <strong>Detalles del Pago:</strong><br>
    Cliente: {$cliente['nombre_contacto']}<br>
    Método de pago: Tarjeta<br>
    Fecha del pago: " . date('d/m/Y') . "<br>
    Hora del pago: " . date('h:i a') . "<br><br>
    Gracias por tu confianza y preferencia. Si necesitas factura o comprobante adicional, no dudes en solicitarlo.<br><br>
    📌 Para cualquier duda o soporte, estamos a tu disposición en WhatsApp: 477 118 1285",
    "Atentamente,<br>El equipo de Conlineweb"
);

    echo '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gracias por tu pago</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: "Montserrat", sans-serif;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            color: #2c3e50;
            text-align: center;
        }
        .mensaje {
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
    </style>
    <script>
        setTimeout(() => {
            window.close();
        }, 5000);
    </script>
</head>
<body>
    <div class="mensaje">
        <h1>✅ ¡Gracias por tu pago, ' . htmlspecialchars($cliente['nombre_contacto']) . '!</h1>
        <p>Tu transacción ha sido registrada exitosamente.</p>
        <p>Esta ventana se cerrará automáticamente en unos segundos.</p>
    </div>
</body>
</html>';
exit;


} catch (\Stripe\Exception\ApiErrorException $e) {
    if (isset($conn) && $conn instanceof mysqli && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    error_log("Stripe Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Error en Stripe: ' . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($conn) && $conn instanceof mysqli && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    error_log("General Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
