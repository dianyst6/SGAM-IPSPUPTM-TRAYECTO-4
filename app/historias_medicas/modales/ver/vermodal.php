<!-- Modal Ver Historia Médica -->
<div class="modal fade" id="vermodal" tabindex="-1" aria-labelledby="vermodallabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="max-height: 90vh;">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold" id="vermodallabel">
          <i class="fas fa-notes-medical me-2"></i>Historia Médica
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body py-4" id="ver-modal-body" style="overflow-y: auto;">
        <div class="text-center py-4">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="mt-2 text-muted">Cargando historia...</p>
        </div>
      </div>
      <div class="modal-footer border-top shadow-sm">
        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<style>
/* Forzar que el modal no se salga de la pantalla y el pie siempre sea visible */
#vermodal .modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}
#vermodal .modal-body {
    background-color: #f8f9fa;
}
</style>

<script>
// Cargar los datos de la historia cuando se hace click en "Ver"
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-ver-historia');
    if (!btn) return;

    const id    = btn.getAttribute('data-id');
    const tabla = btn.getAttribute('data-tabla');
    const body  = document.getElementById('ver-modal-body');

    // Mostrar spinner mientras carga
    body.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Cargando historia...</p>
        </div>`;

    fetch(`/IPSPUPTM/app/historias_medicas/modales/ver/gethistoria.php?id=${id}&tabla=${tabla}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                body.innerHTML = `<div class="alert alert-danger">${res.message}</div>`;
                return;
            }
            const d = res.data;

            if (tabla === 'ginecologia') {
                body.innerHTML = renderGinecologia(d);
            } else {
                body.innerHTML = renderGeneral(d);
            }
        })
        .catch(() => {
            body.innerHTML = `<div class="alert alert-danger">Error al cargar la historia.</div>`;
        });
});

// ---- Plantilla para historia GENERAL ----
function renderGeneral(d) {
    return `
    <div class="row g-3">
        <div class="col-12"><h6 class="fw-bold text-primary border-bottom pb-1"><i class="fas fa-user me-1"></i> Datos del Paciente</h6></div>
        ${fila('Cédula', d.ci_paciente)} ${fila('Nombre', d.nombre_paciente)}
        ${fila('Tipo de Paciente', d.tipo_paciente)} ${fila('Especialidad', d.nombre_especialidad ?? 'General')}
        ${fila('Fecha Nacimiento', formatFecha(d.fecha_nacimiento))} ${fila('Edad', d.edad + ' años')}
        ${fila('Fecha Consulta', formatFecha(d.fecha))} ${fila('Dirección', d.direccion)}

        <div class="col-12 mt-2"><h6 class="fw-bold text-primary border-bottom pb-1"><i class="fas fa-stethoscope me-1"></i> Consulta</h6></div>
        ${filaDos('Motivo de Consulta', d.motivo_consulta)}
        ${filaDos('Enfermedad Actual', d.enfermedad_actual)}
        ${filaDos('Antecedentes Familiares', d.antecedentes_familiares)}
        ${filaDos('Antecedentes Personales', d.antecedentes_personales)}
        ${filaDos('Información Adicional', d.info_adicional)}
    </div>`;
}

// ---- Plantilla para historia GINECOLOGÍA ----
function renderGinecologia(d) {
    return `
    <div class="row g-3">
        <div class="col-12"><h6 class="fw-bold text-primary border-bottom pb-1"><i class="fas fa-user me-1"></i> Datos del Paciente</h6></div>
        ${fila('Cédula', d.ci_paciente)} ${fila('Nombre', d.nombre_paciente)}
        ${fila('Tipo de Paciente', d.tipo_paciente)} ${fila('Especialidad', 'Ginecología')}
        ${fila('Fecha Nacimiento', formatFecha(d.fecha_nacimiento))} ${fila('Edad', d.edad + ' años')}
        ${fila('Fecha Consulta', formatFecha(d.fecha))} ${fila('Dirección', d.direccion)}
        ${fila('Grupo Sanguíneo', d.gs)} ${fila('Fuma', d.fuma)}

        <div class="col-12 mt-2"><h6 class="fw-bold text-primary border-bottom pb-1"><i class="fas fa-stethoscope me-1"></i> Consulta</h6></div>
        ${filaDos('Motivo de Consulta', d.motivo_consulta)}
        ${filaDos('Enfermedad Actual', d.enfermedad_actual)}
        ${filaDos('Antecedentes Familiares', d.antecedentes_familiares)}
        ${filaDos('Antecedentes Personales', d.antecedentes_personales)}

        <div class="col-12 mt-2"><h6 class="fw-bold text-primary border-bottom pb-1"><i class="fas fa-female me-1"></i> Antecedentes Gineco-Obstétricos</h6></div>
        ${filaDos('Ant. Gineco-Obstétrico', d.ant_gineco_obstetrico)}
        ${fila('C.M.', d['c.m'])} ${fila('PRS', d.prs)}
        ${fila('C.S.', d.cs)} ${fila('MAC', d.mac)}
        ${fila('FUC', d.fuc)} ${fila('FUM', d.fum)}
        ${fila('Gestaciones', d.gestaciones)} ${fila('R.C.', d.rc)}
        ${fila('Año', d['año'])} ${fila('Otros', d.otros)}

        <div class="col-12 mt-2"><h6 class="fw-bold text-primary border-bottom pb-1"><i class="fas fa-heartbeat me-1"></i> Examen Físico</h6></div>
        ${fila('T.A.', d['ex.fisico.t.a'])} ${fila('F.C.', d['f.c'])}
        ${fila('Peso', d.peso)} ${fila('Talla', d.talla)}
        ${fila('Cabeza', d.cabeza)} ${fila('O.R.L.', d['o.r.l'])}
        ${fila('C.V.', d['c.v'])} ${fila('Tiroides', d.tiroides)}
        ${fila('Mamas', d.mamas)} ${fila('Abdomen', d.abdomen)}
        ${filaDos('Ginecológico', d.ginecologico)}

        <div class="col-12 mt-2"><h6 class="fw-bold text-primary border-bottom pb-1"><i class="fas fa-notes-medical me-1"></i> Diagnóstico y Conducta</h6></div>
        ${filaDos('Ultrasonido', d.ultrasonido)}
        ${filaDos('Diagnóstico', d.diagnostico)}
        ${filaDos('Conducta / Tratamiento', d.conducta)}
    </div>`;
}

// ---- Helpers ----
function fila(label, val) {
    const v = val && String(val).trim() ? htmlEsc(val) : '<span class="text-muted fst-italic">—</span>';
    return `<div class="col-md-3 col-6"><small class="text-muted d-block">${label}</small><span class="fw-semibold">${v}</span></div>`;
}
function filaDos(label, val) {
    const v = val && String(val).trim() ? htmlEsc(val) : '<span class="text-muted fst-italic">—</span>';
    return `<div class="col-12 col-md-6"><small class="text-muted d-block">${label}</small><span class="fw-semibold">${v}</span></div>`;
}
function formatFecha(f) {
    if (!f) return '—';
    const p = f.split('-');
    return p.length === 3 ? `${p[2]}-${p[1]}-${p[0]}` : f;
}
function htmlEsc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
