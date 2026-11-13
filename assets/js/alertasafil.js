function mostrarAlerta(tipo, mensaje) {
    // Obtiene el contenedor de alertas por su ID
    let alertContainer = document.getElementById('alert-container');

    // Si el contenedor no existe, lo crea dinámicamente
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alert-container';
        document.body.insertBefore(alertContainer, document.body.firstChild); // Lo inserta al principio del body
    }

    // Limpia cualquier contenido previo en el contenedor de alertas
    alertContainer.innerHTML = '';

    // Crea el elemento div para la alerta de Bootstrap
    let alertDiv = document.createElement('div');
    alertDiv.classList.add('alert', 'fade', 'show', 'mt-3');  // Clases de Bootstrap para estilo y animación
    alertDiv.classList.add(`alert-${tipo}`); // Agrega la clase específica del tipo de alerta (success, danger, etc.)
    alertDiv.setAttribute('role', 'alert');  // Establece el atributo role para accesibilidad

    // Construye el contenido HTML de la alerta
    alertDiv.innerHTML = `<strong>${tipo === 'success' ? 'Éxito' : 'Error'}:</strong> ${mensaje}
                           <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;  // Botón de cierre

    // Agrega la alerta al contenedor
    alertContainer.appendChild(alertDiv);
}

// Asigna la función mostrarAlerta al objeto window para que esté disponible globalmente
window.mostrarAlerta = mostrarAlerta;
