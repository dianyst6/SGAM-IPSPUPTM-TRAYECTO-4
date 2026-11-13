<?php
// config/permisos.php

/**
 * Configuración de Niveles de Acceso por Módulo/Vista
 * 
 * En este archivo se definen qué roles tienen acceso a cada módulo (vista).
 * Los IDs de los roles corresponden a los registrados en la base de datos:
 * - 1 = Administrador
 * - 2 = Usuario (Secretaria)
 * - 3 = Médico
 */

return [
    // Vistas generales
    'inicial'                => [1, 2],
    'ayuda'                  => [1, 2, 3],
    
    // Gestión de Pacientes
    'afiliados'              => [1, 2],
    'beneficiarios'          => [1, 2],
    'comunidaduptm'          => [1, 2],
    
    // Gestión de Citas
    'citas'                  => [1, 2],
    
    // Administración de Pagos y Planes
    'principalpagos'         => [1, 2],
    'gestionplanes'          => [1, 2],
    'agregarplan'            => [1, 2],
    'editarplan'             => [1, 2],
    'gestionpagoscontrato'   => [1, 2],
    'gestionpagosexternos'   => [1, 2],
    'gestionplanesasignados' => [1, 2],
    'gestionexamenes'        => [1, 2],
    'gestionpagoscitas'      => [1, 2],
    'gestioncategorias'      => [1, 2],
    'plandepago'             => [1, 2],
    
    // Gestión de Reportes
    'reportes'               => [1, 2],
    
    // Historias Médicas
    'historiasmedicas'       => [3], 
    
    // Configuración y Bitácora (Solo Administrador)
    'configuracion'          => [1],
    'bitacora'               => [1],
    'usuarios'               => [1]
];
