document.querySelector('.toggle-btn').addEventListener('click', function () {
    const sidebar = document.querySelector('#sidebar');
    const main = document.querySelector('.main');

    // Alternar la clase expand en el sidebar
    sidebar.classList.toggle('expand');

    // Alternar la clase expand en el main para mover el contenido
    main.classList.toggle('expand');
});


function mostrarVentana() {
    const ventana = document.getElementById('ventanaSalida');
    ventana.classList.toggle('collapse'); // Muestra u oculta la ventana  
}

function cerrarVentana() {
    const ventana = document.getElementById('ventanaSalida');
    ventana.classList.add('collapse'); // Cierra la ventana  
}

function salir() {
    alert("Saliendo..."); // Aquí puedes agregar la funcionalidad de salir  
}  
