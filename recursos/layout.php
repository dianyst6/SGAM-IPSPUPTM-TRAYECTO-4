<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control IPSPUPTM</title>

    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="/IPSPUPTM/assets/css/bootstrap.min.css">

    <!-- Font Awesome local -->
    <link rel="stylesheet" href="/IPSPUPTM/assets/fontawesome/css/all.min.css"> <!-- Ruta local -->

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="/IPSPUPTM/assets/css/style.css"> <!-- Ruta personalizada -->


</head>

<body>

    <?php include 'C:/xampp/htdocs/IPSPUPTM/recursos/header.php' ?>
    <?php include 'C:/xampp/htdocs/IPSPUPTM/config/alertify.php' ?>
    <?php
    // Configuración de manuales dinámicos por sección
    $manuales_pdf = [
        'inicial' => 'manual_general.pdf',
        'afiliados' => 'manual_afiliados.pdf',
        'beneficiarios' => 'manual_beneficiarios.pdf',
        'comunidaduptm' => 'manual_comunidad.pdf',
        'citas' => 'manual_citas.pdf',
        'principalpagos' => 'manual_pagos.pdf',
        'gestionplanes' => 'manual_planes.pdf',
        'historiasmedicas' => 'manual_historias_medicas.pdf',
        'reportes' => 'manual_reportes.pdf',
        'configuracion' => 'manual_configuracion.pdf',
        'bitacora' => 'manual_bitacora.pdf',
        'usuarios' => 'manual_usuarios.pdf'
    ];
    $archivo_ayuda = isset($manuales_pdf[$vista]) ? $manuales_pdf[$vista] : 'manual_general.pdf';
    $ruta_ayuda = "/IPSPUPTM/ver_pdf.php?archivo=" . $archivo_ayuda;
    ?>

    <!-- Botón flotante de ayuda dinámica -->
    <a href="<?php echo $ruta_ayuda; ?>" target="_blank" class="btn-help-floating" title="Ayuda de esta sección">
        <i class="fas fa-question"></i>
    </a>


    <div class="">
        <aside id="custom-sidebar">

            <ul class="custom-sidebar-nav">
                <?php
                $role_id = $_SESSION['role_id'];
                if ($role_id == 1) { // Administrador
                    ?>
                    <li><a href="/IPSPUPTM/home.php?vista=inicial"
                            class="<?php echo ($vista == 'inicial') ? 'active' : ''; ?>"><i class="fas fa-home"></i>
                            Inicio</a></li>
                    <li>
                        <?php
                        $isPacientes = in_array($vista, ['afiliados', 'beneficiarios', 'comunidaduptm']);
                        ?>
                        <a href="#submenuPacientes" data-bs-toggle="collapse"
                            aria-expanded="<?php echo $isPacientes ? 'true' : 'false'; ?>"
                            class="dropdown-toggle <?php echo $isPacientes ? 'active' : 'collapsed'; ?>">
                            <i class="fas fa-user-plus"></i> Gestión <br>de Pacientes
                        </a>
                        <ul class="collapse list-unstyled <?php echo $isPacientes ? 'show' : ''; ?>" id="submenuPacientes">
                            <li><a href="/IPSPUPTM/home.php?vista=afiliados"
                                    class="<?php echo ($vista == 'afiliados') ? 'active' : ''; ?>">Afiliados</a></li>
                            <li><a href="/IPSPUPTM/home.php?vista=beneficiarios"
                                    class="<?php echo ($vista == 'beneficiarios') ? 'active' : ''; ?>">Beneficiarios</a>
                            </li>
                            <li><a href="/IPSPUPTM/home.php?vista=comunidaduptm"
                                    class="<?php echo ($vista == 'comunidaduptm') ? 'active' : ''; ?>">Comunidad UPTM</a>
                            </li>
                        </ul>
                    </li>

                    <li><a href="/IPSPUPTM/home.php?vista=citas"
                            class="<?php echo ($vista == 'citas') ? 'active' : ''; ?>"><i
                                class="fas fa-calendar-plus"></i>Gestión de Citas</a>
                    </li>
                    <li>
                        <?php
                        $isPagos = in_array($vista, ['principalpagos', 'gestionplanes', 'agregarplan', 'editarplan', 'gestionpagoscontrato', 'gestionpagosexternos', 'gestionplanesasignados', 'gestionexamenes', 'gestionpagoscitas', 'gestioncategorias']);
                        ?>
                        <a href="#submenuPagos" data-bs-toggle="collapse"
                            aria-expanded="<?php echo $isPagos ? 'true' : 'false'; ?>"
                            class="dropdown-toggle <?php echo $isPagos ? 'active' : 'collapsed'; ?>">
                            <i class="fa-solid fa-money-check-dollar"></i> Administracion <br>de Pagos
                        </a>
                        <ul class="collapse list-unstyled <?php echo $isPagos ? 'show' : ''; ?>" id="submenuPagos">
                            <li><a href="/IPSPUPTM/home.php?vista=principalpagos"
                                    class="<?php echo ($vista == 'principalpagos') ? 'active' : ''; ?>">Gestionar pagos</a>
                            </li>
                            <li><a href="/IPSPUPTM/home.php?vista=gestionplanes"
                                    class="<?php echo ($vista == 'gestionplanes') ? 'active' : ''; ?>">Gestionar Planes
                                    salud</a></li>

                        </ul>
                    </li>
                    <li><a href="/IPSPUPTM/home.php?vista=reportes"
                            class="<?php echo ($vista == 'reportes') ? 'active' : ''; ?>"><i
                                class="fas fa-chart-bar"></i>Gestión de Reportes</a>
                    </li>

                    <li><a href="/IPSPUPTM/home.php?vista=configuracion"
                            class="<?php echo ($vista == 'configuracion' || $vista == 'bitacora' || $vista == 'usuarios') ? 'active' : ''; ?>"><i
                                class="fas fa-cog"></i> Configuración</a></li>
                <?php } ?>

                <?php
                $role_id = $_SESSION['role_id'];
                if ($role_id == 2) { // Secretaria
                    ?>
                    <li><a href="/IPSPUPTM/home.php?vista=inicial"
                            class="<?php echo ($vista == 'inicial') ? 'active' : ''; ?>"><i class="fas fa-home"></i>
                            Inicio</a></li>
                    <li>
                        <?php
                        $isPacientes = in_array($vista, ['afiliados', 'beneficiarios', 'comunidaduptm']);
                        ?>
                        <a href="#submenuPacientes" data-bs-toggle="collapse"
                            aria-expanded="<?php echo $isPacientes ? 'true' : 'false'; ?>"
                            class="dropdown-toggle <?php echo $isPacientes ? 'active' : 'collapsed'; ?>">
                            <i class="fas fa-user-plus"></i> Gestión <br>de Pacientes
                        </a>
                        <ul class="collapse list-unstyled <?php echo $isPacientes ? 'show' : ''; ?>" id="submenuPacientes">
                            <li><a href="/IPSPUPTM/home.php?vista=afiliados"
                                    class="<?php echo ($vista == 'afiliados') ? 'active' : ''; ?>">Afiliados</a></li>
                            <li><a href="/IPSPUPTM/home.php?vista=beneficiarios"
                                    class="<?php echo ($vista == 'beneficiarios') ? 'active' : ''; ?>">Beneficiarios</a>
                            </li>
                            <li><a href="/IPSPUPTM/home.php?vista=comunidaduptm"
                                    class="<?php echo ($vista == 'comunidaduptm') ? 'active' : ''; ?>">Comunidad UPTM</a>
                            </li>
                        </ul>
                    </li>

                    <li><a href="/IPSPUPTM/home.php?vista=citas"
                            class="<?php echo ($vista == 'citas') ? 'active' : ''; ?>"><i
                                class="fas fa-calendar-plus"></i>Gestión de Citas</a>
                    </li>
                    <li>
                        <?php
                        $isPagos = in_array($vista, ['principalpagos', 'gestionplanes', 'agregarplan', 'editarplan', 'gestionpagoscontrato', 'gestionpagosexternos', 'gestionplanesasignados', 'gestionexamenes', 'gestionpagoscitas', 'gestioncategorias']);
                        ?>
                        <a href="#submenuPagos" data-bs-toggle="collapse"
                            aria-expanded="<?php echo $isPagos ? 'true' : 'false'; ?>"
                            class="dropdown-toggle <?php echo $isPagos ? 'active' : 'collapsed'; ?>">
                            <i class="fa-solid fa-money-check-dollar"></i> Administracion <br>de Pagos
                        </a>
                        <ul class="collapse list-unstyled <?php echo $isPagos ? 'show' : ''; ?>" id="submenuPagos">
                            <li><a href="/IPSPUPTM/home.php?vista=principalpagos"
                                    class="<?php echo ($vista == 'principalpagos') ? 'active' : ''; ?>">Gestionar pagos</a>
                            </li>
                            <li><a href="/IPSPUPTM/home.php?vista=gestionplanes"
                                    class="<?php echo ($vista == 'gestionplanes') ? 'active' : ''; ?>">Gestionar Planes
                                    salud</a></li>

                        </ul>
                    </li>

                    <li><a href="/IPSPUPTM/home.php?vista=reportes"
                            class="<?php echo ($vista == 'reportes') ? 'active' : ''; ?>"><i
                                class="fas fa-chart-bar"></i>Gestión de Reportes</a>
                    </li>
                <?php } ?>

                <?php
                $role_id = $_SESSION['role_id'];
                if ($role_id == 3) { // Medico
                    ?>

                    <li><a href="/IPSPUPTM/home.php?vista=historiasmedicas"
                            class="<?php echo ($vista == 'historiasmedicas') ? 'active' : ''; ?>"><i
                                class="fas fa-book-medical"></i>Historias Médicas</a></li>
                <?php } ?>

                <li><a href="<?php echo $ruta_ayuda; ?>" target="_blank"
                        class="<?php echo ($vista == 'ayuda') ? 'active' : ''; ?>"><i
                            class="fas fa-question-circle"></i> Ayuda</a></li>
            </ul>
        </aside>
        <div class="cont-general mt-1 pt-1" style="width: 100%; flex-grow: 1;">
            <div class="main p-3">
                <?php if ($vista !== 'inicial'): ?>
                    <div class="card shadow-lg">
                        <div>
                        <?php endif; ?>

                        <?php
                        // Incluir el contenido específico del módulo
                        if (isset($contenido)) {
                            include $contenido;
                        } else {
                            echo "<p>Contenido no disponible.</p>";
                        }
                        ?>

                        <?php if ($vista !== 'inicial'): ?>
                        </div>
                    </div>
                <?php endif; ?>





            </div>
        </div>
    </div>

    <?php include 'C:/xampp/htdocs/IPSPUPTM/recursos/alertas/eliminadomodal.php' ?>


    <!-- Bootstrap JS desde CDN -->
    <script src="/IPSPUPTM/assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>

    <!-- Script personalizado -->
    <script src="/IPSPUPTM/assets/js/script.js"></script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <script>
        document.getElementById('customSidebarToggle').onclick = function () {
            document.getElementById('custom-sidebar').classList.toggle('active');
        };
    </script>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'no_autorizado'): ?>
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            alertify.error('No tiene permisos para acceder a este módulo.');
        });
    </script>
    <?php endif; ?>
<footer class="footerm">
    <div class="container text-center">
        <p>&copy; <?php echo date("Y"); ?> IPSPUPTM. Todos los derechos reservados.</p>
        <p>Diseñado por: Gabriela, Gregory, Dianys, Alondra.</p>
    </div>
</footer>
</body>
