<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);
include "menu.php";
include "conn.php";

// Iniciar sesión para session_id()
$session_id = '';

// Obtener fecha actual, mes y año
$fecha_actual = new DateTime();
$mes_actual = $fecha_actual->format('m');
$anio_actual = $fecha_actual->format('Y');

//////////////////////////////////////////
// PAGOS PARA DOMINIOS
//////////////////////////////////////////

$sql_dominios = "SELECT * FROM dominios WHERE eliminado = 0 AND registrado = 1";
$result_dominios = $conn->query($sql_dominios);

if ($result_dominios->num_rows > 0) {
    while ($row = $result_dominios->fetch_assoc()) {
        $id_clie = $row['cliente_id'];
        $id_dominio = $row['id_dominio'];
        $costo_dominio = $row['costo_dominio'];
        $id_forma_pago = $row['id_forma_pago'];
        $fecha_pago_dominio = $row['fecha_pago'];
        $fecha_contratacion = new DateTime($row['fecha_contratacion']);
        $fecha_limite = new DateTime($fecha_pago_dominio);

        $currency = ($id_forma_pago == 2) ? 'USD' : 'MXN';

        // Calcular días faltantes para el pago
        $dias_faltantes = $fecha_actual->diff($fecha_limite)->days;
        $dias_faltantes = $fecha_limite > $fecha_actual ? $dias_faltantes : -$dias_faltantes;

        // Verificar si faltan 30 días o menos, o si ya pasó la fecha límite
        if ($dias_faltantes <= 30) {
            $concepto = "Pago por dominio ID $id_dominio";

            // Verificar si ya hay un pago en los últimos 30 días
            $sql_check = "SELECT MAX(fecha) as ultima_fecha FROM pagos 
                          WHERE id_clie = $id_clie 
                          AND concepto = '$concepto'
                          AND DATEDIFF(CURDATE(), fecha) <= 30";
            $check_result = $conn->query($sql_check);
            $check_row = $check_result->fetch_assoc();

            $generar_pago = true;

            if ($check_row['ultima_fecha']) {
                $generar_pago = false;
                echo "ℹ️ Ya existe un pago reciente para dominio ID $id_dominio (en los últimos 30 días)<br>";
            }

            if ($generar_pago) {
                // Insertar pago
                $fecha_pago = '0000-00-00';
                $hora_pago = '00:00:00';
                $id_pago = uniqid("pago_");
                $forma_pago = 0;
                $estatus = 0;
                $id_cuenta = 1;
                $tipo_servicio = 2; // 2 para dominios
                
                $sql_insert = "INSERT INTO pagos (
                    id_clie, fecha, hora, fecha_pago, hora_pago, monto, currency,
                    concepto, forma_pago, estatus, id_pago, session_id, id_cuenta, tipo_servicio, id_servicio, fecha_limite_pago
                ) VALUES (
                    $id_clie, CURDATE(), CURTIME(), '$fecha_pago', '$hora_pago',
                    $costo_dominio, '$currency', '$concepto', $forma_pago, $estatus,
                    '$id_pago', '$session_id', $id_cuenta, $tipo_servicio, $id_dominio, '$fecha_pago_dominio'
                )";

                if ($conn->query($sql_insert) === TRUE) {
                    echo "✅ Pago registrado para dominio ID $id_dominio, cliente $id_clie (Días faltantes: $dias_faltantes)<br>";
                } else {
                    echo "❌ Error al registrar pago de dominio: " . $conn->error . "<br>";
                }
            }
        } else {
            echo "⏳ Aún no corresponde pagar dominio ID $id_dominio (fecha límite: " . $fecha_limite->format('Y-m-d') . ", faltan $dias_faltantes días)<br>";
        }
    }
} else {
    echo "No hay dominios registrados.<br>";
}

//////////////////////////////////////////
// PAGOS PARA HOSTING
//////////////////////////////////////////

$sql_hosting = "SELECT * FROM hosting WHERE eliminado = 0";
$result_hosting = $conn->query($sql_hosting);

if ($result_hosting->num_rows > 0) {
    while ($row = $result_hosting->fetch_assoc()) {
        $id_clie = $row['cliente_id'];
        $id_orden = $row['id_orden'];
        $costo_producto = $row['costo_producto'];
        $id_forma_pago = $row['id_forma_pago'];
        $fecha_pago_hosting = $row['fecha_pago'];
        $fecha_contratacion = new DateTime($row['fecha_contratacion']);
        $fecha_limite = new DateTime($fecha_pago_hosting);

        $currency = ($id_forma_pago == 2) ? 'USD' : 'MXN';

        // Calcular días faltantes para el pago
        $dias_faltantes = $fecha_actual->diff($fecha_limite)->days;
        $dias_faltantes = $fecha_limite > $fecha_actual ? $dias_faltantes : -$dias_faltantes;

        // Verificar si faltan 30 días o menos, o si ya pasó la fecha límite
        if ($dias_faltantes <= 30) {
            $concepto = "Pago por hosting ID $id_orden";

            // Verificar si ya hay un pago en los últimos 30 días
            $sql_check = "SELECT MAX(fecha) as ultima_fecha FROM pagos 
                          WHERE id_clie = $id_clie 
                          AND concepto = '$concepto'
                          AND DATEDIFF(CURDATE(), fecha) <= 30";
            $check_result = $conn->query($sql_check);
            $check_row = $check_result->fetch_assoc();

            $generar_pago = true;

            if ($check_row['ultima_fecha']) {
                $generar_pago = false;
                echo "ℹ️ Ya existe un pago reciente para hosting ID $id_orden (en los últimos 30 días)<br>";
            }

            if ($generar_pago) {
                // Insertar pago
                $fecha_pago = '0000-00-00';
                $hora_pago = '00:00:00';
                $id_pago = uniqid("pago_");
                $forma_pago = 0;
                $estatus = 0;
                $id_cuenta = 1;
                $tipo_servicio = 1; // 1 para hosting
                
                $sql_insert = "INSERT INTO pagos (
                    id_clie, fecha, hora, fecha_pago, hora_pago, monto, currency,
                    concepto, forma_pago, estatus, id_pago, session_id, id_cuenta, tipo_servicio, id_servicio, fecha_limite_pago
                ) VALUES (
                    $id_clie, CURDATE(), CURTIME(), '$fecha_pago', '$hora_pago',
                    $costo_producto, '$currency', '$concepto', $forma_pago, $estatus,
                    '$id_pago', '$session_id', $id_cuenta, $tipo_servicio, $id_orden, '$fecha_pago_hosting'
                )";

                if ($conn->query($sql_insert) === TRUE) {
                    echo "✅ Pago registrado para hosting ID $id_orden, cliente $id_clie (Días faltantes: $dias_faltantes)<br>";
                } else {
                    echo "❌ Error al registrar pago de hosting: " . $conn->error . "<br>";
                }
            }
        } else {
            echo "⏳ Aún no corresponde pagar hosting ID $id_orden (fecha límite: " . $fecha_limite->format('Y-m-d') . ", faltan $dias_faltantes días)<br>";
        }
    }
} else {
    echo "No hay servicios de hosting registrados.<br>";
}
$conn->close();

?>