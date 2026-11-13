<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2, 3]);
require_once 'C:/xampp/htdocs/IPSPUPTM/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);

// Logo base64 para evitar problemas de ruta en dompdf si es necesario, 
// pero intentaremos con rutas absolutas primero.
$logo_ipsp = 'C:/xampp/htdocs/IPSPUPTM/recursos/img/IPSPUPTMlogo.png';
$logo_uptm = 'C:/xampp/htdocs/IPSPUPTM/recursos/img/UPTM_logo.png';

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 40px; }
        .header { text-align: center; border-bottom: 2px solid #062974; padding-bottom: 10px; margin-bottom: 20px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; border-top: 1px solid #ccc; padding-top: 5px; }
        h1 { color: #062974; text-align: center; font-size: 24px; }
        h2 { color: #062974; border-left: 5px solid #062974; padding-left: 10px; margin-top: 30px; font-size: 20px; }
        h3 { color: #062974; font-size: 16px; margin-top: 20px; }
        p { margin-bottom: 10px; text-align: justify; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        .table th { background-color: #062974; color: white; }
        .note { background-color: #fff3cd; border-left: 5px solid #ffc107; padding: 10px; margin: 20px 0; font-size: 13px; }
        .page-break { page-break-after: always; }
        .badge { display: inline-block; padding: 3px 7px; font-size: 11px; font-weight: bold; color: #fff; background-color: #6c757d; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 20%; border: none; text-align: left;">
                    <img src="' . $logo_ipsp . '" style="width: 80px;">
                </td>
                <td style="width: 60%; border: none; text-align: center;">
                    <strong style="font-size: 14px;">INSTITUTO DE PREVISIÓN SOCIAL DE LOS PROFESORES DE LA UPTM</strong><br>
                    <span style="font-size: 12px;">MANUAL DE USUARIO DEL SISTEMA IPSPUPTM</span>
                </td>
                <td style="width: 20%; border: none; text-align: right;">
                    <img src="' . $logo_uptm . '" style="width: 80px;">
                </td>
            </tr>
        </table>
    </div>

    <h1>Manual de Uso del Sistema IPSPUPTM</h1>
    
    <p>Bienvenido al manual de usuario oficial del sistema IPSPUPTM. Este documento tiene como objetivo guiarle a través de las diversas funcionalidades de la plataforma para garantizar una gestión eficiente de los datos institucionales, procesos médicos y administrativos.</p>

    <div class="page-break"></div>

    <h2>1. Módulo de Inicio (Estadísticas)</h2>
    <p>Al ingresar al sistema, será recibido por el tablero de control de estadísticas. Este módulo ofrece una visión analítica del periodo actual (mes o año):</p>
    <ul>
        <li><strong>Resumen de Citas:</strong> Visualización del total de citas para Afiliados, Beneficiarios y Comunidad UPTM.</li>
        <li><strong>Distribución de Pacientes:</strong> Gráficos circulares que muestran la proporción de cada tipo de paciente.</li>
        <li><strong>Especialidades más visitadas:</strong> Gráficos de barras con las áreas médicas de mayor demanda.</li>
    </ul>

    <h2>2. Gestión de Afiliados</h2>
    <p>Este módulo permite administrar los datos de los profesores y personal afiliado al instituto.</p>
    <h3>Acciones principales:</h3>
    <ul>
        <li><strong>Agregar Afiliado:</strong> Permite registrar nuevos miembros ingresando Cédula, Nombre, Apellido, Fecha de Nacimiento, entre otros.</li>
        <li><strong>Ver Plan:</strong> Permite consultar el consumo actual del plan de salud del afiliado, detallando los límites y el saldo disponible.</li>
        <li><strong>Editar/Eliminar:</strong> Gestión de los datos existentes con validaciones de seguridad.</li>
    </ul>

    <h2>3. Gestión de Beneficiarios</h2>
    <p>Permite registrar a los familiares directos de los afiliados que gozan de la cobertura.</p>
    <div class="note">
        <strong>Regla de Negocio Importante:</strong> Los beneficiarios con parentesco "Hijo" que alcancen o superen los 25 años de edad son marcados automáticamente por el sistema y no podrán recibir cobertura en nuevas citas, aunque seguirán visibles en la gestión para registros históricos.
    </div>

    <h2>4. Gestión de Citas Médicas</h2>
    <p>El corazón operativo del sistema, donde se coordinan las atenciones médicas.</p>
    <ul>
        <li><strong>Registro de Citas:</strong> Se debe seleccionar el paciente (Afiliado, Beneficiario o Comunidad), la especialidad médica, fecha, hora y descripción.</li>
        <li><strong>Estados de Pago:</strong> Las citas pueden estar en estado "Por Pagar", "Pagada" o "Deducida de Póliza".</li>
        <li><strong>Filtros:</strong> El sistema permite visualizar citas activas, pagadas o canceladas de forma independiente.</li>
    </ul>

    <div class="page-break"></div>

    <h2>5. Gestión de Pagos y Pólizas</h2>
    <p>Este módulo administrativo maneja la parte financiera y de cobertura.</p>
    <ul>
        <li><strong>Planes de Pago:</strong> Definición de categorías de exámenes y servicios con sus respectivos montos de cobertura.</li>
        <li><strong>Pago Inicial:</strong> Al registrar un nuevo contrato de plan, el sistema exige un pago inicial del 30% del monto total para activar la cobertura completa.</li>
        <li><strong>Consumo Externo:</strong> Registro de pagos realizados fuera de la institución que deben ser deducidos o registrados en el sistema.</li>
    </ul>

    <h2>6. Historias Médicas</h2>
    <p>Módulo exclusivo para el personal médico (Rol Médico). Permite:</p>
    <ul>
        <li>Consultar el historial clínico de los pacientes atendidos.</li>
        <li>Registrar observaciones, diagnósticos y tratamientos realizados durante las consultas.</li>
        <li>Mantener la confidencialidad y el seguimiento continuo de cada paciente.</li>
    </ul>

    <h2>7. Reportes y Exportación</h2>
    <p>El sistema permite generar reportes detallados en formatos PDF, Excel y Word:</p>
    <ul>
        <li><strong>Afiliados y Beneficiarios:</strong> Listados completos con datos de contacto y parentesco.</li>
        <li><strong>Citas:</strong> Reportes periódicos (Semanales, Quincenales o Mensuales).</li>
        <li><strong>Pagos:</strong> Resúmenes de transacciones por contratos o pagos externos.</li>
    </ul>

    <h2>8. Configuración y Seguridad</h2>
    <p>Acceso restringido a Administradores:</p>
    <ul>
        <li><strong>Gestión de Usuarios:</strong> Creación y edición de cuentas de acceso con roles específicos.</li>
        <li><strong>Bitácora:</strong> Registro de auditoría de todas las acciones realizadas en el sistema (quién hizo qué y cuándo).</li>
        <li><strong>Respaldo:</strong> Generación de copias de seguridad de la base de datos en formato SQL.</li>
    </ul>

    <div class="page-break"></div>

    <h2>9. Guía de Procedimientos (Paso a Paso)</h2>
    
    <h3>¿Cómo registrar un nuevo Afiliado y asignarle su Plan de Salud?</h3>
    <p>El registro de un afiliado en el sistema IPSPUPTM incluye automáticamente la creación de su contrato de salud.</p>
    <ol>
        <li>Diríjase al módulo <strong>Afiliados</strong>.</li>
        <li>Presione el botón azul <strong>"+ Agregar afiliado"</strong>.</li>
        <li><strong>Paso 1: Datos Personales.</strong> Complete la información básica (Cédula, Nombre, Apellido, Fecha de Nacimiento, etc.).</li>
        <li><strong>Paso 2: Información del Contrato.</strong> En la misma ventana, desplácese hacia abajo hasta la sección verde.</li>
        <li>Seleccione el <strong>Plan de Salud</strong> deseado (ej. Plan Básico). El sistema cargará el precio automáticamente.</li>
        <li>Defina la <strong>Vigencia</strong> (Fecha de Inicio y Fin del contrato).</li>
        <li>Elija la <strong>Frecuencia de Pago</strong> (Mensual, Trimestral, etc.) y el <strong>Día de pago</strong> (ej. 15).</li>
        <li>Presione el botón <strong>"Guardar Registro Completo"</strong>.</li>
    </ol>

    <h3>¿Cómo registrar un Beneficiario?</h3>
    <ol>
        <li>Ingrese al módulo <strong>Beneficiarios</strong>.</li>
        <li>Presione el botón <strong>"+ Agregar beneficiario"</strong>.</li>
        <li><strong>Importante:</strong> Primero debe seleccionar el Afiliado responsable de este beneficiario en la lista desplegable.</li>
        <li>Indique el <strong>Parentesco</strong> (Hijo, Cónyuge, Madre, Padre) y complete los datos personales.</li>
        <li>Presione <strong>"Guardar"</strong>.</li>
    </ol>

    <h3>¿Cómo registrar un paciente de la Comunidad UPTM?</h3>
    <ol>
        <li>Seleccione el módulo <strong>Comunidad UPTM</strong>.</li>
        <li>Presione el botón <strong>"+ Agregar"</strong>.</li>
        <li>Complete la información del paciente externo y guarde los cambios. Estos pacientes no requieren estar vinculados a un afiliado.</li>
    </ol>

    <h3>¿Cómo agendar una cita médica?</h3>
    <ol>
        <li>Diríjase al módulo <strong>Citas</strong>.</li>
        <li>Haga clic en el botón <strong>"+ Agregar cita"</strong>.</li>
        <li>En el campo <strong>Paciente</strong>, comience a escribir el nombre o cédula para filtrar y seleccione al paciente de la lista (el sistema indicará si es Afiliado, Beneficiario o Comunidad).</li>
        <li>Elija la <strong>Especialidad</strong> médica requerida.</li>
        <li>Establezca la <strong>Fecha y Hora</strong> de la consulta.</li>
        <li>Escriba una breve <strong>Descripción</strong> del motivo y presione <strong>"Guardar"</strong>.</li>
    </ol>


    <h3>¿Cómo generar Reportes?</h3>
    <ol>
        <li>Ingrese al módulo <strong>Reportes</strong> desde el menú lateral.</li>
        <li>Seleccione el <strong>Formato de descarga</strong> haciendo clic en el icono correspondiente:
            <ul>
                <li><i class="fas fa-file-pdf text-danger"></i> <strong>PDF:</strong> Para documentos listos para imprimir.</li>
                <li><i class="fas fa-file-excel text-success"></i> <strong>Excel:</strong> Para hojas de cálculo y manipulación de datos.</li>
                <li><i class="fas fa-file-word text-primary"></i> <strong>Word:</strong> Para edición de texto (si está disponible).</li>
            </ul>
        </li>
        <li>Una vez seleccionado el formato, se desplegarán las opciones de categorías. Presione el botón del reporte que necesita (ej. <strong>"Descargar reporte de Afiliados"</strong>).</li>
        <li><strong>Filtros adicionales:</strong> En casos como el reporte de <strong>Citas</strong> o <strong>Especialidades</strong>, el sistema le permitirá elegir el rango de tiempo: <strong>Semanal</strong>, <strong>Quincenal</strong> o <strong>Mensual</strong>.</li>
        <li>El archivo se generará y se descargará automáticamente en su computadora.</li>
    </ol>

    <h3>¿Cómo registrar el pago de cuotas de un plan?</h3>
    <ol>
        <li>Ingrese a <strong>Pagos</strong> -> <strong>Pagos por Contrato</strong>.</li>
        <li>Localice al afiliado en la lista o use el buscador.</li>
        <li>Visualice las cuotas pendientes. <strong>Nota:</strong> Si es el primer pago, el sistema solicitará el <strong>30% de Pago Inicial</strong>.</li>
        <li>Presione el icono de <strong>"Registrar Pago"</strong> en la cuota correspondiente.</li>
        <li>Ingrese los datos de la transferencia o comprobante y guarde.</li>
    </ol>

    <div class="page-break"></div>

    <h2>10. Preguntas Frecuentes</h2>
    <p><strong>¿Qué pasa si un hijo cumple 25 años?</strong><br>El sistema lo detectará automáticamente y mostrará una alerta naranja. No podrá agendar nuevas citas bajo la cobertura del afiliado.</p>
    <p><strong>¿Puedo eliminar una cita ya registrada?</strong><br>Sí, pero solo si la cita aún no ha sido pagada o procesada. Debe presionar el botón rojo de "Cancelar" en el módulo de Citas.</p>
    <p><strong>¿Cómo genero un respaldo de la base de datos?</strong><br>Solo los administradores pueden hacerlo desde <strong>Configuración</strong> -> <strong>Generar Respaldo</strong>. Se descargará un archivo .sql.</p>

    <div class="footer">
        IPSPUPTM - Sistema de Gestión Institucional &copy; ' . date('Y') . ' - Generado el ' . date('d/m/Y H:i') . '
    </div>
</body>
</html>';


$dompdf->loadHtml($html);
$dompdf->render();

// Forzar descarga del PDF
$dompdf->stream("Manual_Usuario_IPSPUPTM_" . date('Ymd') . ".pdf", array("Attachment" => true));
?>
