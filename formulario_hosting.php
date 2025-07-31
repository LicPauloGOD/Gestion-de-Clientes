<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
include "conn.php";
// Ocultar menú si es iframe
$esIframe = isset($_GET['iframe']) && $_GET['iframe'] == 1;
if (!$esIframe) {
    include "menu.php";
}

// Verificar si estamos en modo edición
$modo_edicion = isset($_GET['edit']) && $_GET['edit'] == 1;
$hosting_data = null;

if ($modo_edicion && isset($_GET['id_hosting'])) {
    $id_orden = $_GET['id_hosting'];
    $sql = "SELECT * FROM hosting WHERE id_orden = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_orden);
    $stmt->execute();
    $result = $stmt->get_result();
    $hosting_data = $result->fetch_assoc();
    $stmt->close();
}

// Si viene id_cliente por GET, lo usamos como valor predeterminado
$id_cliente_default = isset($_GET['id_cliente']) ? $_GET['id_cliente'] : '';
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $modo_edicion ? 'Editar Hosting' : 'Registro de Hosting'; ?></title>
    <link rel="icon" type="image/png" href="https://adm.conlineweb.com/images/favicon-16x16.png">
    <!-- SIEMPRE incluir los estilos principales -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .required-field::after {
            content: " *";
            color: red;
        }
        .password-toggle {
            cursor: pointer;
        }
       .btn-primary {
            background-color: #000147;
            border-color: #000147;
}
    </style>
    <script>
        $(document).ready(function() {
            // Función para mostrar datos del cliente en consola
            $('#cliente').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var clienteId = $(this).val();
                var nombreContacto = selectedOption.text();
                
                console.log('Datos del cliente seleccionado:');
                console.log('ID:', clienteId);
                console.log('Información completa:', nombreContacto);
            });

            // Para modo edición, mostrar datos iniciales
            if ($('input[name="modo_edicion"]').val() === '1') {
                var clienteInfo = $('input[type="text"][readonly]').val();
                console.log('Datos del cliente (modo edición):');
                console.log(clienteInfo);
            }
        });
    </script>
</head>
<body class="bg-light">
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <div class="container my-4 p-4 bg-white rounded-4 shadow-sm border border-light-subtle">
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-primary"><?php echo $modo_edicion ? 'Edición de Hosting' : 'Registro de Hosting'; ?></h1>
                </div>

                <form id="formHosting" class="row g-3 needs-validation" novalidate>
                    <!-- Campo oculto para modo edición -->
                    <input type="hidden" name="modo_edicion" value="<?php echo $modo_edicion ? '1' : '0'; ?>">
                    <?php if ($modo_edicion): ?>
                        <input type="hidden" name="id_orden" value="<?php echo htmlspecialchars($_GET['id_hosting']); ?>">
                    <?php endif; ?>

                    <fieldset class="col-12 border p-3 mb-4 rounded">
                        <legend class="float-none w-auto px-2" style="color: #000147;">Información Personal</legend>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cliente" class="form-label required-field">Cliente</label>
                                <?php if ($modo_edicion): ?>
                                    <!-- En modo edición, mostrar como texto plano con checkbox para cambiar -->
                                    <?php
                                        $sql_cliente = "SELECT nombre_contacto, correo, facturacion FROM clientes WHERE id = ?";
                                        $stmt_cliente = $conn->prepare($sql_cliente);
                                        $stmt_cliente->bind_param("i", $hosting_data['cliente_id']);
                                        $stmt_cliente->execute();
                                        $result_cliente = $stmt_cliente->get_result();
                                        if ($result_cliente->num_rows > 0) {
                                            $row_cliente = $result_cliente->fetch_assoc();
                                        }
                                        $stmt_cliente->close();
                                    ?>
                                    <input type="hidden" name="cliente_id" value="<?php echo htmlspecialchars($hosting_data['cliente_id']); ?>">
                                    <input type="hidden" name="cliente" value="<?php echo htmlspecialchars($hosting_data['cliente_id']); ?>" 
                                           data-facturacion="<?php echo htmlspecialchars($row_cliente['facturacion']); ?>">
                                    <input type="hidden" id="cliente_facturacion" value="<?php echo htmlspecialchars($row_cliente['facturacion']); ?>">

                                    <div id="cliente-display">
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($row_cliente['nombre_contacto'].' (ID: '.$hosting_data['cliente_id'].' - '.$row_cliente['correo'].')'); ?>" readonly>
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
                                            $sql_clientes = "SELECT id, nombre_contacto, correo, facturacion FROM clientes ORDER BY id ASC";
                                            $result_clientes = $conn->query($sql_clientes);
                                            if ($result_clientes->num_rows > 0) {
                                                while ($row = $result_clientes->fetch_assoc()) {
                                                    $selected = ($hosting_data['cliente_id'] == $row['id']) ? 'selected' : '';
                                                    echo '<option value="'.$row['id'].'" data-facturacion="'.$row['facturacion'].'" '.$selected.'>'.$row['nombre_contacto'].' (ID: '.$row['id'].' - '.$row['correo'].')</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                <?php else: ?>
                                    <!-- En modo creación, mostramos el select normal -->
                                    <select id="cliente" name="cliente" class="custom-select" >
                                        <option value="" selected disabled>Selecciona un cliente</option>
                                        <?php
                                        $sql_clientes = "SELECT id, nombre_contacto, correo, facturacion FROM clientes ORDER BY id ASC";
                                        $result_clientes = $conn->query($sql_clientes);
                                        if ($result_clientes->num_rows > 0) {
                                            while ($row = $result_clientes->fetch_assoc()) {
                                                $facturacion = $row['facturacion'];
                                                $selected = $id_cliente_default == $row['id'] ? 'selected' : '';
                                                echo '<option value="'.$row['id'].'" '.$selected.' data-facturacion="'.$facturacion.'">'.$row['nombre_contacto'].' (ID: '.$row['id'].' - '.$row['correo'].')</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                <?php endif; ?>
                                <div class="invalid-feedback">Por favor selecciona un cliente</div>
                            </div>
                        <div class="col-md-6 mb-3">
                            <label for="dominio" class="form- required-field">Dominios</label>
                            <select id="dominio" name="dominio" class="custom-select" >
                                <option value="" <?php echo !$modo_edicion ? 'selected disabled' : ''; ?>>Selecciona un dominio</option>
                                <?php
                                $sql = "SELECT id_dominio, url_dominio FROM dominios WHERE eliminado = 0 ORDER BY url_dominio;";
                                $result = $conn->query($sql);
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $nombre = htmlspecialchars($row["url_dominio"]);
                                        $selected = ($modo_edicion && isset($hosting_data['dominio']) && $hosting_data['dominio'] == $nombre) ? 'selected' : '';
                                        echo "<option value=\"$nombre\" $selected>$nombre</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom_host" class="form-label required-field">Nombre Host</label>
                                <div class="input-group">
                                    <span class="input-group-text">cpanel.</span>
                                    <input type="text" class="form-control" name="nom_host" id="nom_host" 
                                           value="<?php echo $modo_edicion ? str_replace('cpanel.', '', htmlspecialchars($hosting_data['nom_host'])) : ''; ?>" 
                                           placeholder="ejemplo.com" >
                                    <div class="invalid-feedback">Por favor ingresa el resto del dominio</div>
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="usuario" class="form-label" >Usuario</label>
                                <input type="text" class="form-control" name="usuario" id="usuario" 
                                       value="<?php echo $modo_edicion ? htmlspecialchars($hosting_data['usuario']) : ''; ?>" 
                                        placeholder="admin" >
                                <div class="invalid-feedback">Por favor ingresa un usuario válido</div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="contrasena" class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="contrasena" id="contrasena" 
                                           value="<?php echo $modo_edicion ? htmlspecialchars($hosting_data['contrasena']) : ''; ?>" 
                                            placeholder="hola$323232" >
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="contrasena">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">Por favor ingresa una contraseña válida</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="tipo_producto" class="form-label">Tipo servicio</label>
                                <input type="text" class="form-control" name="tipo_producto" id="tipo_producto"
                                       value="<?php echo $modo_edicion ? htmlspecialchars($hosting_data['tipo_producto']) : ''; ?>">
                                <div class="invalid-feedback">Por favor ingresa el tipo de servicio</div>
                            </div>

                            <div class="col-md-3 mb-3">                                <label for="producto" class="form-label required-field">Tipo Producto</label>
                                <select id="producto" name="producto" class="custom-select" >
                                    <option value="" selected disabled>Selecciona un Producto</option>
                                    <?php
                                    // Obtener los planes desde la tabla "planes"
                                    $sql_planes = "SELECT id, nombre, precio FROM planes ORDER BY id";
                                    $result_planes = $conn->query($sql_planes);
                                    $plan_id_edicion = $modo_edicion ? $hosting_data['producto'] : '';
                                    if ($result_planes->num_rows > 0) {
                                        while ($row = $result_planes->fetch_assoc()) {
                                            $selected = ($modo_edicion && $plan_id_edicion == $row['id']) ? 'selected' : '';
                                            echo '<option value="'.$row['id'].'" data-precio="'.$row['precio'].'" '.$selected.'>'.$row['nombre'].'</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="costo_hosting" class="form-label">Precio</label>
                                <select id="costo_hosting" class="custom-select" disabled>
                                    <?php if ($modo_edicion): ?>
                                        <option value="<?php echo htmlspecialchars($hosting_data['costo_producto']); ?>" selected>
                                            $<?php echo htmlspecialchars($hosting_data['costo_producto']); ?> 
                                            <?php echo ($hosting_data['id_forma_pago'] == 1) ? 'MXN' : 'USD'; ?>
                                        </option>
                                    <?php else: ?>
                                        <option value="" selected disabled>Selecciona un plan</option>
                                    <?php endif; ?>
                                </select>
                                <input type="hidden" name="costo_hosting" id="costo_hosting_valor" value="<?php echo $modo_edicion ? htmlspecialchars($hosting_data['costo_producto']) : ''; ?>">
                                <input type="hidden" name="costo_producto" id="costo_producto" value="<?php echo $modo_edicion ? htmlspecialchars($hosting_data['costo_producto']) : ''; ?>">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="id_forma_pago" class="form-label required-field">Moneda</label>
                                <select id="id_forma_pago" name="id_forma_pago" class="custom-select" >
                                    <option value="" selected disabled>Selecciona moneda</option>
                                    <option value="1" <?php echo ($modo_edicion && $hosting_data['id_forma_pago'] == 1) ? 'selected' : ''; ?>>MXN (Pesos Mexicanos)</option>
                                    <option value="2" <?php echo ($modo_edicion && $hosting_data['id_forma_pago'] == 2) ? 'selected' : ''; ?>>USD (Dólares Americanos)</option>
                                </select>
                                <div class="invalid-feedback">Por favor selecciona la moneda</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="fecha_contratacion" class="form-label required-field">Fecha de Contratación</label>
                                <input type="date" class="form-control" id="fecha_contratacion" name="fecha_contratacion" 
                                       value="<?php echo $modo_edicion ? htmlspecialchars($hosting_data['fecha_contratacion']) : ''; ?>" >
                                <div class="invalid-feedback">Por favor selecciona una fecha válida</div>
  
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="fecha_pago" class="form-label required-field">Fecha de Pago</label>
                                <input type="date" class="form-control" id="fecha_pago" name="fecha_pago" 
                                       value="<?php echo $modo_edicion ? htmlspecialchars($hosting_data['fecha_pago']) : ''; ?>" >
                                <div class="invalid-feedback">Por favor selecciona una fecha válida</div>
                            </div>
                        </div>
                        <!-- Dentro del fieldset "Información Personal", después del campo fecha_contratacion -->
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="ns1" class="form-label">Nameserver 1 (NS1)</label>
        <input type="text" class="form-control" name="ns1" id="ns1" 
               value="<?php echo $modo_edicion ? htmlspecialchars($hosting_data['ns1'] ?? '') : 'ns1.dns.com'; ?>" 
               placeholder="ns1.dns.com">
    </div>
    <div class="col-md-4 mb-3">
        <label for="ns2" class="form-label">Nameserver 2 (NS2)</label>
        <input type="text" class="form-control" name="ns2" id="ns2" 
               value="<?php echo $modo_edicion ? htmlspecialchars($hosting_data['ns2'] ?? '') : 'ns2.dns.com'; ?>" 
               placeholder="ns2.dns.com">
    </div>
    <div class="col-md-4 mb-3">
        <label for="ns3" class="form-label">Nameserver 3 (NS3)</label>
        <input type="text" class="form-control" name="ns3" id="ns3" 
               value="<?php echo $modo_edicion ? htmlspecialchars($hosting_data['ns3'] ?? '') : ''; ?>" 
               placeholder="Opcional">
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="ns4" class="form-label">Nameserver 4 (NS4)</label>
        <input type="text" class="form-control" name="ns4" id="ns4" 
               value="<?php echo $modo_edicion ? htmlspecialchars($hosting_data['ns4'] ?? '') : ''; ?>" 
               placeholder="Opcional">
    </div>
    <div class="col-md-4 mb-3">
        <label for="ns5" class="form-label">Nameserver 5 (NS5)</label>
        <input type="text" class="form-control" name="ns5" id="ns5" 
               value="<?php echo $modo_edicion ? htmlspecialchars($hosting_data['ns5'] ?? '') : ''; ?>" 
               placeholder="Opcional">
    </div>
    <div class="col-md-4 mb-3">
        <label for="ns6" class="form-label">Nameserver 6 (NS6)</label>
        <input type="text" class="form-control" name="ns6" id="ns6" 
               value="<?php echo $modo_edicion ? htmlspecialchars($hosting_data['ns6'] ?? '') : ''; ?>" 
               placeholder="Opcional">
    </div>
</div>
                    </fieldset>

                    <div class="col-12 d-flex justify-content-between mt-4">
                        <button type="reset" class="btn btn-secondary px-4">Limpiar</button>
                        <button type="submit" class="btn btn-primary px-4">
                            <?php echo $modo_edicion ? 'Actualizar Hosting' : 'Guardar Hosting'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="sticky-footer bg-white">
        <div class="container my-auto">
            <div class="copyright text-center my-auto">
                <span>Copyright &copy; Your Website <?php echo date("Y"); ?></span>
            </div>
        </div>
    </footer>
</div>

<!-- Scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
<script src="vendor/chart.js/Chart.min.js"></script>
<script src="js/demo/chart-area-demo.js"></script>
<script src="js/demo/chart-pie-demo.js"></script>

<script>
$(document).ready(function() {
    // Función para mostrar/ocultar contraseña
    $('.toggle-password').click(function() {
        const target = $(this).data('target');
        const passwordInput = $('#' + target);
        const icon = $(this).find('i');
        
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordInput.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Función para cargar dominios de un cliente
    function cargarDominios(clienteId) {
        if (!clienteId) return;
        
        $('#dominio').html('<option value="" selected disabled>Cargando...</option>');
        
        $.ajax({
            url: 'dominios_obtener.php',
            type: 'GET',
            data: { cliente_id: clienteId },
            dataType: 'json',
            success: function(data) {
                $('#dominio').html('<option value="" selected disabled>Selecciona un dominio</option>');
                
                if (data.length > 0) {
                    $.each(data, function(index, dominio) {
                        $('#dominio').append($('<option>', {
                            value: dominio.url_dominio,
                            text: dominio.url_dominio
                        }));
                    });
                } else {
                    $('#dominio').html('<option value="" selected disabled>No hay dominios registrados</option>');
                }
            },
            error: function() {
                $('#dominio').html('<option value="" selected disabled>Error al cargar</option>');
            }
        });
    }

    // Si estamos en modo creación, cargar dominios cuando cambie el cliente
    <?php if (!$modo_edicion): ?>
        $('#cliente').change(function() {
            cargarDominios($(this).val());
        });
        
        // Si hay un cliente seleccionado por defecto, cargar sus dominios
        <?php if ($id_cliente_default): ?>
            cargarDominios(<?php echo $id_cliente_default; ?>);
        <?php endif; ?>
    <?php endif; ?>

    // Funcionalidad del checkbox para cambiar cliente en modo edición
    $('#cambiar_cliente').on('change', function() {
        if ($(this).is(':checked')) {
            $('#cliente-display input[type="text"]').hide();
            $('#cliente-select-wrapper').show();
            // Actualizar el nombre del select para que se envíe en el formulario
            $('#cliente-select-wrapper select').attr('name', 'cliente');
            // Actualizar el cálculo del precio cuando se selecciona un cliente diferente
            $('#cliente-select-wrapper select').on('change', function() {
                actualizarPrecio();
                // En modo edición, también cargar dominios del nuevo cliente
                cargarDominios($(this).val());
            });
            // Actualizar inmediatamente el precio con el cliente seleccionado
            actualizarPrecio();
        } else {
            $('#cliente-display input[type="text"]').show();
            $('#cliente-select-wrapper').hide();
            // Restaurar el nombre original
            $('#cliente-select-wrapper select').attr('name', 'cliente_nuevo');
            // Restaurar el cálculo del precio con el cliente original
            actualizarPrecio();
        }
    });

    

    const tipoCambio = 19.01; // Tipo de cambio MXN a USD

    // Función única para actualizar precios
    function actualizarPrecio() {
        const productoId = $('#producto').val();
        const productoOption = $('#producto option:selected');
        const precioBaseRaw = productoOption.data('precio');
        const moneda = $('#id_forma_pago').val();
        let requiereFacturacion;
        
        // Determinar si estamos en modo edición o creación
        if ($('input[name="modo_edicion"]').val() === '1') {
            // En modo edición, verificar si el checkbox está marcado
            const cambiarCliente = $('#cambiar_cliente').is(':checked');
            
            if (cambiarCliente) {
                // Usar la información del cliente seleccionado en el select
                const clienteSeleccionado = $('#cliente-select-wrapper select option:selected');
                requiereFacturacion = clienteSeleccionado.data('facturacion') == 1;
            } else {
                // Usar la información del cliente original
                requiereFacturacion = $('#cliente_facturacion').val() == 1;
            }
        } else {
            // En modo creación, obtener la información del select
            const clienteSeleccionado = $('#cliente option:selected');
            requiereFacturacion = clienteSeleccionado.data('facturacion') == 1;
        }
        
        // Limpiar el select de precio
        $('#costo_hosting').empty();
        
        // Si no hay producto seleccionado
        if (!productoId || !precioBaseRaw) {
            const option = new Option('Selecciona un plan primero', '');
            option.disabled = true;
            option.selected = true;
            $('#costo_hosting').append(option);
            $('#costo_hosting').prop('disabled', true);
            
            $('#costo_producto').val('');
            $('#costo_hosting_valor').val('');
            return;
        }

        // Si no hay moneda seleccionada
        if (!moneda) {
            const option = new Option('Selecciona una moneda primero', '');
            option.disabled = true;
            option.selected = true;
            $('#costo_hosting').append(option);
            $('#costo_hosting').prop('disabled', true);
            
            $('#costo_producto').val('');
            $('#costo_hosting_valor').val('');
            return;
        }

        // Obtener precio base
        let precioBase = parseFloat(precioBaseRaw);
        let monedaTexto = 'MXN';
        
        // Convertir a USD si es necesario
        if (moneda === '2') {
            precioBase = (precioBase / tipoCambio).toFixed(2);
            monedaTexto = 'USD';
        }
        let precioFinal = parseFloat(precioBase);
        let textoMostrar = '';

        if (requiereFacturacion) {
            // Aplicar IVA del 16%
            precioFinal = (precioBase * 1.16).toFixed(2);
            textoMostrar = `$${precioBase} + IVA (16%) = $${precioFinal} ${monedaTexto}`;
        } else {
            textoMostrar = `$${precioBase} ${monedaTexto} (Sin IVA)`;
        }

        // Actualizar el select de precio
        $('#costo_hosting').append(new Option(textoMostrar, precioFinal));
        $('#costo_hosting').prop('disabled', false);
        
        // Actualizar campos ocultos
        $('#costo_producto').val(precioFinal);
        $('#costo_hosting_valor').val(precioFinal);
        // Log para debug
        console.log('Precio calculado:', {
            productoId: productoId,
            moneda: monedaTexto,
            precioBase: precioBase,
            requiereFacturacion: requiereFacturacion,
            precioFinal: precioFinal,
            modoEdicion: $('input[name="modo_edicion"]').val() === '1'
        });
    }

    // Eventos para actualizar precios
    $('#producto, #id_forma_pago, #cliente').on('change', function() {
        actualizarPrecio();
    });

    // Si estamos en modo edición, inicializar el precio    <?php if ($modo_edicion): ?>
        // En modo edición, inicializar con los datos del cliente oculto
        setTimeout(function() {
            // Obtener el valor de facturación del campo oculto del cliente
            const clienteHidden = $('input[name="cliente"]');
            const requiereFacturacion = clienteHidden.data('facturacion') == 1;
            
            // Log para debug
            console.log('Modo edición - Datos del cliente:', {
                id: clienteHidden.val(),
                requiereFacturacion: requiereFacturacion
            });
            
            // Forzar la actualización del precio con la información de facturación
            actualizarPrecio();
        }, 100);
    <?php endif; ?>

    // Evento para cuando se selecciona un cliente (información adicional)
    $('#cliente').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var clienteId = $(this).val();
        var nombreContacto = selectedOption.text();
        var requiereFactura = selectedOption.data('facturacion') == 1;
        
        console.log('Cliente seleccionado:', {
            id: clienteId,
            nombre: nombreContacto,
            requiereFactura: requiereFactura
        });
    });

    // Manejo del formulario con AJAX
    $('#formHosting').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);
        
        if (!form.checkValidity()) {
            $(form).addClass('was-validated');
            return;
        }

        // Debug: mostrar todos los datos que se enviarán
        console.log('Datos del formulario a enviar:');
        for (let [key, value] of formData.entries()) {
            console.log(key, value);
        }

        // Aquí puedes descomentar el AJAX cuando esté listo
      
        $.ajax({
            url: 'guardar_hosting.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: response.message
                    }).then(() => {
                        // Si estamos en iframe, avisar al padre para recargar o cerrar modal
                        if (window.self !== window.top) {
                            window.parent.postMessage({ tipo: 'hosting_actualizado' }, '*');
                        } else {
                            // Si no es iframe, redirigir si es necesario
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            }
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });

                let mensaje = 'No se pudo completar la operación.';
                try {
                    const respuesta = JSON.parse(xhr.responseText);
                    if (respuesta && respuesta.mensaje) {
                        mensaje = respuesta.mensaje;
                    }
                } catch (e) {
                    // No era JSON válido
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: mensaje,
                    footer: `Código de error: ${xhr.status}`
                });
            }
        });
        
    });

});
</script>

</body>
</html>