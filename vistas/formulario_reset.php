<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4">
                <h2>Restablecer Contraseña</h2>
                <form action="/IPSPUPTM/vistas/actualizar_contrasena.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" name="nueva_password" id="reset_password" class="form-control" placeholder="Escribe la nueva contraseña" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleResetPassword">
                                <i class="fas fa-eye" id="resetEyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirmar Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" name="confirmar_password" id="reset_confirm_password" class="form-control" placeholder="Repite la contraseña" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleResetConfirmPassword">
                                <i class="fas fa-eye" id="resetConfirmEyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Restablecer</button>
                </form>
            </div>
        </div>
    </div>
</div>