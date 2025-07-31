<?php
include "conn.php";

// Obtener mes y año por GET, si existen, si no usar el actual
$mes_inicio = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
$anio_inicio = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');

// Calcular la fecha de inicio (primer día del mes actual)
$fecha_inicio = sprintf('%04d-%02d-01', $anio_inicio, $mes_inicio);
// Calcular la fecha de fin (último día del mes siguiente)
$fecha_fin = date('Y-m-t', strtotime("+1 month", strtotime($fecha_inicio)));

// Consulta SQL: solo pagos pendientes (estatus = 0) y usando fecha_limite_pago
$sql_pagos = "
  SELECT 
    pagos.id, 
    pagos.id_clie AS cliente_id, 
    clientes.nombre_contacto AS nombre_cliente, 
    pagos.concepto AS nombre, 
    pagos.fecha_limite_pago, 
    pagos.tipo_servicio,
    pagos.estatus
  FROM pagos
  INNER JOIN clientes ON pagos.id_clie = clientes.id
  WHERE pagos.fecha_limite_pago BETWEEN ? AND ?
    AND (
      (pagos.tipo_servicio = 1 AND EXISTS (
        SELECT 1 FROM hosting 
        WHERE hosting.id_orden = pagos.id_servicio AND hosting.eliminado = 0
      ))
      OR
      (pagos.tipo_servicio = 2 AND EXISTS (
        SELECT 1 FROM dominios 
        WHERE dominios.id_dominio = pagos.id_servicio AND dominios.eliminado = 0
      ))
      OR
      (pagos.tipo_servicio NOT IN (1, 2))
    )
  ORDER BY pagos.fecha_limite_pago ASC
";


$stmt_pagos = $conn->prepare($sql_pagos);
$stmt_pagos->bind_param('ss', $fecha_inicio, $fecha_fin);
$stmt_pagos->execute();
$res_pagos = $stmt_pagos->get_result();

$pagos = [];
while($row = $res_pagos->fetch_assoc()) $pagos[] = $row;

// Filtrar por tipo y estatus para los modals y cards
$dominios = array_filter($pagos, function($p) { return $p['tipo_servicio'] == 2 && $p['estatus'] == 0; });
$hostings = array_filter($pagos, function($p) { return $p['tipo_servicio'] == 1 && $p['estatus'] == 0; });
$manuales = array_filter($pagos, function($p) { return $p['tipo_servicio'] == 0 && $p['estatus'] == 0; });
$cuenta_dominios = count($dominios);
$cuenta_hostings = count($hostings);
$cuenta_servicios = count(array_filter($pagos, function($p){ return $p['estatus'] == 0; }));

// Pagos con estatus 1 (pagados)
$pagos_pagados = array_filter($pagos, function($p){ return $p['estatus'] == 1; });
$cuenta_pagados = count($pagos_pagados);

// Para el modal de pagos, armar la lista con tipo SOLO de pagos pendientes (estatus=0)
$servicios = [];
foreach($pagos as $p) {
    if ($p['estatus'] == 0) { // Solo pendientes
        $tipo = $p['tipo_servicio'] == 2 ? 'Dominio' : ($p['tipo_servicio'] == 1 ? 'Hosting' : 'Pago');
        $servicios[] = [
            'tipo' => $tipo,
            'id' => $p['id'],
            'cliente_id' => $p['cliente_id'],
            'nombre' => $p['nombre'],
            'fecha_limite_pago' => $p['fecha_limite_pago'],
            'tipo_servicio' => $p['tipo_servicio'],
            'nombre_cliente' => $p['nombre_cliente']
        ];
    }
}
// Ordenar por fecha_limite_pago ascendente
usort($servicios, function($a, $b) {
    $t1 = !empty($a['fecha_limite_pago']) ? strtotime($a['fecha_limite_pago']) : PHP_INT_MAX;
    $t2 = !empty($b['fecha_limite_pago']) ? strtotime($b['fecha_limite_pago']) : PHP_INT_MAX;
    return $t1 <=> $t2;
});
?>

<?php include 'menu.php'; ?>

<!DOCTYPE html>
<head>
    
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
        
    <!-- Custom styles for this template -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

</head>
     
                <div class="container-fluid">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    </div>

                    <div class="row">

                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="#" data-toggle="modal" data-target="#modalDominios" style="text-decoration:none; color:inherit;">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body" style="cursor:pointer;">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-3">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    DOMINIOS
                                                </div>
                                                <div class="display-4 font-weight-bold text-primary" style="font-size:2.5rem;">
                                                    <?=$cuenta_dominios?>
                                                </div>
                                                <div class="small text-gray-600">Dominios con pago este mes</div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-globe fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="#" data-toggle="modal" data-target="#modalHostings" style="text-decoration:none; color:inherit;">
                                <div class="card border-left-success shadow h-100 py-2">
                                    <div class="card-body" style="cursor:pointer;">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-3">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    HOSTING
                                                </div>
                                                <div class="display-4 font-weight-bold text-success" style="font-size:2.5rem;">
                                                    <?=$cuenta_hostings?>
                                                </div>
                                                <div class="small text-gray-600">Hostings con pago este mes</div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-server fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="#" data-toggle="modal" data-target="#modalPagos" style="text-decoration:none; color:inherit;">
                                <div class="card border-left-info shadow h-100 py-2">
                                    <div class="card-body" style="cursor:pointer;">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-3">
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                    PAGOS
                                                </div>
                                                <div class="display-4 font-weight-bold text-info" style="font-size:2.5rem;">
                                                    <?=$cuenta_servicios?>
                                                </div>
                                                <div class="small text-gray-600">Todos los pagos y servicios en el rango</div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="#" data-toggle="modal" data-target="#modalPagosRealizados" style="text-decoration:none; color:inherit;">
                                <div class="card border-left-warning shadow h-100 py-2">
                                    <div class="card-body" style="cursor:pointer;">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-3">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                    PAGOS REALIZADOS
                                                </div>
                                                <div class="display-4 font-weight-bold text-warning" style="font-size:2.5rem;">
                                                    <?=$cuenta_pagados?>
                                                </div>
                                                <div class="small text-gray-600">Pagos con estatus 1 en el rango</div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Content Row -->

                    <div class="row">

                        <!-- Area Chart -->
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <!-- Card Header - Dropdown -->
                                <div
                                    class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Earnings Overview</h6>
                                    <div class="dropdown no-arrow">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                                            aria-labelledby="dropdownMenuLink">
                                            <div class="dropdown-header">Dropdown Header:</div>
                                            <a class="dropdown-item" href="#">Action</a>
                                            <a class="dropdown-item" href="#">Another action</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#">Something else here</a>
                                        </div>
                                    </div>
                                </div>
                                <!-- Card Body -->
                                <div class="card-body">
                                    <div class="chart-area">
                                        <canvas id="myAreaChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pie Chart -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4">
                                <!-- Card Header - Dropdown -->
                                <div
                                    class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Revenue Sources</h6>
                                    <div class="dropdown no-arrow">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                                            aria-labelledby="dropdownMenuLink">
                                            <div class="dropdown-header">Dropdown Header:</div>
                                            <a class="dropdown-item" href="#">Action</a>
                                            <a class="dropdown-item" href="#">Another action</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#">Something else here</a>
                                        </div>
                                    </div>
                                </div>
                                <!-- Card Body -->
                                <div class="card-body">
                                    <div class="chart-pie pt-4 pb-2">
                                        <canvas id="myPieChart"></canvas>
                                    </div>
                                    <div class="mt-4 text-center small">
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-primary"></i> Direct
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-success"></i> Social
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-info"></i> Referral
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">

                        <!-- Content Column -->
                        <div class="col-lg-6 mb-4">

                            <!-- Project Card Example -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Projects</h6>
                                </div>
                                <div class="card-body">
                                    <h4 class="small font-weight-bold">Server Migration <span
                                            class="float-right">20%</span></h4>
                                    <div class="progress mb-4">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: 20%"
                                            aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <h4 class="small font-weight-bold">Sales Tracking <span
                                            class="float-right">40%</span></h4>
                                    <div class="progress mb-4">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 40%"
                                            aria-valuenow="40" aria-valuenmin="0" aria-valuemax="100"></div>
                                    </div>
                                    <h4 class="small font-weight-bold">Customer Database <span
                                            class="float-right">60%</span></h4>
                                    <div class="progress mb-4">
                                        <div class="progress-bar" role="progressbar" style="width: 60%"
                                            aria-valuenow="60" aria-valuenmin="0" aria-valuemax="100"></div>
                                    </div>
                                    <h4 class="small font-weight-bold">Payout Details <span
                                            class="float-right">80%</span></h4>
                                    <div class="progress mb-4">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 80%"
                                            aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <h4 class="small font-weight-bold">Account Setup <span
                                            class="float-right">Complete!</span></h4>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"
                                            aria-valuenow="100" aria-valuenmin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Color System -->
                            <div class="row">
                                <div class="col-lg-6 mb-4">
                                    <div class="card bg-primary text-white shadow">
                                        <div class="card-body">
                                            Primary
                                            <div class="text-white-50 small">#4e73df</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card bg-success text-white shadow">
                                        <div class="card-body">
                                            Success
                                            <div class="text-white-50 small">#1cc88a</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card bg-info text-white shadow">
                                        <div class="card-body">
                                            Info
                                            <div class="text-white-50 small">#36b9cc</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card bg-warning text-white shadow">
                                        <div class="card-body">
                                            Warning
                                            <div class="text-white-50 small">#f6c23e</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card bg-danger text-white shadow">
                                        <div class="card-body">
                                            Danger
                                            <div class="text-white-50 small">#e74a3b</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card bg-secondary text-white shadow">
                                        <div class="card-body">
                                            Secondary
                                            <div class="text-white-50 small">#858796</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card bg-light text-black shadow">
                                        <div class="card-body">
                                            Light
                                            <div class="text-black-50 small">#f8f9fc</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card bg-dark text-white shadow">
                                        <div class="card-body">
                                            Dark
                                            <div class="text-white-50 small">#5a5c69</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="col-lg-6 mb-4">

                            <!-- Illustrations -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Illustrations</h6>
                                </div>
                                <div class="card-body">
                                    <div class="text-center">
                                        <img class="img-fluid px-3 px-sm-4 mt-3 mb-4" style="width: 25rem;"
                                            src="img/undraw_posting_photo.svg" alt="...">
                                    </div>
                                    <p>Add some quality, svg illustrations to your project courtesy of <a
                                            target="_blank" rel="nofollow" href="https://undraw.co/">unDraw</a>, a
                                        constantly updated collection of beautiful svg images that you can use
                                        completely free and without attribution!</p>
                                    <a target="_blank" rel="nofollow" href="https://undraw.co/">Browse Illustrations on
                                        unDraw &rarr;</a>
                                </div>
                            </div>

                            <!-- Approach -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Development Approach</h6>
                                </div>
                                <div class="card-body">
                                    <p>SB Admin 2 makes extensive use of Bootstrap 4 utility classes in order to reduce
                                        CSS bloat and poor page performance. Custom CSS classes are used to create
                                        custom components and custom utility classes.</p>
                                    <p class="mb-0">Before working with this theme, you should become familiar with the
                                        Bootstrap framework, especially the utility classes.</p>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Your Website 2021</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>


    <!-- Modal Dominios -->
    <div class="modal fade" id="modalDominios" tabindex="-1" role="dialog" aria-labelledby="modalDominiosLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalDominiosLabel">Dominios con pago este mes</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <?php if($cuenta_dominios > 0): ?>
            <ul class="list-group">
              <?php foreach($dominios as $d): ?>
                <li class="list-group-item d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center">
                    <i class="fas fa-globe text-primary mr-2"></i>
                   <strong class="ml-2"><?=htmlspecialchars($d['nombre_cliente'])?></strong>
                    <strong class="ml-2"><?=htmlspecialchars($d['nombre'])?></strong>
                    <span class="ml-2"><small>Pago: <?=date('d/m/Y', strtotime($d['fecha_limite_pago']))?></small></span>
                  </div>
                  <button class="btn btn-sm btn-outline-primary enviar-correo-btn ml-2"
                    data-id="<?=$d['id']?>"
                    data-manual="0"
                    data-tiposervicio="2"
                    title="Reenviar correo">
                    <i class="fas fa-envelope"></i>
                  </button>
                </li>
              <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p>No hay dominios con pago este mes.</p>
            <?php endif; ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Hostings -->
    <div class="modal fade" id="modalHostings" tabindex="-1" role="dialog" aria-labelledby="modalHostingsLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalHostingsLabel">Hostings con pago este mes</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <?php if($cuenta_hostings > 0): ?>
            <ul class="list-group">
              <?php foreach($hostings as $h): ?>
                <li class="list-group-item d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center">
                    <i class="fas fa-server text-success mr-2"></i>
                    <strong class="ml-2"><?=htmlspecialchars($h['nombre_cliente'])?></strong>
                    <strong class="ml-2"><?=htmlspecialchars($h['nombre'])?></strong>
                    <span class="ml-2"><small>Pago: <?=date('d/m/Y', strtotime($h['fecha_limite_pago']))?></small></span>
                  </div>
                  <button class="btn btn-sm btn-outline-primary enviar-correo-btn ml-2"
                    data-id="<?=$h['id']?>"
                    data-manual="0"
                    data-tiposervicio="1"
                    title="Reenviar correo">
                    <i class="fas fa-envelope"></i>
                  </button>
                </li>
              <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p>No hay hostings con pago este mes.</p>
            <?php endif; ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Pagos/Servicios -->
    <div class="modal fade" id="modalPagos" tabindex="-1" role="dialog" aria-labelledby="modalPagosLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalPagosLabel">Pagos y Servicios en el rango</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <?php if($cuenta_servicios > 0): ?>
            <ul class="list-group">
              <?php foreach($servicios as $s): ?>
                <li class="list-group-item d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center">
                    <?php if($s['tipo'] === 'Dominio'): ?>
                      <i class="fas fa-globe text-primary mr-2"></i>
                    <?php elseif($s['tipo'] === 'Hosting'): ?>
                      <i class="fas fa-server text-success mr-2"></i>
                    <?php elseif($s['tipo'] === 'Pago'): ?>
                      <i class="fas fa-money-bill-wave text-info mr-2"></i>
                    <?php endif; ?>
                    <span class="badge badge-secondary mr-2"><?=htmlspecialchars($s['tipo'])?></span>
                    <strong class="ml-2"><?=htmlspecialchars($s['nombre_cliente'])?></strong>
                    <strong class="ml-2"><?=htmlspecialchars($s['nombre'])?></strong>
                    <span class="ml-2"><small>Pago: <?=date('d/m/Y', strtotime($s['fecha_limite_pago']))?></small></span>
                  </div>
                  <button class="btn btn-sm btn-outline-primary enviar-correo-btn ml-2"
                    data-id="<?=$s['id']?>"
                    data-manual="<?=($s['tipo'] === 'Pago') ? 1 : 0?>"
                    data-tiposervicio="<?=$s['tipo_servicio']?>"
                    title="Reenviar correo">
                    <i class="fas fa-envelope"></i>
                  </button>
                </li>
              <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p>No hay pagos ni servicios en el rango seleccionado.</p>
            <?php endif; ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Pagos Realizados -->
    <div class="modal fade" id="modalPagosRealizados" tabindex="-1" role="dialog" aria-labelledby="modalPagosRealizadosLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalPagosRealizadosLabel">Pagos realizados en el rango</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <?php if($cuenta_pagados > 0): ?>
            <ul class="list-group">
              <?php foreach($pagos_pagados as $p): ?>
                <li class="list-group-item d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center">
                    <?php if($p['tipo_servicio'] == 2): ?>
                      <i class="fas fa-globe text-primary mr-2"></i>
                    <?php elseif($p['tipo_servicio'] == 1): ?>
                      <i class="fas fa-server text-success mr-2"></i>
                    <?php else: ?>
                      <i class="fas fa-money-bill-wave text-info mr-2"></i>
                    <?php endif; ?>
                    <span class="badge badge-secondary mr-2">
                      <?php echo $p['tipo_servicio'] == 2 ? 'Dominio' : ($p['tipo_servicio'] == 1 ? 'Hosting' : 'Pago'); ?>
                    </span>
                    <strong class="ml-2"><?=htmlspecialchars($p['nombre_cliente'])?></strong>
                    <strong class="ml-2"><?=htmlspecialchars($p['nombre'])?></strong>
                    <span class="ml-2"><small>Pago: <?=date('d/m/Y', strtotime($p['fecha_limite_pago']))?></small></span>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p>No hay pagos realizados en el rango seleccionado.</p>
            <?php endif; ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          </div>
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
    <script src="vendor/chart.js/Chart.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="js/demo/chart-area-demo.js"></script>
    <script src="js/demo/chart-pie-demo.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Mostrar todos los pagos en consola para debug
    <?php echo "const pagos = ".json_encode($pagos).";\nconsole.log('Pagos:', pagos);"; ?>

    $(document).on('click', '.enviar-correo-btn', function() {
        const pagoId = $(this).data('id');
        const manual = $(this).data('manual');
        const tiposervicio = $(this).data('tiposervicio');

        Swal.fire({
            title: '¿Enviar correo?',
            text: 'Se enviará 1 correo. ¿Deseas continuar?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, enviar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                let url = '';
                if (manual == 1) {
                    url = 'reenviar_correo_manual.php';
                } else if (tiposervicio == 1) {
                    url = 'reenviar_correos_hosting.php';
                } else {
                    url = 'reenviar_correos_dominios.php';
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id: pagoId,
                        ...(manual == 1 && {
                            pago_id: pagoId,
                            resend: 1
                        })
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Enviado', response.message, 'success');
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            title: 'Error',
                            html: `<p><strong>Status:</strong> ${status}</p>` +
                                  `<p><strong>Mensaje:</strong> ${error}</p>` +
                                  `<p><strong>Respuesta:</strong><br><pre>${xhr.responseText}</pre></p>`,
                            icon: 'error',
                            width: 600
                        });
                    }
                });
            }
        });
    });
    </script>

</body>

</html>