<?php

$dbhost = "cpanel.conlineweb.com";
$dbuser = "admin_clientes";
$dbpass = "NPJidzGipy-@";
$dbname = "admin_clientes";

$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
if(!$conn)
{
    die("no hay conexion: ".mysql_connect_error());
}

if (!isset($conn)) {
    error_log("conn.php: \$conn no está definido.");
} elseif ($conn->connect_error) {
    error_log("conn.php: Error de conexión - " . $conn->connect_error);
} else {
    error_log("conn.php: Conexión establecida correctamente.");
}


?>