<!-- Agrega este pequeño bloque de estilos arriba o en tu CSS -->
<style>
    .config-card {
        transition: all 0.3s ease;
        border: 2px solid transparent;
        background-color: #f8f9fa;
        text-decoration: none !important;
        color: #333;
    }

    .config-card:hover {
        transform: translateY(-5px);
        border-color: #062974;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        background-color: #fff;
    }

    .config-card i {
        color: #062974;
        transition: transform 0.3s ease;
    }

    .config-card:hover i {
        transform: scale(1.1);
    }

    .btn-custom-blue {
        background-color: #062974;
        color: white;
        border: none;
    }
    
    .btn-custom-blue:hover {
        background-color: #041d52;
        color: white;
    }
</style>

<div class="container py-5">
    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="card-body p-5">
            <!-- Encabezado -->
            <div class="text-center mb-5">
                <h1 class="fw-bold" style="color: #062974; letter-spacing: -1px;">Configuración del Sistema</h1>
                <div class="mx-auto rounded-pill" style="width: 60px; height: 4px; background-color: #062974;"></div>
                <p class="text-muted mt-3 fs-5">Panel exclusivo para administradores. Gestione la seguridad y los datos de la plataforma.</p>
            </div>

            <!-- Grid de Opciones -->
            <div class="row g-4 justify-content-center">
                
                <!-- Gestión de Usuarios -->
                <div class="col-md-4">
                    <a href="/IPSPUPTM/home.php?vista=usuarios" class="card h-100 p-4 text-center shadow-sm config-card rounded-4">
                        <div class="mb-3">
                            <i class="fas fa-user-shield fa-3x"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Gestión de Usuarios</h5>
                        <p class="small text-muted mb-0">Control de accesos, roles y permisos de la comunidad.</p>
                    </a>
                </div>

                <!-- Bitácora -->
                <div class="col-md-4">
                    <a href="/IPSPUPTM/home.php?vista=bitacora" class="card h-100 p-4 text-center shadow-sm config-card rounded-4">
                        <div class="mb-3">
                            <i class="fas fa-clipboard-list fa-3x"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Bitácora</h5>
                        <p class="small text-muted mb-0">Auditoría detallada de todos los movimientos realizados.</p>
                    </a>
                </div>

                <!-- Respaldo -->
                <div class="col-md-4">
                    <div class="card h-100 p-4 text-center shadow-sm config-card rounded-4" style="cursor: pointer;" onclick="document.getElementById('form-respaldo').submit();">
                        <div class="mb-3">
                            <i class="fas fa-database fa-3x"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Base de Datos</h5>
                        <p class="small text-muted mb-3">Generar un respaldo de seguridad del sistema.</p>
                        
                        <form id="form-respaldo" action="/IPSPUPTM/app/configuracion/respaldo.php" method="post" class="d-none">
                            <!-- Formulario oculto disparado por la card -->
                        </form>
                        
                        <span class="btn btn-sm btn-custom-blue rounded-pill px-4 mt-auto">Respaldar ahora</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>