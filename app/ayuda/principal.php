<div class="card shadow-lg">
    <div class="mt-3 m-3 text-justify">
        <h1>Ayuda para el Uso de la Aplicación</h1>
        <p class="mb-3">Esta sección te guiará en el uso de la aplicación, diseñada para simplificar la gestión de datos
            de
            la institución.</p>

        <div class="mb-4">
            <a href="/IPSPUPTM/app/reportes/pdf/manual_pdf.php" class="btn btn-danger shadow-sm me-2">
                <i class="fas fa-file-pdf me-2"></i> Descargar Manual (PDF)
            </a>
            <a href="/IPSPUPTM/app/reportes/word/manual_word.php" class="btn btn-primary shadow-sm">
                <i class="fas fa-file-word me-2"></i> Descargar Manual (Word)
            </a>
        </div>


        <h2>Módulos Principales</h2>
        <p class="mb-3">El sistema se organiza en tres módulos principales para la gestión: <strong>Afiliados</strong>,
            <strong>Beneficiarios</strong> y <strong>Citas</strong>. A continuación, te explicamos cómo interactuar con
            cada
            uno.
        </p>

        <h3>Estructura General de los Módulos</h3>
        <p class="mb-3">Al ingresar a cualquiera de los módulos que requiera una organización de los datos (Afiliados,
            Beneficiarios, Citas, Bitácora o Usuarios), lo primero que visualizarás será una tabla con los datos más
            relevantes, similar a este ejemplo:</p>

        <a href="#" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#agregar">
            <i class="fas fa-plus-circle"></i> Agregar
        </a>
        <div class="my-4">
            <table class="table table-sm table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Dato 1</th>
                        <th>Dato 2</th>
                        <th>Dato 3</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Ejemplo 1</td>
                        <td>Ejemplo 2</td>
                        <td>Ejemplo 3</td>
                        <td class="text-center">
                            <a href="#" class="btn btn-info btn-sm me-1" data-bs-toggle="modal"
                                data-bs-target="#vermodal">
                                <i class="fas fa-eye"></i> Ver información
                            </a>
                            <a href="#" class="btn btn-warning btn-sm me-1" data-bs-toggle="modal"
                                data-bs-target="#formulariomodal">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="#" class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                data-bs-target="#deleteModal">
                                <i class="fas fa-trash"></i> Eliminar
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h3>Acciones Disponibles</h3>
        <p class="mb-3">En la tabla de cada módulo, encontrarás las siguientes acciones:</p>
        <ul class="mb-3">
            <li><i class="fas fa-plus-circle text-primary me-2"></i> <strong>Agregar:</strong> Al hacer clic en este
                botón,
                se abrirá un formulario para ingresar nueva información al módulo correspondiente.</li>
            <li><i class="fas fa-eye text-info me-2"></i> <strong>Ver información:</strong> Este botón te permitirá ver
                los
                detalles completos del registro seleccionado en una ventana modal.</li>
            <li><i class="fas fa-edit text-warning me-2"></i> <strong>Editar:</strong> Al seleccionar esta opción, se
                mostrará un formulario modal con los datos del registro, listo para ser modificado.</li>
            <li><i class="fas fa-trash text-danger me-2"></i> <strong>Eliminar:</strong> Este botón abrirá una ventana
                de
                confirmación para eliminar el registro seleccionado.</li>
        </ul>

        <p class="mb-3">Al realizar las acciones de "Agregar" o "Editar", aparecerá un formulario que deberás completar
            con
            la información solicitada.</p>

        <h2>Formulario para agregar afiliado o beneficiario</h2>
        <p class="mb-3"> Ambos formularios mantienen una estructura similar, los campos que se requieren son los
            siguientes:
        </p>
        <div class="mt-4 mb-4">
            <div class="card shadow p-4">
                <form action="" method="POST">
                    <div class="container">
                        <div class="row mb-3">
                            <div class="col-md-6 col-12">
                                <label for="cedula" class="form-label">Cédula</label>
                                <input type="text" name="cedula" id="cedula" class="form-control" required>
                            </div>
                            <div class="col-md-6 col-12">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6 col-12">
                                <label for="apellido" class="form-label">Apellido</label>
                                <input type="text" name="apellido" id="apellido" class="form-control" required>
                            </div>
                            <div class="col-md-6 col-12">
                                <label for="fechanacimiento" class="form-label">Fecha de Nacimiento</label>
                                <input type="date" name="fechanacimiento" id="fechanacimiento" class="form-control"
                                    required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6 col-12">
                                <label for="genero" class="form-label">Género</label>
                                <select name="genero" id="genero" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="masculino">Masculino</option>
                                    <option value="femenino">Femenino</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-12">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" name="telefono" id="telefono" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <input type="email" name="correo" id="correo" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="ocupacion" class="form-label">Ocupación</label>
                                <input type="text" name="ocupacion" id="ocupacion" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <h4 class="mb-4"><i class="fas fa-exclamation-circle text-warning me-2"></i> <strong>Nota importante:</strong>
            En el
            caso de beneficiarios, se debe seleccionar el afiliado al cual está asociado. La cédula y el género no se
            pueden
            cambiar luego de su registro en el sistema.</h4>

        <h2> Formulario de citas </h2>

        <p class="mb-3"> Para ingresar una cita es necesario seleccionar tanto el paciente como la especialidad. El
            formulario es el siguiente: </p>

        <div class="mt-4 mb-4">
            <div class="card shadow p-4">
                <form action="" method="POST">
                    <div class="container">
                        <div class="mb-3">
                            <label for="id_paciente" class="form-label">Paciente (Afiliado/Beneficiario)</label>
                            <select name="id_paciente" id="id_paciente" class="form-select" required>
                                <option value="" selected disabled>Buscar paciente...</option>
                                <?php
                                include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

                                // Consulta para cargar afiliados y beneficiarios
                                $sql_pacientes = "
                                SELECT
                                    a.id AS id_paciente, CONCAT(p.nombre, ' ', p.apellido, ' - (Afiliado)') AS nombre_completo
                                FROM afiliados a
                                JOIN persona p ON a.cedula = p.cedula
                                UNION
                                SELECT
                                    b.id_beneficiario AS id_paciente, CONCAT(p.nombre, ' ', p.apellido, ' - (Beneficiario)') AS nombre_completo
                                FROM beneficiarios b
                                JOIN persona p ON b.cedula = p.cedula
                                ORDER BY nombre_completo ASC";
                                $result_pacientes = $conn->query($sql_pacientes);

                                if ($result_pacientes) {
                                    if ($result_pacientes->num_rows > 0) {
                                        while ($row_paciente = $result_pacientes->fetch_assoc()) {
                                            echo '<option value="' . $row_paciente['id_paciente'] . '">' . $row_paciente['nombre_completo'] . '</option>';
                                        }
                                    } else {
                                        echo '<option value="">No hay pacientes disponibles</option>';
                                    }
                                } else {
                                    echo '<option value="">Error al cargar pacientes</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_especialidad" class="form-label">Especialidad</label>
                            <select name="id_especialidad" id="id_especialidad" class="form-select" required>
                                <option value="" selected disabled>Seleccionar especialidad...</option>
                                <?php
                                // Consulta para cargar especialidades existentes
                                $sql_especialidades = "SELECT id_especialidad, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad ASC";
                                $result_especialidades = $conn->query($sql_especialidades);

                                if ($result_especialidades) {
                                    if ($result_especialidades->num_rows > 0) {
                                        while ($row_especialidad = $result_especialidades->fetch_assoc()) {
                                            echo '<option value="' . $row_especialidad['id_especialidad'] . '">' . $row_especialidad['nombre_especialidad'] . '</option>';
                                        }
                                    } else {
                                        echo '<option value="">No hay especialidades disponibles</option>';
                                    }
                                } else {
                                    echo '<option value="">Error al cargar especialidades</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="fecha_cita" class="form-label">Fecha y Hora</label>
                            <input type="datetime-local" name="fecha_cita" id="fecha_cita" class="form-control"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea name="descripcion" id="descripcion" class="form-control" rows="3"
                                required></textarea>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <h5 class="mb-3"> La tabla de la vista principal se actualizará con los registros que se vayan agregando
            conforme el
            paso del tiempo. Así mismo, dependiendo de si las acciones de editar o eliminar se realizaron correctamente
            se
            mostrarán alertas que indicarán esto mismo. Haga click en los botones para tener una visualización de las
            alertas.</h5>
        <button id="botonExito" class="btn btn-success me-2 mb-2">Éxito</button>
        <button id="botonError" class="btn btn-danger mb-2">Error</button>

        <script>
            document.getElementById('botonExito').addEventListener('click', function () {
                alertify.success('Edición/Eliminar se ha realizado correctamente');
            });

            document.getElementById('botonError').addEventListener('click', function () {
                alertify.error('Ha ocurrido un error');
            });
        </script>

        <p class="mb-4"><strong> Nota: El error puede variar. </strong></p>

        <h3> El sistema también tiene la opción de gestionar reportes, a través de esta misma podrás descargar los
            reportes
            de lo registrado en el sistema con distintos formatos y según lo que necesites en el momento. Al dar click
            en el
            módulo, aparecerán los siguientes íconos</h3>

        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <button class="btn btn-danger btn-lg w-100 d-flex flex-column align-items-center justify-content-center"
                    onclick="showPDFOptions()" style="height: 150px;">
                    <i class="fas fa-file-pdf fa-3x mb-2"></i>
                    <p class="mt-2">Descargar PDF</p>
                </button>
            </div>

            <div class="col-md-4 mb-3">
                <button
                    class="btn btn-success btn-lg w-100 d-flex flex-column align-items-center justify-content-center"
                    onclick="showExcelOptions()" style="height: 150px;">
                    <i class="fas fa-file-excel fa-3x mb-2"></i>
                    <p class="mt-2">Descargar Excel</p>
                </button>
            </div>

            <div class="col-md-4 mb-3">
                <button
                    class="btn btn-primary btn-lg w-100 d-flex flex-column align-items-center justify-content-center"
                    onclick="showWordOptions()" style="height: 150px;">
                    <i class="fas fa-file-word fa-3x mb-2"></i>
                    <p class="mt-2">Descargar Word</p>
                </button>
            </div>
        </div>

        <h5 class="mb-4"> Al dar click en estas opciones, el sistema redireccionará a una pantalla donde se encuentran 4
            botones.
            Estos botones permiten descargar el reporte de afiliados, de beneficiarios, de citas y de especialidades.
            Los reportes se descargan según el formato elegido. Al descargar aparecerá una alerta. </h5>

        <h3> La sección de configuración está únicamente disponible para los administradores. En esta sección, al dar
            click
            en el módulo apareceran los siguientes botones</h3>

        <div class="mt-4 d-flex flex-column align-items-center w-50 mx-auto">
            <div class="mb-3 w-100">
                <a class="btn btn-lg btn-primary w-100 d-flex flex-column align-items-center justify-content-center"
                    style="height: 120px;">
                    <i class="fas fa-users fa-3x mb-2"></i>
                    Gestión de usuarios
                </a>
            </div>
            <div class="mb-3 w-100">
                <a class="btn btn-lg btn-primary w-100 d-flex flex-column align-items-center justify-content-center"
                    style="height: 120px;">
                    <i class="far fa-sticky-note fa-3x mb-2"></i>
                    Bitácora de movimientos
                </a>
            </div>
            <div class="mb-3 w-100">
                <form action="/IPSPUPTM/app/configuracion/respaldo.php" method="post" class="w-100">
                    <button type="submit"
                        class="btn btn-lg btn-primary w-100 d-flex flex-column align-items-center justify-content-center"
                        style="height: 120px;">
                        <i class="fas fa-save fa-3x mb-2"></i>
                        Generar Respaldo
                    </button>
                </form>
            </div>
        </div>

        <h5 class="mt-4 mb-3"> Al dar click a <strong> generar respaldo</strong> se descargará un archivo en formato
            <strong>SQL</strong>. Este archivo se guardará en la carpeta que se tenga predeterminada para los archivos
            que
            se descargan en el computador.
        </h5>
        <h5 class="mb-3"> Dicho archivo se puede importar a un gestor de base de datos, garantizando su integridad.</H5>

        <h5 class="mb-3"> Por otro lado, la <strong> bitacora </strong> permite al administrador llevar un registro de
            los
            movimientos que se han registrado en el sistema.
            La bitácora muestra un formato de tabla similar a las que ahí en los demás módulos. La bitácora se puede
            <strong> descargar en PDF </strong> o <strong> eliminar</strong> según
            lo que necesite el administrador
        </h5>

        <h5 class="mb-3"> Por último, la gestión de usuarios permite, tal como lo dice, controlar los usuarios que
            tienen
            acceso a la app. Como se mencionó al principio de esta sección, se maneja igualmente por una tabla donde
            muestran los datos. El formulario en el que se puede registrar un usuario es el siguiente:</h5>

        <div class="card shadow p-4">
            <form action="" method="POST">
                <div class="mb-3">
                    <label for="nombre_completo" class="form-label">Nombre completo</label>
                    <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label">Nombre de usuario</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="role_id" class="form-label">Rol</label>
                    <select class="form-select" id="role_id" name="role_id" required>
                        <option value="1">Admin</option>
                        <option value="2">Usuario</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="pregunta_seguridad_id1" class="form-label">Pregunta de seguridad 1</label>
                    <select class="form-select" id="pregunta_seguridad_id1" name="pregunta_seguridad_id1" required>
                        <option value="">Selecciona una pregunta</option>
                        <?php foreach ($preguntas as $id => $pregunta): ?>
                            <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($pregunta); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="respuesta_seguridad1" class="form-label">Respuesta de seguridad 1</label>
                    <input type="text" class="form-control" id="respuesta_seguridad1" name="respuesta_seguridad1"
                        required>
                </div>

                <div class="mb-3">
                    <label for="pregunta_seguridad_id2" class="form-label">Pregunta de seguridad 2</label>
                    <select class="form-select" id="pregunta_seguridad_id2" name="pregunta_seguridad_id2" required>
                        <option value="">Selecciona una pregunta</option>
                        <?php foreach ($preguntas as $id => $pregunta): ?>
                            <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($pregunta); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="respuesta_seguridad2" class="form-label">Respuesta de seguridad 2</label>
                    <input type="text" class="form-control" id="respuesta_seguridad2" name="respuesta_seguridad2"
                        required>
                </div>

            </form>
        </div>


        <div class="mt-3">
            <h3> Todos los formularios al final van a tener la acción de guardar o cancelar. Las preguntas de seguridad
                del
                formulario de usuario sirven para la recuperación de contraseña del mismo. </h3>
        </div>
        <h3> <strong> Fin de la sección de ayuda. </strong></h3>
    </div>
</div>