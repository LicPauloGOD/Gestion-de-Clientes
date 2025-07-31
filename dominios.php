<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
include "menu.php";
include "conn.php";

// Consulta principal para obtener todos los dominios ordenados
$sql = "SELECT d.*, c.nombre_contacto, c.facturacion
        FROM dominios d
        LEFT JOIN clientes c ON d.cliente_id = c.id
        ORDER BY d.fecha_pago >= CURDATE() DESC, d.fecha_pago ASC";
$result = $conn->query($sql);

$dominios_activos = [];
$dominios_eliminados = [];
$dominios = [];

if ($result && $result->num_rows > 0) {
    $dominios = $result->fetch_all(MYSQLI_ASSOC);
    
    foreach ($dominios as $row) {
        if (isset($row['eliminado']) && $row['eliminado'] == 1) {
            $dominios_eliminados[] = $row;
        } else {
            $dominios_activos[] = $row;
        }
    }
}

$sqlPagos = "SELECT * FROM pagos WHERE tipo_servicio = 2";
$resultPagos = $conn->query($sqlPagos);
$pagos = [];
if ($resultPagos && $resultPagos->num_rows > 0) {
    $pagos = $resultPagos->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tabla Dominios</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>
<body>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <div class="container-xl my-4 py-4 bg-white rounded-4 shadow-sm border border-light-subtle" style="max-width: 90% !important;">
                <div class="d-sm-flex align-items-center mb-4">
                    <h1 class="h3 mb-0 text-primary">Dominios</h1>
                </div>
                
                <ul class="nav nav-tabs" id="dominiosTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="activos-tab" data-toggle="tab" href="#activos" role="tab" aria-controls="activos" aria-selected="true">Activos</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="eliminados-tab" data-toggle="tab" href="#eliminados" role="tab" aria-controls="eliminados" aria-selected="false">Eliminados</a>
                    </li>
                </ul>
                
                <div class="tab-content" id="dominiosTabContent">
                    <!-- TAB ACTIVOS -->
                    <div class="tab-pane fade show active" id="activos" role="tabpanel" aria-labelledby="activos-tab">
                        <div class="table-responsive mt-3">
                            <?php if (count($dominios_activos) > 0): ?>
                                <table class="table table-bordered" id="dataTableActivos" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>ID Cliente</th>
                                            <th>Nombre Cliente</th>
                                            <th>Estado</th>
                                            <th>Registrado</th>
                                            <th>Proveedor</th>
                                            <th>URL</th>
                                            <th>Fecha Pago</th>
                                            <th>Costo Dominio</th>
                                            <th>Tipo Moneda</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dominios_activos as $row): ?>
                                            <tr>
                                                <td><?php echo $row["id_dominio"]; ?></td>
                                                <td><?php echo $row["cliente_id"]; ?></td>
                                                <td><?php echo $row["nombre_contacto"]; ?></td>
                                                <td><?php echo $row["estado_dominio"] == 1 ? "Activo" : ($row["estado_dominio"] == 0 ? "Inactivo" : ""); ?></td>
                                                <td><?php echo $row["registrado"] == 1 ? "Registrado" : ($row["registrado"] == 0 ? "No registrado" : ""); ?></td>
                                                <td><?php echo $row["proveedor"]; ?></td>
                                                <td><?php echo $row["url_dominio"]; ?></td>
                                                <td><?php echo !empty($row["fecha_pago"]) && $row["fecha_pago"] !== "0000-00-00" ? date("d M Y", strtotime($row["fecha_pago"])) : ""; ?></td>
                                                <td><?php echo $row["costo_dominio"]; ?></td>
                                                <td><?php echo $row["id_forma_pago"] == 1 ? "MXN" : ($row["id_forma_pago"] == 2 ? "USD" : ""); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-success enviar-correo-btn" data-id="<?php echo $row["id_dominio"]; ?>">
                                                        <i class="fas fa-paper-plane"></i> Enviar correo
                                                    </button>
                                                    <button class="btn btn-sm btn-warning editar-info-btn" data-id="<?php echo $row["id_dominio"]; ?>" data-toggle="modal" data-target="#editarDominioModal">
                                                        <i class="fas fa-pencil-alt"></i> Editar
                                                    </button>
                                                    <button class="btn btn-sm btn-danger eliminar-dominio-btn" data-id="<?php echo $row["id_dominio"]; ?>">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-muted">No se encontraron dominios activos.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- TAB ELIMINADOS -->
                    <div class="tab-pane fade" id="eliminados" role="tabpanel" aria-labelledby="eliminados-tab">
                        <div class="table-responsive mt-3">
                            <?php if (count($dominios_eliminados) > 0): ?>
                                <table class="table table-bordered" id="dataTableEliminados" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>ID Cliente</th>
                                            <th>Nombre Cliente</th>
                                            <th>Estado</th>
                                            <th>Registrado</th>
                                            <th>Proveedor</th>
                                            <th>URL</th>
                                            <th>Fecha Pago</th>
                                            <th>Costo Dominio</th>
                                            <th>Tipo Moneda</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dominios_eliminados as $row): ?>
                                            <tr>
                                                <td><?php echo $row["id_dominio"]; ?></td>
                                                <td><?php echo $row["cliente_id"]; ?></td>
                                                <td><?php echo $row["nombre_contacto"]; ?></td>
                                                <td><?php echo $row["estado_dominio"] == 1 ? "Activo" : ($row["estado_dominio"] == 0 ? "Inactivo" : ""); ?></td>
                                                <td><?php echo $row["registrado"] == 1 ? "Registrado" : ($row["registrado"] == 0 ? "No registrado" : ""); ?></td>
                                                <td><?php echo $row["proveedor"]; ?></td>
                                                <td><?php echo $row["url_dominio"]; ?></td>
                                                <td><?php echo !empty($row["fecha_pago"]) && $row["fecha_pago"] !== "0000-00-00" ? date("d M Y", strtotime($row["fecha_pago"])) : ""; ?></td>
                                                <td><?php echo $row["costo_dominio"]; ?></td>
                                                <td><?php echo $row["id_forma_pago"] == 1 ? "MXN" : ($row["id_forma_pago"] == 2 ? "USD" : ""); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary restaurar-dominio-btn" data-id="<?php echo $row["id_dominio"]; ?>">
                                                        <i class="fas fa-undo"></i> Restaurar
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-muted">No se encontraron dominios eliminados.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edición con iframe -->
    <div class="modal fade" id="editarDominioModal" tabindex="-1" role="dialog" aria-labelledby="editarDominioModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarDominioModalLabel">Editar Dominio</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0" style="height:80vh;">
                    <iframe id="iframeEditarDominio" src="" style="width:100%;height:100%;border:none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function () {
            let pagos = <?php echo json_encode($pagos); ?>;
            let dominios = <?php echo json_encode($dominios); ?>;
            
            // Inicializar DataTables
            $('#dataTableActivos').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
                }
            });
            
            $('#dataTableEliminados').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
                }
            });

            // Función para calcular el costo con IVA
            function calcularCostoConIVA() {
                const costo = parseFloat($('#edit_costo_dominio').val()) || 0;
                const facturacion = $('#edit_facturacion').val();

                if (facturacion == 1) { // Si el cliente requiere facturación (IVA)
                    const costoConIVA = costo * 1.16; // 16% de IVA
                    $('#edit_costo_dominio').val(costoConIVA.toFixed(2));
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
            $('#edit_costo_dominio').on('blur', calcularCostoConIVA);

            // Enviar correo
            $('.enviar-correo-btn').click(function () {
                const dominioId = $(this).data('id');
                let pagosFiltrados = pagos.filter((pago) => pago.id_servicio == dominioId && pago.estatus == 0);

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
                            Swal.fire({
                                title: 'Enviando correos...',
                                allowOutsideClick: false,
                                didOpen: () => { Swal.showLoading(); }
                            });
                            
                            let promises = pagosFiltrados.map(pago => {
                                return $.ajax({
                                    url: 'reenviar_correos_dominios.php',
                                    type: 'POST',
                                    dataType: 'json',
                                    data: { id: pago.id }
                                });
                            });

                            Promise.all(promises).then(function(results) {
                                Swal.close();
                                let successCount = results.filter(r => r.success).length;
                                let errorCount = results.filter(r => !r.success).length;
                                
                                if (errorCount === 0) {
                                    Swal.fire('Éxito', `Todos los ${successCount} correos se enviaron correctamente`, 'success');
                                } else {
                                    Swal.fire('Resultado', `${successCount} correos enviados, ${errorCount} fallidos`, 'info');
                                }
                            }).catch(function(error) {
                                Swal.fire('Error', 'Ocurrió un error al enviar los correos', 'error');
                                console.error(error);
                            });
                        }
                    });
                } else {
                    Swal.fire('Información', 'No hay correos pendientes por enviar para este dominio', 'info');
                }
            });

            // Editar dominio
            $(document).on('click', '.editar-info-btn', function () {
                const id = $(this).data('id');
                const dominio = dominios.find(d => d.id_dominio == id);
                if (dominio) {
                    // Construir la URL del iframe con los parámetros correctos y el flag iframe=1&novalid=1
                    const url = `https://adm.conlineweb.com/formulario_dominio.php?edit=1&id_dominio=${dominio.id_dominio}&id_cliente=${dominio.cliente_id}&iframe=1&novalid=1`;
                    $('#iframeEditarDominio').attr('src', url);
                    $('#editarDominioModal').modal('show');
                } else {
                    Swal.fire('Error', 'No se encontró el dominio solicitado', 'error');
                }
            });

            // Toggle para mostrar/ocultar contraseña
            $(document).on('click', '.toggle-password', function () {
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
            $(document).on('submit', '#formEditarDominio', function (e) {
                e.preventDefault();


                Swal.fire({
                    title: '¿Guardar cambios?',
                    text: "¿Estás seguro de que deseas actualizar este dominio?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, guardar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Actualizando dominio',
                            html: 'Por favor espera...',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        $.ajax({
                            url: 'actualizar_dominio.php',
                            type: 'POST',
                            dataType: 'json',
                            data: $(this).serialize(),
                            success: function (response) {
                                Swal.close();
                                if (response.success) {
                                    Swal.fire({
                                        title: '¡Actualizado!',
                                        text: 'El dominio se ha actualizado correctamente',
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        $('#editarDominioModal').modal('hide');
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
                            error: function (xhr, status, error) {
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

            // Eliminar dominio
            $(document).on('click', '.eliminar-dominio-btn', function () {
                const id_dominio = $(this).data('id');
                
                Swal.fire({
                    title: '¿Eliminar dominio?',
                    text: '¿Estás seguro de que deseas eliminar este dominio?',
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
                            url: 'eliminar_dominio.php',
                            type: 'POST',
                            dataType: 'json',
                            data: { id: id_dominio, eliminar: 1 },
                            success: function (response) {
                                Swal.close();
                                if (response.success) {
                                    Swal.fire({
                                        title: '¡Eliminado!',
                                        text: 'El dominio ha sido eliminado correctamente',
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', response.message || 'No se pudo eliminar', 'error');
                                }
                            },
                            error: function (xhr, status, error) {
                                Swal.fire('Error', 'Error al conectar con el servidor: ' + error, 'error');
                            }
                        });
                    }
                });
            });

            // Restaurar dominio
            $(document).on('click', '.restaurar-dominio-btn', function () {
                const id_dominio = $(this).data('id');
                
                Swal.fire({
                    title: '¿Restaurar dominio?',
                    text: '¿Estás seguro de que deseas restaurar este dominio?',
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
                            url: 'eliminar_dominio.php',
                            type: 'POST',
                            dataType: 'json',
                            data: { id: id_dominio, eliminar: 0 },
                            success: function (response) {
                                Swal.close();
                                if (response.success) {
                                    Swal.fire({
                                        title: '¡Restaurado!',
                                        text: 'El dominio ha sido restaurado correctamente',
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', response.message || 'No se pudo restaurar', 'error');
                                }
                            },
                            error: function (xhr, status, error) {
                                Swal.fire('Error', 'Error al conectar con el servidor: ' + error, 'error');
                            }
                        });
                    }
                });
            });

            // Escuchar mensaje del iframe para cerrar modal y recargar
            window.addEventListener('message', function(event) {
                if (event.data && event.data.tipo === 'dominioActualizado') {
                    $('#editarDominioModal').modal('hide');
                    location.reload();
                }
            });
        });
    </script>
</body>
</html>