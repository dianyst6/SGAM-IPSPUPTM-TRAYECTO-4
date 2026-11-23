<!-- Estilos de Select2 -->
<link href="/IPSPUPTM/assets/select2/css/select2.min.css" rel="stylesheet" />

<div class="modal fade" id="formulariomodal" tabindex="-1" aria-labelledby="formulariomodallabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="FormularioModalLabel">Formulario de registro</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="/IPSPUPTM/app/beneficiarios/modales/formulario/guardar.php" method="post">
                    <div class="row">
                        <div class="mb-3 col-12">
                            <label for="cedula_afil" class="form-label">Afiliado relacionado</label>
                            <select name="cedula_afil" id="cedula_afil" class="form-select" required>
                                <option value="" selected disabled>Seleccionar afiliado...</option>
                                <?php
                                // Consulta para cargar afiliados existentes
                                $sql_afiliados = "
                                SELECT a.id AS cedula_afil, CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo
                                FROM afiliados a
                                JOIN persona p ON a.id_persona = p.id_persona
                                ORDER BY p.nombre ASC";
                                $result_afiliados = $conn->query($sql_afiliados);

                                if ($result_afiliados) {
                                    if ($result_afiliados->num_rows > 0) {
                                        while ($row_afiliado = $result_afiliados->fetch_assoc()) {
                                            echo '<option value="' . $row_afiliado['cedula_afil'] . '">' . $row_afiliado['nombre_completo'] . '</option>';
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
                            <div id="cedulaFeedback" class="text-danger mt-1" style="display:none; font-size: 0.875em;">
                            </div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" name="nombre" id="nombre" class="form-control" maxlength="50"
                                pattern="[a-zA-ZáéíóúüñÑÁÉÍÓÚÜÑ\s]+" title="Solo se permiten letras" required>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="apellido" class="form-label">Apellido</label>
                            <input type="text" name="apellido" id="apellido" class="form-control" maxlength="50"
                                pattern="[a-zA-ZáéíóúüñÑÁÉÍÓÚÜÑ\s]+" title="Solo se permiten letras" required>
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
                                <option value=""> Seleccionar... </option>
                                <option value="Hijo">Hijo</option>
                                <option value="Esposo/a">Esposo/a</option>
                                <option value="Padre">Padre</option>
                                <option value="Madre">Madre</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="genero" class="form-label">Género</label>
                            <select name="genero" id="genero" class="form-select" required>
                                <option value=""> Seleccionar... </option>
                                <option value="masculino">Masculino</option>
                                <option value="femenino">Femenino</option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" maxlength="11" class="form-control"
                                pattern="04[0-9]{9}" title="El teléfono debe tener 11 dígitos y comenzar con 04 (ej: 04121234567)" required>
                            <div id="telefonoFeedback" class="text-danger mt-1" style="display:none; font-size: 0.875em;">El teléfono debe comenzar con 04 y tener exactamente 11 dígitos.</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="correo" class="form-label">Correo Electrónico</label>
                            <input type="email" name="correo" id="correo" maxlength="100" class="form-control" required>
                            <div id="correoFeedback" class="text-danger mt-1" style="display:none; font-size: 0.875em;">
                                El correo electrónico ya está registrado por otra persona.</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="ocupacion" class="form-label">Ocupación</label>
                            <input type="text" name="ocupacion" id="ocupacion" maxlength="50" class="form-control"
                                pattern="[a-zA-ZáéíóúüñÑÁÉÍÓÚÜÑ\s]+" title="Solo se permiten letras" required>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" id="btnRegistrar" class="btn btn-primary">Guardar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Usar delegación de eventos para asegurar que funcione incluso si el modal se carga dinámicamente
    document.body.addEventListener('input', function (event) {
        if (!event.target) return;

        // Validación y sanitización del campo cédula (solo números, máx 8 dígitos)
        if (event.target.id === 'cedula') {
            let cedulaInput = event.target;
            cedulaInput.value = cedulaInput.value.replace(/\D/g, '').slice(0, 8);
            let cedula = cedulaInput.value.trim();
            const feedback = document.getElementById('cedulaFeedback');
            const btnRegistrar = document.getElementById('btnRegistrar');

            if (cedula.length > 0) {
                fetch('/IPSPUPTM/app/beneficiarios/modales/formulario/check_cedula.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'cedula=' + encodeURIComponent(cedula)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.existe_beneficiario) {
                            feedback.textContent = 'La cédula ya está registrada como beneficiario.';
                            feedback.style.display = 'block';
                            btnRegistrar.disabled = true;
                            cedulaInput.classList.add('is-invalid');
                        } else if (data.existe_afiliado) {
                            feedback.textContent = 'La cédula ya está registrada como afiliado.';
                            feedback.style.display = 'block';
                            btnRegistrar.disabled = true;
                            cedulaInput.classList.add('is-invalid');
                        } else {
                            feedback.style.display = 'none';
                            btnRegistrar.disabled = false;
                            cedulaInput.classList.remove('is-invalid');
                        }
                    })
                    .catch(error => {
                        console.error('Error verificando la cédula:', error);
                    });
            } else {
                feedback.style.display = 'none';
                btnRegistrar.disabled = false;
                cedulaInput.classList.remove('is-invalid');
            }
        }

        // Validación y sanitización de los campos Nombre, Apellido y Ocupación (solo letras y espacios, máx 50 caracteres)
        if (event.target.id === 'nombre' || event.target.id === 'apellido' || event.target.id === 'ocupacion') {
            let input = event.target;
            input.value = input.value.replace(/[^a-zA-ZáéíóúüñÑÁÉÍÓÚÜÑ\s]/g, '');
        }

        // Validación y sanitización del campo teléfono (solo números, máx 11, debe comenzar con 04)
        if (event.target.id === 'telefono') {
            let input = event.target;
            input.value = input.value.replace(/\D/g, '').slice(0, 11);
            const feedback = input.parentNode.querySelector('#telefonoFeedback');
            const btnRegistrar = input.closest('form').querySelector('button[type="submit"]');
            if (input.value.length > 0 && !input.value.startsWith('04')) {
                feedback.style.display = 'block';
                input.classList.add('is-invalid');
                if (btnRegistrar) btnRegistrar.disabled = true;
            } else if (input.value.length === 11 && input.value.startsWith('04')) {
                feedback.style.display = 'none';
                input.classList.remove('is-invalid');
                if (btnRegistrar) btnRegistrar.disabled = false;
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
            const btnRegistrar = correoInput.closest('form').querySelector('button[type="submit"]');

            if (correo.length > 0) {
                fetch('/IPSPUPTM/app/configuracion/check_correo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'correo=' + encodeURIComponent(correo)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.existe) {
                        feedback.style.display = 'block';
                        btnRegistrar.disabled = true;
                        correoInput.classList.add('is-invalid');
                    } else {
                        feedback.style.display = 'none';
                        btnRegistrar.disabled = false;
                        correoInput.classList.remove('is-invalid');
                    }
                })
                .catch(error => console.error('Error:', error));
            } else {
                feedback.style.display = 'none';
                btnRegistrar.disabled = false;
                correoInput.classList.remove('is-invalid');
            }
        }
    });
</script>

<script>
    // Esperar a que jQuery esté disponible en el layout principal antes de cargar e inicializar Select2
    (function initSelect2WhenReady() {
        if (window.jQuery) {
            // Cargar el JS de Select2 dinámicamente
            var select2Script = document.createElement('script');
            select2Script.src = '/IPSPUPTM/assets/select2/js/select2.min.js';

            select2Script.onload = function () {
                // Inicializar Select2 una vez que el script se ha cargado
                window.jQuery('#cedula_afil').select2({
                    dropdownParent: window.jQuery('#formulariomodal'),
                    width: '100%',
                    language: 'es'
                });
            };

            document.body.appendChild(select2Script);
        } else {
            // Volver a comprobar en 50 milisegundos si jQuery ya cargó
            setTimeout(initSelect2WhenReady, 50);
        }
    })();
</script>