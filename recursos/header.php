<body>

    <header class=" bg-primary text-white fixed-top shadow-lg">
        <nav class="navbar navbar-expand-lg navbar-dark container-fluid navbar-max-width bg-custom">
            <div class="row w-100 align-items-center">
                <!-- Parte 1: Logotipo -->
                <div class="col-4 d-flex align-items-center">
                    <button id="customSidebarToggle" class="custom-sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a class="navbar-brand d-flex align-items-center navbar-logo" href="#">
                        <img src="/IPSPUPTM/recursos/img/logoipsp.png" alt="Isotipo" width="150" height="50"
                            class="d-inline-block align-top me-2">
                        <strong></strong>
                    </a>
                </div>
                <!-- Parte 2: Espacio central (puedes poner algo aquí si quieres) -->
                <div class="col-4 text-center">
                    <!-- Puedes agregar contenido aquí, por ejemplo, un título o menú -->
                </div>
                <!-- Parte 3: Usuario y menú -->
                <div class="col-4 d-flex justify-content-end">
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link text-white dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Bienvenido, <span id="nombreUsuario"><?php echo $_SESSION['username']; ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal"
                                        data-bs-target="#ventanaSalida">Cerrar sesión</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Ventana Modal (popup) -->
    <div class="modal fade" id="ventanaSalida" tabindex="-1" aria-labelledby="ventanaSalidaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="modal-title text-dark" id="ventanaSalidaLabel">Confirmar Cierre de Sesión</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-dark">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>¿Estás seguro que quieres salir?</span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="/IPSPUPTM/config/actions.php?action=logout" class="btn btn-danger">Sí, salir</a>
                </div>
            </div>
        </div>
    </div>
</body>