<?php
include_once 'auth_middleware.php';
include_once 'conn.php';

$uid = $_SESSION['uid'] ?? null;

// Lógica comentada (activarla si quieres redirección por contraseña)
/*
if (!$uid) {
    die("Usuario no autenticado.");
}

try {
    $stmt = $conn->prepare("SELECT cambio_contrasena FROM login WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        if ((int)$row['cambio_contrasena'] === 0) {
            header("Location: cambiar_contrasena.php");
            exit;
        }
    } else {
        die("Usuario no encontrado en la base de datos.");
    }

    echo "Usuario autenticado correctamente.";
} catch (Exception $e) {
    die("Error al consultar la base de datos: " . $e->getMessage());
}
*/

// Detectar página actual
$current_page = basename($_SERVER['PHP_SELF']);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Tabla de clientes del sistema">
    <meta name="author" content="">

    <link rel="shortcut icon" href="./images/favicon-16x16.png" type="image/x-icon">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Font Awesome -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <!-- Custom CSS -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Custom styles for sidebar -->
    <style>
        .sidebar .nav-link {
            transition: background-color 0.2s, color 0.2s;
        }

        .sidebar .nav-link:hover {
            background-color: #1a1a7a;
            color: #d1d1d1 !important;
        }

        .sidebar .nav-link:hover i {
            color: #d1d1d1 !important;
        }

        .sidebar .nav-item.active > .nav-link {
            background-color: #2929a3;
            color: #ffffff !important;
        }

        .sidebar .nav-item.active > .nav-link i {
            color: #ffffff !important;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav sidebar sidebar-light accordion text-white" id="accordionSidebar" style="background-color: #000147; font-size: 1.05rem;">

            <!-- Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
                <div>
                    <img src="images/c-online_completo.png" alt="Logo de mi empresa" style="width: 130px; height: auto;">
                </div>
            </a>

            <hr class="sidebar-divider my-0">

            <!-- Enlaces -->
            <li class="nav-item <?php echo ($current_page === 'formulario_cliente.php') ? 'active' : ''; ?>">
                <a class="nav-link text-white" href="formulario_cliente.php">
                    <i class="fas fa-user-plus mr-2"></i>
                    <span>Registro de Cliente</span>
                </a>
            </li>

            <li class="nav-item <?php echo ($current_page === 'formulario_dominio.php') ? 'active' : ''; ?>">
                <a class="nav-link text-white" href="formulario_dominio.php">
                    <i class="bi bi-globe2 mr-2"></i>
                    <span>Registro de Dominio</span>
                </a>
            </li>

            <li class="nav-item <?php echo ($current_page === 'formulario_hosting.php') ? 'active' : ''; ?>">
                <a class="nav-link text-white" href="formulario_hosting.php">
                    <i class="bi bi-hdd-network mr-2"></i>
                    <span>Registro de Hosting</span>
                </a>
            </li>

            <li class="nav-item <?php echo ($current_page === 'clientes.php') ? 'active' : ''; ?>">
                <a class="nav-link text-white" href="clientes.php">
                    <i class="fas fa-users mr-2"></i>
                    <span>Consulta de Clientes</span>
                </a>
            </li>

            <li class="nav-item <?php echo ($current_page === 'dominios.php') ? 'active' : ''; ?>">
                <a class="nav-link text-white" href="dominios.php">
                    <i class="bi bi-globe mr-2"></i>
                    <span>Consulta de Dominios</span>
                </a>
            </li>

            <li class="nav-item <?php echo ($current_page === 'hosting.php') ? 'active' : ''; ?>">
                <a class="nav-link text-white" href="hosting.php">
                    <i class="bi bi-hdd-stack mr-2"></i>
                    <span>Consulta de Hosting</span>
                </a>
            </li>

            <li class="nav-item <?php echo ($current_page === 'pagos.php') ? 'active' : ''; ?>">
                <a class="nav-link text-white" href="pagos.php">
                    <i class="fas fa-credit-card mr-2"></i>
                    <span>Consulta de Pagos</span>
                </a>
            </li>

            <hr class="sidebar-divider">

        </ul>
        <!-- End of Sidebar -->

    
