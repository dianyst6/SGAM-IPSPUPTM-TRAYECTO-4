

document.getElementById('deleteButton').addEventListener('click', function() {
    alertify.confirm('Confirmación', '¿Estás seguro de que quieres eliminar todos los registros?',
        function() {
            // Si el usuario confirma
            fetch('/IPSPUPTM/app/configuracion/bitacora/eliminar.php', { method: 'POST' })
                .then(response => response.text())
                .then(data => {
                    alertify.success(data); // Mostrar mensaje de éxito
                    location.reload(); // Recargar la página
                })
                .catch(error => alertify.error('Error al eliminar registros'));
        },
        function() {
            // Si el usuario cancela
            alertify.error('Acción cancelada');
        }
    );
});

document.getElementById('downloadButton').addEventListener('click', function() {
    alertify.message('Generando PDF, espera un momento...');
    window.location.href = '/IPSPUPTM/app/configuracion/bitacora/descargar.php'; // Redirige para descargar el archivo
});
