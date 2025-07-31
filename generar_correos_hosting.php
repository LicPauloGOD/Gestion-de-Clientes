<?php
include 'conn.php'; // Asegúrate de que este archivo se llama correctamente y contiene la conexión

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require "PHPMailer/src/Exception.php";
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";



function enviarCorreo($correo_destino, $asunto, $titulo, $cuerpo, $despedida)
{
    $mail = new PHPMailer(true);
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


    
     // Enviar correo
    $mail_enviado = false; // mail($correo_destino, $asunto, $mensaje, $headers);
    $correoRemitente = "info@conlineweb.com ";
    $nombreRemitente = "C-onlineWeb";
    
        echo $mensaje;
        $headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: InfoConlineweb <info@conlineweb.com>\r\n";

$mail_enviado = mail($correo_destino, $asunto, $mensaje, $headers);
  
}

// Consulta corregida
$sql = "SELECT * FROM `vista_datos_correo_hosting` WHERE id = 89";

$result = mysqli_query($conn, $sql);

$sump = 0;





if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        
        
    $pago = "";    
    $sump += $row['costo'];

    $nombre = $row['nombre_contacto'];
    $correo_destino = $row['correo'];
    $hosting = $row['nom_host'];
    $dominio = $row['dominio'];

    $fecha_renovacion = $row['fecha_pago'];
    $periodo = '1 año'; // Puedes cambiar esto si tienes el periodo en tu base de datos
    $costo = $row['costo'];
$moneda = (int)$row['id_forma_pago'] == 1 ? "MXN" : "USD";

$data = [
    'id' => $row['id'],
    'nombre' => $nombre,
    'costo' => $sump,
    'moneda' => $moneda,
    'concepto' => "Pago de renovación de hosting " . $hosting,
    'correo' => $correo_destino
];

 var_dump($data); // Útil para depurar
    $asunto = "🔔 Recordatorio de renovación de hosting – $hosting";
    $titulo = "Recordatorio de renovación de hosting – $hosting";

    $cuerpo = "
    <p>Hola <strong>$nombre</strong>,</p>

    <p>Espero que te encuentres bien.</p>

    <p>Este es un recordatorio automático para recordarte  que el servicio de hosting <strong>$hosting</strong> asociado al dominio <strong>$dominio</strong> están próximos a renovarse.</p>

    <p><strong>📅 Fecha de renovación:</strong> $fecha_renovacion</p>
 <hr>
    <h4>🔄 Detalles del servicio:</h4>
    <ul>
      <li><strong>Renovación de hosting (vinculado al dominio $dominio)</strong> </li>
      <li><strong>Periodo:</strong> $periodo</li>
      <li><strong>Costo:</strong> $sump $moneda</li>
    </ul>
 <hr>
    <h4>💳 Opciones de pago:</h4>
    <p><strong>Transferencia bancaria</strong></p>
    <ul>
      <li><strong>Nombre del titular:</strong> Jose Antonio Martinez Karam</li>
      <li><strong>Número de cuenta:</strong> 60622161632 (Santander)</li>
      <li><strong>CLABE:</strong> 014225606221616325 (Santander)</li>
    </ul>
    <hr>
    <p>Una vez realizado el pago, por favor envía el comprobante vía WhatsApp al número <strong>477 118 1285</strong> para confirmar la renovación.</p>

    <p>Si tienes alguna pregunta o necesitas asistencia, no dudes en contactarnos. Estaremos encantados de ayudarte.</p>";

   $despedida = "<p>Gracias por confiar en nuestros servicios..</p>

    <p>Saludos cordiales,
 <br>
    <strong>C-onlineWeb,</strong></p>
    ";
    
   enviarCorreo("proyectos@conlineweb.com", $asunto, $titulo, $cuerpo, $despedida);
}


    echo "<strong>Suma total del costo de hostings: $sump</strong>";
} else {
    echo "No se encontraron resultados.";
}

mysqli_close($conn);
?>
