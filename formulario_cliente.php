<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'menu.php';
include 'conn.php';
include 'obtener_municipios.php';


// Obtener estados
$sql_estados = "SELECT id_estado, estado FROM estados ORDER BY estado";
$result_estados = $conn->query($sql_estados);

$sql = "SELECT id, empresa, `nombre_contacto`,correo FROM clientes";
$result = $conn->query($sql);
?>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Cliente</title>
    <style>
        .required-field::after {
            content: " *";
            color: red;
        }

        .btn-primary {
            background-color: #000147;
            border-color: #000147;
        }
        legend{
            color: #000147;
        }
    </style>
</head>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <!-- Título FUERA del contenedor del formulario -->
        <br>
       

        <!-- Contenedor del formulario (sin el título) -->
        <div class="container my-4 p-4 bg-white rounded-4 shadow-sm border border-light-subtle">
            
            <!-- Page Heading (eliminado o ajustado si es necesario) -->
            <div class="d-sm-flex align-items-center justify-content-between mb-5">
                 <h1 class="h3 mb-0 text-primary">Registro de Cliente</h1>
            </div>

                <form class="row  needs-validation" method="POST" novalidate enctype="multipart/form-data">
                <!-- Sección 1: Información Personal -->
                <fieldset class="col-12 border p-3 mb-4 rounded">
                    <legend class="float-none w-auto px-2 ">Información Personal</legend>
                   
                    <div class="row">
                        <div class="col-md-3 mb-3">
                        <label for="nombre" class="form-label required-field">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre"
                            value="<?php echo htmlspecialchars($cliente['nombre'] ?? ''); ?>"
                            placeholder="Juan">
                        <div class="invalid-feedback">Por favor ingresa un nombre</div>
                    </div>
                
                    <div class="col-md-3 mb-3">
                        <label for="apellidos" class="form-label required-field">Apellido</label>
                        <input type="text" class="form-control" id="apellido" name="apellido" 
                            value="<?php echo htmlspecialchars($cliente['apellido'] ?? ''); ?>"
                            placeholder="Pérez López">
                        <div class="invalid-feedback">Por favor ingresa los apellidos</div>
                    </div>
                        <div class="col-md-3 mb-3">
                            <label for="telefono" class="form-label required-field">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                value="<?php echo htmlspecialchars($cliente["telefono"] ?? ''); ?>"
                                placeholder="55 1234 5678">
                            <div class="invalid-feedback">Por favor ingresa un teléfono válido</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="email" class="form-label required-field">Correo</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                value="<?php echo htmlspecialchars($cliente["correo"] ?? ''); ?>"
                                placeholder="juan@empresa.com">
                            <div class="invalid-feedback">Por favor ingresa un correo válido</div>
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="requiereFacturacion"
                                    name="requiereFacturacion">
                                <label class="form-check-label" for="requiereFacturacion">
                                    Requiero facturación
                                </label>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <fieldset id="seccionEmpresarial" class="col-12 border p-3 mb-4 rounded" style="display: none;">
                    <legend class="float-none w-auto px-2 text-primary">Información Empresarial</legend>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="empresa" class="form-label required-field">Empresa</label>
                            <input type="text" class="form-control" id="empresa" name="empresa"
                                value="<?php echo htmlspecialchars($cliente["empresa"] ?? ''); ?>"
                                placeholder="Nombre de la empresa">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="rsocial" class="form-label required-field">Razón Social</label>
                            <input type="text" class="form-control" id="rsocial" name="rsocial"
                                value="<?php echo htmlspecialchars($cliente["rsocial"] ?? ''); ?>"
                                placeholder="Razón social completa">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="rfc" class="form-label required-field">RFC</label>
                            <input type="text" class="form-control" id="rfc" name="rfc"
                                value="<?php echo htmlspecialchars($cliente["rfc"] ?? ''); ?>"
                                placeholder="XAXX010101000">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="especificacion" class="form-label">Especificación</label>
                            <input type="text" id="especificacion" name="especificacion" class="form-control" value="">
                        </div>
                           <div class="col-md-12 mb-3">
                            <label for="constancia_fiscal" class="form-label">Constancia de Situación Fiscal</label>
                            <input type="file" class="form-control" id="constancia_fiscal" name="constancia_fiscal"
                                accept=".pdf,.jpg,.jpeg,.png">
                            <?php if (!empty($cliente['constancia_situacion_fiscal'])): ?>
                                <div class="form-text">
                                    Archivo actual:
                                    <a href="uploads/<?php echo htmlspecialchars($cliente['constancia_situacion_fiscal']); ?>"
                                        target="_blank">
                                        <?php echo htmlspecialchars($cliente['constancia_situacion_fiscal']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset id="seccionDireccion" class="col-12 border p-3 mb-4 rounded" style="display: none;">
                    <legend class="float-none w-auto px-2 text-primary">Dirección</legend>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="calle" class="form-label required-field">Calle</label> <input type="text" class="form-control"
                                id="calle" name="calle" value="<?php echo htmlspecialchars($cliente["calle"] ?? ''); ?>"
                                placeholder="Av. Principal">
                            <div class="invalid-feedback">Por favor ingresa la calle</div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="next" class="form-label required-field">N° Exterior</label>
                            <input type="text" class="form-control" id="next" name="next"
                                value="<?php echo htmlspecialchars($cliente["next"] ?? ''); ?>" placeholder="123">
                            <div class="invalid-feedback">Por favor ingresa el número</div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="nint" class="form-label">N° Interior</label>
                            <input type="text" class="form-control" id="nint" name="nint"
                                value="<?php echo htmlspecialchars($cliente["nint"] ?? ''); ?>" placeholder="A">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="colonia" class="form-label required-field">Colonia</label>
                            <input type="text" class="form-control" id="colonia" name="colonia"
                                value="<?php echo htmlspecialchars($cliente["col"] ?? ''); ?>" placeholder="Centro">
                            <div class="invalid-feedback">Por favor ingresa la colonia</div>
                        </div>
                        <div class="row align-items-end">
                            <div class="col-md-2 mb-3">
                                <label for="cp" class="form-label required-field ">Código Postal</label>
                                <input type="text" class="form-control" id="cp" name="cp"
                                    value="<?php echo htmlspecialchars($cliente["cp"] ?? ''); ?>" placeholder="01000"
                                    pattern="[0-9]{5}">
                                <div class="invalid-feedback">Código postal de 5 dígitos</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="pais" class="form-label required-field " >País</label>
                                <input type="text" class="form-control" name="pais" placeholder="País" id="pais"
                                    value="<?php echo htmlspecialchars($cliente["pais"] ?? ''); ?>">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select id="estado" name="estado" class="custom-select">
                                    <option value="" >Selecciona un estado</option>
                                    <?php
                                    $result_estados->data_seek(0);
                                    while ($row_estado = $result_estados->fetch_assoc()):
                                        $selected = ($row_estado['id_estado'] == $cliente['estado']) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo $row_estado['id_estado']; ?>" <?php echo $selected; ?>>
                                            <?php echo $row_estado['estado']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="municipio" class="form-label">Ciudad</label>
                                <select id="municipio" name="municipio" class="custom-select" >
                                    <option value="<?php echo htmlspecialchars($cliente['ciudad'] ?? ''); ?>" selected>
                                        <?php echo htmlspecialchars($cliente['ciudad'] ?? ''); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="col-12 border p-3 mb-4 rounded">
                    <legend class="float-none w-auto px-2">Seguridad</legend>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="inputPassword4" class="form-label required-field">Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="contrasena" id="contrasena"
                                    placeholder="Mínimo 8 caracteres" minlength="8">
                                <button class="btn btn-outline-secondary toggle-password" type="button"
                                    data-target="contrasena">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">La contraseña debe contener al menos 8 caracteres</div>
                            <div class="invalid-feedback">La contraseña debe tener mínimo 8 caracteres</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirmPassword" class="form-label required-field">Confirmar Contraseña*</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirmPassword"
                                    placeholder="Repite tu contraseña">
                                <button class="btn btn-outline-secondary toggle-password" type="button"
                                    data-target="confirmPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Las contraseñas no coinciden</div>
                        </div>
                    </div>
                </fieldset>

                <div class="col-12 d-flex justify-content-between mt-4">
                    <span></span>
                    <button type="submit" class="btn btn-primary px-4">Guardar</button>
                </div>
            </form>
        </div>
        <!-- End of Footer -->

    </div>
    <!-- End of Content Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="login.html">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>

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
    $(document).ready(function () {
        // Función para mostrar/ocultar secciones según facturación
        function toggleSecciones() {
            const requiereFacturacion = $('#requiereFacturacion').prop('checked');
            $('#seccionEmpresarial, #seccionDireccion').toggle(requiereFacturacion);

            // Campos básicos que siempre son requeridos
            const camposBasicos = $('#nombre, #apellido, #telefono, #email, #contrasena, #confirmPassword');
            
            if (requiereFacturacion) {
                // Si requiere facturación, activar validación para campos relevantes
                $('#empresa, #rsocial, #rfc, #calle, #next, #colonia, #cp, #pais').prop('required', true);
                
                // Campos opcionales en facturación
                $('#nint, #constancia_fiscal').prop('required', false);
            } else {
                // Si no requiere facturación, desactivar validación para campos de facturación
                $('#seccionEmpresarial input, #seccionDireccion input, #seccionDireccion select').prop('required', false);
                
                // Limpiar y resetear campos de facturación
                $('#seccionEmpresarial input, #seccionDireccion input, #seccionDireccion select').val('');
                $('#seccionEmpresarial input, #seccionDireccion input, #seccionDireccion select').removeClass('is-invalid');
            }
                    }

        // Evento de cambio en el checkbox
        $('#requiereFacturacion').change(toggleSecciones);

        // Inicializar el estado al cargar la página
        toggleSecciones();

        // Cuando cambia el estado
        $('#estado').change(function () {
            var estados_id_estado = $(this).val();
            if (estados_id_estado) {
                $.ajax({
                    url: 'obtener_municipios.php',
                    type: 'POST',
                    data: { estados_id_estado: estados_id_estado },
                    success: function (data) {
                        $('#municipio').html(data);
                    }
                });
            } else {
                $('#municipio').html('<option value="" selected disabled>Selecciona un municipio</option>');
            }
        });

        // Función para mostrar/ocultar contraseña
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });

        // Validación de confirmación de contraseña
        $('#confirmPassword').on('input', function() {
            const password = $('#contrasena').val();
            const confirmPassword = $(this).val();
            
            if (password !== confirmPassword) {
                this.setCustomValidity("Las contraseñas no coinciden");
            } else {
                this.setCustomValidity("");
            }
        });

        // Envío del formulario
        // Envío del formulario
// Envío del formulario
$('form.needs-validation').on('submit', function (e) {
    e.preventDefault();

    // Validar contraseñas coincidan
    if ($('#contrasena').val() !== $('#confirmPassword').val()) {
        $('#confirmPassword')[0].setCustomValidity("Las contraseñas no coinciden");
        $('#confirmPassword')[0].reportValidity();
        return;
    } else {
        $('#confirmPassword')[0].setCustomValidity("");
    }

    const requiereFacturacion = $('#requiereFacturacion').prop('checked');
    
    // Validar formulario
    if (!this.checkValidity()) {
        e.stopPropagation();
        $(this).addClass('was-validated');
        return;
    }

    Swal.fire({
        title: 'Guardando...',
        html: 'Por favor espere mientras se procesa la petición',
        allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
            
            // Preparar datos del formulario
            const form = this;
            const formData = new FormData();
            
            // Agregar solo los campos necesarios manualmente
            const camposBasicos = ['nombre', 'apellido', 'telefono', 'email', 'contrasena'];
            camposBasicos.forEach(campo => {
                formData.append(campo, $(`#${campo}`).val());
            });
            
            // Si requiere facturación, agregar campos adicionales
            if(requiereFacturacion) {
                const camposFacturacion = ['empresa', 'rsocial', 'rfc', 'especificacion', 
                                         'calle', 'next', 'nint', 'colonia', 'cp', 
                                         'pais', 'estado', 'municipio'];
                camposFacturacion.forEach(campo => {
                    formData.append(campo, $(`#${campo}`).val());
                });
                
                // Agregar archivo solo si se seleccionó uno
                const archivo = $('#constancia_fiscal')[0].files[0];
                if(archivo && archivo.size > 0) {
                    formData.append('constancia_fiscal', archivo);
                }
            }
            
            formData.append('facturacion', requiereFacturacion ? 1 : 0);
            
            // Enviar datos por AJAX
             $.ajax({
                url: 'guardar_cliente.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Cliente Guardado',
                            html: `<div class='text-left'>
                                     <p>✅ Cliente agregado correctamente</p>
                                  </div>`,
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#ffc107'
                        }).then(() => {
                            // Limpiar el formulario
            $('form.needs-validation')[0].reset(); // Esto limpia todos los campos
            
            // Ocultar las secciones de facturación si estaban visibles
            $('#seccionEmpresarial, #seccionDireccion').hide();
            
            // Desmarcar el checkbox de facturación
            $('#requiereFacturacion').prop('checked', false);
            
            // Quitar las clases de validación
            $('form.needs-validation').removeClass('was-validated');
            
            // Opcional: Restablecer el select de municipios
            $('#municipio').html('<option value="" selected disabled>Selecciona un municipio</option>');
            
            // Opcional: Restablecer el select de estados
            $('#estado').val('');
            
            // Opcional: Enfocar el primer campo
            $('#nombre').focus()
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Hubo un error al procesar la solicitud.',
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    let errorMessage = 'Hubo un error al procesar la solicitud.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage = response.message;
                        }
                    } catch (e) {}
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage,
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        }
    });
});
    });
</script>

    </body>

    </html>