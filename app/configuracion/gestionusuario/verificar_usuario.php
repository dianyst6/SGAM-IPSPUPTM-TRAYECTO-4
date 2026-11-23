<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1]);
// 1. Limpiamos cualquier salida previa que pueda meter ruidos o espacios en blanco
ob_clean();

// 2. Incluimos tu base de datos de manera segura
// Ajusta bien los "../" según la distancia real entre tus carpetas
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';  

if (isset($_POST['username'])) {
    // Limpiamos los espacios alrededor de la cadena enviada por POST
    $username = trim($_POST['username']);

    if (empty($username)) {
        echo "vacio";
        exit;
    }

    // Usamos tu variable de conexión $conn
    // Forzamos que la búsqueda sea idéntica usando la estructura nativa de MySQL
    $query = "SELECT id FROM usuarios WHERE username = ? LIMIT 1";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        
        // Almacenamos el resultado internamente para poder contar las filas encontradas
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            // El usuario existe en la base de datos
            echo "existe";
        } else {
            // El usuario no existe y puede ser registrado
            echo "disponible";
        }
        
        $stmt->close();
    } else {
        // En caso de que falle la preparación SQL (útil para debuguear en la consola del navegador)
        echo "error_query";
    }
    
    $conn->close();
} else {
    echo "no_post_data";
}

// Aseguramos que no se renderice absolutamente nada más (ni espacios ni HTML accidental)
exit; 
?>