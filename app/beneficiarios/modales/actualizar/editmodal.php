<div class="modal fade" id="editmodal" tabindex="-1" aria-labelledby="editmodallabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="editmodalLabel">Editar beneficiario </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="/IPSPUPTM/app/beneficiarios/modales/actualizar/actualizar.php" method="post">
                    <input type="hidden" name="id" id="id">
                    <input type="hidden" name="id_persona" id="id_persona">
                    <input type="hidden" name="cedula_original" id="cedula_original">
                    <div class="row">
                        <div class="mb-3 col-12">
                            <label for="cedula_afil" class="form-label">Afiliado relacionado</label>
                            <select name="cedula_afil" id="cedula_afil" class="form-select" required>
                                <option value="" selected disabled>Seleccionar afiliado...</option>
                                <?php
                                // Consulta para cargar afiliados existentes
                                $afiliado_seleccionado_id = $afiliado_seleccionado_id ?? null;
                                $sql_afiliados = "
                            SELECT a.id AS id_afiliado, CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo
                            FROM afiliados a
                            JOIN persona p ON a.id_persona = p.id_persona
                            ORDER BY p.nombre ASC";
                                $result_afiliados = $conn->query($sql_afiliados);

                                if ($result_afiliados) {
                                    if ($result_afiliados->num_rows > 0) {
                                        while ($row_afiliado = $result_afiliados->fetch_assoc()) {
                                            $selected = '';
                                            // Comparamos el ID del afiliado (value de la opción) con el cedula_afil del beneficiario
                                            if ($afiliado_seleccionado_id !== null && $row_afiliado['id_afiliado'] == $afiliado_seleccionado_id) {
                                                $selected = 'selected';
                                            }
                                            echo '<option value="' . $row_afiliado['id_afiliado'] . '" ' . $selected . '>' . $row_afiliado['nombre_completo'] . '</option>';
                                        }
                                    } else {
                                        echo '<option value="">No hay afiliados disponibles</option>';
                                    }
                                } else {
                                    echo '<option value="">Error al cargar afiliados</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="cedula" class="form-label">Cédula</label>
                            <input type="text" name="cedula" id="cedula" class="form-control" pattern="[0-9]{1,8}"
                                title="Solo se permiten hasta 8 números" maxlength="8" required>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" name="nombre" id="nombre" class="form-control" maxlength="50" required>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="apellido" class="form-label">Apellido</label>
                            <input type="text" name="apellido" id="apellido" class="form-control" required>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="fechanacimiento" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" name="fechanacimiento" id="fechanacimiento" class="form-control" required
                                min="<?php echo date('Y-m-d', strtotime('-110 years')); ?>"
                                max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="parentesco" class="form-label">Parentesco</label>
                            <select name="parentesco" id="parentesco" class="form-select" required>
                                <option value="Hijo">Hijo</option>
                                <option value="Esposo/a">Esposo/a</option>
                                <option value="Padre">Padre</option>
                                <option value="Madre">Madre</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="genero" class="form-label">Género</label>
                            <input type="text" id="genero" class="form-control" readonly name="genero">
                        </div>
                        <div class="mb-3  col-md-6">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" maxlength="11" class="form-control"
                                pattern="04[0-9]{9}"
                                title="El teléfono debe tener 11 dígitos y comenzar con 04 (ej: 04121234567)" required>
                            <div id="telefonoFeedback" class="text-danger mt-1"
                                style="display:none; font-size: 0.875em;">El teléfono debe comenzar con 04 y tener
                                exactamente 11 dígitos.</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="correo" class="form-label">Correo Electrónico</label>
                            <input type="email" name="correo" id="correo" class="form-control" required>
                            <div id="correoFeedback" class="text-danger mt-1" style="display:none; font-size: 0.875em;">
                                El correo electrónico ya está registrado por otra persona.</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="ocupacion" class="form-label">Ocupación</label>
                            <input type="text" name="ocupacion" id="ocupacion" class="form-control" required>
                        </div>
                        <div class="">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" id="btnGuardarEditBenef" class="btn btn-primary">Guardar
                                Cambios</button>
                        </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<script>
    document.body.addEventListener('input', function (event) {
        if (!event.target) return;

        // Validación y sanitización del campo teléfono en editmodal (solo números, máx 11, debe comenzar con 04)
        if (event.target.id === 'telefono') {
            let input = event.target;
            input.value = input.value.replace(/\D/g, '').slice(0, 11);
            const feedback = input.parentNode.querySelector('#telefonoFeedback');
            const btnGuardar = input.closest('form').querySelector('button[type="submit"]');
            if (input.value.length > 0 && !input.value.startsWith('04')) {
                feedback.style.display = 'block';
                input.classList.add('is-invalid');
                if (btnGuardar) btnGuardar.disabled = true;
            } else if (input.value.length === 11 && input.value.startsWith('04')) {
                feedback.style.display = 'none';
                input.classList.remove('is-invalid');
                if (btnGuardar) btnGuardar.disabled = false;
            } else {
                feedback.style.display = 'none';
                input.classList.remove('is-invalid');
            }
        }

        // Validación asíncrona de correo electrónico
        if (event.target.id === 'correo') {
            let correoInput = event.target;
            let correo = correoInput.value.trim();
            const feedback = correoInput.parentNode.querySelector('#correoFeedback');
            const btnGuardar = correoInput.closest('form').querySelector('button[type="submit"]');
            const idPersonaInput = correoInput.closest('form').querySelector('[name="id_persona"]');
            const idPersona = idPersonaInput ? idPersonaInput.value : '';
            const cedulaInput = correoInput.closest('form').querySelector('[name="cedula"]');
            const cedula = cedulaInput ? cedulaInput.value : '';

            if (correo.length > 0) {
                fetch('/IPSPUPTM/app/configuracion/check_correo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'correo=' + encodeURIComponent(correo)
                        + '&id_persona_ignorada=' + encodeURIComponent(idPersona)
                        + '&cedula_ignorada=' + encodeURIComponent(cedula)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.existe) {
                        feedback.style.display = 'block';
                        if (btnGuardar) btnGuardar.disabled = true;
                        correoInput.classList.add('is-invalid');
                    } else {
                        feedback.style.display = 'none';
                        if (btnGuardar) btnGuardar.disabled = false;
                        correoInput.classList.remove('is-invalid');
                    }
                })
                .catch(error => console.error('Error:', error));
            } else {
                feedback.style.display = 'none';
                if (btnGuardar) btnGuardar.disabled = false;
                correoInput.classList.remove('is-invalid');
            }
        }
    });
</script>