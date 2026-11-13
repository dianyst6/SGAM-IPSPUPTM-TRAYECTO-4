<div class="modal fade" id="editmodal" tabindex="-1" aria-labelledby="editmodallabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="FormularioModalLabel">Editar Cita</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="/IPSPUPTM/app/citas/modales/actualizar/actualizar.php" method="post"
                    id="formularioEditarCita">
                    <input type="hidden" name="id_cita" id="id_cita_editar">

                    <div class="card mb-3 border-light shadow-sm">
                        <div class="card-body">

                            <div id="campos_internos_editar">
                                <label class="form-label fw-bold">Paciente (Interno) &nbsp;<span id="tipo_int_badge"
                                        class="badge bg-secondary"></span></label>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <input type="text" id="nombre_int_editar" class="form-control bg-light"
                                            placeholder="Nombre" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" id="apellido_int_editar" class="form-control bg-light"
                                            placeholder="Apellido" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" id="cedula_int_editar" class="form-control bg-light"
                                            placeholder="Cédula" readonly maxlength="8">
                                    </div>
                                </div>
                                <input type="hidden" name="id_paciente" id="id_paciente_editar">
                            </div>

                            <div id="campos_externos_editar" style="display: none;">
                                <label class="form-label fw-bold text-primary">Datos del Paciente Externo</label>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <input type="text" name="nombre_ext" id="nombre_ext_editar"
                                            class="form-control bg-light" placeholder="Nombre" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="apellido_ext" id="apellido_ext_editar"
                                            class="form-control bg-light" placeholder="Apellido" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="cedula_ext" id="cedula_ext_editar"
                                            class="form-control bg-light" placeholder="Cédula" readonly maxlength="8">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="id_especialidad_editar" class="form-label fw-bold">Especialidad</label>
                            <select name="id_especialidad" id="id_especialidad_editar" class="form-select" required>
                                <option value="" disabled selected>Seleccionar...</option>
                                <?php
                                $sql_esp = "SELECT id_especialidad, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad ASC";
                                $res_esp = $conn->query($sql_esp);
                                if ($res_esp)
                                    while ($row = $res_esp->fetch_assoc()) {
                                        echo '<option value="' . $row['id_especialidad'] . '">' . $row['nombre_especialidad'] . '</option>';
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fecha_cita_editar" class="form-label fw-bold">Fecha y Hora</label>
                            <input type="datetime-local" name="fecha_cita" id="fecha_cita_editar" class="form-control"
                                required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion_editar" class="form-label fw-bold">Descripción / Motivo</label>
                        <textarea name="descripcion" id="descripcion_editar" class="form-control" rows="3"
                            required></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>