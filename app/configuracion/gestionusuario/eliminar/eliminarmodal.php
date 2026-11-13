<div class="modal fade" id="eliminamodal" tabindex="-1" aria-labelledby="eliminamodalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eliminamodalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.
                <form id="formEliminarUsuario" action="/IPSPUPTM/app/configuracion/gestionusuario/eliminar/eliminar.php" method="POST">
                    <input type="hidden" name="id" id="usuario_id_eliminar" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formEliminarUsuario" class="btn btn-danger">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('eliminamodal').addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-bs-id');
        document.getElementById('usuario_id_eliminar').value = id;
        console.log("ID asignado al modal de eliminación:", id);
    });
</script>