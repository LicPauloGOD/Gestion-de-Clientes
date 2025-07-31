<?php
ob_start();
header('Content-Type: application/json');
include "conn.php";
error_reporting(E_ALL);
ini_set('display_errors', 1); // Mostrar errores en pantalla para depuración

// Verificar si PHPMailer existe
$phpmailerFiles = [
    './PHPMailer/src/Exception.php',
    './PHPMailer/src/PHPMailer.php',
    './PHPMailer/src/SMTP.php'
];

foreach ($phpmailerFiles as $file) {
    if (!file_exists($file)) {
        echo json_encode(["success" => false, "message" => "Archivo PHPMailer no encontrado: $file"]);
        exit();
    }
}

require "./PHPMailer/src/Exception.php";
require "./PHPMailer/src/PHPMailer.php";
require "./PHPMailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
    $sqlDominio = "SELECT * FROM dominios WHERE cliente_id = ?";
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
    $asunto = "Recordatorio de renovación de dominio: $url_dominio";
    $titulo = "Renovación de dominio";
    $cuerpo = "Estimado/a $nombre_cliente,<br><br>
    Le recordamos que el dominio <strong>$url_dominio</strong> tiene como fecha de renovación el <strong>$fecha_pago</strong>.<br><br>
    Por favor, considere renovarlo a tiempo para evitar interrupciones en su servicio.";
    $despedida = "Gracias por su atención.<br>Atentamente, <br><strong>Equipo de soporte técnico</strong>";

    // Configuración de PHPMailer con el estilo del código que funciona
    $mail = new PHPMailer(true);
    $mensaje = "
    <html>
    <head>
      <title>$asunto</title>
      <style>
        /* Importar la fuente desde Google Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond&display=swap');

        .bg{
          width: 96%;
          margin: 0 auto;
          padding: 50px 0px;
          background-color: #e8e8e8;
          font-family: 'Cormorant Garamond', serif;
        }

        p {
          margin: 15px;
        }

        .container {
          width: 450px;
          margin: 0 auto;
          border-radius: 30px;
          background-color: #fff;
          line-height: 1.5;
          font-size: 1.2rem;
          box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-container {
          padding: 10px 30px;
          margin: 10px;
        }

        .header {
          text-align: left;
          padding: 10px 50px;
          font-size: 1.5rem;
          background-color: #eee8dc;
          color: black;
          font-weight: 500;
          margin-top: 13px;
        }

        .content {
          padding: 20px 0px 0px 0px;
          margin: 0px;
        }
      </style>
    </head>
    <body>
      <div class='bg'>
        <div class='container'>
          <div class='content'>
            <div class='header'>
              $titulo
            </div>
            <div class='card-container'>
              <p>$cuerpo</p>
              <p>$despedida</p>
            </div>
          </div>
        </div>
        <div style='text-align: center;'>
          <img style='width: 140px; margin: 0 auto;' alt='efegephologo' src='https://adm.conlineweb.com/images/logo.png'/>
        </div>
      </div>
    </body>
    </html>";

    // Configuración SMTP (igual que en tu código funcional)
    $correoRemitente = "info@conlineweb.com";
    $nombreRemitente = "InfoConlineweb";
    
    try {
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = "starttls";
        $mail->Port = 587;
        $mail->Host = "smtp.gmail.com";
        $mail->Username = $correoRemitente;
        $mail->Password = "glhewzgjzdnsbuvj";

        $mail->setFrom($correoRemitente, $nombreRemitente);
        $mail->addAddress($correo_destino, $nombre_cliente);

        $mail->Subject = $asunto;
        $mail->isHTML(true);
        $mail->Body = $mensaje;

        $mail->send();
        echo json_encode(["success" => true, "message" => "✅ Correo enviado a $correo_destino"]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "❌ Error al enviar el correo: " . $mail->ErrorInfo]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "❌ Error en el proceso: " . $e->getMessage()]);
}