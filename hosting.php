<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
include "menu.php";
include "conn.php";

$sql = "SELECT h.*, c.nombre_contacto, c.facturacion
        FROM hosting h
        LEFT JOIN clientes c ON h.cliente_id = c.id
        ORDER BY h.fecha_pago >= CURDATE() DESC, h.fecha_pago ASC";
$result = $conn->query($sql);
// Verificar si hay resultados y convertirlos a array
$hosting_activos = [];
$hosting_eliminados = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (isset($row['eliminado']) && $row['eliminado'] == 1) {
            $hosting_eliminados[] = $row;
        } else {
            $hosting_activos[] = $row;
        }
    }
}
$sqlPagos = "SELECT * FROM pagos WHERE tipo_servicio = 1";
$resultPagos = $conn->query($sqlPagos);
// Verificar si hay resultados y convertirlos a array
$pagos = [];
if ($resultPagos && $resultPagos->num_rows > 0) {
    $pagos = $resultPagos->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tabla Hosting</title>
      <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
        
    <!-- Custom styles for this template -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Custom styles for this page -->
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
  </head>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">

    <!-- Main Content -->
    <div id="content">

        

        <!-- Begin Page Content -->
          <div class="container-xl my-4 py-4  bg-white rounded-4 shadow-sm border border-light-subtle" style="max-width: 96% !important;">

            <div class="d-sm-flex align-items-center  mb-4">
                <br>
                <h1 class="h3 mb-0 text-primary">Consulta de Hosting</h1>
            </div>
    <!-- Tabs para activos y eliminados -->
    <ul class="nav nav-tabs" id="hostingTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="activos-tab" data-toggle="tab" href="#activos" role="tab" aria-controls="activos" aria-selected="true">Activos</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="eliminados-tab" data-toggle="tab" href="#eliminados" role="tab" aria-controls="eliminados" aria-selected="false">Eliminados</a>
        </li>
    </ul>
    <div class="tab-content" id="hostingTabContent">
        <!-- TAB ACTIVOS -->
        <div class="tab-pane fade show active" id="activos" role="tabpanel" aria-labelledby="activos-tab">
            <div class="table-responsive mt-3">
                <?php if (!empty($hosting_activos)): ?>
                    <table class="table table-bordered" id="dataTableActivos" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ID Cliente</th>
                                <th>Nombre Contacto</th>
                                <th>Hosting</th>
                                <th>Estado</th>
                                <th>Tipo Servicio</th>
                                <th>Producto</th>
                                <th>Fecha Contratacion</th>
                                <th>Fecha Pago</th>
                                <th>Costo Producto</th>
                                <th>Tipo Moneda</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hosting_activos as $row): ?>
                                <tr>
                                    <td><?php echo $row["id_orden"]; ?></td>
                                    <td><?php echo $row["cliente_id"]; ?></td>
                                    <td><?php echo $row["nombre_contacto"]; ?></td>
                                    <td><?php echo $row["nom_host"]; ?></td>
                                    <td><?php if ($row["estado_producto"] == 1) {echo "Activo"; } if ($row["estado_producto"] == 0 ) { echo "Inactivo";}else{ echo ""; }?></td>
                                    <td><?php echo $row["tipo_producto"]; ?></td>
                                    <td><?php echo $row["producto"]; ?></td>
                                    <td><?php echo (!empty($row["fecha_contratacion"]) && $row["fecha_contratacion"] !== '0000-00-00') ? date("d M Y", strtotime($row["fecha_contratacion"])) : ""; ?></td>
                                    <td><?php echo (!empty($row["fecha_pago"]) && $row["fecha_pago"] !== '0000-00-00') ? date("d M Y", strtotime($row["fecha_pago"])): "";?></td>
                                    <td><?php echo (!empty($row["costo_producto"]) && $row["costo_producto"] !== '000') ? $row["costo_producto"] : ""; ?></td>
                                    <td><?php if ($row["id_forma_pago"] == 1) {echo "MXN"; } if ($row["id_forma_pago"] == 2 ) { echo "USD";}else{ echo "";}?></td>
                                    <td>
                                        <button class="btn btn-sm btn-success enviar-correo-btn" data-id="<?php echo $row["id_orden"]; ?>">
                                            <i class="fas fa-paper-plane"></i> Enviar correo
                                        </button>
                                        <button class="btn btn-sm btn-warning editar-info-btn" data-id="<?php echo $row["id_orden"]; ?>" data-toggle="modal" data-target=".bd-example-modal-lg">
                                            <i class="bi bi-pencil-fill"></i> Editar información
                                        </button>
                                        <button class="btn btn-sm btn-danger eliminar-hosting-btn" data-id="<?php echo $row["id_orden"]; ?>">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No se encontraron hostings activos.</p>
                <?php endif; ?>
            </div>
        </div>
        <!-- TAB ELIMINADOS -->
        <div class="tab-pane fade" id="eliminados" role="tabpanel" aria-labelledby="eliminados-tab">
            <div class="table-responsive mt-3">
                <?php if (!empty($hosting_eliminados)): ?>
                    <table class="table table-bordered" id="dataTableEliminados" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ID Cliente</th>
                                <th>Nombre Contacto</th>
                                <th>Dominio</th>
                                <th>Estado</th>
                                <th>Tipo Servicio</th>
                                <th>Producto</th>
                                <th>Fecha Contratacion</th>
                                <th>Fecha Pago</th>
                                <th>Costo Producto</th>
                                <th>Tipo Moneda</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hosting_eliminados as $row): ?>
                                <tr>
                                    <td><?php echo $row["id_orden"]; ?></td>
                                    <td><?php echo $row["cliente_id"]; ?></td>
                                    <td><?php echo $row["nombre_contacto"]; ?></td>
                                    <td><?php echo $row["dominio"]; ?></td>
                                    <td><?php if ($row["estado_producto"] == 1) {echo "Activo"; } if ($row["estado_producto"] == 0 ) { echo "Inactivo";}else{ echo ""; }?></td>
                                    <td><?php echo $row["tipo_producto"]; ?></td>
                                    <td><?php echo $row["producto"]; ?></td>
                                    <td><?php echo (!empty($row["fecha_contratacion"]) && $row["fecha_contratacion"] !== '0000-00-00') ? date("d M Y", strtotime($row["fecha_contratacion"])) : ""; ?></td>
                                    <td><?php echo (!empty($row["fecha_pago"]) && $row["fecha_pago"] !== '0000-00-00') ? date("d M Y", strtotime($row["fecha_pago"])): "";?></td>
                                    <td><?php echo (!empty($row["costo_producto"]) && $row["costo_producto"] !== '000') ? $row["costo_producto"] : ""; ?></td>
                                    <td><?php if ($row["id_forma_pago"] == 1) {echo "MXN"; } if ($row["id_forma_pago"] == 2 ) { echo "USD";}else{ echo "";}?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary restaurar-hosting-btn" data-id="<?php echo $row["id_orden"]; ?>">
                                            <i class="fas fa-undo"></i> Restaurar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No se encontraron hostings eliminados.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Edición -->
<div class="modal fade bd-example-modal-xl" id="editarHostingModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Editar Hosting</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Contenedor para el iframe -->
        <div id="iframeContainer" style="display:none; min-height:900px;"></div>
        <!-- Formulario original ocultable -->
        <form id="formEditarHosting">
          <input type="hidden" name="id_orden" id="edit_id_orden">
          <input type="hidden" id="edit_facturacion">
          <input type="hidden" name="cliente" id="edit_cliente_id">
          <input type="hidden" name="contrasena" id="edit_contrasena">
          <input type="hidden" name="modo_edicion" value="1">
          <!-- Sección 1: Información Básica -->
          <fieldset class="col-12 border p-3 mb-4 rounded">
            <legend class="float-none w-auto px-2" style="color: #000147;">Información Personal</legend>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="edit_nombre" class="form-label">Cliente</label>
                <input type="text" class="form-control" id="edit_nombre" name="nombre" disabled>
              </div>
                <div class="col-md-6 mb-3">
                <label for="edit_nom_host" class="form-label">Nombre Host</label>
                <input type="text" class="form-control" id="edit_nom_host" name="nom_host">
              </div>
              <div class="col-md-6 mb-3">
                <label for="edit_tipo_producto" class="form-label">Tipo Producto</label>
                <input type="text" class="form-control" id="edit_tipo_producto" name="tipo_producto">
              </div>
              <div class="col-md-6 mb-3">
                <label for="edit_dominio" class="form-label">Dominio</label>
                <input type="text" class="form-control" id="edit_dominio" name="dominio">
              </div>
              <div class="col-md-6 mb-3">
                <label for="edit_producto" class="form-label">Plan</label>
                <select class="form-control" id="edit_producto" name="producto">
                  <option value="" selected disabled>Selecciona un Producto</option>
                  <?php
                  // Obtener los planes desde la tabla "planes"
                  include_once 'conn.php';
                  $sql_planes = "SELECT id, nombre, precio FROM planes ORDER BY id";
                  $result_planes = $conn->query($sql_planes);
                  if ($result_planes && $result_planes->num_rows > 0) {
                    while ($row = $result_planes->fetch_assoc()) {
                      echo '<option value="' . $row['id'] . '" data-precio="' . $row['precio'] . '">' . htmlspecialchars($row['nombre']) . '</option>';
                    }
                  }
                  ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label for="edit_estado_producto" class="form-label">Estado</label>
                <select class="form-control" id="edit_estado_producto" name="estado_producto">
                  <option value="1">Activo</option>
                  <option value="0">Inactivo</option>
                </select>
              </div>
              
            </div>
            
          </fieldset>
          
          <!-- Sección 2: Fechas y Costos -->
          <fieldset class="col-12 border p-3 mb-4 rounded">
            <legend class="float-none w-auto px-2" style="color: #000147;">Fechas y Costos</legend>
            
            <div class="row">
              <div class="col-md-4 mb-3">
                <label for="edit_fecha_contratacion" class="form-label">Fecha Contratación</label>
                <input type="date" class="form-control" id="edit_fecha_contratacion" name="fecha_contratacion">
              </div>
              <div class="col-md-4 mb-3">
                <label for="edit_fecha_pago" class="form-label">Fecha Pago</label>
                <input type="date" class="form-control" id="edit_fecha_pago" name="fecha_pago">
              </div>
              <div class="col-md-4 mb-3">
                <label for="edit_costo_producto" class="form-label">Costo</label>
                <div class="input-group">
                  <input type="number" step="0.01" class="form-control" id="edit_costo_producto" name="costo_producto">
                  <span class="input-group-text" id="ivaIndicator">+IVA</span>
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="edit_id_forma_pago" class="form-label">Moneda</label>
                <select class="form-control" id="edit_id_forma_pago" name="id_forma_pago">
                  <option value="1">MXN</option>
                  <option value="2">USD</option>
                </select>
              </div>
            </div>
          </fieldset>
          
          
          <!-- Sección 3: Configuracion DNS -->
          <fieldset class="col-12 border p-3 mb-4 rounded">
            <legend class="float-none w-auto px-2" style="color: #000147;">Configuracion DNS</legend>
            
        <div class="row">
            
            <div class="col-md-4 mb-3">
                <label for="edit_dns" class="form-label">DNS</label>
                <input type="text" class="form-control" id="edit_dns" name="dns" >
              </div>
           <div class="col-md-4 mb-3">
                <label for="edit_NS1" class="form-label">Nameserver 1 (NS1)</label>
                <input type="text" class="form-control" id="edit_NS1" name="ns1">
              </div>
              <div class="col-md-4 mb-3">
                <label for="edit_NS2" class="form-label">Nameserver 2 (NS2)</label>
                <input type="text" class="form-control" id="edit_NS2" name="ns2">
              </div>
              <div class="col-md-4 mb-3">
                <label for="edit_NS3" class="form-label">Nameserver 3 (NS3)</label>
                <input type="text" class="form-control" id="edit_NS3" name="ns3">
              </div>

            <div class="col-md-4 mb-3">
                <label for="edit_NS1" class="form-label">Nameserver 4 (NS4)</label>
                <input type="text" class="form-control" id="edit_NS4" name="ns4">
              </div>
              <div class="col-md-4 mb-3">
                <label for="edit_NS5" class="form-label">Nameserver 5 (NS5)</label>
                <input type="text" class="form-control" id="edit_NS5" name="ns5" >
              </div>
              <div class="col-md-4 mb-3">
                <label for="edit_NS6" class="form-label">Nameserver 6 (NS6)</label>
                <input type="text" class="form-control" id="edit_NS6" name="ns6" >
              </div>
              </div>
          </fieldset>
          
          <!-- Sección 3: Credenciales -->
          <fieldset class="col-12 border p-3 mb-4 rounded">
            <legend class="float-none w-auto px-2" style="color: #000147;">Credenciales</legend>
            
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="edit_usuario_admin" class="form-label">Usuario Admin</label>
                <input type="text" class="form-control" id="edit_usuario_admin" name="usuario">
              </div>
              <div class="col-md-6 mb-3">
                <label for="edit_contrasena_admin" class="form-label">Contraseña Admin</label>
                <div class="input-group">
                  <input type="password" class="form-control" id="edit_contrasena_admin" name="contrasena_normal">
                  <button class="btn btn-outline-secondary toggle-password" type="button">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>
            </div>

            
            
          </fieldset>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
          </div>
        </form>
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
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="js/demo/datatables-demo.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
   $(document).ready(function() {
  let pagos = <?php echo json_encode($pagos); ?>;
  let hosting = <?php echo json_encode(array_merge($hosting_activos, $hosting_eliminados)); ?>;

  console.log("Datos de pagos:", pagos);
  console.log("Datos de hosting:", hosting);
  
  $('.enviar-correo-btn').click(function() {
    const hostingId = $(this).data('id');
    console.log("ID del hosting para enviar correo:", hostingId);

    let pagosFiltrados = pagos.filter((pago) => pago.id_servicio == hostingId && pago.estatus == 0);
    console.log("Pagos filtrados:", pagosFiltrados);

    if (pagosFiltrados.length > 0) {
      Swal.fire({
        title: '¿Enviar correos?',
        text: `Se enviarán ${pagosFiltrados.length} correos. ¿Deseas continuar?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          pagosFiltrados.forEach(pago => {
            $.ajax({
              url: 'reenviar_correos_hosting.php',
              type: 'POST',
              dataType: 'json',
              data: { id: pago.id },
              success: function(response) {
                if (response.success) {
                  Swal.fire('Enviado', response.message, 'success');
                } else {
                  Swal.fire('Error', response.message, 'error');
                }
              },
              error: function() {
                Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
              }
            });
          });
        }
      });
    }
  });


 // Función para calcular el costo con IVA
  function calcularCostoConIVA() {
    const costo = parseFloat($('#edit_costo_producto').val()) || 0;
    const facturacion = $('#edit_facturacion').val();
    
    if (facturacion == 1) { // Si el cliente requiere facturación (IVA)
      const costoConIVA = costo * 1.16; // 16% de IVA
      $('#edit_costo_producto').val(costoConIVA.toFixed(2));
    }
  }
  
  function actualizarIndicadorIVA() {
    const facturacion = $('#edit_facturacion').val();
    const ivaIndicator = $('#ivaIndicator');
    
    if (facturacion == 1) {
      ivaIndicator.text('+IVA').removeClass('text-muted').addClass('text-danger');
    } else {
      ivaIndicator.text('Sin IVA').removeClass('text-danger').addClass('text-muted');
    }
  }
  
  // Escuchar cambios en el campo de costo
  $('#edit_costo_producto').on('blur', calcularCostoConIVA);
  
  
  
   $(document).on('click', '.editar-info-btn', function() {
    const id_orden = $(this).data('id');
    console.log("ID del hosting a editar:", id_orden);
    
    // Cambié el nombre de la variable para evitar conflicto con el array
    const hostingEncontrado = hosting.find(h => h.id_orden == id_orden);
    console.log("Datos del hosting encontrado:", hostingEncontrado);
    
    if (hostingEncontrado) {
      // Llenar el formulario con los datos del hosting
      $('#edit_id_orden').val(hostingEncontrado.id_orden);
      $('#edit_cliente_id').val(hostingEncontrado.cliente_id || '');
      $('#edit_nombre').val(hostingEncontrado.nombre_contacto || '');
      $('#edit_dominio').val(hostingEncontrado.dominio || '');
      $('#edit_nom_host').val(hostingEncontrado.nom_host || '');
      $('#edit_tipo_producto').val(hostingEncontrado.tipo_producto || '');
      $('#edit_producto').val(hostingEncontrado.producto || '');
      $('#edit_estado_producto').val(hostingEncontrado.estado_producto || '');
      $('#edit_hosting').val(hostingEncontrado.hosting || '');
      $('#edit_fecha_contratacion').val(hostingEncontrado.fecha_contratacion || '');
      $('#edit_fecha_pago').val(hostingEncontrado.fecha_pago || '');
      $('#edit_costo_producto').val(hostingEncontrado.costo_producto || '');
      $('#edit_id_forma_pago').val(hostingEncontrado.id_forma_pago || '');
      $('#edit_dns').val(hostingEncontrado.dns || '');
      $('#edit_NS1').val(hostingEncontrado.ns1 || '');
      $('#edit_NS2').val(hostingEncontrado.ns2 || '');
      $('#edit_NS3').val(hostingEncontrado.ns3 || '');
      $('#edit_NS4').val(hostingEncontrado.ns4 || '');
      $('#edit_NS5').val(hostingEncontrado.ns5 || '');
      $('#edit_NS6').val(hostingEncontrado.ns6 || '');
      // Cambié hosting por hostingEncontrado
      $('#edit_usuario_admin').val(hostingEncontrado.usuario || '');
      $('#edit_contrasena_admin').val(hostingEncontrado.contrasena_normal || '');
      $('#edit_contrasena').val(hostingEncontrado.contrasena_normal || '');

      
      // Agregar el valor de facturación al campo oculto
      $('#edit_facturacion').val(hostingEncontrado.facturacion || 0);
      
      // Actualizar el indicador de IVA
      actualizarIndicadorIVA();
      
      // Mostrar el iframe y ocultar el formulario
      const id_hosting = hostingEncontrado.id_orden;
      const id_cliente = hostingEncontrado.cliente_id;
      const url = `https://adm.conlineweb.com/formulario_hosting.php?edit=1&id_hosting=${id_hosting}&id_cliente=${id_cliente}&iframe=1&novalid=1`;
      $('#iframeContainer').html(`<iframe src="${url}" width="100%" height="900" frameborder="0" style="border-radius:10px;"></iframe>`).show();
      $('#formEditarHosting').hide();
      
      // Mostrar el modal
      $('#editarHostingModal').modal('show');
    } else {
      console.error("No se encontró el hosting con ID:", id_orden);
    }
  });

  // Toggle para mostrar/ocultar contraseña
  $(document).on('click', '.toggle-password', function() {
    const input = $(this).siblings('input');
    const icon = $(this).find('i');
    
    if (input.attr('type') === 'password') {
      input.attr('type', 'text');
      icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
      input.attr('type', 'password');
      icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
  });

  // Manejo del formulario de actualización
  $(document).on('submit', '#formEditarHosting', function(e) {
    e.preventDefault();
    
    // Antes de serializar, igualar contrasena a contrasena_normal
    $('#edit_contrasena').val($('#edit_contrasena_admin').val());
    const formData = $(this).serialize();
    console.log("Datos a enviar:", formData);
    
    // Validación básica de campos requeridos
    if (!$('#edit_nom_host').val() || !$('#edit_dominio').val()) {
      Swal.fire({
        title: 'Campos requeridos',
        text: 'URL del dominio y nombre Host son campos obligatorios',
        icon: 'warning',
        confirmButtonText: 'Entendido'
      });
      return;
    }

    Swal.fire({
      title: '¿Guardar cambios?',
      text: "¿Estás seguro de que deseas actualizar este hosting?",
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Sí, guardar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({
          title: 'Actualizando hosting',
          html: 'Por favor espera...',
          allowOutsideClick: false,
          didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
          url: 'actualizar_hosting.php',
          type: 'POST',
          dataType: 'json',
          data: formData,
          success: function(response) {
              console.log(response)
            Swal.close();
            if (response.success) {
              Swal.fire({
                title: '¡Actualizado!',
                text: 'El hosting se ha actualizado correctamente',
                icon: 'success',
                confirmButtonText: 'OK'
              }).then(() => {
                $('#editarHostingModal').modal('hide');
                location.reload();
              });
            } else {
              Swal.fire({
                title: 'Error',
                text: response.message || 'Ocurrió un error al actualizar',
                icon: 'error',
                confirmButtonText: 'OK'
              });
            }
          },
          error: function(xhr, status, error) {
                            console.log(xhr)
                            console.log(status)
                            console.log(error)

            Swal.fire({
              title: 'Error',
              text: 'Error al conectar con el servidor: ' + error,
              icon: 'error',
              confirmButtonText: 'OK'
            });
          }
        });
      }
    });
  });
  
  // --- Lógica de actualización dinámica de precio en el modal de edición ---
const tipoCambio = 19.01; // Tipo de cambio MXN a USD

function actualizarPrecioEditar() {
  const planOption = $('#edit_producto option:selected');
  let precioBase = parseFloat(planOption.data('precio')) || 0;
  const moneda = $('#edit_id_forma_pago').val();
  const facturacion = $('#edit_facturacion').val();
  let precioFinal = precioBase;
  let monedaTexto = 'MXN';

  if (moneda === '2') { // USD
    precioBase = (precioBase / tipoCambio);
    monedaTexto = 'USD';
  }
  precioFinal = precioBase;
  if (facturacion == 1) {
    precioFinal = (precioBase * 1.16);
  }
  // Redondear a 2 decimales
  precioFinal = precioFinal ? precioFinal.toFixed(2) : '';
  $('#edit_costo_producto').val(precioFinal);
  // Actualizar el indicador de IVA
  actualizarIndicadorIVA();
}

// Al cambiar plan, moneda o facturación, actualizar precio
$('#edit_producto, #edit_id_forma_pago').on('change', function() {
  actualizarPrecioEditar();
});

// Al abrir el modal y cargar datos, también actualizar el precio
$(document).on('click', '.editar-info-btn', function() {
  setTimeout(actualizarPrecioEditar, 200); // Espera a que se seleccione el plan
});

// --- Lógica de eliminación de hosting ---
$(document).on('click', '.eliminar-hosting-btn', function() {
  const id_orden = $(this).data('id');
  console.log(id_orden)
  Swal.fire({
    title: '¿Eliminar hosting?',
    text: '¿Estás seguro de que deseas eliminar este hosting?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire({
        title: 'Eliminando...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
      });
      $.ajax({
        url: 'eliminar_hosting.php',
        type: 'POST',
        dataType: 'json',
        data: { id: id_orden, eliminar: 1 }, // Cambiado de restaurar:0 a eliminar:1
        success: function(response) {
            console.log(response)
          Swal.close();
          if (response.success) {
              
            Swal.fire({
              title: '¡Eliminado!',
              text: 'El hosting ha sido eliminado correctamente',
              icon: 'success',
              confirmButtonText: 'OK'
            }).then(() => {
              location.reload();
            });
          } else {
            Swal.fire('Error', response.message || 'No se pudo eliminar', 'error');
          }
        },
        error: function(xhr, status, error) {
          Swal.fire('Error', 'Error al conectar con el servidor: ' + error, 'error');
        }
      });
    }
  });
});

// --- Lógica de restaurar hosting ---
$(document).on('click', '.restaurar-hosting-btn', function () {
  const id_orden = $(this).data('id');
  Swal.fire({
    title: '¿Restaurar hosting?',
    text: '¿Estás seguro de que deseas restaurar este hosting?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, restaurar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire({
        title: 'Restaurando...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
      });
      $.ajax({
        url: 'eliminar_hosting.php',
        type: 'POST',
        dataType: 'json',
        data: { id: id_orden, eliminar: 0 },
        success: function(response) {
          Swal.close();
          if (response.success) {
            Swal.fire({
              title: '¡Restaurado!',
              text: 'El hosting ha sido restaurado correctamente',
              icon: 'success',
              confirmButtonText: 'OK'
            }).then(() => {
              location.reload();
            });
          } else {
            Swal.fire('Error', response.message || 'No se pudo restaurar', 'error');
          }
        },
        error: function(xhr, status, error) {
          Swal.fire('Error', 'Error al conectar con el servidor: ' + error, 'error');
        }
      });
    }
  });
});

// --- Inicialización de DataTables en tabs ---
  $('#hostingTab').on('shown.bs.tab', function (e) {
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
    $.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
  });
  // Forzar la inicialización de la tabla activa al cargar la página
  $('#activos-tab').trigger('shown.bs.tab');
});
</script>

</body>

</html>