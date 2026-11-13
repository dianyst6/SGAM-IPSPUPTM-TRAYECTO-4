<?php
// ver_pdf.php
session_start();

// 1. Validar que el usuario esté autenticado para poder ver el archivo
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Acceso denegado.';
    exit();
}

// 2. Obtener y limpiar el nombre del archivo para evitar inyección de directorios (Path Traversal)
if (!isset($_GET['archivo'])) {
    header('HTTP/1.0 400 Bad Request');
    echo 'Archivo no especificado.';
    exit();
}

$archivo = basename($_GET['archivo']); // Limpia rutas como ../../
$directorio = 'C:/xampp/htdocs/IPSPUPTM/recursos/manuales/';
$ruta_completa = $directorio . $archivo;

// 3. Verificar que el archivo realmente exista y sea un PDF
if (file_exists($ruta_completa) && is_file($ruta_completa) && pathinfo($ruta_completa, PATHINFO_EXTENSION) === 'pdf') {
    // Definir las cabeceras para mostrar el PDF en el navegador
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $archivo . '"');
    header('Content-Transfer-Encoding: binary');
    header('Accept-Ranges: bytes');
    
    // Leer y enviar el contenido del archivo al navegador
    readfile($ruta_completa);
    exit();
} else {
    header('HTTP/1.0 404 Not Found');
    echo 'El archivo solicitado no existe.';
    exit();
}
