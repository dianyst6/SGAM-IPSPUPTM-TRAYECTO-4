
<!-- MODAL PARA CREAR NUEVO EXAMEN -->
<div class="modal fade" id="modalNuevoExamen" tabindex="-1" aria-labelledby="modalNuevoExamenLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold" id="modalNuevoExamenLabel">Registrar Examen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-nuevo-examen">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Examen</label>
                        <input type="text" name="nombre_examen" maxlength="150" pattern="[a-zA-ZáéíóúüñÑÁÉÍÓÚÜÑ\s]+" title="Solo se permiten letras" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Especialidad Asociada</label>
                        <select name="id_especialidad" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <?php
                            $esp = mysqli_query($conn, "SELECT id_especialidad, nombre_especialidad FROM especialidades");
                            while($e = mysqli_fetch_assoc($esp)) {
                                echo "<option value='{$e['id_especialidad']}'>{$e['nombre_especialidad']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoría del Examen</label>
                        <select name="id_categoria" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <?php
                            $cat_list = mysqli_query($conn, "SELECT id_categoria, nombre_categoria FROM categorias_examenes ORDER BY nombre_categoria ASC");
                            while($cl = mysqli_fetch_assoc($cat_list)) {
                                echo "<option value='{$cl['id_categoria']}'>{$cl['nombre_categoria']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Precio Base ($)</label>
                        <input type="number" step="0.01" name="precio" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-info text-white">Guardar Examen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL PARA CREAR NUEVA CATEGORÍA -->
<div class="modal fade" id="modalNuevaCategoria" tabindex="-1" aria-labelledby="modalNuevaCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="modalNuevaCategoriaLabel">Nueva Categoría de Exámenes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-nueva-categoria">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre de la Categoría</label>
                        <input type="text" name="nombre_categoria" class="form-control" placeholder="Ej: Consultas Médicas" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3" placeholder="Opcional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success">Guardar Categoría</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('form-nuevo-examen').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('/IPSPUPTM/app/pagos/guardar_examen_nuevo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alertify.success(data.message);
            const selects = document.querySelectorAll('select[name="id_examen[]"]');
            selects.forEach(select => {
                const option = document.createElement('option');
                option.value = data.id_examen;
                option.text = data.nombre_examen;
                select.add(option);
            });
            this.reset();
            bootstrap.Modal.getInstance(document.getElementById('modalNuevoExamen')).hide();
            // Disparar evento personalizado para que otras vistas puedan reaccionar (ej: recargar tabla)
            document.dispatchEvent(new CustomEvent('examenCreado', { detail: data }));
        } else {
            alertify.error(data.message);
        }
    })
    .catch(error => alertify.error("Error al guardar el examen"));
});

document.getElementById('form-nueva-categoria').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('/IPSPUPTM/app/pagos/guardar_categoria_nueva.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alertify.success(data.message);
            const selects = document.querySelectorAll('select[name="id_categoria_comp[]"], select[name="id_categoria"]');
            selects.forEach(select => {
                const option = document.createElement('option');
                option.value = data.id_categoria;
                option.text = data.nombre_categoria;
                select.add(option);
            });
            this.reset();
            bootstrap.Modal.getInstance(document.getElementById('modalNuevaCategoria')).hide();
        } else {
            alertify.error(data.message);
        }
    })
    .catch(error => alertify.error("Error al guardar la categoría"));
});
</script>
