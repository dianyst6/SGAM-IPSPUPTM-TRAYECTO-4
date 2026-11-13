<?php
/**
 * Utilidades de identidad de persona.
 *
 * La cédula es el dato que ingresa el usuario (búsqueda, formularios).
 * id_persona es la clave interna que usa la base de datos.
 */

if (!function_exists('buscarIdPersonaPorCedula')) {
    /**
     * Devuelve el id_persona asociado a una cédula, o null si no existe.
     */
    function buscarIdPersonaPorCedula(mysqli $conn, string $cedula): ?int
    {
        $sql = "SELECT id_persona FROM persona WHERE cedula = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("s", $cedula);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado ? $resultado->fetch_assoc() : null;
        $stmt->close();

        return $fila ? (int) $fila['id_persona'] : null;
    }
}
