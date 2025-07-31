<?php

require "PHPMailer/src/Exception.php";
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";

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

// aprobar_pago.php
header('Content-Type: application/json');
include "conn.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$log_file = 'pago_errors.log';
date_default_timezone_set('America/Mexico_City');

function log_error($message, $log_file) {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] ERROR: $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Verificar que la petición sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error_msg = 'Método no permitido';
    log_error($error_msg, $log_file);
    echo json_encode(['success' => false, 'message' => $error_msg]);
    exit;
}

// Verificar que se reciban los datos necesarios
if (!isset($_POST['id']) || !isset($_POST['forma_pago']) || !isset($_POST['tipo_servicio']) || !isset($_POST['id_servicio'])) {
    $error_msg = 'Datos incompletos';
    log_error($error_msg, $log_file);
    echo json_encode(['success' => false, 'message' => $error_msg]);
    exit;
}

$id = intval($_POST['id']);
$forma_pago = intval($_POST['forma_pago']);
$tipo_servicio = intval($_POST['tipo_servicio']);
$id_servicio = intval($_POST['id_servicio']);

// Validar forma de pago (solo transferencia=2 o efectivo=3)
if ($forma_pago !== 2 && $forma_pago !== 3) {
    $error_msg = 'Forma de pago inválida: ' . $forma_pago;
    log_error($error_msg, $log_file);
    echo json_encode(['success' => false, 'message' => 'Forma de pago inválida']);
    exit;
}

// Verificar que el pago existe y está pendiente
$check_sql = "SELECT id_clie FROM pagos WHERE id = ? AND estatus = 0";
$check_stmt = $conn->prepare($check_sql);

if (!$check_stmt) {
    $error_msg = 'Error en la consulta: ' . $conn->error;
    log_error($error_msg, $log_file);
    echo json_encode(['success' => false, 'message' => $error_msg]);
    exit;
}

$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$pago_data = $result->fetch_assoc();
$check_stmt->close();

if (!$pago_data) {
    $error_msg = 'Pago no encontrado o ya está aprobado. ID: ' . $id;
    log_error($error_msg, $log_file);
    echo json_encode(['success' => false, 'message' => 'Pago no encontrado o ya está aprobado']);
    exit;
}

// Obtener datos del cliente
$clientStmt = $conn->prepare("SELECT correo, nombre_contacto FROM clientes WHERE id = ?");
$clientStmt->bind_param("i", $pago_data['id_clie']);
$clientStmt->execute();
$cliente = $clientStmt->get_result()->fetch_assoc();
$clientStmt->close();

if (!$cliente || empty($cliente['correo'])) {
    log_error('No se encontró correo del cliente o está vacío para ID: '.$pago_data['id_clie'], $log_file);
}

// Actualizar el pago
$update_sql = "UPDATE pagos SET estatus = 1, forma_pago = ?, fecha_pago = CURDATE(), hora_pago = NOW() WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);

if (!$update_stmt) {
    $error_msg = 'Error en la preparación de la consulta: ' . $conn->error;
    log_error($error_msg, $log_file);
    echo json_encode(['success' => false, 'message' => $error_msg]);
    exit;
}

$update_stmt->bind_param("ii", $forma_pago, $id);

if ($update_stmt->execute()) {
    if ($update_stmt->affected_rows > 0) {
        $success = true;
        $message = 'Pago aprobado correctamente';
        
        // Actualizar fechas según tipo de servicio
        if ($tipo_servicio == 1) {
            // Hosting
            $query = "SELECT fecha_pago FROM hosting WHERE id_orden = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id_servicio);
            $stmt->execute();
            $stmt->bind_result($fecha);
            $stmt->fetch();
            $stmt->close();

            if ($fecha) {
                $nuevaFecha = date('Y-m-d', strtotime('+1 year -1 day', strtotime($fecha)));
                $update = $conn->prepare("UPDATE hosting SET fecha_pago = ? WHERE id_orden = ?");
                $update->bind_param("si", $nuevaFecha, $id_servicio);
                $update->execute();
                $update->close();
            } else {
                $success = false;
                $message = "No se encontró la fecha de pago para el servicio de hosting";
                log_error($message." ID: $id_servicio", $log_file);
            }
        } else if ($tipo_servicio == 2) {
            // Dominios
            $query = "SELECT fecha_pago FROM dominios WHERE id_dominio = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id_servicio);
            $stmt->execute();
            $stmt->bind_result($fecha);
            $stmt->fetch();
            $stmt->close();

            if ($fecha) {
                $nuevaFecha = date('Y-m-d', strtotime('+1 year -1 day', strtotime($fecha)));
                $update = $conn->prepare("UPDATE dominios SET fecha_pago = ? WHERE id_dominio = ?");
                $update->bind_param("si", $nuevaFecha, $id_servicio);
                $update->execute();
                $update->close();
            } else {
                $success = false;
                $message = "No se encontró la fecha de pago para el dominio";
                log_error($message." ID: $id_servicio", $log_file);
            }
        }

        // Enviar email solo si tenemos datos del cliente
        if ($success && $cliente && !empty($cliente['correo'])) {
            $emailSent = sendEmail(
                $cliente['correo'],
                "Confirmación de Pago Recibido",
                "✅ Confirmación de Pago Recibido",
                "Estimado/a {$cliente['nombre_contacto']},<br><br>
                Te confirmamos que hemos recibido tu pago correspondiente al servicio, el cual se encuentra correctamente registrado y validado.<br><br>
                📄 <strong>Detalles del Pago:</strong><br>
                Cliente: {$cliente['nombre_contacto']}<br>
                Método de pago: ".($forma_pago == 2 ? 'Transferencia' : 'Efectivo')."<br>
                Fecha del pago: ".date('d/m/Y')."<br>
                Hora del pago: ".date('h:i a')."<br><br>
                Gracias por tu confianza y preferencia. Si necesitas factura o comprobante adicional, no dudes en solicitarlo.<br><br>
                📌 Para cualquier duda o soporte, estamos a tu disposición en WhatsApp: 477 118 1285",
                "Atentamente,<br>El equipo de Conlineweb"
            );

            if (!$emailSent) {
                log_error("Falló el envío de email a {$cliente['correo']}", $log_file);
            }
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'id' => $id,
            'forma_pago' => $forma_pago
        ]);
    } else {
        $error_msg = 'No se pudo actualizar el registro. ID: ' . $id;
        log_error($error_msg, $log_file);
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el registro']);
    }
} else {
    $error_msg = 'Error al ejecutar la consulta: ' . $update_stmt->error;
    log_error($error_msg, $log_file);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el pago']);
}

$update_stmt->close();
$conn->close();
?>