<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

include 'conn.php';
$conn->set_charset("utf8");

$sql = "SELECT d.*, c.nombre_contacto
        FROM dominios d
        LEFT JOIN clientes c ON d.id = c.id";

if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $cliente_id = intval($_GET['id']);
    $sql .= " WHERE d.id = $cliente_id";
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
