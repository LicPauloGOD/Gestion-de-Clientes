<?php
header('Content-Type: application/json');
include 'conn.php';

$response = array('success' => false);

if (isset($_POST['id']) && isset($_POST['eliminar'])) {
    $id = intval($_POST['id']);
    $eliminar = intval($_POST['eliminar']);
    
    $sql = "UPDATE hosting SET eliminado = ? WHERE id_orden = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ii', $eliminar, $id);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Hosting eliminado correctamente';
        } else {
            $response['message'] = 'Error al ejecutar la consulta';
        }
        $stmt->close();
    } else {
        $response['message'] = 'Error al preparar la consulta';
    }
} else {
    $response['message'] = 'Datos insuficientes';
}

echo json_encode($response);
?>