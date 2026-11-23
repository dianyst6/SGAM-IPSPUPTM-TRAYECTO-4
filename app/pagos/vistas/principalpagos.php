<?php

include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

$sql_ext = "SELECT COUNT(*) AS total 
            FROM citas c
            INNER JOIN persona u ON u.id_persona = c.id_persona
            LEFT JOIN afiliados af_u     ON af_u.id_persona = u.id_persona
            LEFT JOIN beneficiarios be_u ON be_u.id_persona = u.id_persona
            LEFT JOIN pagos_externos p ON c.id_cita = p.id_cita
            WHERE p.id_pago_ext IS NULL AND c.estado != 'cancelada'
              AND af_u.id_persona IS NULL AND be_u.id_persona IS NULL";
$res_ext = mysqli_query($conn, $sql_ext);
$pendientes_externos = mysqli_fetch_assoc($res_ext)['total'];

// Localización de fecha en español para el mes
$meses_es = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];
$mes_actual_es = $meses_es[date('n') - 1];
?>

<div class="container-fluid p-4">
    <div class="text-center mb-5">
        <h1 class="fw-bold" style="color: #062974; letter-spacing: 0.5px; font-size: 2.25rem;">Administración de Pagos</h1>
        <p class="text-muted fs-5">Seleccione el módulo de recaudación para comenzar la gestión</p>
        <div class="mx-auto" style="width: 80px; height: 3px; background: #e2e8f0; border-radius: 10px; margin-top: 15px;"></div>
    </div>

    <div class="row g-4 justify-content-center">
        <!-- Pagos de Externos -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-premium-grid hover-card-grid text-center p-3">
                <div class="card-body d-flex flex-column align-items-center">
                    <div class="icon-bubble mb-4" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="fa-solid fa-user-doctor fs-1"></i>
                    </div>
                    
                    <h3 class="h4 fw-bold mb-2" style="color: #1e293b;">Pagos de Externos</h3>
                    <p class="text-secondary mb-4 flex-grow-1" style="font-size: 0.95rem;">Gestión de cobros para Comunidad UPTM y pacientes particulares por servicios médicos.</p>
                    
                    <div class="mb-4">
                        <span class="badge rounded-pill px-3 py-2" style="background-color: rgba(25, 135, 84, 0.1); color: #198754; font-weight: 600;">
                            <i class="fas fa-clock me-1"></i> <?php echo $pendientes_externos; ?> Pendientes
                        </span>
                    </div>
                    
                    <a href="/IPSPUPTM/home.php?vista=gestionpagosexternos" class="btn btn-success-premium w-100 py-3 fw-bold">
                        Entrar al Módulo <i class="fas fa-chevron-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Pagos por Contrato -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-premium-grid hover-card-grid text-center p-3">
                <div class="card-body d-flex flex-column align-items-center">
                    <div class="icon-bubble mb-4" style="background: rgba(6, 41, 116, 0.1); color: #062974;">
                        <i class="fa-solid fa-file-invoice-dollar fs-1"></i>
                    </div>
                    
                    <h3 class="h4 fw-bold mb-2" style="color: #1e293b;">Pagos por Contrato</h3>
                    <p class="text-secondary mb-4 flex-grow-1" style="font-size: 0.95rem;">Gestion de pagos por contrato de Planes salud</p>
                    
                    <div class="mb-4">
                        <span class="badge rounded-pill px-3 py-2" style="background-color: rgba(6, 41, 116, 0.1); color: #062974; font-weight: 600;">
                            <i class="fas fa-calendar-alt me-1"></i> Mes: <?php echo $mes_actual_es; ?>
                        </span>
                    </div>
                    
                    <a href="/IPSPUPTM/home.php?vista=gestionpagoscontrato" class="btn btn-primary-premium w-100 py-3 fw-bold">
                        Entrar al Módulo <i class="fas fa-chevron-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Pagos por Seguro -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-premium-grid hover-card-grid text-center p-3">
                <div class="card-body d-flex flex-column align-items-center">
                    <div class="icon-bubble mb-4" style="background: rgba(39, 177, 241, 0.1); color: #27b1f1;">
                        <i class="fa-solid fa-hand-holding-medical fs-1"></i>
                    </div>
                    
                    <h3 class="h4 fw-bold mb-2" style="color: #1e293b;">Pagos de Citas</h3> 
                    <p class="text-secondary mb-4 flex-grow-1" style="font-size: 0.95rem;">Descuento automático de cobertura para Afiliados y Beneficiarios registrados.</p>
                    
                    <div class="mb-4">
                        <span class="badge rounded-pill px-3 py-2" style="background-color: rgba(39, 177, 241, 0.1); color: #27b1f1; font-weight: 600;">
                            <i class="fas fa-shield-alt me-1"></i> Control Activo
                        </span>
                    </div>
                    
                    <a href="/IPSPUPTM/home.php?vista=gestionpagoscitas" class="btn btn-info-premium w-100 py-3 fw-bold">
                        Entrar al Módulo <i class="fas fa-chevron-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .shadow-premium-grid {
        box-shadow: 0 15px 45px rgba(0, 0, 0, 0.1), 0 5px 15px rgba(0, 0, 0, 0.04) !important;
    }

    .hover-card-grid {
        border-radius: 20px !important;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        position: relative;
        overflow: hidden;
        border: 1px solid #f1f5f9 !important;
        background: #fff !important;
    }

    .hover-card-grid:hover {
        transform: translateY(-12px);
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.12) !important;
        border-color: #e2e8f0 !important;
    }

    .icon-bubble {
        width: 100px;
        height: 100px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.5s ease;
    }

    .hover-card-grid:hover .icon-bubble {
        transform: scale(1.1) rotate(5deg);
    }

    .btn-success-premium, .btn-primary-premium, .btn-info-premium {
        border: none;
        color: white;
        transition: all 0.3s ease;
        border-radius: 12px; /* Botones cuadrados con bordes suaves */
    }

    .btn-success-premium { background: #198754; }
    .btn-success-premium:hover {
        background: #146c43;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(25, 135, 84, 0.4);
        color: white;
    }

    .btn-primary-premium { background: #062974; }
    .btn-primary-premium:hover {
        background: #041d52;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(6, 41, 116, 0.4);
        color: white;
    }

    .btn-info-premium { background: #27b1f1; }
    .btn-info-premium:hover {
        background: #219bd4;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(39, 177, 241, 0.4);
        color: white;
    }

    @media (max-width: 768px) {
        .icon-bubble {
            width: 80px;
            height: 80px;
        }
    }
</style>