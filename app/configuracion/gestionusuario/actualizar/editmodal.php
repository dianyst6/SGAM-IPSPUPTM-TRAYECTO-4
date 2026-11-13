<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg"> <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editLabel">Editar Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
        <form id="formEditar" action="/IPSPUPTM/app/configuracion/gestionusuario/actualizar/actualizar.php" method="post">        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">
          
          <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Nombre de usuario</label>
                <input type="text" name="username" id="edit_username" class="form-control" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Rol</label>
                <select name="role_id" id="edit_role_id" class="form-select" required>
                    <option value="1">Admin</option>
                    <option value="2">Usuario</option>
                    <option value="3">Médico</option>
                </select>
              </div>
          </div>

          <hr>
          <h6>Cambiar Contraseña (Dejar vacío para no cambiar)</h6>
          <div class="row">
              <div class="col-md-6 mb-3">
                  <div class="input-group">
                      <input type="password" name="password" id="edit_password" class="form-control" placeholder="Nueva contraseña">
                      <button class="btn btn-outline-secondary" type="button" id="toggleEditPassword">
                          <i class="fas fa-eye" id="editEyeIcon"></i>
                      </button>
                  </div>
              </div>
              <div class="col-md-6 mb-3">
                  <div class="input-group">
                      <input type="password" name="confirm_password" id="edit_confirm_password" class="form-control" placeholder="Confirmar nueva">
                      <button class="btn btn-outline-secondary" type="button" id="toggleEditConfirmPassword">
                          <i class="fas fa-eye" id="editConfirmEyeIcon"></i>
                      </button>
                  </div>
              </div>
          </div>

          <hr>
          <h6>Preguntas de Seguridad</h6>
          <?php 
          // Reutilizamos el array $preguntas que ya tenías en tu vista principal
          ?>
          <div class="mb-3">
            <select name="pregunta1_id" id="edit_p1" class="form-select mb-2" required>
                <?php foreach ($preguntas as $id => $p): ?>
                    <option value="<?= $id ?>"><?= $p ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="respuesta1" id="edit_r1" class="form-control" placeholder="Respuesta 1" required>
          </div>

          <div class="mb-3">
            <select name="pregunta2_id" id="edit_p2" class="form-select mb-2" required>
                <?php foreach ($preguntas as $id => $p): ?>
                    <option value="<?= $id ?>"><?= $p ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="respuesta2" id="edit_r2" class="form-control" placeholder="Respuesta 2" required>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>