<?php

include_once 'C:/xampp/htdocs/IPSPUPTM/config/estadistica.php';

// Array de meses en español
$meses_es = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];
$mes_actual_es = $meses_es[date('n') - 1];
?>

<div class="card border-0 shadow-premium-main mb-4" style="background-color: #fff; border-radius: 25px;">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-5 px-lg-2">
             <div>
                <h1 class="fw-bold mb-1" style="color: #062974; letter-spacing: -1px; font-size: 2.75rem;">
                    Control de Estadísticas
                </h1>
                <p class="text-muted mb-0 fw-semibold fs-5">Resumen analítico del periodo <?php echo ($periodo === 'mes') ? "actual (" . $mes_actual_es . ")" : "(" . date('Y') . ")"; ?></p>
             </div>
             <form method="GET" class="d-flex align-items-center bg-white p-2 rounded-3 shadow-sm mt-4 mt-lg-0 border px-3" style="min-width: 280px; height: 60px;">
                <label for="periodo" class="ms-2 me-3 fw-bold text-nowrap" style="color: #062974; font-size: 1rem;">Periodo:</label>
                <select name="periodo" id="periodo" class="form-select border-0 bg-transparent fw-bold" style="color: #062974; font-size: 1rem; cursor: pointer; min-width: 150px;" onchange="this.form.submit()">
                    <option value="anio" <?php echo ($periodo === 'anio') ? 'selected' : ''; ?>>Este Año</option>
                    <option value="mes" <?php echo ($periodo === 'mes') ? 'selected' : ''; ?>>Este Mes</option>
                </select>
             </form>
        </div>

        <div class="row g-4 mx-lg-1">
            <!-- Citas Afiliados -->
            <div class="col-md-6 col-xl-3">
                <div class="card h-100 border-0 shadow-premium-grid hover-card-dash" style="background: #062974 !important; color: #fff; border-radius: 20px;">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="icon-bubble-dash flex-shrink-0 me-3" style="background: rgba(255, 255, 255, 0.15); color: #fff;">
                            <i class="fa-solid fa-person fs-3"></i>
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-white mb-0 fw-bold small text-uppercase" style="letter-spacing: 0.5px; opacity: 0.9; font-size: 0.75rem;">Citas Afiliados</p>
                            <h2 class="fw-bold mb-0 text-white" style="font-size: 2.2rem; line-height: 1.2;"><?php echo $total_citas_afil; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Citas Beneficiarios -->
            <div class="col-md-6 col-xl-3">
                <div class="card h-100 border-0 shadow-premium-grid hover-card-dash" style="background: #062974 !important; color: #fff; border-radius: 20px;">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="icon-bubble-dash flex-shrink-0 me-3" style="background: rgba(255, 255, 255, 0.15); color: #fff;">
                            <i class="fa-solid fa-people-arrows fs-3"></i>
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-white mb-0 fw-bold small text-uppercase" style="letter-spacing: 0.5px; opacity: 0.9; font-size: 0.75rem;">Citas Beneficiarios</p>
                            <h2 class="fw-bold mb-0 text-white" style="font-size: 2.2rem; line-height: 1.2;"><?php echo $total_citas_benef; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Citas Comunidad -->
            <div class="col-md-6 col-xl-3">
                <div class="card h-100 border-0 shadow-premium-grid hover-card-dash" style="background: #062974 !important; color: #fff; border-radius: 20px;">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="icon-bubble-dash flex-shrink-0 me-3" style="background: rgba(255, 255, 255, 0.15); color: #fff;">
                            <i class="fa-solid fa-house-medical fs-3"></i>
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-white mb-0 fw-bold small text-uppercase" style="letter-spacing: 0.5px; opacity: 0.9; font-size: 0.75rem;">Citas Comunidad UPTM</p>
                            <h2 class="fw-bold mb-0 text-white" style="font-size: 2.2rem; line-height: 1.2;"><?php echo $total_citas_uptm; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Citas Totales -->
            <div class="col-md-6 col-xl-3">
                <div class="card h-100 border-0 shadow-premium-grid hover-card-dash" style="background: #062974 !important; color: #fff; border-radius: 20px;">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="icon-bubble-dash flex-shrink-0 me-3" style="background: rgba(255, 255, 255, 0.15); color: #fff;">
                            <i class="fa-solid fa-user-nurse fs-3"></i>
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-white mb-0 fw-bold small text-uppercase" style="letter-spacing: 0.5px; opacity: 0.9; font-size: 0.75rem;">Citas Totales</p>
                            <h2 class="fw-bold mb-0 text-white" style="font-size: 2.2rem; line-height: 1.2;"><?php echo $total_citas; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 pb-5">
    <!-- Distribución -->
    <div class="col-md-6 col-xl-3">
        <div class="card border border-light shadow-premium-grid h-100 p-3 hover-card-dash" style="border-radius: 25px; background: #fff;">
            <div class="card-body mt-2">
                <h5 class="fw-bold mb-4 text-center" style="color: #062974;">Distribución de Pacientes</h5>
                <div style="position: relative; height:240px; width:100%">
                    <canvas id="chartPacientes"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Progreso -->
    <div class="col-md-6 col-xl-5">
        <div class="card border border-light shadow-premium-grid h-100 p-3 hover-card-dash" style="border-radius: 25px; background: #fff;">
            <div class="card-body mt-2">
                <?php 
                    $meses_largos = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                    $mes_largo_actual = $meses_largos[date('n') - 1];
                    
                    if ($periodo === 'mes') {
                        $titulo_progreso = "Pacientes registrados en " . $mes_largo_actual;
                        $valor_progreso = $total_citas_mes;
                    } else {
                        $titulo_progreso = "Pacientes registrados en " . date('Y');
                        $valor_progreso = $total_citas;
                    }
                ?>
                <h5 class="fw-bold mb-4 text-center" style="color: #062974;"><?php echo $titulo_progreso; ?></h5>
                <div style="position: relative; height: 180px;">
                    <canvas id="chartProgreso"></canvas>
                    <div style="position: absolute; top: 65%; left: 50%; transform: translate(-50%, -50%);">
                        <span class="display-4 fw-bold" style="color: #0e213d;">
                            <?php echo $valor_progreso ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Especialidades -->
    <div class="col-md-12 col-xl-4">
        <div class="card border border-light shadow-premium-grid h-100 p-3 hover-card-dash" style="border-radius: 25px; background: #fff;">
            <div class="card-body mt-2">
                <h5 class="fw-bold mb-4 text-center" style="color: #062974;">Especialidades más visitadas</h5>
                <div style="position: relative; height:240px; width:100%;">
                    <canvas id="chartEspecialidades"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .shadow-premium-main {
        box-shadow: 0 15px 45px rgba(0, 0, 0, 0.08) !important;
    }

    .shadow-premium-grid {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08), 0 4px 8px rgba(0, 0, 0, 0.02) !important;
    }


    .hover-card-dash {
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 18px !important;
        position: relative;
        overflow: hidden;
    }

    .hover-card-dash:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12) !important;
        border-color: rgba(255, 255, 255, 0.2) !important;
    }

    .icon-bubble-dash {
        width: 65px;
        height: 65px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.4s ease;
    }

    .hover-card-dash:hover .icon-bubble-dash {
        transform: scale(1.1) rotate(-5deg);
    }
</style>

<script src="./recursos/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Chart Pacientes
    const ctx = document.getElementById('chartPacientes').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Afiliados Activos', 'Beneficiarios Activos', 'Comunidad UPTM'],
            datasets: [{
                data: [<?php echo $total_afiliados_activos; ?>, <?php echo $total_beneficiarios_activos; ?>, <?php echo $total_comunidad_uptm; ?>],
                backgroundColor: ['#0e213d', '#062974', '#27b1f1'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15, font: { weight: 'bold' } } }
            }
        }
    });

    // Chart Especialidades
    const ctxBar = document.getElementById('chartEspecialidades').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['Odontol.', 'Ginecol.', 'Imagenol.', 'Gastro.', 'Med. Int.', 'Oftalm.'],
            datasets: [{
                label: 'Visitas',
                data: [<?php echo $citas_odontologia; ?>, <?php echo $citas_ginecologia; ?>, <?php echo $citas_imagenologia; ?>, <?php echo $citas_gastroenterologia; ?>, <?php echo $citas_medicina_interna; ?>, <?php echo $citas_oftalmologia; ?>],
                backgroundColor: '#062974',
                borderRadius: 4,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { grid: { color: '#f1f5f9' }, beginAtZero: true },
                x: { grid: { display: false } }
            },
            plugins: { legend: { display: false } }
        }
    });

    // Chart Progreso
    const ctxProgreso = document.getElementById('chartProgreso').getContext('2d');
    new Chart(ctxProgreso, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [<?php echo $valor_progreso ?>, Math.max(0, 100 - <?php echo $valor_progreso ?>)],
                backgroundColor: ['#0e213d', '#f1f5f9'],
                borderWidth: 0,
                circumference: 180,
                rotation: 270,
                cutout: '82%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { tooltip: { enabled: false } }
        }
    });
});
</script>
