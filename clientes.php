<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set("display_errors", 1);
include "menu.php";
include "conn.php";

$sql = "SELECT id, empresa, nombre_contacto, correo,telefono, actualizado, eliminado FROM clientes";
$result = $conn->query($sql);

$clientes = array();
$clientes_activos = array();
$clientes_eliminados = array();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
        if (isset($row['eliminado']) && $row['eliminado'] == 1) {
            $clientes_eliminados[] = $row;
        } else {
            $clientes_activos[] = $row;
        }
    }
    $result->data_seek(0);
}
?>

<!DOCTYPE html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tabla Clientes</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
        
    <!-- Custom styles for this template -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Custom styles for this page -->
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    
            <style>
        .btn-primary {
            background-color: #000147;
            border-color: #000147;
}

    </style> 
  </head>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">

    <!-- Main Content -->
    <div id="content">
        
         <div class="container-xl my-4 py-4  bg-white rounded-4 shadow-sm border border-light-subtle" style="max-width: 90% !important;">

            <div class="d-sm-flex align-items-center  mb-4">
                <br>
                <h1 class="h3 mb-0 text-primary">Consulta de Clientes</h1>
            </div>
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" id="clientesTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="activos-tab" data-bs-toggle="tab" data-bs-target="#activos" type="button" role="tab" aria-controls="activos" aria-selected="true">Activos</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="eliminados-tab" data-bs-toggle="tab" data-bs-target="#eliminados" type="button" role="tab" aria-controls="eliminados" aria-selected="false">Eliminados</button>
              </li>
            </ul>
            <div class="tab-content" id="clientesTabsContent">
              <div class="tab-pane fade show active" id="activos" role="tabpanel" aria-labelledby="activos-tab">
                <div class="table-responsive mt-3">
                  <?php if (count($clientes_activos) > 0): ?>
                  <table class="table table-bordered" id="dataTableActivos" width="100%" cellspacing="0">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Nombre Empresa</th>
                        <th>Nombre Contacto</th>
                        <th>Correo</th>
                        <th>Numero Contacto</th>
                        <th>Actualizado</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($clientes_activos as $row): ?>
                      <tr>
                        <td scope="col"><?php echo $row["id"]; ?></td>
                        <td scope="col"><?php echo $row["empresa"]; ?></td>
                        <td scope="col"><?php echo $row["nombre_contacto"]; ?></td>
                        <td scope="col"><?php echo $row["correo"]; ?></td>
                        <td scope="col"><?php echo $row["telefono"]; ?></td>
                        <td scope="col"><?php echo $row["actualizado"]; ?></td>
                        <td>
                          <button onclick="window.location.href='detalle_cliente.php?id=<?php echo $row["id"]; ?>'" class="btn btn-primary btn-sm mx-2 my-1">Ver Info</button>
                          <button onclick="eliminarCliente(<?php echo $row['id']; ?>)" class="btn btn-danger btn-sm mx-2 my-1">Eliminar</button>
                          <button onclick="mandarWhatsApp('<?php echo $row['telefono']; ?>', <?php echo $row['id']; ?>)" class="btn btn-success btn-sm mx-2 my-1">WhatsApp</button>
                          

                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                  <?php else: ?>
                  <p>No se encontraron clientes activos.</p>
                  <?php endif; ?>
                </div>
              </div>
              <div class="tab-pane fade" id="eliminados" role="tabpanel" aria-labelledby="eliminados-tab">
                <div class="table-responsive mt-3">
                  <?php if (count($clientes_eliminados) > 0): ?>
                  <table class="table table-bordered" id="dataTableEliminados" width="100%" cellspacing="0">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Nombre Empresa</th>
                        <th>Nombre Contacto</th>
                        <th>Correo</th>
                        <th>Actualizado</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($clientes_eliminados as $row): ?>
                      <tr>
                        <td scope="col"><?php echo $row["id"]; ?></td>
                        <td scope="col"><?php echo $row["empresa"]; ?></td>
                        <td scope="col"><?php echo $row["nombre_contacto"]; ?></td>
                        <td scope="col"><?php echo $row["correo"]; ?></td>
                        <td scope="col"><?php echo $row["actualizado"]; ?></td>
                        <td>
                          <button onclick="restaurarCliente(<?php echo $row['id']; ?>)" class="btn btn-success btn-sm mx-2 my-1">Restaurar</button>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                  <?php else: ?>
                  <p>No se encontraron clientes eliminados.</p>
                  <?php endif; ?>
                </div>
              </div>
            </div>


    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <!-- Page level custom scripts -->
    <script src="js/demo/datatables-demo.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    
    
    var clientesData = <?php echo json_encode($clientes); ?>;
    
 
  $(document).ready(function() {
    // Inicializar DataTables después de que los tabs estén completamente cargados
    $('#clientesTabs').on('shown.bs.tab', function (e) {
        // Verificar si las tablas ya están inicializadas
        if (!$.fn.DataTable.isDataTable('#dataTableActivos')) {
            $('#dataTableActivos').DataTable({
                searching: true,
                language: {
                    search: "Buscar:",
                    searchPlaceholder: "Ingrese término..."
                }
            });
        }
        
        if (!$.fn.DataTable.isDataTable('#dataTableEliminados')) {
            $('#dataTableEliminados').DataTable({
                searching: true,
                language: {
                    search: "Buscar:",
                    searchPlaceholder: "Ingrese término..."
                }
            });
        }
        
        // Ajustar las tablas después de mostrarse
        $.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
    });
    
    // Forzar la inicialización de la tabla activa al cargar la página
    $('#activos-tab').trigger('shown.bs.tab');
});


    function eliminarCliente(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'eliminar_cliente.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('¡Eliminado!', 'El cliente ha sido eliminado.', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', 'No se pudo eliminar el cliente.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Ocurrió un error en la petición.', 'error');
                    }
                });
            }
        });
    }

    function restaurarCliente(id) {
        Swal.fire({
            title: '¿Restaurar cliente?',
            text: "¿Deseas activar este cliente de nuevo?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, restaurar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'eliminar_cliente.php',
                    type: 'POST',
                    data: { id: id, restaurar: 1 },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('¡Restaurado!', 'El cliente ha sido activado.', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', 'No se pudo restaurar el cliente.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Ocurrió un error en la petición.', 'error');
                    }
                });
            }
        });
    }

    
    
    function mandarWhatsApp(telefono, id) {
    let numero = telefono.replace(/[\s\-\(\)\+]/g, '');

    if (numero.startsWith('00')) {
        numero = numero.substring(2);
    }
    if (/^\d{10}$/.test(numero)) {
        numero = '52' + numero;
    }
    if (numero.length < 10 || numero.length > 15) {
        Swal.fire('Número inválido', 'El número de WhatsApp no tiene el formato adecuado.', 'error');
        return;
    }

    const mensaje = ` ¡Hola!
Desde C-onlineWeb queremos asegurarnos de que sigas recibiendo notificaciones importantes sobre tus servicios (renovaciones, vencimientos y recordatorios de pago), para que no te quedes sin tus servicios activos.

Por favor, confirma o actualiza tus datos de contacto en el siguiente enlace:
https://adm.conlineweb.com/validacion-cliente.php?id=${id}

El proceso es rápido, seguro y te tomará menos de un minuto.

¡Gracias por tu atención y preferencia!
— Equipo C-onlineWeb`;


    const url = `https://wa.me/${numero}?text=${encodeURIComponent(mensaje)}`;
    window.open(url, '_blank');
    
}

</script>

</body>

</html>