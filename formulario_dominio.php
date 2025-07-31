<?php
if (empty($_GET['iframe'])) include 'menu.php';
include 'conn.php';

// Obtener lista de clientes para el select con información de facturación
// Ordenar por id de menor a mayor
$sql_clientes = "SELECT id, nombre_contacto, correo, facturacion FROM clientes ORDER BY id ASC";
$result_clientes = $conn->query($sql_clientes);

// Verificar si estamos en modo edición
$modo_edicion = isset($_GET['edit']) && $_GET['edit'] == 1;
$dominio_data = null;

if ($modo_edicion && isset($_GET['id_dominio'])) {
    $id_dominio = $_GET['id_dominio'];
    $sql = "SELECT d.*, c.nombre_contacto as nombre_cliente, c.facturacion 
            FROM dominios d 
            LEFT JOIN clientes c ON d.cliente_id = c.id 
            WHERE d.id_dominio = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_dominio);
    $stmt->execute();
    $result = $stmt->get_result();
    $dominio_data = $result->fetch_assoc();
    $stmt->close();
}

// Si viene id_cliente por GET, lo usamos como valor predeterminado
$id_cliente_default = isset($_GET['id_cliente']) ? $_GET['id_cliente'] : '';

// Quitar validaciones si viene novalid=1
$no_valid = isset($_GET['novalid']) && $_GET['novalid'] == 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSS principal siempre cargado -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title><?php echo $modo_edicion ? 'Editar Dominio' : 'Registro de Dominio'; ?></title>
    <style>
        .required-field::after {
            content: " *";
            color: red;
        }
        .btn-primary {
            background-color: #000147;
            border-color: #000147;
        }
        .iva-info {
            background-color: #e7f3ff;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            padding: 8px;
            margin-top: 5px;
            font-size: 0.9em;
            color: #0c5460;
        }
    </style> 
</head>
<body class="bg-light">

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">

    <!-- Main Content -->
    <div id="content">
       

        <div class="container my-4 p-4 bg-white rounded-4 shadow-sm border border-light-subtle">
            
            <div class="d-sm-flex align-items-center  mb-5">
                   <br><h1 class="h3 mb-0 text-primary"><?php echo $modo_edicion ? 'Edición de Dominio' : 'Registro de Dominio'; ?></h1>
            </div>
            
            <!-- Begin Page Content -->

                <form id="formDominio" class="row g-3 needs-validation"<?php if ($no_valid) echo ' novalidate'; ?>>
                    <!-- Campo oculto para modo edición -->
                    <input type="hidden" name="modo_edicion" value="<?php echo $modo_edicion ? '1' : '0'; ?>">
                    <?php if ($modo_edicion): ?>
                        <input type="hidden" name="id_dominio" value="<?php echo htmlspecialchars($_GET['id_dominio']); ?>">
                    <?php endif; ?>

                    <!-- Sección 1: Información Personal -->
                    <fieldset class="col-12 border p-3 mb-4 rounded">
    <legend class="float-none w-auto px-2" style="color: #000147;">Información Personal</legend>
    
    <div class="row">
        <div class="col-md-3 mb-3">
            <label for="cliente" class="form-label<?php if (!$modo_edicion) echo ' required-field'; ?>">Cliente</label>

            <?php if ($modo_edicion): ?>
                <!-- En modo edición, mostrar como texto plano sin validación -->
                <input type="hidden" name="cliente_id" value="<?php echo htmlspecialchars($dominio_data['cliente_id']); ?>">
                <input type="hidden" name="cliente" value="<?php echo htmlspecialchars($dominio_data['cliente_id']); ?>">
                <input type="hidden" id="cliente_facturacion" value="<?php echo htmlspecialchars($dominio_data['facturacion']); ?>">

                <div id="cliente-display">
                    <p class="form-control-plaintext">
                        <?php echo htmlspecialchars($dominio_data['nombre_cliente'] ?? 'Cliente ID: ' . $dominio_data['cliente_id']); ?>
                    </p>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="cambiar_cliente" name="cambiar_cliente">
                        <label class="form-check-label" for="cambiar_cliente">
                            Cambiar cliente
                        </label>
                    </div>
                </div>

                <!-- Select de clientes oculto inicialmente -->
                <div id="cliente-select-wrapper" style="display: none;">
                    <select id="cliente" name="cliente_nuevo" class="custom-select">
                        <option value="" selected disabled>Selecciona un cliente</option>
                        <?php
                        // Resetear el puntero del resultado para volver a usarlo
                        $result_clientes->data_seek(0);
                        if ($result_clientes->num_rows > 0) {
                            while($row = $result_clientes->fetch_assoc()) {
                                $selected = ($dominio_data['cliente_id'] == $row['id']) ? 'selected' : '';
                                echo '<option value="'.$row['id'].'" data-facturacion="'.$row['facturacion'].'" '.$selected.'>'.$row['nombre_contacto'].' (ID: '.$row['id'].' - '.$row['correo'].')</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            <?php else: ?>
                <!-- En modo normal, con validación -->
                <select id="cliente" name="cliente" class="custom-select" required>
                    <option value="" selected disabled>Selecciona un cliente</option>
                    <?php
                    if ($result_clientes->num_rows > 0) {
                        while($row = $result_clientes->fetch_assoc()) {
                            $selected = ($id_cliente_default == $row['id']) ? 'selected' : '';
                            echo '<option value="'.$row['id'].'" data-facturacion="'.$row['facturacion'].'" '.$selected.'>'.$row['nombre_contacto'].' (ID: '.$row['id'].' - '.$row['correo'].')</option>';
                        }
                    }
                    ?>
                </select>
                <div class="invalid-feedback">Por favor selecciona un cliente</div>
            <?php endif; ?>
        </div>

        <div class="col-md-3 mb-3">
            <label for="proveedor" class="form-label<?php if (!$modo_edicion) echo ' required-field'; ?>">Proveedor</label>
            <input type="text" class="form-control" name="proveedor" <?php if (!$modo_edicion) echo 'required'; ?> 
                   value="<?php echo $modo_edicion ? htmlspecialchars($dominio_data['proveedor']) : ''; ?>" 
                   placeholder="Nombre del proveedor">
            <?php if (!$modo_edicion): ?>
                <div class="invalid-feedback">Por favor ingresa el proveedor</div>
            <?php endif; ?>
        </div>
        <div class="col-md-3 mb-3">
            <label for="url_dominio" class="form-label<?php if (!$modo_edicion) echo ' required-field'; ?>">URL Dominio</label>
            <input type="text" class="form-control" name="url_dominio" <?php if (!$modo_edicion) echo 'required'; ?> 
                   value="<?php echo $modo_edicion ? htmlspecialchars($dominio_data['url_dominio']) : ''; ?>" 
                   placeholder="https://ejemplo.com">
            <?php if (!$modo_edicion): ?>
                <div class="invalid-feedback">Por favor ingresa una URL válida</div>
            <?php endif; ?>
        </div>
        <div class="col-md-3 mb-3">
            <label for="url_admin" class="form-label<?php if (!$modo_edicion) echo ' required-field'; ?>">URL Administrador</label>
            <input type="url" class="form-control" name="url_admin" <?php if (!$modo_edicion) echo 'required'; ?> 
                   value="<?php echo $modo_edicion ? htmlspecialchars($dominio_data['url_admin']) : ''; ?>" 
                   placeholder="https://ejemplo.com/admin">
            <?php if (!$modo_edicion): ?>
                <div class="invalid-feedback">Por favor ingresa una URL válida</div>
            <?php endif; ?>
        </div>
    </div>
</fieldset>

<!-- Credenciales de Acceso -->
<fieldset class="col-12 border p-3 mb-4 rounded">
    <legend class="float-none w-auto px-2" style="color: #000147;">Credenciales de Acceso</legend>
    
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="usuario_admin" class="form-label<?php if (!$modo_edicion) echo ' required-field'; ?>">Usuario Admin</label>
            <input type="text" class="form-control" name="usuario_admin" <?php if (!$modo_edicion) echo 'required'; ?> 
                   value="<?php echo $modo_edicion ? htmlspecialchars($dominio_data['usuario']) : ''; ?>" 
                   placeholder="Nombre de usuario">
            <?php if (!$modo_edicion): ?>
                <div class="invalid-feedback">Por favor ingresa el usuario admin</div>
            <?php endif; ?>
        </div>
        <div class="col-md-4 mb-3">
            <label for="contrasena_admin" class="form-label<?php if (!$modo_edicion) echo ' required-field'; ?>">Contraseña Admin</label>
            <div class="input-group">
                <input type="password" class="form-control" name="contrasena_admin" <?php if (!$modo_edicion) echo 'required'; ?> 
                       value="<?php echo $modo_edicion ? htmlspecialchars($dominio_data['contrasena']) : ''; ?>" 
                       placeholder="Contraseña" id="contrasena_admin">
                <button class="btn btn-outline-secondary password-toggle" type="button" data-target="contrasena_admin">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <?php if (!$modo_edicion): ?>
                <div class="invalid-feedback">Por favor ingresa la contraseña admin</div>
            <?php endif; ?>
        </div>
        <div class="col-md-4 mb-3">
            <label for="url_cpanel" class="form-label<?php if (!$modo_edicion) echo ' required-field'; ?>">URL cPanel</label>
            <input type="url" class="form-control" name="url_cpanel" <?php if (!$modo_edicion) echo 'required'; ?> 
                   value="<?php echo $modo_edicion ? htmlspecialchars($dominio_data['url_cpanel']) : ''; ?>" 
                   placeholder="https://ejemplo.com/cpanel">
            <?php if (!$modo_edicion): ?>
                <div class="invalid-feedback">Por favor ingresa una URL válida</div>
            <?php endif; ?>
        </div>
    </div>
</fieldset>

<!-- Configuración DNS -->
<fieldset class="col-12 border p-3 mb-4 rounded">
    <legend class="float-none w-auto px-2" style="color: #000147;">Configuración DNS</legend>
    
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="ns1" class="form-label">Nameserver 1 (NS1)</label>
            <input type="text" class="form-control" name="ns1" 
                   value="<?php echo $modo_edicion ? htmlspecialchars($dominio_data['ns1']) : ''; ?>" 
                   placeholder="ns1.dominio.com">
        </div>
        <div class="col-md-4 mb-3">
            <label for="ns2" class="form-label">Nameserver 2 (NS2)</label>
            <input type="text" class="form-control" name="ns2" 
                   value="<?php echo $modo_edicion ? htmlspecialchars($dominio_data['ns2']) : ''; ?>" 
                   placeholder="ns2.dominio.com">
        </div>
        <div class="col-md-4 mb-3">
            <label for="ns3" class="form-label">Nameserver 3 (NS3)</label>
            <input type="text" class="form-control" name="ns3" 
                   value="<?php echo $modo_edicion ? htmlspecialchars($dominio_data['ns3']) : ''; ?>" 
                   placeholder="ns3.dominio.com">
        </div>
        <div class="col-md-4 mb-3">
            <label for="ns4" class="form-label">Nameserver 4 (NS4)</label>
            <input type="text" class="form-control" name="ns4" 
                   value="<?php echo $modo_edicion ? htmlspecialchars($dominio_data['ns4']) : ''; ?>" 
                   placeholder="ns4.dominio.com">
        </div>
        <div class="col-md-4 mb-3">
            <label for="ns5" class="form-label">Nameserver 5 (NS5)</label>
            <input type="text" class="form-control" name="ns5" 
                   value="<?php echo $modo_edicion ? htmlspecialchars($dominio_data['ns5']) : ''; ?>" 
                   placeholder="ns5.dominio.com">
        </div>
        <div class="col-md-4 mb-3">
            <label for="ns6" class="form-label">Nameserver 6 (NS6)</label>
            <input type="text" class="form-control" name="ns6" 
                   value="<?php echo $modo_edicion ? htmlspecialchars($dominio_data['ns6']) : ''; ?>" 
                   placeholder="ns6.dominio.com">
        </div>
    </div>

    <div class="row align-items-end">
        <div class="col-md-3 mb-3">
            <label for="costo_dominio" class="form-label<?php if (!$modo_edicion) echo ' required-field'; ?>">Costo Base</label>
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" class="form-control" id="costo_dominio" name="costo_dominio" step="0.01" 
                       value="<?php echo $modo_edicion ? htmlspecialchars($dominio_data['costo_dominio']) : ''; ?>" 
                       placeholder="0.00" <?php if (!$modo_edicion) echo 'required'; ?> >
            </div>
            <?php if (!$modo_edicion): ?>
                <div class="invalid-feedback">Por favor ingresa un costo válido</div>
            <?php endif; ?>
            <div id="iva-info" class="iva-info" style="display: none;">
                <strong>Costo con IVA (16%):</strong> $<span id="costo-con-iva">0.00</span>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <label for="id_forma_pago" class="form-label<?php if (!$modo_edicion) echo ' required-field'; ?>">Moneda</label>
            <select id="id_forma_pago" name="id_forma_pago" class="custom-select" <?php if (!$modo_edicion) echo 'required'; ?> >
                <option value="" selected disabled>Selecciona moneda</option>
                <option value="1" <?php echo ($modo_edicion && $dominio_data['id_forma_pago'] == 1) ? 'selected' : ''; ?>>MXN (Pesos Mexicanos)</option>
                <option value="2" <?php echo ($modo_edicion && $dominio_data['id_forma_pago'] == 2) ? 'selected' : ''; ?>>USD (Dólares Americanos)</option>
            </select>
            <div class="invalid-feedback">Por favor selecciona la moneda</div>
        </div>
    </div>
    
    <!-- Campo oculto para el costo final que se enviará -->
    <input type="hidden" id="costo_final" name="costo_final" value="">
</fieldset>

<fieldset class="col-12 border p-3 mb-4 rounded">
    <legend class="float-none w-auto px-2" style="color: #000147;">Registro</legend>
    
    <div class="row">
        <div class="col-md-3 mb-3">
            <label for="registrado" class="form-label<?php if (!$modo_edicion) echo ' required-field'; ?>">Registrado</label>
            <select id="registrado" name="registrado" class="custom-select" <?php if (!$modo_edicion) echo 'required'; ?> >
                <option value="" selected disabled>Selecciona una opción</option>
                <option value="0" <?php echo ($modo_edicion && $dominio_data['registrado'] == 0) ? 'selected' : ''; ?>>No registrado</option>
                <option value="1" <?php echo ($modo_edicion && $dominio_data['registrado'] == 1) ? 'selected' : ''; ?>>Registrado</option>
            </select>
            <div class="invalid-feedback">Por favor selecciona una opción</div>
        </div>
        
        <div class="col-md-3 mb-3">
            <label for="fecha_contratacion" class="form-label<?php if (!$modo_edicion) echo ' required-field'; ?>">Fecha de Contratación</label>
            <input type="date" class="form-control" id="fecha_contratacion" name="fecha_contratacion" 
                   value="<?php echo $modo_edicion ? htmlspecialchars($dominio_data['fecha_contratacion']) : ''; ?>" <?php if (!$modo_edicion) echo 'required'; ?> >
            <div class="invalid-feedback">Por favor selecciona una fecha válida</div>
        </div>
        <div class="col-md-3 mb-3">
            <label for="fecha_pago" class="form-label<?php if (!$modo_edicion) echo ' required-field'; ?>">Fecha de Pago</label>
            <input type="date" class="form-control" id="fecha_pago" name="fecha_pago" 
                   value="<?php echo $modo_edicion ? htmlspecialchars($dominio_data['fecha_pago']) : ''; ?>" <?php if (!$modo_edicion) echo 'required'; ?> >
            <div class="invalid-feedback">Por favor selecciona una fecha válida</div>
        </div>
    </div>
</fieldset>

<div class="col-12 d-flex justify-content-between mt-4">
    <button type="reset" class="btn btn-secondary px-4">Limpiar</button>
    <button type="submit" class="btn btn-primary px-4">
        <?php echo $modo_edicion ? 'Actualizar Dominio' : 'Guardar Dominio'; ?>
    </button>
</div>

                </form>
            
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- End of Main Content -->

    <!-- Footer -->
    <footer class="sticky-footer bg-white">
        <div class="container my-auto">
            <div class="copyright text-center my-auto">
                <span>Copyright &copy; Your Website <?php echo date('Y'); ?></span>
            </div>
        </div>
    </footer>
    <!-- End of Footer -->
</div>

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Bootstrap core JavaScript-->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/jquery.validation/1.19.5/jquery.validate.min.js"></script>
<script>
$(document).ready(function() {
    // Tasa de cambio y moneda
    var ultimaMoneda = $('#id_forma_pago').val();
    // IVA
    var ivaPorcentaje = 0.16;
    function actualizarCostoFinal() {
        var costo = parseFloat($('#costo_dominio').val()) || 0;
        var clienteOption, facturacion;
        
        // Verificar si estamos en modo edición y el checkbox está marcado
        var modoEdicion = $('input[name="modo_edicion"]').val() == '1';
        var cambiarCliente = $('#cambiar_cliente').is(':checked');
        
        if (modoEdicion && !cambiarCliente) {
            // Usar la facturación del cliente original
            facturacion = $('#cliente_facturacion').val();
        } else {
            // Usar la facturación del cliente seleccionado
            clienteOption = $('#cliente option:selected');
            facturacion = clienteOption.data('facturacion');
        }
        
        var tieneIVA = facturacion == 1 || facturacion == '1';
        var costoFinal = costo;
        if (tieneIVA) {
            costoFinal = (costo * (1 + ivaPorcentaje)).toFixed(2);
            $('#iva-info').show();
            $('#costo-con-iva').text(costoFinal);
        } else {
            $('#iva-info').hide();
        }
        $('#costo_final').val(costoFinal);
    }
    // Al cambiar cliente o costo, recalcula IVA
    $('#cliente').on('change', actualizarCostoFinal);
    $('#costo_dominio').on('input', actualizarCostoFinal);
    // Al cargar, si ya hay cliente/costo
    actualizarCostoFinal();

    // Funcionalidad del checkbox para cambiar cliente en modo edición
    $('#cambiar_cliente').on('change', function() {
        if ($(this).is(':checked')) {
            $('#cliente-display p').hide();
            $('#cliente-select-wrapper').show();
            // Actualizar el nombre del select para que se envíe en el formulario
            $('#cliente-select-wrapper select').attr('name', 'cliente');
            // Actualizar el cálculo del IVA cuando se selecciona un cliente diferente
            $('#cliente-select-wrapper select').on('change', actualizarCostoFinal);
            // Actualizar inmediatamente el cálculo con el cliente seleccionado
            actualizarCostoFinal();
        } else {
            $('#cliente-display p').show();
            $('#cliente-select-wrapper').hide();
            // Restaurar el nombre original
            $('#cliente-select-wrapper select').attr('name', 'cliente_nuevo');
            // Restaurar el cálculo del IVA con el cliente original
            actualizarCostoFinal();
        }
    });

    // Mostrar/ocultar tasa de cambio y usar su valor
    $('#id_forma_pago').on('change', function() {
        var moneda = $(this).val();
        var simbolo = (moneda == '2') ? 'US$' : '$';
        var placeholder = (moneda == '2') ? '0.00 USD' : '0.00';
        var costo = parseFloat($('#costo_dominio').val());
        var tasaCambio = parseFloat($('#tasa_cambio').val()) || 20;
        if (moneda == '2') {
            $('#tasa-cambio-group').show();
        } else {
            $('#tasa-cambio-group').hide();
        }
        if (!isNaN(costo) && costo > 0 && ultimaMoneda && ultimaMoneda !== moneda) {
            if (moneda == '2' && ultimaMoneda == '1') {
                costo = (costo / tasaCambio).toFixed(2);
            } else if (moneda == '1' && ultimaMoneda == '2') {
                costo = (costo * tasaCambio).toFixed(2);
            }
            $('#costo_dominio').val(costo);
            actualizarCostoFinal();
        }
        $("#costo_dominio").prev('.input-group-text').text(simbolo);
        $("#costo_dominio").attr('placeholder', placeholder);
        ultimaMoneda = moneda;
    });
    // Si cambia la tasa de cambio y está en USD, recalcula
    $('#tasa_cambio').on('input', function() {
        var moneda = $('#id_forma_pago').val();
        if (moneda == '2') {
            var costo = parseFloat($('#costo_dominio').val());
            var tasaCambio = parseFloat($(this).val()) || 20;
            if (!isNaN(costo) && costo > 0) {
                var costoMXN = (costo * tasaCambio).toFixed(2);
                $('#costo_dominio').val((costoMXN / tasaCambio).toFixed(2));
                actualizarCostoFinal();
            }
        }
    });
    // Quitar required y validación visual en modo edición
    var modoEdicion = $('input[name="modo_edicion"]').val() == '1';
    if (modoEdicion) {
        var form = $('#formDominio');
        form.find('[required]').removeAttr('required');
        form.find('.invalid-feedback').remove();
        form.removeClass('needs-validation');
        form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
    }
    // AJAX submit
    $('#formDominio').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = form.serialize();
        $.ajax({
            url: 'guardar_dominio.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message
                    }).then(function() {
                        // Si estamos en un iframe, avisar al padre y no redirigir
                        if (window.self !== window.top) {
                            window.parent.postMessage({ tipo: 'dominioActualizado' }, '*');
                        } else if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            form[0].reset();
                        }
                    });
                } else {
                    Swal.fire('Error', response.message || 'Ocurrió un error al guardar el dominio.', 'error');
                }
            },
            error: function(xhr) {
                let msg = 'Ocurrió un error al guardar el dominio.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    // Mostrar/ocultar contraseña en el campo de admin
    $(document).on('click', '.password-toggle', function() {
        var targetId = $(this).data('target');
        var input = $('#' + targetId);
        var icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
});
</script>
<?php if ($no_valid): ?>
<script>
$(document).ready(function() {
    // Desactivar validación HTML5 y jQuery si novalid=1
    $('#formDominio').removeClass('needs-validation');
    $('#formDominio').find('[required]').removeAttr('required');
    $('#formDominio').find('.invalid-feedback').remove();
});
</script>
<?php endif; ?>

</body>
</html>