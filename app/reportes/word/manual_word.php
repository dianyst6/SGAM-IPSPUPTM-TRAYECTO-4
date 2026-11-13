<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2, 3]);
require_once 'C:/xampp/htdocs/IPSPUPTM/vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

$phpWord = new PhpWord();

// Estilos de fuente
$phpWord->addTitleStyle(1, array('bold' => true, 'color' => '062974', 'size' => 20));
$phpWord->addTitleStyle(2, array('bold' => true, 'color' => '062974', 'size' => 16, 'borderBottomSize' => 6));
$phpWord->addTitleStyle(3, array('bold' => true, 'color' => '062974', 'size' => 12));

$section = $phpWord->addSection();

// Cabecera con "Logos" (Texto por ahora, ya que el usuario agregará imágenes)
$header = $section->addHeader();
$header->addText('INSTITUTO DE PREVISIÓN SOCIAL DE LOS PROFESORES DE LA UPTM', array('bold' => true, 'size' => 10), array('alignment' => Jc::CENTER));
$header->addText('MANUAL DE USUARIO DEL SISTEMA IPSPUPTM', array('size' => 9), array('alignment' => Jc::CENTER));

// Título Principal
$section->addTitle('Manual de Uso del Sistema IPSPUPTM', 1);
$section->addTextBreak(1);

$section->addText('Bienvenido al manual de usuario oficial del sistema IPSPUPTM. Este documento tiene como objetivo guiarle a través de las diversas funcionalidades de la plataforma para garantizar una gestión eficiente de los datos institucionales, procesos médicos y administrativos.');

$section->addPageBreak();

// Secciones
$section->addTitle('1. Módulo de Inicio (Estadísticas)', 2);
$section->addText('Al ingresar al sistema, será recibido por el tablero de control de estadísticas. Este módulo ofrece una visión analítica del periodo actual (mes o año):');
$section->addListItem('Resumen de Citas: Visualización del total de citas para Afiliados, Beneficiarios y Comunidad UPTM.');
$section->addListItem('Distribución de Pacientes: Gráficos circulares que muestran la proporción de cada tipo de paciente.');
$section->addListItem('Especialidades más visitadas: Gráficos de barras con las áreas médicas de mayor demanda.');

$section->addTitle('2. Gestión de Afiliados', 2);
$section->addText('Este módulo permite administrar los datos de los profesores y personal afiliado al instituto.');
$section->addTitle('Acciones principales:', 3);
$section->addListItem('Agregar Afiliado: Permite registrar nuevos miembros e iniciar su contrato de salud.');
$section->addListItem('Ver Plan: Permite consultar el consumo actual del plan de salud del afiliado.');
$section->addListItem('Editar/Eliminar: Gestión de los datos existentes.');

$section->addTitle('3. Gestión de Beneficiarios', 2);
$section->addText('Permite registrar a los familiares directos de los afiliados.');
$section->addText('Regla de Negocio Importante: Los beneficiarios con parentesco "Hijo" que alcancen o superen los 25 años no podrán recibir cobertura en nuevas citas.', array('italic' => true, 'color' => '856404'));

$section->addTitle('4. Gestión de Citas Médicas', 2);
$section->addListItem('Registro de Citas: Selección de paciente, especialidad, fecha y hora.');
$section->addListItem('Estados de Pago: "Por Pagar", "Pagada" o "Deducida de Póliza".');

$section->addPageBreak();

$section->addTitle('9. Guía de Procedimientos (Paso a Paso)', 2);

$section->addTitle('¿Cómo registrar un nuevo Afiliado y asignarle su Plan de Salud?', 3);
$section->addListItem('Diríjase al módulo Afiliados.');
$section->addListItem('Presione el botón azul "+ Agregar afiliado".');
$section->addListItem('Complete la información de "Datos Personales".');
$section->addListItem('Desplácese hacia abajo a la sección "Información del Contrato".');
$section->addListItem('Seleccione el Plan de Salud, vigencia y frecuencia de pago.');
$section->addListItem('Presione "Guardar Registro Completo".');

$section->addTitle('¿Cómo registrar un Beneficiario?', 3);
$section->addListItem('Ingrese al módulo Beneficiarios.');
$section->addListItem('Presione "+ Agregar beneficiario".');
$section->addListItem('Seleccione el Afiliado responsable.');
$section->addListItem('Indique el Parentesco y complete los datos.');
$section->addListItem('Presione "Guardar".');

$section->addTitle('¿Cómo agendar una cita médica?', 3);
$section->addListItem('Diríjase al módulo Citas.');
$section->addListItem('Haga clic en "+ Agregar cita".');
$section->addListItem('Busque y seleccione al paciente.');
$section->addListItem('Elija la Especialidad y la fecha/hora.');
$section->addListItem('Presione "Guardar".');

$section->addTitle('¿Cómo generar Reportes?', 3);
$section->addListItem('Ingrese al módulo Reportes.');
$section->addListItem('Seleccione el Formato de descarga (PDF, Excel o Word).');
$section->addListItem('Elija la categoría y el periodo si es necesario.');

$section->addTitle('¿Cómo registrar el pago de cuotas de un plan?', 3);
$section->addListItem('Ingrese a Pagos -> Pagos por Contrato.');
$section->addListItem('Presione el icono de "Registrar Pago" en la cuota correspondiente.');
$section->addListItem('Nota: El primer pago requiere el 30% de Pago Inicial.');

$section->addPageBreak();

$section->addTitle('10. Preguntas Frecuentes', 2);
$section->addText('¿Qué pasa si un hijo cumple 25 años? El sistema mostrará una alerta y bloqueará nuevas coberturas.');
$section->addText('¿Cómo genero un respaldo? Desde Configuración -> Generar Respaldo.');

// Footer
$footer = $section->addFooter();
$footer->addText('IPSPUPTM - Manual de Usuario ' . date('Y'), array('size' => 8), array('alignment' => Jc::CENTER));
$footer->addPreserveText('Página {PAGE} de {NUMPAGES}', array('size' => 8), array('alignment' => Jc::CENTER));

// Guardar y descargar
$filename = "Manual_Usuario_IPSPUPTM_" . date('Ymd') . ".docx";
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$objWriter = IOFactory::createWriter($phpWord, 'Word2017');
$objWriter->save('php://output');
exit;
