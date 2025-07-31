<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

header("Content-Type: application/json; charset=utf-8");
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

require "PHPMailer/src/Exception.php";
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";

$correoRemitente = "info@conlineweb.com";
$nombreRemitente = "C-onlineWeb";

try {
    require_once "conn.php";
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }

    if (!isset($_REQUEST["id"]) || !is_numeric($_REQUEST["id"])) {
        throw new Exception("ID no proporcionado o inválido");
    }
    $id = (int) $_REQUEST["id"];

    $checkStmt = $conn->prepare(
        "SELECT id, id_servicio, id_clie FROM pagos WHERE id = ? AND tipo_servicio = 2 LIMIT 1"
    );
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 0) {
        throw new Exception("No se encontró el pago con ID: " . $id);
    }

    $pagoData = $checkResult->fetch_assoc();
    $checkStmt->close();

    $stmt = $conn->prepare("
        SELECT 
            d.url_dominio AS dominio,
            d.costo_dominio,
            d.fecha_pago AS fecha_renovacion,
            c.nombre_contacto,
            c.correo,
            p.currency
        FROM 
            dominios d
        JOIN 
            clientes c ON d.cliente_id = c.id
        JOIN 
            pagos p ON p.id_servicio = d.id_dominio
        WHERE 
            p.id = ? AND
            p.tipo_servicio = 2
        LIMIT 1
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("No se encontraron datos de dominio/cliente para el pago ID: " . $id);
    }

    $row = $result->fetch_assoc();
    $stmt->close();

    $nombre = $row["nombre_contacto"];
    $correo_destino = $row["correo"];
    $dominio = $row["dominio"];
    $fecha_renovacion = $row["fecha_renovacion"];
    $costo = $row["costo_dominio"];
    $moneda = $row["currency"];

    $paymentData = [
        'id' => $id,
        'nombre' => $nombre,
        'costo' => $costo,
        'moneda' => $moneda,
        'concepto' => "Renovación de dominio $dominio",
        'correo' => $correo_destino
    ];

    $ch = curl_init('https://adm.conlineweb.com/procesar_pago.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($paymentData),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false || $httpCode !== 200) {
        throw new Exception("Error al conectar con Stripe");
    }

    $paymentResult = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($paymentResult['success']) || !$paymentResult['success']) {
        throw new Exception("Error en Stripe");
    }

    $urlStripe = $paymentResult['session_url'];
    $session_id = $paymentResult['session_id'];

    $update_sql = "UPDATE pagos SET session_id = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $session_id, $id);
    $update_stmt->execute();
    $update_stmt->close();
    
    $asunto = "Confirmación de Pago Aprobado - $dominio";
    $titulo = "Confirmación de Pago Aprobado - $dominio";

    $cuerpo ="
    <p>✅ Estimado cliente, <strong>" .htmlspecialchars($nombre, ENT_QUOTES, "UTF-8") ."</strong>,</p>
    <p>Nos complace informarte que hemos recibido y procesado exitosamente tu pago correspondiente a la renovación del dominio <strong>" .htmlspecialchars($dominio, ENT_QUOTES, "UTF-8") ."</strong>.</p>

    <div style='margin:20px 0;'>
      <p>🔍 <strong>Detalles del Pago</strong></p>
      <ul>
        <li><strong>Servicio:</strong> Renovación de Dominio " .htmlspecialchars($dominio, ENT_QUOTES, "UTF-8") ."</li>
        <li><strong>Fecha de renovación:</strong> " .htmlspecialchars($fecha_renovacion, ENT_QUOTES, "UTF-8") ."</li>
        <li><strong>Monto pagado:</strong> " .htmlspecialchars($costo . " " . $moneda, ENT_QUOTES, "UTF-8") ."</li>
        <li><strong>Estado:</strong> <span style='color: #4CAF50; font-weight: bold;'>APROBADO</span></li>
      </ul>
    </div>

    <p>El dominio ha sido renovado exitosamente y estará activo hasta la próxima fecha de vencimiento.</p>

    <p>Si tienes alguna pregunta sobre esta transacción o necesitas asistencia adicional, no dudes en contactarnos respondiendo a este correo o a través de nuestro WhatsApp: 477 118 1285.</p>

    <p>¡Gracias por confiar en nuestros servicios!</p>
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
          background-color: #ECECEC;
          font-family: 'Montserrat', sans-serif;
        }

        .container {
          background-color: #FFFFFF;
          width: 90%;
          max-width: 650px;
          margin: 40px auto;
          overflow: hidden;
          padding: 20px;
          border-radius: 12px;
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .banner {
          width: 100%;
          background-color: #f5f5f5;
          padding: 20px 0;
          display: flex;
          justify-content: center;
          align-items: center;
          border-radius: 12px 12px 0 0;
        }

        .banner img {
          max-height: 50%;
          max-width: 30%;
          object-fit: cover;
        }

        .header {
          padding: 40px 0px;
          text-align: center;
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

        @media (max-width: 600px) {
          .container {
            width: 95%;
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
      <div class='container'>
        <div class='banner'>
          <img src='https://adm.conlineweb.com/images/logo.png' alt='Company Banner'>
        </div>

        <div class='header'>
          <h1>$titulo</h1>
        </div>
        <div class='card-container'>
          <div class='greeting'>
            $cuerpo
          </div>
          <div class='signature'>
            <p>$despedida</p>
          </div>
        </div>
        <div class='footer'>
          Este mensaje ha sido enviado automáticamente. Por favor, no responda a este correo.
        </div>
      </div>
    </body>
    </html>
    ";

    $mail = new PHPMailer(true);
    $emailEnviado = false;

    try {
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = "tls";
        $mail->Port = 587;
        $mail->Host = "smtp.gmail.com";
        $mail->Username = $correoRemitente;
        $mail->Password = "bwctvomkzretakmu";

        $mail->setFrom($correoRemitente, $nombreRemitente);
        $mail->addAddress($correo_destino);

        $mail->Subject = $asunto;
        $mail->isHTML(true);
        $mail->Body = $mensaje;
        $mail->CharSet = "UTF-8";
        $mail->Encoding = "base64";

        $emailEnviado = $mail->send();
        
    } catch (Exception $e) {
        $errorCorreo = $e->getMessage();
        error_log("Error al enviar correo: " . $errorCorreo);
        $emailEnviado = false;
    }

    ob_clean();
    echo json_encode([
        "success" => $emailEnviado,
        "message" => $emailEnviado ? "Correo enviado correctamente" : "Error al enviar el correo",
        "data" => [
            "pago_id" => $id,
            "dominio" => $dominio,
            "cliente" => $nombre,
            "email" => $correo_destino,
            "payment_link" => $urlStripe,
            "email_sent" => $emailEnviado,
            "error" => $emailEnviado ? null : $errorCorreo,
        ],
    ]);
} catch (Exception $e) {
    ob_clean();
    error_log("Error en reenviar_correos_dominios.php: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "error" => $e->getTraceAsString(),
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
