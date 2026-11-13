

function mostrarAlertaDescarga(formato, reporte) {
  alertify.message(`Se está descargando el reporte de ${reporte} en formato ${formato}.`, 2, function () { });
}

function generarReporte(tipoReporte, formato) {
  const fechaInicio = document.getElementById(`fecha_inicio_${tipoReporte}_${formato}`).value;
  const fechaFin = document.getElementById(`fecha_fin_${tipoReporte}_${formato}`).value;

  let url = `/IPSPUPTM/app/reportes/${formato}/reporte_${tipoReporte}.php?tipo_reporte=personalizado`;

  if (fechaInicio && fechaFin) {
    url += `&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
  }

  mostrarAlertaDescarga(formato.toUpperCase(), tipoReporte.charAt(0).toUpperCase() + tipoReporte.slice(1));
  window.location.href = url;
}

function generarReportePago(tipoPago, formato) {
  const fechaInicio = document.getElementById(`fecha_inicio_pagos_${formato}`).value;
  const fechaFin = document.getElementById(`fecha_fin_pagos_${formato}`).value;

  let url = `/IPSPUPTM/app/reportes/${formato}/reporte_pagos.php?tipo_pago=${tipoPago}`;

  if (fechaInicio && fechaFin) {
    url += `&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
  }

  mostrarAlertaDescarga(formato.toUpperCase(), tipoPago === 'contrato' ? 'Pagos de Contratos' : 'Pagos Externos');
  window.location.href = url;
}

function generarReporteGastosPlan(formato) {
  const cedula = document.getElementById(`cedula_gastos_${formato}`).value.trim();

  let url = `/IPSPUPTM/app/reportes/${formato}/reporte_gastos_plan.php`;

  if (cedula) {
    url += `?cedula=${encodeURIComponent(cedula)}`;
  }

  mostrarAlertaDescarga(formato.toUpperCase(), 'Historial de Gastos del Plan');
  window.location.href = url;
}