<?php

session_start();
include "conn.php";

$nombre = $_POST['txusuario'];
$pass = md5($_POST['txpassword']); // 👈 contraseña cifrada en MD5
$captcha = $_POST['g-recaptcha-response'];

$secret = '6LccysAdAAAAABx9GvXHFtpORAORoXdjRiI_6gdQ';

$_SESSION['id'] = null;
$_SESSION['login'] = false;

$query = mysqli_query($conn, "SELECT * FROM login WHERE usuario = '$nombre' AND contrasena = '$pass'");
$nr = mysqli_num_rows($query);
$res = $query->fetch_assoc();

if ($nr == 1) {
    $_SESSION['id'] = session_id();
    $_SESSION['uid'] = $res['id'];
    $_SESSION['login'] = true;
    $_SESSION['tipo'] = $res['id_tipo_usuario'];
    $_SESSION['last_activity'] = time();
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR']; // Opcional, para mayor seguridad


    $data = [
        'status' => 'ok',
        'tipo' => intval($res['id_tipo_usuario'])
    ];
} else {
    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$captcha");
    $arr = json_decode($response, TRUE);

    $data = [
        'status' => 'error'
    ];
}

header("Content-type: application/json; charset=utf-8");
echo json_encode($data);
