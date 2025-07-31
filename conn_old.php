<?php

$dbhost = "localhost";
$dbuser = "admin_conline_website";
$dbpass = "%y#1=&~KRr[_";
$dbname = "admin_conline_website";

$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
if(!$conn) {
    die("no hay conexion: ".mysqli_connect_error());
}

?>