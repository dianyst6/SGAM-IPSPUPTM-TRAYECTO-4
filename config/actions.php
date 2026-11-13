<?php  
session_start(); // Inicia la sesión  

// Verifica si se desea cerrar la sesión  
if (isset($_GET['action']) && $_GET['action'] === 'logout') {  
    session_destroy(); // Destruye la sesión  
    header('Location: /IPSPUPTM/index.php'); // Redirige al inicio de sesión  
    exit(); // Termina la ejecución del script  
}  

// Verifica si el usuario está autenticado  
if (!isset($_SESSION['user_id'])) {  
    // Si no está autenticado, destruye la sesión y redirige  
    session_destroy();  
    header('Location: /IPSPUPTM/index.php'); // Redirige al inicio de sesión  
    exit();  
}  
?>  