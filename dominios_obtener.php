<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

include 'conn.php';
$conn->set_charset("utf8");

$sql = "SELECT d.id_dominio AS id, d.url_dominio, d.usuario, d.contrasena, c.nombre_contacto
        FROM dominios d
        LEFT JOIN clientes c ON d.cliente_id = c.id";

if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $cliente_id = intval($_GET['id']);
    $sql .= " WHERE d.cliente_id = $cliente_id";
}


$result = $conn->query($sql);

$todosDominios = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $todosDominios[] = $row;
    }
}

echo json_encode($todosDominios);

$conn->close();
