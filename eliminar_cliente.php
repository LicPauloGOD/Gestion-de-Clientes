<?php
header('Content-Type: application/json');
include 'conn.php';

$response = array('success' => false);

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $nuevo_estado = 1;
    if (isset($_POST['restaurar']) && $_POST['restaurar'] == 1) {
        $nuevo_estado = 0;
    }
    $sql = "UPDATE clientes SET eliminado = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ii', $nuevo_estado, $id);
        if ($stmt->execute()) {
            // Update dominios
            $sql_dominios = "UPDATE dominios SET eliminado = ? WHERE cliente_id = ?";
            $stmt_dominios = $conn->prepare($sql_dominios);
            if ($stmt_dominios) {
                $stmt_dominios->bind_param('ii', $nuevo_estado, $id);
                $stmt_dominios->execute();
                $stmt_dominios->close();
            }
            // Update hosting
            $sql_hosting = "UPDATE hosting SET eliminado = ? WHERE cliente_id = ?";
            $stmt_hosting = $conn->prepare($sql_hosting);
            if ($stmt_hosting) {
                $stmt_hosting->bind_param('ii', $nuevo_estado, $id);
                $stmt_hosting->execute();
                $stmt_hosting->close();
            }
            $response['success'] = true;
        }
        $stmt->close();
    }
}

echo json_encode($response);
