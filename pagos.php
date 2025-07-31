<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
include "menu.php";
include "conn.php";
include "plan_helper.php";

$sql = "SELECT 
    p.*,
    c.nombre_contacto AS cliente,
    TRIM(c.correo) AS correo_cliente,
    CASE 
        WHEN p.tipo_servicio = '2' THEN d.url_dominio
        WHEN p.tipo_servicio = '1' THEN h.nom_host
        ELSE NULL
    END AS nombre_servicio,
    h.producto
FROM pagos p
LEFT JOIN clientes c ON p.id_clie = c.id
LEFT JOIN dominios d ON p.tipo_servicio = '2' AND p.id_servicio = d.id_dominio
LEFT JOIN hosting h ON p.tipo_servicio = '1' AND p.id_servicio = h.id_orden
WHERE p.estatus = 0  -- Esta línea filtra solo los pendientes
ORDER BY p.fecha_pago >= CURDATE() DESC, p.fecha_pago ASC;";
$result = $conn->query($sql);
// Verificar si hay resultados y convertirlos a array
$hosting = [];
if ($result && $result->num_rows > 0) {
    $hosting = $result->fetch_all(MYSQLI_ASSOC);
}

// Obtener fechas del formulario si existen
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Consulta para aprobados con filtro de fechas
$sql_aprobados = "SELECT 
    p.*,
    c.nombre_contacto AS cliente,
    TRIM(c.correo) AS correo_cliente,
    CASE 
        WHEN p.tipo_servicio = '2' THEN d.url_dominio
        WHEN p.tipo_servicio = '1' THEN h.nom_host
        ELSE NULL
    END AS nombre_servicio,
    h.producto
FROM pagos p
LEFT JOIN clientes c ON p.id_clie = c.id
LEFT JOIN dominios d ON p.tipo_servicio = '2' AND p.id_servicio = d.id_dominio
LEFT JOIN hosting h ON p.tipo_servicio = '1' AND p.id_servicio = h.id_orden
WHERE p.estatus = 1";

// Agregar filtros de fecha si están presentes
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $sql_aprobados .= " AND DATE(p.fecha_pago) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
} elseif (!empty($fecha_inicio)) {
    $sql_aprobados .= " AND DATE(p.fecha_pago) >= '$fecha_inicio'";
} elseif (!empty($fecha_fin)) {
    $sql_aprobados .= " AND DATE(p.fecha_pago) <= '$fecha_fin'";
}

$sql_aprobados .= " ORDER BY p.fecha_pago >= CURDATE() DESC, p.fecha_pago ASC;";

$result_aprobados = $conn->query($sql_aprobados);
$aprobados = [];
if ($result_aprobados && $result_aprobados->num_rows > 0) {
    $aprobados = $result_aprobados->fetch_all(MYSQLI_ASSOC);
}

// Calcular total de pagos aprobados por moneda
$monedas_aprobadas = [];
$mostrarTotalAprobados = false;
if (!empty($aprobados)) {
    foreach ($aprobados as $pago) {
        $moneda = $pago['currency'];
        $monto = floatval($pago['monto']);
        
        if (!isset($monedas_aprobadas[$moneda])) {
            $monedas_aprobadas[$moneda] = 0;
        }
        
        $monedas_aprobadas[$moneda] += $monto;
        $mostrarTotalAprobados = true;
    }
}
?>

    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fc;
        }

        .container-xl {
            padding: 2rem;
        }

        .nav-tabs .nav-link {
            font-weight: 600;
            color: #4e73df;
        }

        .nav-tabs .nav-link.active {
            background-color: #e2e6ea;
            border-color: #dee2e6 #dee2e6 #fff;
            color: #1b4de4;
        }

        .table thead th {
            background-color: #f1f4f8;
            color: #495057;
            vertical-align: middle;
            text-align: center;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .table tbody td {
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .table-bordered {
            border: 1px solid #dee2e6;
        }

        .table td, .table th {
            padding: 0.75rem;
            border-color: #e3e6f0;
        }

        .btn-actions .btn {
            font-size: 0.75rem;
            padding: 0.3rem 0.6rem;
            border-radius: 0.3rem;
        }

        .btn-success {
            background-color: #1cc88a;
            border-color: #17a673;
        }

        .btn-success:hover {
            background-color: #17a673;
            border-color: #148f68;
        }

        .btn-info {
            background-color: #36b9cc;
            border-color: #2c9faf;
        }

        .btn-info:hover {
            background-color: #2c9faf;
            border-color: #248a96;
        }

        .h3.text-primary {
            font-weight: 700;
        }

        .tab-content {
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 0.375rem 0.375rem;
            background-color: #fff;
        }

        .swal2-popup {
            font-size: 1rem !important;
        }
    </style>
<style>
    .btn-approve {
        background-color: #28a745;
        border-color: #28a745;
        color: white;
        padding: 5px 10px;
        font-size: 12px;
        border-radius: 4px;
    }

    .btn-approve:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }

    .btn-actions {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
</style>

<!DOCTYPE html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tabla Pagos</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
        
    <!-- Custom styles for this template -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Custom styles for this page -->
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">

    <!-- Main Content -->
    <div id="content">

       
                <div class="container-xl my-4 py-4  bg-white rounded-4 shadow-sm border border-light-subtle" style="max-width: 90% !important;">

            <div class="d-sm-flex align-items-center  mb-4">
                <br>
                <h1 class="h3 mb-0 text-primary">Consulta de Pagos</h1>
            </div>

            <!-- Estructura de tabs Bootstrap -->
<ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="hosting-tab" data-bs-toggle="tab" data-bs-target="#hosting" type="button" role="tab" aria-controls="hosting" aria-selected="true">Hosting</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="dominio-tab" data-bs-toggle="tab" data-bs-target="#dominio" type="button" role="tab" aria-controls="dominio" aria-selected="false">Dominio</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab" aria-controls="manual" aria-selected="false">Servicios</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="aprobados-tab" data-bs-toggle="tab" data-bs-target="#aprobados" type="button" role="tab" aria-controls="aprobados" aria-selected="false">Aprobados</button>
  </li>
</ul>
<div class="tab-content" id="myTabContent">
  <div class="tab-pane fade show active" id="hosting" role="tabpanel" aria-labelledby="hosting-tab">
    <?php $hosting_tab = array_filter($hosting, function($row) { return $row['tipo_servicio'] == 1; }); ?>
    <div class="table-responsive">
    <?php if (!empty($hosting_tab)): ?>
    <table class="table table-bordered" width="100%" cellspacing="0">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">ID servicio</th>
                <th scope="col">ID Cliente</th>
                <th scope="col">Cliente</th>
                <th scope="col">Plan</th>
                 <th scope="col">Fecha de vencimiento</th>
                <th scope="col">Nombre Servicio</th>
                <th scope="col">Forma Pago</th>
                <th scope="col">Monto</th>
                <th scope="col">Moneda</th>
                <th scope="col">Estatus</th>
                <th scope="col">Concepto</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($hosting_tab as $row): ?>
                <tr>
                    <td><?php echo $row["id"]; ?></td>
                    <td><?php echo $row["id_servicio"]; ?></td>
                    <td><?php echo $row["id_clie"]; ?></td>
                    <td><?php echo $row["cliente"]; ?></td>
                    <td><?php echo obtenerNombrePlan($conn, $row['producto']); ?></td>
                     <td><?php echo $row["fecha_limite_pago"]; ?></td>
                    <td><?php echo $row["nombre_servicio"]; ?></td>
                    <td>
    <?php 
        $formaPendiente = $row["forma_pago"] == 0 || $row["forma_pago"] === null || $row["forma_pago"] === "undefined" || $row["forma_pago"] === "";
        if ($formaPendiente) {
            echo "Pendiente";
        } elseif ($row["forma_pago"] == 1) {
            echo "Tarjeta";
        } elseif ($row["forma_pago"] == 2) {
            echo "Transferencia";
        } elseif ($row["forma_pago"] == 3) {
            echo "Efectivo";
        } else {
            echo "Pendiente";
        }
    ?>
</td>

                    <td><?php echo $row["monto"]; ?></td>
                    <td><?php echo $row["currency"]; ?></td>
                    <td><?php if ($row["estatus"] == 1) {
                        echo "Aprobado";
                    } else {
                        echo "Pendiente";
                    } ?></td>
                    <td><?php echo $row["concepto"]; ?></td>
                    <td>
                        <?php if (isset($row["estatus"]) && $row["estatus"] == 0): ?>
                            <div class="btn-actions">
                                <button class="btn btn-success btn-sm" onclick="aprobarPago(<?php echo $row['id']; ?>,<?php echo $row['id_servicio']; ?>,<?php echo $row['tipo_servicio']; ?>)">Aprobar Pago</button>
                                <button class="btn btn-info btn-sm" onclick="reenviarCorreo(<?php echo $row['id']; ?>, <?php echo $row['tipo_servicio']; ?>, '<?php echo $row['correo_cliente']; ?>','<?php echo $row['manual']; ?>')">Reenviar Correo</button>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No se encontraron registros.</p>
    <?php endif; ?>
    </div>
  </div>
  <div class="tab-pane fade" id="dominio" role="tabpanel" aria-labelledby="dominio-tab">
    <?php $dominio_tab = array_filter($hosting, function($row) { return $row['tipo_servicio'] == 2; }); ?>
    <div class="table-responsive">
    <?php if (!empty($dominio_tab)): ?>
    <table class="table table-bordered" width="100%" cellspacing="0">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">ID servicio</th>
                <th scope="col">ID Cliente</th>
                <th scope="col">Cliente</th>
                <th scope="col">Nombre Servicio</th>
                <th scope="col">Fecha Vencimiento</th>
                <th scope="col">Forma Pago</th>
                <th scope="col">Monto</th>
                <th scope="col">Moneda</th>
                <th scope="col">Estatus</th>
                <th scope="col">Concepto</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dominio_tab as $row): ?>
                <tr>
                    <td><?php echo $row["id"]; ?></td>
                    <td><?php echo $row["id_servicio"]; ?></td>
                    <td><?php echo $row["id_clie"]; ?></td>
                    <td><?php echo $row["cliente"]; ?></td>
                    <td><?php echo $row["nombre_servicio"]; ?></td>
                     <td><?php echo $row["fecha_limite_pago"]; ?></td>
                    <td><?php if ($row["forma_pago"] == 1) {
                        echo "Tarjeta";
                    } elseif ($row["forma_pago"] == 2) {
                        echo "Transferencia";
                    } elseif ($row["forma_pago"] == 3) {
                        echo "Efectivo";
                    } else {
                        echo "Pendiente";
                    } ?></td>
                    <td><?php echo $row["monto"]; ?></td>
                    <td><?php echo $row["currency"]; ?></td>
                    <td><?php if ($row["estatus"] == 1) {
                        echo "Aprobado";
                    } else {
                        echo "Pendiente";
                    } ?></td>
                    <td><?php echo $row["concepto"]; ?></td>
                    <td>
                        <?php if (isset($row["estatus"]) && $row["estatus"] == 0): ?>
                            <div class="btn-actions">
                                <button class="btn btn-success btn-sm" onclick="aprobarPago(<?php echo $row['id']; ?>,<?php echo $row['id_servicio']; ?>,<?php echo $row['tipo_servicio']; ?>)">Aprobar Pago</button>
                                <button class="btn btn-info btn-sm" onclick="reenviarCorreo(<?php echo $row['id']; ?>, <?php echo $row['tipo_servicio']; ?>, '<?php echo $row['correo_cliente']; ?>','<?php echo $row['manual']; ?>')">Reenviar Correo</button>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No se encontraron registros.</p>
    <?php endif; ?>
    </div>
  </div>
  <div class="tab-pane fade" id="manual" role="tabpanel" aria-labelledby="manual-tab">
    <?php $manual_tab = array_filter($hosting, function($row) { return isset($row['manual']) && $row['manual'] == 1; }); ?>
    <div class="table-responsive">
    <?php if (!empty($manual_tab)): ?>
    <table class="table table-bordered" width="100%" cellspacing="0">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">ID servicio</th>
                <th scope="col">ID Cliente</th>
                <th scope="col">Cliente</th>
               
                <th scope="col">Fecha Vencimiento</th>
                <th scope="col">Forma Pago</th>
                <th scope="col">Monto</th>
                <th scope="col">Moneda</th>
                <th scope="col">Estatus</th>
                <th scope="col">Concepto</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($manual_tab as $row): ?>
                <tr>
                    <td><?php echo $row["id"]; ?></td>
                    <td><?php echo $row["id_servicio"]; ?></td>
                    <td><?php echo $row["id_clie"]; ?></td>
                    <td><?php echo $row["cliente"]; ?></td>
                     <td><?php echo $row["fecha_limite_pago"]; ?></td>
                    <td><?php if ($row["forma_pago"] == 1) {
                        echo "Tarjeta";
                    } elseif ($row["forma_pago"] == 2) {
                        echo "Transferencia";
                    } elseif ($row["forma_pago"] == 3) {
                        echo "Efectivo";
                    } else {
                        echo "Pendiente";
                    } ?></td>
                    <td><?php echo $row["monto"]; ?></td>
                    <td><?php echo $row["currency"]; ?></td>
                    <td><?php if ($row["estatus"] == 1) {
                        echo "Aprobado";
                    } else {
                        echo "Pendiente";
                    } ?></td>
                    <td><?php echo $row["concepto"]; ?></td>
                    <td>
                        <?php if (isset($row["estatus"]) && $row["estatus"] == 0): ?>
                            <div class="btn-actions">
                                <button class="btn btn-success btn-sm" onclick="aprobarPago(<?php echo $row['id']; ?>,<?php echo $row['id_servicio']; ?>,<?php echo $row['tipo_servicio']; ?>)">Aprobar Pago</button>
                                <button class="btn btn-info btn-sm" onclick="reenviarCorreo(<?php echo $row['id']; ?>, <?php echo $row['tipo_servicio']; ?>, '<?php echo $row['correo_cliente']; ?>','<?php echo $row['manual']; ?>')">Reenviar Correo</button>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No se encontraron registros.</p>
    <?php endif; ?>
    </div>
  </div>
  <div class="tab-pane fade" id="aprobados" role="tabpanel" aria-labelledby="aprobados-tab">
    <!-- Formulario de filtros por fecha -->
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">Filtrar por Fecha</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <div class="row">
                    <div class="col-md-4">
                        <label for="fecha_inicio" class="form-label">Fecha Inicio:</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="fecha_fin" class="form-label">Fecha Fin:</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">Limpiar</a>
                    </div>
                </div>
                <?php if (!empty($fecha_inicio) || !empty($fecha_fin)): ?>
                    <div class="mt-2">
                        <small class="text-muted">
                            Mostrando pagos 
                            <?php if (!empty($fecha_inicio) && !empty($fecha_fin)): ?>
                                desde <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> hasta <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
                            <?php elseif (!empty($fecha_inicio)): ?>
                                desde <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?>
                            <?php elseif (!empty($fecha_fin)): ?>
                                hasta <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <?php $aprobados_tab = $aprobados; ?>
    <div class="table-responsive">
    <?php if (!empty($aprobados_tab)): ?>
    <table class="table table-bordered" width="100%" cellspacing="0">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">ID servicio</th>
                <th scope="col">ID Cliente</th>
                <th scope="col">Cliente</th>
                <th scope="col">Nombre Servicio</th>
                <th scope="col">Fecha Pago</th>
                <th scope="col">Forma Pago</th>
                <th scope="col">Monto</th>
                <th scope="col">Moneda</th>
                <th scope="col">Estatus</th>
                <th scope="col">Concepto</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($aprobados_tab as $row): ?>
                <tr>
                    <td><?php echo $row["id"]; ?></td>
                    <td><?php echo $row["id_servicio"]; ?></td>
                    <td><?php echo $row["id_clie"]; ?></td>
                    <td><?php echo $row["cliente"]; ?></td>
                    <td><?php echo $row["nombre_servicio"]; ?></td>
                    <td><?php echo !empty($row["fecha_pago"]) && $row["fecha_pago"] !== "0000-00-00" ? date("d M Y", strtotime($row["fecha_pago"])) : ""; ?></td>
                    <td><?php if ($row["forma_pago"] == 1) {
                        echo "Tarjeta";
                    } elseif ($row["forma_pago"] == 2) {
                        echo "Transferencia";
                    } elseif ($row["forma_pago"] == 3) {
                        echo "Efectivo";
                    } else {
                        echo "Pendiente";
                    } ?></td>
                    <td><?php echo $row["monto"]; ?></td>
                    <td><?php echo $row["currency"]; ?></td>
                    <td><?php if ($row["estatus"] == 1) {
                        echo "Aprobado";
                    } else {
                        echo "Pendiente";
                    } ?></td>
                    <td><?php echo $row["concepto"]; ?></td>
                    <td>
                        <!-- No acciones para aprobados -->
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No se encontraron registros.</p>
    <?php endif; ?>
    
    <?php if ($mostrarTotalAprobados && !empty($monedas_aprobadas)): ?>
            <div class="total-pendientes" style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 10px; border-left: 4px solid #28a745; font-family: Calibri, sans-serif;">
                <h5 style="margin: 0 0 10px 0; color: #28a745; font-family: Calibri, sans-serif;">
                    <i class="fas fa-check-circle"></i> Total de Pagos Aprobados
                    <?php if (!empty($fecha_inicio) || !empty($fecha_fin)): ?>
                        (Filtrado)
                    <?php endif; ?>:
                </h5>
                <?php foreach ($monedas_aprobadas as $moneda => $total): ?>
                    <div style="font-size: 18px; font-weight: bold; color: #495057; margin-bottom: 5px; font-family: Calibri, sans-serif;">
                        <?php echo number_format($total, 2); ?> <?php echo htmlspecialchars($moneda); ?>
                    </div>
                <?php endforeach; ?>
                <small style="color: #6c757d; font-style: italic; font-family: Calibri, sans-serif;">
                    * Solo se incluyen los pagos con estatus aprobado
                    <?php if (!empty($fecha_inicio) || !empty($fecha_fin)): ?>
                        <?php if (!empty($fecha_inicio) && !empty($fecha_fin)): ?>
                            desde <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> hasta <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
                        <?php elseif (!empty($fecha_inicio)): ?>
                            desde <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?>
                        <?php elseif (!empty($fecha_fin)): ?>
                            hasta <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </small>
            </div>
        <?php endif; ?>
    </div>
  </div>
</div>

<!-- Bootstrap core JavaScript-->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="js/sb-admin-2.min.js"></script>

<!-- Page level plugins -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Page level custom scripts -->
<script src="js/demo/datatables-demo.js"></script>

<script>

    // Función para activar automáticamente la pestaña de aprobados si hay filtros aplicados
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('fecha_inicio') || urlParams.has('fecha_fin')) {
            // Activar la pestaña de aprobados si hay filtros
            const aprobadosTab = document.getElementById('aprobados-tab');
            const aprobadosPane = document.getElementById('aprobados');
            
            // Desactivar la pestaña activa actual
            document.querySelector('.nav-link.active').classList.remove('active');
            document.querySelector('.tab-pane.active').classList.remove('active', 'show');
            
            // Activar la pestaña de aprobados
            aprobadosTab.classList.add('active');
            aprobadosPane.classList.add('active', 'show');
        }
    });

    function aprobarPago(idPago,id_servicio,tipo_servicio) {
        Swal.fire({
            title: 'Aprobar Pago',
            text: '¿Cómo fue realizado el pago?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Transferencia',
            cancelButtonText: 'Efectivo',
            showDenyButton: true,
            denyButtonText: 'Cancelar',
            denyButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                procesarAprobacion(idPago, 2,id_servicio,tipo_servicio);
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                procesarAprobacion(idPago, 3,id_servicio,tipo_servicio);
            }
        });
    }

    function reenviarCorreo(idPago, tipoServicio,correoCliente,manual) {
        Swal.fire({
            title: '¿Reenviar correo?',
            html: `¿Está seguro de que desea reenviar el correo a <strong>${correoCliente}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, reenviar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                procesarReenvioCorreo(idPago,tipoServicio, manual);
            }
        });
    }

    function procesarReenvioCorreo(idPago,tipoServicio,manual) {
        Swal.fire({
            title: 'Enviando correo...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
let urlArchivo = '';

if(manual == 1){
   urlArchivo = "reenviar_correo_pago_pendienteManual.php"
}else{
   urlArchivo =  tipoServicio == 1 ? "reenviar_correos_hosting.php" : "reenviar_correos_dominios.php";
}


console.log("idPago ",idPago)
        $.ajax({
            url:urlArchivo,
            type: 'POST',
            data: { id: idPago, ...(manual == 1 && { pago_id: idPago, resend: 1 }) },
            dataType: 'json',
            success: function(result) {
                console.log(result)
                try {
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: result.message || 'El correo ha sido reenviado correctamente',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message || 'Error al reenviar el correo'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error en la respuesta del servidor'
                    });
                }
            },
            error: function(xhr, status, error) {
                                console.log(error)

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión con el servidor: ' + error
                });
            }
        });
        console.log(tipoServicio)
    }
   
    function mandarWhatsapp(idPago) {
        
    }

    function procesarAprobacion(idPago, formaPago, id_servicio, tipo_servicio) {
    Swal.fire({
        title: 'Procesando...',
        text: 'Aprobando el pago',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: 'aprobar_pago.php',
        type: 'POST',
        data: {
            id: idPago,
            forma_pago: formaPago,
            id_servicio,
            tipo_servicio: tipo_servicio // Corregí el nombre del parámetro (tenías tipo_servicio)
        },
        success: function(result) {
            try {
                if (typeof result === 'string') {
                    result = JSON.parse(result);
                }

                if (result.success) {
                    // Primero mostrar confirmación de aprobación
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'El pago ha sido aprobado correctamente',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Luego enviar el correo de confirmación
                        enviarCorreoConfirmacion(idPago, tipo_servicio, result.correo_cliente, result.manual);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'Error al aprobar el pago'
                    });
                }
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error en la respuesta del servidor',
                    html: `<strong>Detalle:</strong><br><pre>${result}</pre>`
                });
                console.error('Error al parsear JSON:', e, result);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                html: `
                    <strong>Status:</strong> ${textStatus}<br>
                    <strong>Mensaje:</strong> ${errorThrown}<br>
                    <strong>Respuesta del servidor:</strong><br><pre>${jqXHR.responseText}</pre>
                `
            });
        }
    });
}

function enviarCorreoConfirmacion(idPago, tipoServicio, correoCliente, manual) {
    Swal.fire({
        title: 'Enviando confirmación...',
        text: 'Enviando correo de confirmación al cliente',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Modifica esta parte para distinguir entre tipos de servicio
    let urlArchivo = manual == 1 ? "reenviar_correo_manual.php" : 
                    (tipoServicio == 1 ? "enviar_correo_confirmacion_hosting.php" : "enviar_correo_confirmacion_dominio.php");

    $.ajax({
        url: urlArchivo,
        type: 'POST',
        data: { 
            id: idPago, 
            tipo_servicio: tipoServicio, // Añade este parámetro
            ...(manual == 1 && { pago_id: idPago, resend: 1 }) 
        },
        dataType: 'json',
        success: function(result) {
            Swal.close();
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Correo enviado!',
                    text: 'La confirmación de pago ha sido enviada al cliente',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pago aprobado pero correo no enviado',
                    text: result.message || 'El pago se aprobó pero hubo un problema al enviar el correo',
                    confirmButtonText: 'Entendido'
                }).then(() => {
                    location.reload();
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'warning',
                title: 'Pago aprobado pero correo no enviado',
                text: 'El pago se aprobó pero hubo un problema al enviar el correo de confirmación',
                confirmButtonText: 'Entendido'
            }).then(() => {
                location.reload();
            });
        }
    });
}
</script>

</body>
</html>