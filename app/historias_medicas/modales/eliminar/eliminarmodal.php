<div class="modal fade" id="eliminamodal" tabindex="-1" aria-labelledby="eliminamodallabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="eliminamodallabel">Aviso</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        ¿Desea eliminar esta historia médica?
      </div>
      <div class="modal-footer">
        <input type="hidden" id="elim_id_historia">
        <input type="hidden" id="elim_tipo_tabla">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="btn_confirmar_eliminar">Eliminar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('btn_confirmar_eliminar').addEventListener('click', function () {
    const id    = document.getElementById('elim_id_historia').value;
    const tabla = document.getElementById('elim_tipo_tabla').value;

    const formData = new FormData();
    formData.append('id_historia', id);
    formData.append('tipo_tabla', tabla);

    fetch('/IPSPUPTM/app/historias_medicas/modales/eliminar/eliminar.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('⚠️ Error: ' + data.message);
        }
    })
    .catch(() => alert('❌ Error de conexión.'));
});
</script>
