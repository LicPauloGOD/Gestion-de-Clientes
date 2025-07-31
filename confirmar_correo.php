<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
include 'conn.php';

require "PHPMailer/src/Exception.php";
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";


$id= $_GET['id'] ?? 0;

function mostrar_html($titulo, $mensaje) {
    echo "<html><head><title>$titulo</title><meta charset='utf-8'><style>body{font-family:Montserrat,sans-serif;background:#f5f5f5;margin:0;padding:0;} .container{max-width:500px;margin:60px auto;background:#fff;padding:40px 30px;border-radius:10px;box-shadow:0 2px 10px #0001;text-align:center;} h1{color:#2c3e50;} p{color:#34495e;font-size:18px;} .btn{margin-top:30px;display:inline-block;padding:10px 30px;background:#2c3e50;color:#fff;text-decoration:none;border-radius:5px;font-weight:bold;} </style></head><body><div class='container'><h1>$titulo</h1><p>$mensaje</p><a class='btn' href='https://cliente.conlineweb.com/cliente.php'>Volver</a></div></body></html>";
    exit;
}

if($id != 0){
    $sql = "SELECT * FROM clientes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc();
    $stmt->close();
    
    if(!$cliente) {
        mostrar_html('Error', 'Cliente no encontrado.');
    }
    
    if($cliente['actualizar_correo'] == 0){
        mostrar_html('No disponible', 'No es posible actualizar el correo o ya se actualizó previamente.');
    } else{
        $correo_por_actualizar = $cliente['correo_pendiente_actualizar'];
        $nombre  = $cliente['nombre_contacto'];
        $correo_pendiente_actualizar = '';
        $actualizar_correo = 0;
        $sql_correo = "UPDATE clientes SET correo = ?, correo_pendiente_actualizar = ?, actualizar_correo = ? WHERE id = ?";
        $stmt_correo = $conn->prepare($sql_correo);
        $stmt_correo->bind_param("ssii", $correo_por_actualizar,$correo_pendiente_actualizar,$actualizar_correo, $id);
        $stmt_correo->execute();
        $stmt_correo->close();
              $sql_login = "UPDATE login SET usuario = ? WHERE id = ?";
      $stmt_login = $conn->prepare($sql_login);
      $stmt_login->bind_param("si", $correo_por_actualizar, $id);
      $stmt_login->execute();
      $stmt_login->close();
        
        
        
        //madnar correo
         $asunto = "Cambio de credenciales - C-onliWeb";
        $titulo = "Cambio de credenciales - C-onliWeb";

        $cuerpo = "
                <p>🔔 Estimado cliente, <strong>" . htmlspecialchars($nombre, ENT_QUOTES, "UTF-8") . "</strong>,</p>
                
                <p>Le informamos que sus credenciales de acceso han sido actualizadas correctamente.</p>
                
                <p>A partir de ahora, su nuevo usuario de acceso será: <strong>" . htmlspecialchars($correo_por_actualizar, ENT_QUOTES, "UTF-8") . "</strong></p>
                
                <p>Si usted no solicitó este cambio o tiene alguna duda, por favor comuníquese con nuestro equipo de soporte a la brevedad.</p>
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
          min-height: 100vh;
        }
        
        .container {
          width: 90%;
          max-width: 650px;
          margin: 0 auto;
          overflow: hidden;
        }
        
        .banner {
          width: 100%;
          max-height: 150px;
          background-color: #f5f5f5;
          padding: 30px;
          display: flex;
          justify-content: center;
          align-items: center;
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
            Este mensaje ha sido enviado automaticamente. Por favor, no responda a este correo.
          </div>
        </div>
      </div>
    </body>
    </html>
    ";
        $correoRemitente = "info@conlineweb.com";
        $nombreRemitente = "C-onlineWeb";
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
          $mail->addAddress($correo_por_actualizar);
          $mail->Subject = $asunto;
          $mail->isHTML(true);
          $mail->Body = $mensaje;
          $mail->CharSet = "UTF-8";
          $mail->Encoding = "base64";
          $emailEnviado = $mail->send();
        } catch (Exception $e) {
          $emailEnviado = false;
        }
        if($emailEnviado) {
            mostrar_html('Correo actualizado', 'El correo fue actualizado y se notificó al cliente exitosamente.');
        } else {
            mostrar_html('Correo actualizado, pero error al notificar', 'El correo fue actualizado, pero hubo un error al enviar la notificación al cliente.');
        }
    }
} else {
    mostrar_html('Error', 'ID de cliente no válido.');
}

?>