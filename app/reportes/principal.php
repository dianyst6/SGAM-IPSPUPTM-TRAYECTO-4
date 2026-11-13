<div class="card shadow-lg">
    <div class="mt-3 m-3 text-justify">
        <h1 class="fw-bold text-center" style="color: #062974;">Reportes</h1>
        <hr class="mx-auto" style="width: 50px; height: 3px; background-color: #062974;">
        
        <p class="text-center text-muted mb-4 mt-3">Seleccione el formato y el tipo de reporte que desea generar.</p>

        <!-- Nav tabs -->
        <ul class="nav nav-tabs justify-content-center mb-4" id="reportesTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fs-5" id="pdf-tab" data-bs-toggle="tab" data-bs-target="#pdf" type="button" role="tab" aria-controls="pdf" aria-selected="true">
                    <i class="fas fa-file-pdf text-danger"></i> Formato PDF
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fs-5" id="excel-tab" data-bs-toggle="tab" data-bs-target="#excel" type="button" role="tab" aria-controls="excel" aria-selected="false">
                    <i class="fas fa-file-excel text-success"></i> Formato Excel
                </button>
            </li>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content" id="reportesTabsContent">
            <!-- PDF Pane -->
            <div class="tab-pane fade show active" id="pdf" role="tabpanel" aria-labelledby="pdf-tab">
                <div class="row g-4 justify-content-center">
                    
                    <!-- Reporte de Afiliados -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-danger shadow-sm text-center">
                            <div class="card-body d-flex flex-column">
                                <i class="fas fa-users fa-3x text-danger mb-3 mt-3"></i>
                                <h5 class="card-title fw-bold">Afiliados</h5>
                                <p class="card-text text-muted mb-4">Listado completo de todos los afiliados registrados en el sistema.</p>
                                <div class="mt-auto">
                                    <a href="/IPSPUPTM/app/reportes/pdf/reporte_afiliados.php" class="btn btn-outline-danger w-100" onclick="mostrarAlertaDescarga('PDF', 'Afiliados')"><i class="fas fa-download"></i> Descargar Reporte</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de Beneficiarios -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-danger shadow-sm text-center">
                            <div class="card-body d-flex flex-column">
                                <i class="fas fa-user-friends fa-3x text-danger mb-3 mt-3"></i>
                                <h5 class="card-title fw-bold">Beneficiarios</h5>
                                <p class="card-text text-muted mb-4">Listado de los beneficiarios asociados a los afiliados.</p>
                                <div class="mt-auto">
                                    <a href="/IPSPUPTM/app/reportes/pdf/reporte_beneficiarios.php" class="btn btn-outline-danger w-100" onclick="mostrarAlertaDescarga('PDF', 'Beneficiarios')"><i class="fas fa-download"></i> Descargar Reporte</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de Citas -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-danger shadow-sm text-center">
                            <div class="card-body d-flex flex-column">
                                <i class="fas fa-calendar-check fa-3x text-danger mb-3 mt-3"></i>
                                <h5 class="card-title fw-bold">Citas</h5>
                                <p class="card-text text-muted">Historial de citas médicas en un periodo específico.</p>
                                <div class="mt-auto">
                                    <div class="row px-2">
                                        <div class="col-6 mb-2 text-start">
                                            <label class="form-label form-label-sm mb-0">Desde:</label>
                                            <input type="date" id="fecha_inicio_citas_pdf" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-6 mb-3 text-start">
                                            <label class="form-label form-label-sm mb-0">Hasta:</label>
                                            <input type="date" id="fecha_fin_citas_pdf" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-danger w-100" onclick="generarReporte('citas', 'pdf')"><i class="fas fa-download"></i> Generar Reporte</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de Especialidades -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-danger shadow-sm text-center">
                            <div class="card-body d-flex flex-column">
                                <i class="fas fa-stethoscope fa-3x text-danger mb-3 mt-3"></i>
                                <h5 class="card-title fw-bold">Especialidades</h5>
                                <p class="card-text text-muted">Estadísticas de especialidades más solicitadas por fecha.</p>
                                <div class="mt-auto">
                                    <div class="row px-2">
                                        <div class="col-6 mb-2 text-start">
                                            <label class="form-label form-label-sm mb-0">Desde:</label>
                                            <input type="date" id="fecha_inicio_especialidades_pdf" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-6 mb-3 text-start">
                                            <label class="form-label form-label-sm mb-0">Hasta:</label>
                                            <input type="date" id="fecha_fin_especialidades_pdf" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-danger w-100" onclick="generarReporte('especialidades', 'pdf')"><i class="fas fa-download"></i> Generar Reporte</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de Pagos -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-danger shadow-sm text-center">
                            <div class="card-body d-flex flex-column">
                                <i class="fas fa-file-invoice-dollar fa-3x text-danger mb-3 mt-3"></i>
                                <h5 class="card-title fw-bold">Pagos</h5>
                                <p class="card-text text-muted">Registro de pagos de contratos y pagos externos por fecha.</p>
                                <div class="mt-auto">
                                    <div class="row px-2">
                                        <div class="col-6 mb-2 text-start">
                                            <label class="form-label form-label-sm mb-0">Desde:</label>
                                            <input type="date" id="fecha_inicio_pagos_pdf" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-6 mb-3 text-start">
                                            <label class="form-label form-label-sm mb-0">Hasta:</label>
                                            <input type="date" id="fecha_fin_pagos_pdf" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-danger w-50" onclick="generarReportePago('contrato', 'pdf')" title="Pagos de Contratos">Contratos</button>
                                        <button class="btn btn-outline-danger w-50" onclick="generarReportePago('externo', 'pdf')" title="Pagos Externos">Externos</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de Historial de Gastos del Plan -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-danger shadow-sm text-center">
                            <div class="card-body d-flex flex-column">
                                <i class="fas fa-file-medical-alt fa-3x text-danger mb-3 mt-3"></i>
                                <h5 class="card-title fw-bold">Historial de Gastos del Plan</h5>
                                <p class="card-text text-muted">Montos descontados de la cobertura por afiliado y su núcleo familiar.</p>
                                <div class="mt-auto">
                                    <div class="row px-2">
                                        <div class="col-12 mb-3 text-start">
                                            <label class="form-label form-label-sm mb-0">Cédula afiliado (opcional):</label>
                                            <input type="number" id="cedula_gastos_pdf" class="form-control form-control-sm" placeholder="Dejar vacío para todos">
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-danger w-100" onclick="generarReporteGastosPlan('pdf')"><i class="fas fa-download"></i> Generar PDF</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Excel Pane -->
            <div class="tab-pane fade" id="excel" role="tabpanel" aria-labelledby="excel-tab">
                <div class="row g-4 justify-content-center">
                    
                    <!-- Reporte de Afiliados -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-success shadow-sm text-center">
                            <div class="card-body d-flex flex-column">
                                <i class="fas fa-users fa-3x text-success mb-3 mt-3"></i>
                                <h5 class="card-title fw-bold">Afiliados</h5>
                                <p class="card-text text-muted mb-4">Listado completo de todos los afiliados registrados en el sistema.</p>
                                <div class="mt-auto">
                                    <a href="/IPSPUPTM/app/reportes/excel/reporte_afiliados.php" class="btn btn-outline-success w-100" onclick="mostrarAlertaDescarga('Excel', 'Afiliados')"><i class="fas fa-download"></i> Descargar Reporte</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de Beneficiarios -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-success shadow-sm text-center">
                            <div class="card-body d-flex flex-column">
                                <i class="fas fa-user-friends fa-3x text-success mb-3 mt-3"></i>
                                <h5 class="card-title fw-bold">Beneficiarios</h5>
                                <p class="card-text text-muted mb-4">Listado de los beneficiarios asociados a los afiliados.</p>
                                <div class="mt-auto">
                                    <a href="/IPSPUPTM/app/reportes/excel/reporte_beneficiarios.php" class="btn btn-outline-success w-100" onclick="mostrarAlertaDescarga('Excel', 'Beneficiarios')"><i class="fas fa-download"></i> Descargar Reporte</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de Citas -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-success shadow-sm text-center">
                            <div class="card-body d-flex flex-column">
                                <i class="fas fa-calendar-check fa-3x text-success mb-3 mt-3"></i>
                                <h5 class="card-title fw-bold">Citas</h5>
                                <p class="card-text text-muted">Historial de citas médicas en un periodo específico.</p>
                                <div class="mt-auto">
                                    <div class="row px-2">
                                        <div class="col-6 mb-2 text-start">
                                            <label class="form-label form-label-sm mb-0">Desde:</label>
                                            <input type="date" id="fecha_inicio_citas_excel" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-6 mb-3 text-start">
                                            <label class="form-label form-label-sm mb-0">Hasta:</label>
                                            <input type="date" id="fecha_fin_citas_excel" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-success w-100" onclick="generarReporte('citas', 'excel')"><i class="fas fa-download"></i> Generar Reporte</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de Especialidades -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-success shadow-sm text-center">
                            <div class="card-body d-flex flex-column">
                                <i class="fas fa-stethoscope fa-3x text-success mb-3 mt-3"></i>
                                <h5 class="card-title fw-bold">Especialidades</h5>
                                <p class="card-text text-muted">Estadísticas de especialidades más solicitadas por fecha.</p>
                                <div class="mt-auto">
                                    <div class="row px-2">
                                        <div class="col-6 mb-2 text-start">
                                            <label class="form-label form-label-sm mb-0">Desde:</label>
                                            <input type="date" id="fecha_inicio_especialidades_excel" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-6 mb-3 text-start">
                                            <label class="form-label form-label-sm mb-0">Hasta:</label>
                                            <input type="date" id="fecha_fin_especialidades_excel" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-success w-100" onclick="generarReporte('especialidades', 'excel')"><i class="fas fa-download"></i> Generar Reporte</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de Pagos -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-success shadow-sm text-center">
                            <div class="card-body d-flex flex-column">
                                <i class="fas fa-file-invoice-dollar fa-3x text-success mb-3 mt-3"></i>
                                <h5 class="card-title fw-bold">Pagos</h5>
                                <p class="card-text text-muted">Registro de pagos de contratos y pagos externos por fecha.</p>
                                <div class="mt-auto">
                                    <div class="row px-2">
                                        <div class="col-6 mb-2 text-start">
                                            <label class="form-label form-label-sm mb-0">Desde:</label>
                                            <input type="date" id="fecha_inicio_pagos_excel" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-6 mb-3 text-start">
                                            <label class="form-label form-label-sm mb-0">Hasta:</label>
                                            <input type="date" id="fecha_fin_pagos_excel" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-success w-50" onclick="generarReportePago('contrato', 'excel')" title="Pagos de Contratos">Contratos</button>
                                        <button class="btn btn-outline-success w-50" onclick="generarReportePago('externo', 'excel')" title="Pagos Externos">Externos</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de Historial de Gastos del Plan (Excel) -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-success shadow-sm text-center">
                            <div class="card-body d-flex flex-column">
                                <i class="fas fa-file-medical-alt fa-3x text-success mb-3 mt-3"></i>
                                <h5 class="card-title fw-bold">Historial de Gastos del Plan</h5>
                                <p class="card-text text-muted">Montos descontados de la cobertura por afiliado y su núcleo familiar.</p>
                                <div class="mt-auto">
                                    <div class="row px-2">
                                        <div class="col-12 mb-3 text-start">
                                            <label class="form-label form-label-sm mb-0">Cédula afiliado (opcional):</label>
                                            <input type="number" id="cedula_gastos_excel" class="form-control form-control-sm" placeholder="Dejar vacío para todos">
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-success w-100" onclick="generarReporteGastosPlan('excel')"><i class="fas fa-download"></i> Generar Excel</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="/IPSPUPTM/assets/js/reportes.js?v=<?php echo time(); ?>"></script>