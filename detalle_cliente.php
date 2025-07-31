<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

// Procesar el formulario cuando se envía (ANTES DE CUALQUIER INCLUDE O HTML)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Limpiar el buffer de salida para evitar espacios o warnings antes del JSON
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/json'); // Forzar header SIEMPRE

    // Recoger datos del formulario
    $nombre = $_POST['nombre'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $correo = $_POST['email'] ?? '';
    $empresa = $_POST['empresa'] ?? '';
    $rsocial = $_POST['rsocial'] ?? '';
    $rfc = $_POST['rfc'] ?? '';
    $especificacion = $_POST['especificacion'] ?? '';
    $calle = $_POST['calle'] ?? '';
    $next = $_POST['next'] ?? '';
    $nint = $_POST['nint'] ?? '';
    $colonia = $_POST['colonia'] ?? '';
    $cp = $_POST['cp'] ?? '';
    $pais = $_POST['pais'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $ciudad = $_POST['municipio'] ?? '';
    $contrasena = $_POST['inputPassword'] ?? '';
    $contrasena_segura = md5($contrasena);
    $facturacion = $_POST['facturacion'] ?? '';

    // Manejo de Constancia de Situación Fiscal
    $nombre_archivo = '';
    if (isset($_FILES['constancia_fiscal']) && $_FILES['constancia_fiscal']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['constancia_fiscal'];
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombre_archivo = 'constancia_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $ruta_destino = "constancias_fiscales/" . $nombre_archivo;
        if (!is_dir("constancias_fiscales")) {
            mkdir("constancias_fiscales", 0777, true);
        }
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
            $errorMsg = 'Error al subir el archivo';
            echo json_encode(['success' => false, 'message' => $errorMsg]);
            exit();
        }
    } else {
        // Si no se sube archivo, conservar el actual si existe
        // Necesitamos obtener el valor actual de la constancia del cliente
        include_once "conn.php";
        $id = $_GET["id"] ?? $_POST["id"] ?? null;
        $nombre_archivo = '';
        if ($id) {
            $sql = "SELECT constancia_situacion_fiscal FROM clientes WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $nombre_archivo = $row['constancia_situacion_fiscal'] ?? '';
            $stmt->close();
        }
    }

    // Actualizar datos del cliente
    include_once "conn.php";
    $id = $_GET["id"] ?? $_POST["id"] ?? null;
    $sql_update = "UPDATE clientes SET 
        nombre_contacto = ?,
        telefono = ?,
        correo = ?,
        empresa = ?,
        rsocial = ?,
        rfc = ?,
        especificacion = ?,
        calle = ?,
        next = ?,
        nint = ?,
        col = ?,
        cp = ?,
        pais = ?,
        estado = ?,
        ciudad = ?,
        constancia_situacion_fiscal = ?,
        facturacion = ?
        WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param(
        "ssssssssssssssssii",
        $nombre,
        $telefono,
        $correo,
        $empresa,
        $rsocial,
        $rfc,
        $especificacion,
        $calle,
        $next,
        $nint,
        $colonia,
        $cp,
        $pais,
        $estado,
        $ciudad,
        $nombre_archivo,
        $facturacion,
        $id
    );

    // --- CORRECCIÓN: Actualizar SIEMPRE el usuario/correo en login, y la contraseña solo si se proporciona ---
    $login_ok = true;
    if (!empty($contrasena)) {
        $sql_update_login = "UPDATE login SET 
            contrasena = ?,
            contrasena_normal = ?,
            usuario = ?
            WHERE id = ?";
        $stmt_update_login = $conn->prepare($sql_update_login);
        $stmt_update_login->bind_param("sssi", $contrasena_segura, $contrasena, $correo, $id);
        $login_ok = $stmt_update_login->execute();
        $login_error = $stmt_update_login->error;
        $stmt_update_login->close();
    } else {
        $sql_update_login = "UPDATE login SET usuario = ? WHERE id = ?";
        $stmt_update_login = $conn->prepare($sql_update_login);
        $stmt_update_login->bind_param("si", $correo, $id);
        $login_ok = $stmt_update_login->execute();
        $login_error = $stmt_update_login->error;
        $stmt_update_login->close();
    }

    // Si hubo error en login, mostrar error y salir
    if (!$login_ok) {
        $errorMsg = 'Error al actualizar usuario/correo o contraseña: ' . addslashes($login_error);
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        exit();
    }

    // Ejecutar actualización de cliente
    if ($stmt_update->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Cliente actualizado correctamente',
            'redirect' => 'detalle_cliente.php?id=' . $id
        ]);
        exit();
    } else {
        $errorMsg = 'Error al actualizar cliente: ' . addslashes($stmt_update->error);
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        exit();
    }
}

include "menu.php";
include "conn.php";

// Obtener lista de estados
$sql_estados = "SELECT id_estado, estado FROM estados ORDER BY estado";
$result_estados = $conn->query($sql_estados);

// Verificar si se recibió un ID válido
if (isset($_GET["id"]) && is_numeric($_GET["id"])) {
    $id = $_GET["id"];

    // Consulta para obtener datos del cliente
    $sql = "SELECT * FROM clientes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    // Consulta para obtener datos de dominios (ahora usando la tabla correcta)
    $sql3 = "SELECT * FROM dominios WHERE cliente_id = ?";
    $stmt3 = $conn->prepare($sql3);
    $stmt3->bind_param("i", $id);

    if ($stmt3->execute()) {
        $result3 = $stmt3->get_result();
        $dominios = $result3->fetch_all(MYSQLI_ASSOC); // Cambiado a fetch_all para obtener múltiples dominios
        $stmt3->close();
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error en la consulta de dominio: " . addslashes($stmt3->error) . "',
                confirmButtonText: 'Aceptar'
            });
        </script>";
    }

    // Consulta para obtener datos de hosting
    $sql4 = "SELECT * FROM hosting WHERE cliente_id = ?";
    $stmt4 = $conn->prepare($sql4);
    $stmt4->bind_param("i", $id);

    if ($stmt4->execute()) {
        $result4 = $stmt4->get_result();
        $hostings = $result4->fetch_all(MYSQLI_ASSOC); // Obtenemos todos los hostings
        $stmt4->close();
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error en la consulta de hosting: " . addslashes($stmt4->error) . "',
                confirmButtonText: 'Aceptar'
            });
        </script>";
    }

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $cliente = $result->fetch_assoc();
        $stmt->close();

        // Consulta para obtener datos de login
        $sql2 = "SELECT * FROM login WHERE id = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("i", $id);

        if ($stmt2->execute()) {
            $result2 = $stmt2->get_result();
            $login = $result2->fetch_assoc();
            $stmt2->close();

            // Debug en consola
            echo "<script>
                console.log('Cliente:', " . json_encode($cliente) . ");
                console.log('Login:', " . json_encode($login) . ");
                console.log('Dominios:', " . json_encode($dominios) . ");
                console.log('Hostings:', " . json_encode($hostings) . ");
            </script>";
        } else {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error en la consulta de login: " . addslashes($stmt2->error) . "',
                    confirmButtonText: 'Aceptar'
                });
            </script>";
        }
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error en la consulta de cliente: " . addslashes($stmt->error) . "',
                confirmButtonText: 'Aceptar'
            });
        </script>";
    }
} else {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'ID no válido',
            confirmButtonText: 'Aceptar',
            willClose: () => {
                window.location.href = 'lista_clientes.php';
            }
        });
    </script>";
    exit();
}


// El resto del archivo igual, con includes y HTML
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Modificar Cliente</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <style>
        .mt-custom {
            margin-top: 20px;
            /* Ajusta según tu necesidad */
        }

        .required-field::after {
            content: " *";
            color: red;
        }

        .btn-primary {
            background-color: #000147;
            border-color: #000147;
        }
    </style>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
        <!-- Main Content -->
        <div class="container-fluid">
            <div id="content">

                <div class="d-sm-flex align-items-center justify-content-between mt-custom mb-4">
                    <h1 class="mb-0 text-primary h3">Modificar Cliente</h1>
                </div>


                <!-- Pestañas -->
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="info-tab" data-toggle="tab" href="#info">
                            Información Cliente
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="details-tab" data-toggle="tab" href="#details">
                            Dominios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="other-tab" data-toggle="tab" href="#other">
                            Hostings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="payments-tab" data-toggle="tab" href="#payments">
                            Pagos
                        </a>
                    </li>
                </ul>

                <!-- Contenido de las pestañas -->
                <div class="bg-white p-3 border-end border-start border-bottom tab-content" id="myTabContent">
                    <!-- Pestaña 1: Información Principal -->
                    <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                        <form class="row g-3 needs-validation" method="POST" novalidate>
                            <fieldset class="mb-4 p-3 border rounded col-12">
                                <legend class="float-none px-2 w-auto" style="color: #000147;">Información Personal</legend>

                                <div class="row">
                                    <div class="mb-3 col-md-3">
                                        <label for="nombre" class="form-label required-field">Nombre</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required
                                            value="<?php echo htmlspecialchars($cliente['nombre_contacto'] ?? ''); ?>"
                                            placeholder="Juan">
                                        <div class="invalid-feedback">Por favor ingresa un nombre</div>
                                    </div>


                                    <div class="mb-3 col-md-3">
                                        <label for="telefono" class="form-label required-field">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono" required
                                            value="<?php echo htmlspecialchars($cliente["telefono"] ?? ''); ?>"
                                            placeholder="55 1234 5678">
                                        <div class="invalid-feedback">Por favor ingresa un teléfono válido</div>
                                    </div>
                                    <div class="mb-3 col-md-3">
                                        <label for="email" class="form-label required-field">Correo</label>
                                        <input type="email" class="form-control" id="email" name="email" required
                                            value="<?php echo htmlspecialchars($cliente["correo"] ?? ''); ?>"
                                            placeholder="juan@empresa.com">
                                        <div class="invalid-feedback">Por favor ingresa un correo válido</div>
                                    </div>
                                    <div class="d-flex align-items-end mb-3 col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="requiereFacturacion"
                                                name="requiereFacturacion" <?php echo (isset($cliente["facturacion"]) && $cliente["facturacion"] == 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="requiereFacturacion">
                                                Requiero facturación
                                            </label>
                                            <!-- Campo hidden para enviar el valor al formulario -->
                                            <input type="hidden" name="facturacion" id="facturacion" value="<?php echo (isset($cliente["facturacion"]) && $cliente["facturacion"] == 1) ? '1' : '0'; ?>">
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset id="seccionEmpresarial" class="mb-4 p-3 border rounded col-12" style="display: none;">
                                <legend class="float-none px-2 w-auto" style="color: #000147;">Información Empresarial</legend>

                                <div class="row">
                                    <div class="mb-3 col-md-4">
                                        <label for="empresa" class="form-label required-field">Empresa</label>
                                        <input type="text" class="form-control" id="empresa" name="empresa"
                                            value="<?php echo htmlspecialchars($cliente["empresa"] ?? ''); ?>"
                                            placeholder="Nombre de la empresa">
                                    </div>

                                    <div class="mb-3 col-md-4">
                                        <label for="rsocial" class="form-label required-field">Razón Social</label>
                                        <input type="text" class="form-control" id="rsocial" name="rsocial"
                                            value="<?php echo htmlspecialchars($cliente["rsocial"] ?? ''); ?>"
                                            placeholder="Razón social completa">
                                    </div>

                                    <div class="mb-3 col-md-4">
                                        <label for="rfc" class="form-label required-field">RFC</label>
                                        <input type="text" class="form-control" id="rfc" name="rfc"
                                            value="<?php echo htmlspecialchars($cliente["rfc"] ?? ''); ?>"
                                            placeholder="XAXX010101000">
                                    </div>
                                    <div class="mb-3 col-md-12">
                                        <label for="especificacion" class="form-label">Especificación</label>
                                        <input type="text" id="especificacion" name="especificacion" class="form-control" value="<?php echo htmlspecialchars($cliente["especificacion"] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3 col-md-12">
                                        <label for="constancia_fiscal" class="form-label">Constancia de Situación Fiscal</label>
                                        <input type="file" class="form-control" id="constancia_fiscal" name="constancia_fiscal" accept=".pdf,.jpg,.jpeg,.png">

                                        <?php
                                        $archivo = $cliente['constancia_situacion_fiscal'] ?? null;

                                        if (!empty($archivo)) {
                                            $url_clientes = 'https://cliente.conlineweb.com/constancias_fiscales/' . urlencode($archivo);
                                            $url_adm = 'https://adm.conlineweb.com/constancias_fiscales/' . urlencode($archivo);

                                            $headers = @get_headers($url_clientes);
                                            if ($headers && strpos($headers[0], '200') !== false) {
                                                $url_final = $url_clientes;
                                            } else {
                                                $url_final = $url_adm;
                                            }
                                        ?>
                                            <div class="mt-2 form-text">
                                                Archivo actual:
                                                <a href="<?php echo htmlspecialchars($url_final); ?>" target="_blank">
                                                    <?php echo htmlspecialchars($archivo); ?>
                                                </a>
                                            </div>
                                        <?php } ?>
                                    </div>

                                </div>
                            </fieldset>

                            <fieldset id="seccionDireccion" class="mb-4 p-3 border rounded col-12" style="display: none;">
                                <legend class="float-none px-2 w-auto" style="color: #000147;">Dirección</legend>

                                <div class="row">
                                    <div class="mb-3 col-md-4">
                                        <label for="calle" class="form-label required-field">Calle</label> <input type="text" class="form-control"
                                            id="calle" name="calle" value="<?php echo htmlspecialchars($cliente["calle"] ?? ''); ?>"
                                            placeholder="Av. Principal">
                                        <div class="invalid-feedback">Por favor ingresa la calle</div>
                                    </div>
                                    <div class="mb-3 col-md-2">
                                        <label for="next" class="form-label required-field">N° Exterior</label>
                                        <input type="text" class="form-control" id="next" name="next"
                                            value="<?php echo htmlspecialchars($cliente["next"] ?? ''); ?>" placeholder="123">
                                        <div class="invalid-feedback">Por favor ingresa el número</div>
                                    </div>
                                    <div class="mb-3 col-md-2">
                                        <label for="nint" class="form-label">N° Interior</label>
                                        <input type="text" class="form-control" id="nint" name="nint"
                                            value="<?php echo htmlspecialchars($cliente["nint"] ?? ''); ?>" placeholder="A">
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label for="colonia" class="form-label required-field">Colonia</label>
                                        <input type="text" class="form-control" id="colonia" name="colonia"
                                            value="<?php echo htmlspecialchars($cliente["col"] ?? ''); ?>" placeholder="Centro">
                                        <div class="invalid-feedback">Por favor ingresa la colonia</div>
                                    </div>
                                    <div class="align-items-end row">
                                        <div class="mb-3 col-md-2">
                                            <label for="cp" class="form-label required-field">Código Postal</label>
                                            <input type="text" class="form-control" id="cp" name="cp"
                                                value="<?php echo htmlspecialchars($cliente["cp"] ?? ''); ?>" placeholder="01000"
                                                pattern="[0-9]{5}">
                                            <div class="invalid-feedback">Código postal de 5 dígitos</div>
                                        </div>
                                        <div class="mb-3 col-md-3">
                                            <label for="pais" class="form-label required-field">País</label>
                                            <input type="text" class="form-control" name="pais" placeholder="País"
                                                value="<?php echo htmlspecialchars($cliente["pais"] ?? ''); ?>">
                                        </div>

                                        <div class="mb-3 col-md-3">
                                            <label for="estado" class="form-label">Estado</label>
                                            <select id="estado" name="estado" class="custom-select">
                                                <option value="" selected disabled>Selecciona un estado</option>
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
                                            <div class="invalid-feedback">Selecciona un estado</div>
                                        </div>
                                        <div class="mb-3 col-md-4">
                                            <label for="municipio" class="form-label">Ciudad</label>
                                            <select id="municipio" name="municipio" class="custom-select" required>
                                                <option value="<?php echo htmlspecialchars($cliente['ciudad'] ?? ''); ?>" selected>
                                                    <?php echo htmlspecialchars($cliente['ciudad'] ?? ''); ?>
                                                </option>
                                            </select>
                                            <div class="invalid-feedback">Selecciona una ciudad</div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>


                            <!-- Sección 4: Seguridad -->
                            <fieldset class="mb-4 p-3 border rounded col-12">
                                <legend class="float-none px-2 w-auto" style="color: #000147;">Seguridad</legend>

                                <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <label for="inputPassword" class="form-label">Contraseña</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="inputPassword" name="inputPassword" value="<?php echo htmlspecialchars($login["contrasena_normal"] ?? ''); ?>" placeholder="Mínimo 8 caracteres" minlength="8">
                                            <button class="btn-outline-secondary btn toggle-password" type="button">
                                                <i class="bi bi-eye" style="color: black"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Dejar en blanco para mantener la contraseña actual</div>
                                        <div class="invalid-feedback">La contraseña debe tener mínimo 8 caracteres</div>
                                    </div>

                                    <div class="mb-3 col-md-6">
                                        <label for="confirmPassword" class="form-label">Confirmar Contraseña</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirmPassword" value="<?php echo htmlspecialchars($login["contrasena_normal"] ?? ''); ?>" placeholder="Repite tu contraseña">
                                            <button class="btn-outline-secondary btn toggle-password" type="button">
                                                <i class="bi bi-eye" style="color: black"></i>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback">Las contraseñas no coinciden</div>
                                    </div>
                                </div>
                            </fieldset>

                            <div class="d-flex justify-content-between mt-4 col-12">
                                <button type="submit" class="px-4 btn btn-primary">Guardar Cambios</button>
                                <a href="detalle_cliente.php?id=<?php echo $id; ?>" class="px-4 btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>

                    <!-- Pestaña 2: Dominios -->
                    <div class="tab-pane fade" id="details" role="tabpanel" aria-labelledby="details-tab">
                        <div class="p-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h4>Dominios del Cliente</h4>
                            </div>

                            <?php if (!empty($dominios)): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                            <tr>
                                                <th>Proveedor</th>
                                                <th>Dominio</th>
                                                <th>Usuario Admin</th>
                                                <th>Contraseña Admin</th>
                                                <th>Fecha Contratación</th>
                                                <th>Fecha Pago</th>
                                                <th>Costo</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($dominios as $dominio): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($dominio['proveedor']); ?></td>
                                                    <td>
                                                        <a href="<?php echo htmlspecialchars($dominio['url_dominio']); ?>" target="_blank">
                                                            <?php echo htmlspecialchars($dominio['url_dominio']); ?>
                                                        </a>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($dominio['usuario']); ?></td>
                                                    <td>
                                                        <div class="input-group">
                                                            <input type="password" class="form-control form-control-sm password-field"
                                                                value="<?php echo htmlspecialchars($dominio['contrasena_normal']); ?>" readonly>
                                                            <button class="btn-outline-secondary btn btn-sm toggle-password" type="button">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($dominio['fecha_contratacion']); ?></td>
                                                    <td><?php echo htmlspecialchars($dominio['fecha_pago']); ?></td>
                                                    <td>$<?php echo number_format($dominio['costo_dominio'], 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo ($dominio['estado_dominio'] == 1) ? 'success' : 'danger'; ?>">
                                                            <?php echo ($dominio['estado_dominio'] == 1) ? 'Activo' : 'Inactivo'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="formulario_dominio.php?edit=1&id_dominio=<?php echo $dominio['id_dominio']; ?>&id_cliente=<?php echo $dominio['cliente_id']; ?>"
                                                            class="btn btn-sm btn-warning" title="Editar">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <!-- <a href="eliminar_dominio.php?id_dominio=<?php echo $dominio['id_dominio']; ?>" 
                                   class="btn btn-sm btn-danger" title="Eliminar"
                                   onclick="return confirm('¿Estás seguro de eliminar este dominio?');">
                                    <i class="bi bi-trash"></i>
                                </a> -->
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">Este cliente no tiene dominios registrados.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Pestaña 3: Hostings -->
                    <div class="tab-pane fade" id="other" role="tabpanel" aria-labelledby="other-tab">
                        <div class="p-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h4>Hostings del Cliente</h4>
                            </div>

                            <?php if (!empty($hostings)): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">

                                        <thead class="">
                                            <tr>
                                                <th>Dominio</th>
                                                <th>Nombre Hosting</th>
                                                <th>Tipo Producto</th>
                                                <th>Usuario</th>
                                                <th>Contraseña</th>
                                                <th>Costo</th>
                                                <th>Fecha Contratación</th>
                                                <th>Fecha Pago</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($hostings as $hosting): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($hosting['dominio']); ?></td>
                                                    <td><?php echo htmlspecialchars($hosting['nom_host']); ?></td>
                                                    <td><?php echo htmlspecialchars($hosting['tipo_producto']); ?></td>
                                                    <td><?php echo htmlspecialchars($hosting['usuario']); ?></td>
                                                    <td>
                                                        <div class="input-group">
                                                            <input type="password" class="form-control form-control-sm password-field"
                                                                value="<?php echo htmlspecialchars($hosting['contrasena_normal']); ?>" readonly>
                                                            <button class="btn-outline-secondary btn btn-sm toggle-password" type="button">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td>$<?php echo number_format($hosting['costo_producto'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($hosting['fecha_contratacion']); ?></td>
                                                    <td><?php echo htmlspecialchars($hosting['fecha_pago']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo ($hosting['estado_producto'] == 1) ? 'success' : 'danger'; ?>">
                                                            <?php echo ($hosting['estado_producto'] == 1) ? 'Activo' : 'Inactivo'; ?>
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <a href="formulario_hosting.php?edit=1&id_hosting=<?php echo $hosting['id_orden']; ?>&id_cliente=<?php echo $hosting['cliente_id']; ?>"
                                                            class="btn btn-sm btn-warning" title="Editar">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">Este cliente no tiene hostings registrados.</div>
                            <?php endif; ?>
                        </div>
                    </div>




                    <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
                        <div class="p-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h4>Historial de Pagos</h4>

                            </div>

                            <?php
                            $sql_cliente = "SELECT * FROM clientes WHERE id = ?";
                            $stmt_cliente = $conn->prepare($sql_cliente);
                            $stmt_cliente->bind_param("i", $id);

                            if ($stmt_cliente->execute()) {
                                $result_cliente = $stmt_cliente->get_result();
                                $cliente = $result_cliente->fetch_assoc();

                                $stmt_cliente->close();
                            }



                            // Consulta para obtener los pagos del cliente
                            $sql_pagos = "SELECT * FROM pagos WHERE id_clie = ? ORDER BY fecha_pago DESC";
                            $stmt_pagos = $conn->prepare($sql_pagos);
                            $stmt_pagos->bind_param("i", $id);

                            if ($stmt_pagos->execute()) {
                                $result_pagos = $stmt_pagos->get_result();
                                $pagos = $result_pagos->fetch_all(MYSQLI_ASSOC);
                                $stmt_pagos->close();
                            ?>
                                <div class="mt-4 mb-5">
                                    <h2>Agregar Nuevo Pago</h2>
                                    <form id="pagoForm">
                                        <input type="hidden" name="cliente_id" value="<?php echo $_GET['id']; ?>">
                                        <input type="hidden" name="cliente_facturacion" value="<?php echo isset($cliente['facturacion']) ? $cliente['facturacion'] : ''; ?>">

                                        <div class="mb-3 row">
                                            <div class="col-md-4">
                                                <label for="monto" class="form-label">Monto*</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="monto" name="monto" step="0.01" min="0.01" required>
                                                    <input type="text" class="form-control" id="montoConIVA" readonly style="display:none; background:#f8f9fa;" placeholder="Con IVA">
                                                </div>
                                                <div id="ivaHelp" class="text-success form-text" style="display:none;">Monto con IVA (16%)</div>
                                            </div>

                                            <div class="col-md-4">
                                                <label for="currency" class="form-label">Moneda*</label>
                                                <select class="custom-select" id="currency" name="currency" required>
                                                    <option value="MXN">MXN</option>
                                                    <option value="USD">USD</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4">
                                                <label for="concepto" class="form-label">Concepto*</label>
                                                <input type="text" class="form-control" id="concepto" name="concepto" required>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between mt-4">
                                            <button type="submit" class="btn btn-primary">Procesar Pago</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Fecha Pago</th>
                                                <th>Concepto</th>
                                                <th>Subtotal</th>
                                                <th>IVA (16%)</th>
                                                <th>Monto (con IVA)</th>
                                                <th>Forma de Pago</th>
                                                <th>Estatus</th>
                                                <th>ID Transacción</th>
                                                <th>Fecha Límite</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pagos as $pago):
                                                // Determinar clase CSS según estatus
                                                $estatus_class = '';
                                                if ($pago['estatus'] == 0) {
                                                    $estatus_class = 'bg-warning text-dark';
                                                } elseif ($pago['estatus'] == 1) {
                                                    $estatus_class = 'bg-success text-white';
                                                }

                                                // Formatear fecha
                                                $fecha_pago = !empty($pago['fecha_pago']) ? date('d/m/Y', strtotime($pago['fecha_pago'])) : 'Pendiente';
                                                $fecha_limite = !empty($pago['fecha_limite_pago']) ? date('d/m/Y', strtotime($pago['fecha_limite_pago'])) : 'N/A';
                                                $fecha = !empty($pago['fecha']) ? date('d/m/Y', strtotime($pago['fecha'])) : 'N/A';

                                                // Traducir forma de pago
                                                $formas_pago = [
                                                    0 => 'Pendiente',
                                                    1 => 'Tarjeta',
                                                    2 => 'Transferencia',
                                                    3 => 'Efectivo'
                                                ];
                                                $forma_pago = $formas_pago[$pago['forma_pago']] ?? 'Desconocido';

                                                // Calcular desglose de IVA si aplica
                                                $monto_total = floatval($pago['monto']);
                                                $subtotal = $monto_total;
                                                $iva = 0;
                                                if (isset($cliente['facturacion']) && $cliente['facturacion'] == 1) {
                                                    $subtotal = $monto_total / 1.16;
                                                    $iva = $monto_total - $subtotal;
                                                }
                                            ?>
                                                <tr>
                                                    <td><?php echo $fecha; ?></td>
                                                    <td>
                                                        <?php
                                                        $fecha = $pago['fecha_pago'];
                                                        echo (!empty($fecha) && $fecha !== '0000-00-00' && $fecha !== '0000000000')
                                                            ? date('d/m/Y', strtotime($fecha))
                                                            : 'Pendiente';
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($pago['concepto']); ?></td>
                                                    <td><?php echo $pago['currency'] . ' ' . number_format($subtotal, 2); ?></td>
                                                    <td><?php echo $pago['currency'] . ' ' . number_format($iva, 2); ?></td>
                                                    <td><?php echo $pago['currency'] . ' ' . number_format($monto_total, 2); ?></td>
                                                    <td><?php echo $forma_pago; ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $estatus_class; ?>">
                                                            <?php echo ($pago['estatus'] == 1) ? 'Aprobado' : 'Pendiente'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($pago['id_pago']); ?></td>
                                                    <td><?php echo $fecha_limite; ?></td>
                                                    <td>
                                                        <?php if ($pago["estatus"] == 0): ?>
                                                            <div class="btn-actions">
                                                                <button class="btn btn-sm btn-success enviar-correo-btn"
                                                                    data-id="<?php echo $pago["id"]; ?>"
                                                                    data-manual="<?php echo $pago["manual"]; ?>"
                                                                    data-tiposervicio="<?php echo $pago["tipo_servicio"]; ?>">
                                                                    <i class="fas fa-paper-plane"></i> Enviar correo
                                                                </button>

                                                                <button class="btn btn-success btn-sm btn-aprobarpago" data-id="<?php echo $pago["id"]; ?>" data-idservicio=" <?php echo $pago['id_servicio']; ?>" data-tiposervicio="<?php echo $pago['tipo_servicio']; ?>">
                                                                    Aprobar Pago
                                                                </button>
                                                            </div>
                                                        <?php endif; ?>

                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php
                            } else {
                                echo '<div class="alert alert-danger">Error al cargar los pagos: ' . $stmt_pagos->error . '</div>';
                            }

                            if (empty($pagos)): ?>
                                <div class="alert alert-info">Este cliente no tiene registros de pago.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {


            function toggleSecciones() {
                const requiereFacturacion = $('#requiereFacturacion').prop('checked');

                // Actualizar el campo hidden
                $('#facturacion').val(requiereFacturacion ? '1' : '0');

                $('#seccionEmpresarial, #seccionDireccion').toggle(requiereFacturacion);

                // Obtener todos los campos de facturación
                const camposFacturacion = $('#seccionEmpresarial input, #seccionDireccion input, #seccionDireccion select');
                const camposBasicos = $('#nombre, #email, #telefono');

                if (requiereFacturacion) {
                    // Si requiere facturación, todos los campos son obligatorios
                    camposFacturacion.prop('required', true);
                    camposBasicos.prop('required', true);

                    // Reactivar la validación para los campos de facturación
                    camposFacturacion.each(function() {
                        $(this).addClass('form-control');
                        if ($(this).attr('id') !== 'nint' && $(this).attr('id') !== 'constancia_fiscal') { // Estos campos no son obligatorios
                            $(this).prop('required', true);
                        }
                    });
                } else {
                    // Si no requiere facturación, solo los campos básicos son obligatorios
                    camposFacturacion.prop('required', false);
                    camposBasicos.prop('required', true);

                    // Limpiar los campos de facturación (solo si se está desactivando)
                    if (!requiereFacturacion) {
                        camposFacturacion.val('');
                        camposFacturacion.removeClass('is-invalid');
                    }
                }


            }

            $('#requiereFacturacion').change(function() {
                toggleSecciones();
            })

            const facturacionHabilitada = <?php echo (isset($cliente["facturacion"]) && $cliente["facturacion"] == 1) ? 'true' : 'false'; ?>;

            if (facturacionHabilitada) {
                $('#requiereFacturacion').prop('checked', true);
                $('#facturacion').val('1');
                $('#seccionEmpresarial, #seccionDireccion').show();
            } else {
                $('#requiereFacturacion').prop('checked', false);
                $('#facturacion').val('0');
                $('#seccionEmpresarial, #seccionDireccion').hide();
            }
            toggleSecciones();
            // Agregar después de la inicialización para verificar
            console.log('Estado inicial checkbox:', $('#requiereFacturacion').prop('checked'));
            console.log('Valor inicial facturacion:', $('#facturacion').val());
            console.log('Valor de cliente facturacion:', <?php echo json_encode($cliente["facturacion"] ?? 0); ?>);
            // Siempre quitar required de constancia_fiscal
            $("#constancia_fiscal").prop('required', false);
            // Evento de cambio en el checkbox
            $('#requiereFacturacion').change(toggleSecciones);

            // Inicializar el estado al cargar la página







            // 1. Función para mostrar/ocultar contraseñas
            $(document).on('click', '.toggle-password', function() {
                const input = $(this).closest('.input-group').find('input');
                const icon = $(this).find('i');

                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('bi-eye').addClass('bi-eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('bi-eye-slash').addClass('bi-eye');
                }
            });

            // 2. Confirmación para actualizar cliente
            $('form.needs-validation').on('submit', function(e) {
                e.preventDefault();
                const form = this; // Guardar referencia al formulario

                Swal.fire({
                    title: '¿Confirmar actualización?',
                    text: "¿Estás seguro de actualizar los datos del cliente?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, actualizar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Validar contraseñas si se ingresaron
                        const password = $('#inputPassword').val();
                        const confirmPassword = $('#confirmPassword').val();

                        if (password && password !== confirmPassword) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Las contraseñas no coinciden'
                            });
                            return false;
                        }

                        // Validación del formulario
                        if (!form.checkValidity()) {
                            e.stopPropagation();
                            form.classList.add('was-validated');
                            return false;
                        }

                        // Crear un formulario temporal para enviar los datos
                        const formData = new FormData(form);
                        for (const [key, value] of formData.entries()) {
                            console.log(`${key}: ${value}`);
                        }
                        // Mostrar carga
                        Swal.fire({
                            title: 'Actualizando...',
                            html: 'Por favor espera',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Enviar por AJAX
                        $.ajax({
                            url: form.action,
                            type: form.method,
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                console.log(response)
                                // Si la respuesta es JSON válida, mostrar alerta y redirigir
                                if (typeof response === 'string') {
                                    try {
                                        response = JSON.parse(response);
                                    } catch (e) {
                                        response = {};
                                    }
                                }
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '¡Éxito!',
                                        text: response.message || 'Cliente actualizado correctamente',
                                        showConfirmButton: true
                                    }).then(() => {
                                        window.location.href = response.redirect || (form.action + '?id=' + (new URLSearchParams(window.location.search).get('id')));
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message || 'Ocurrió un error al actualizar.'
                                    });
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Ocurrió un error al actualizar: ' + xhr.responseText
                                });
                            }
                        });
                    }
                });
            });

            // 3. Funcionalidad de aprobar pagos
            $(document).on('click', '.btn-aprobarpago', function() {
                const pagoId = $(this).data('id');
                const idservicio = $(this).data('idservicio');
                const tiposervicio = $(this).data('tiposervicio');


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
                    console.log("tiposervicio ",tiposervicio)
                    console.log("idservicio ",idservicio)

                    if (result.isConfirmed) {
                         procesarAprobacion(pagoId, 2, idservicio, tiposervicio);
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        procesarAprobacion(pagoId, 3, idservicio, tiposervicio);
                    }
                });
            });

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
                        tipo_servicio,
                    },
                    success: function(result) {
                        try {
                            const response = typeof result === 'string' ? JSON.parse(result) : result;
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Éxito!',
                                    text: 'El pago ha sido aprobado correctamente',
                                    confirmButtonText: 'OK'
                                }).then(() => location.reload());
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'Error al aprobar el pago'
                                });
                            }
                        } catch (e) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error procesando la respuesta del servidor'
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error de conexión: ' + xhr.statusText
                        });
                    }
                });
            }

            // 4. Funcionalidad para enviar correos
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
                            url = 'reenviar_correo_pago_pendienteManual.php';
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
                                    html: `<p><strong>Status:</strong> ${status}</p>
                                  <p><strong>Mensaje:</strong> ${error}</p>
                                  <p><strong>Respuesta:</strong><br><pre>${xhr.responseText}</pre></p>`,
                                    icon: 'error',
                                    width: 600
                                });
                            }
                        });
                    }
                });
            });

            // 5. Funcionalidad del formulario de pagos
            $('#pagoForm').submit(function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Procesando...',
                    html: 'Por favor espera mientras se completa el pago',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const formData = {
                    cliente_id: $('input[name="cliente_id"]').val(),
                    monto: $('#monto').val(),
                    currency: $('#currency').val(),
                    concepto: $('#concepto').val(),
                    cliente_facturacion: $('input[name="cliente_facturacion"]').val()
                };
                console.log(formData);
                if( formData.cliente_facturacion == 1) {
                    //sumar 16% de iva al monto
                    formData.monto = parseFloat(formData.monto) * 1.16;

                    //mostrar mensaje de confirmación mostrando el monto con IVA y el anterior
                    Swal.fire({
                        title: 'Confirmación',
                        text: `Se ha agregado el 16% de IVA al monto. Monto anterior: ${formData.monto / 1.16}, Monto con IVA: ${formData.monto}`,
                        icon: 'info',
                        confirmButtonText: 'Aceptar'
                    });
                } 
                $.ajax({
                    url: 'guardar_pago.php',
                    type: 'POST',
                    data: formData,
                    success: function(pagoId) {
                        procesarConStripe(pagoId, formData);
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire('Error', 'No se pudo guardar el pago', 'error');
                    }
                });
            });

            function procesarConStripe(pagoIdResponse, formData) {
                // Si la respuesta es un string, intentar parsear
                if (typeof pagoIdResponse === 'string') {
                    try {
                        pagoIdResponse = JSON.parse(pagoIdResponse);
                    } catch (e) {
                        pagoIdResponse = {};
                    }
                }
                // Si la respuesta es un objeto con pago_id
                const pagoId = pagoIdResponse.pago_id || pagoIdResponse.id || pagoIdResponse;
                if (!pagoId) {
                    Swal.close();
                    Swal.fire('Error', 'No se pudo obtener el ID del pago', 'error');
                    return;
                }
                // Si la respuesta trae success false, mostrar error
                if (pagoIdResponse.success === false) {
                    Swal.close();
                    Swal.fire('Error', pagoIdResponse.message || 'No se pudo guardar el pago', 'error');
                    return;
                }
                const stripeData = {
                    id: pagoId,
                    nombre: "Cliente " + formData.cliente_id,
                    costo: formData.monto,
                    moneda: formData.currency,
                    concepto: formData.concepto,
                    correo: "proyectos@conlineweb.com"
                };

                $.ajax({
                    url: '/procesar_pago.php',
                    type: 'POST',
                    data: stripeData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            actualizarPagoStripe(pagoId, response.session_id, response.session_url);
                        } else {
                            Swal.close();
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.close();
                        Swal.fire('Error', 'Error al conectar con Stripe: ' + error, 'error');
                    }
                });
            }

            function actualizarPagoStripe(pagoId, sessionId, sessionUrl) {
                console.log(pagoId, sessionId, sessionUrl)
                $.ajax({
                    url: 'enviar_correo_pago.php',
                    type: 'POST',
                    data: {
                        pago_id: pagoId,
                        session_id: sessionId,
                        session_url: sessionUrl
                    },
                    success: function(response) {
                        console.log(response)
                        Swal.close();
                        // Procesar respuesta JSON
                        if (typeof response === 'string') {
                            try {
                                response = JSON.parse(response);
                            } catch (e) {
                                response = {};
                            }
                        }
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Pago actualizado!',
                                text: response.message || 'El pago se actualizó correctamente.',
                                confirmButtonText: 'OK'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'No se pudo actualizar el pago.'
                            });
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire('Error', 'No se pudo actualizar el pago', 'error');
                    }
                });
            }

            // 6. Selectores de estado/municipio
            $('#estado').change(function() {
                const estados_id_estado = $(this).val();
                if (estados_id_estado) {
                    $.ajax({
                        url: 'obtener_municipios.php',
                        type: 'POST',
                        data: {
                            estados_id_estado: estados_id_estado
                        },
                        success: function(data) {
                            $('#municipio').html(data);
                        }
                    });
                } else {
                    $('#municipio').html('<option value="" selected disabled>Selecciona un municipio</option>');
                }
            });

            // Inicializar municipio si hay estado seleccionado
            const estadoSeleccionado = $('#estado').val();
            if (estadoSeleccionado) {
                $.ajax({
                    url: 'obtener_municipios.php',
                    type: 'POST',
                    data: {
                        estados_id_estado: estadoSeleccionado
                    },
                    success: function(data) {
                        const ciudadActual = '<?php echo $cliente["ciudad"] ?? ""; ?>';
                        $('#municipio').html(data);
                        if (ciudadActual) {
                            $('#municipio').val(ciudadActual);
                        }
                    }
                });
            }

            // Siempre quitar required de constancia_fiscal
            $("#constancia_fiscal").prop('required', false); // Siempre quitar

            // Dentro de toggleSecciones(), después de cada cambio de required:
            $("#constancia_fiscal").prop('required', false); // Nunca requerido

            $('#requiereFacturacion').on('change', function() {
                $('#facturacion').val(this.checked ? '1' : '0');
            });

            // Al cargar la página, sincronizar el valor:
            $('#facturacion').val($('#requiereFacturacion').prop('checked') ? '1' : '0');

            function actualizarInputIVA() {
                const facturacion = $('input[name="cliente_facturacion"]').val();
                const monto = parseFloat($('#monto').val()) || 0;
                const currency = $('#currency').val() || '';
                if (facturacion == '1') {
                    const montoConIVA = monto * 1.16;
                    $('#montoConIVA').val(currency + ' ' + montoConIVA.toFixed(2)).show();
                    $('#ivaHelp').show();
                } else {
                    $('#montoConIVA').hide();
                    $('#ivaHelp').hide();
                }
            }
            $('#monto, #currency').on('input change', actualizarInputIVA);
            // Mostrar/ocultar input con IVA al cargar
            actualizarInputIVA();
            // Si cambia el valor de facturación (por si se cambia dinámicamente)
            $('input[name="cliente_facturacion"]').on('change', actualizarInputIVA);
        });
    </script>
</body>

</html>