<?php
// 1.3 Guard central: solo web (las migraciones CLI siguen disponibles)
if (PHP_SAPI !== 'cli') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
    auth_guard([1]);
}
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

echo "Iniciando migración de base de datos...\n";

// 1. Crear tabla categorias_examenes
$sql1 = "CREATE TABLE IF NOT EXISTS categorias_examenes (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre_categoria VARCHAR(100) NOT NULL,
    descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if (mysqli_query($conn, $sql1)) {
    echo "Tabla 'categorias_examenes' creada o ya existía.\n";
} else {
    die("Error al crear tabla: " . mysqli_error($conn));
}

// 2. Insertar categorías iniciales si no existen
$sql2 = "INSERT INTO categorias_examenes (nombre_categoria, descripcion) 
         SELECT 'Consultas', 'Todas las consultas médicas generales y especializadas'
         WHERE NOT EXISTS (SELECT 1 FROM categorias_examenes WHERE nombre_categoria = 'Consultas');";
mysqli_query($conn, $sql2);

// 3. Modificar tabla examenes para incluir id_categoria
$sql3 = "ALTER TABLE examenes ADD COLUMN id_categoria INT NULL AFTER ID_especialidad_examenes,
         ADD CONSTRAINT fk_examen_categoria FOREIGN KEY (id_categoria) REFERENCES categorias_examenes(id_categoria) ON DELETE SET NULL;";

if (mysqli_query($conn, $sql3)) {
    echo "Columna 'id_categoria' añadida a 'examenes'.\n";
} else {
    echo "Aviso: La columna 'id_categoria' ya puede existir o hubo un error.\n";
}

// 4. Modificar tabla componentes_planes
$sql4 = "ALTER TABLE componentes_planes 
         MODIFY COLUMN ID_examen_componentes INT NULL,
         ADD COLUMN id_categoria_componente INT NULL AFTER ID_examen_componentes,
         ADD CONSTRAINT fk_componente_categoria FOREIGN KEY (id_categoria_componente) REFERENCES categorias_examenes(id_categoria) ON DELETE CASCADE;";

if (mysqli_query($conn, $sql4)) {
    echo "Tabla 'componentes_planes' modificada correctamente.\n";
} else {
    echo "Aviso: La tabla 'componentes_planes' ya puede tener estas modificaciones o hubo un error.\n";
}

// 5. Asignar categoría 'Consultas' a los exámenes actuales que parezcan consultas
$id_consultas = 1; // Asumimos que es el primero insertado
$sql5 = "UPDATE examenes SET id_categoria = (SELECT id_categoria FROM categorias_examenes WHERE nombre_categoria = 'Consultas' LIMIT 1) 
         WHERE nombre_examen LIKE '%Consulta%';";
mysqli_query($conn, $sql5);

echo "Migración completada con éxito.\n";
?>
