<?php

include 'C:/xampp/htdocs/IPSPUPTM/config/actions.php';

// Determinar la vista a cargar
$vista = isset($_GET['vista']) ? $_GET['vista'] : (($_SESSION['role_id'] == 3) ? 'historiasmedicas' : 'inicial');

// Validar permisos por nivel de usuario
$permisos = require 'C:/xampp/htdocs/IPSPUPTM/config/permisos.php';
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;

if (!isset($permisos[$vista]) || !in_array($role_id, $permisos[$vista])) {
    $default_vista = ($role_id == 3) ? 'historiasmedicas' : 'inicial';
    // Evitar bucle de redirección infinita
    if ($vista !== $default_vista) {
        header("Location: /IPSPUPTM/home.php?vista=" . $default_vista . "&error=no_autorizado");
        exit();
    }
}

// Asignar el archivo de contenido correspondiente a la vista
switch ($vista) {
    case 'afiliados':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/afiliados/principal.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';

        break;
    case 'beneficiarios':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/beneficiarios/principal.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
        break;
    case 'comunidaduptm':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/comunidaduptm/principal.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
        break;
    case 'citas':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/citas/principal.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
        break;
     case 'agregarplan':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/pagos/agregar_plan.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
        break;
     case 'editarplan':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/pagos/editar_plan.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
        break;
     case 'gestionpagoscontrato':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/pagos/vistas/gestion_pagoscontrato.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
        break;
    case 'gestionpagosexternos':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/pagos/vistas/gestion_pagosexternos.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
        break;
    case 'principalpagos':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/pagos/vistas/principalpagos.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
        break;


    case 'gestionplanes':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/pagos/vistas/gestion_planes.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
        break;
    case 'gestionplanesasignados':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/pagos/vistas/gestion_planesasignados.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
        break;
    case 'gestionexamenes':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/pagos/vistas/gestion_examenes.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
        break;
    case 'gestionpagoscitas':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/pagos/vistas/gestion_pagoscitas.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
        break;
     case 'historiasmedicas':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/historias_medicas/principal.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
        break;
    case 'gestioncategorias':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/pagos/vistas/gestion_categorias.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
        break;
    case 'reportes':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/reportes/principal.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';

        break;
    case 'configuracion':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/configuracion/principal.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
        break;
    case 'bitacora':
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/configuracion/bitacora/vistabitacora.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
        break;
    case 'usuarios':
            $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/configuracion/gestionusuario/vistausuarios.php';
            include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';
            break;
    case 'ayuda' :
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/ayuda/principal.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';

        break;
    case 'plandepago' :
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/plan_pagos/principal.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';

        break;
    case 'gestionplanes' :
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/app/pagos/gestion_planes.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';

        break;
        
        
    default:
        $contenido = $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/vistas/contenido_inicio.php';
        include $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/recursos/layout.php';

        break;
    
}
?>