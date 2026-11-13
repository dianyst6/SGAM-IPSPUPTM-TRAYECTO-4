
<?php
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

// Obtener el ID del plan a editar
$id_plan = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_plan <= 0) {
    echo "<div class='alert alert-danger'>ID de plan inválido.</div>";
    exit;
}

// Consultar datos generales del plan
$sql_plan = "SELECT * FROM planes WHERE ID_planes = $id_plan";
$res_plan = mysqli_query($conn, $sql_plan);
$plan = mysqli_fetch_assoc($res_plan);

if (!$plan) {
    echo "<div class='alert alert-danger'>El plan no existe.</div>";
    exit;
}

// Consultar componentes por examen
$sql_comp = "SELECT * FROM componentes_planes WHERE ID_planes_componentes = $id_plan AND ID_examen_componentes IS NOT NULL";
$res_comp_exist = mysqli_query($conn, $sql_comp);

// Consultar componentes por categoría
$sql_cat = "SELECT * FROM componentes_planes WHERE ID_planes_componentes = $id_plan AND id_categoria_componente IS NOT NULL";
$res_cat_exist = mysqli_query($conn, $sql_cat);
?>

<div class="card shadow">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0 fw-bold"><i class="fa-solid fa-pen-to-square"></i> Editar Plan de Salud: <?php echo $plan['nombre_plan']; ?></h4>
        <a href="/IPSPUPTM/home.php?vista=gestionplanes" class="btn btn-sm btn-white text-white"><i class="fa-solid fa-arrow-left"></i> Volver</a>
    </div>
    <form action="/IPSPUPTM/app/pagos/procesar_edicion_plan.php" method="POST">
        <input type="hidden" name="id_plan" value="<?php echo $id_plan; ?>">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nombre del Plan</label>
                    <input type="text" name="nombre_plan" class="form-control" value="<?php echo $plan['nombre_plan']; ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Precio Plan ($)</label>
                    <input type="number" step="0.01" name="precio" class="form-control" value="<?php echo $plan['precio']; ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Cobertura Póliza ($)</label>
                    <input type="number" step="0.01" name="monto_cobertura" class="form-control" value="<?php echo $plan['monto_cobertura']; ?>" required>
                </div>
                <div class="col-md-12 mt-3">
                    <label class="form-label fw-bold">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="2"><?php echo $plan['descripcion']; ?></textarea>
                </div>
            </div>

            <hr>
            <div class="row">
                <!-- SECCIÓN EXÁMENES INDIVIDUALES -->
                <div class="col-md-6 border-end">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-microscope text-primary"></i> Límites por Examen</h5>
                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalNuevoExamen">
                            <i class="fa-solid fa-plus"></i> Nuevo Examen
                        </button>
                    </div>
                    <div id="contenedor-examenes">
                        <?php 
                        $has_exams = false;
                        while($comp = mysqli_fetch_assoc($res_comp_exist)): 
                            $has_exams = true;
                        ?>
                        <div class="row g-2 mb-2 examen-item p-2 border rounded bg-white shadow-sm">
                            <div class="col-md-8">
                                <select name="id_examen[]" class="form-select select-examen">
                                    <option value="">Seleccione un examen...</option>
                                    <?php
                                    $ex = mysqli_query($conn, "SELECT ID_examen, nombre_examen FROM examenes ORDER BY nombre_examen ASC");
                                    while($f = mysqli_fetch_assoc($ex)) {
                                        $selected = ($f['ID_examen'] == $comp['ID_examen_componentes']) ? 'selected' : '';
                                        echo "<option value='{$f['ID_examen']}' $selected>{$f['nombre_examen']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="cantidad_examen[]" class="form-control" placeholder="Cant." value="<?php echo $comp['cantidad_maxima']; ?>" title="Cantidad Máxima">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100 h-100" onclick="eliminarFila(this, '.examen-item')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endwhile; ?>

                        <!-- Fila de ejemplo (si no hay ninguno o como plantilla oculta) -->
                        <div class="row g-2 mb-2 examen-item p-2 border rounded bg-white shadow-sm <?php echo $has_exams ? 'd-none' : ''; ?>" id="template-examen">
                            <div class="col-md-8">
                                <select name="id_examen[]" class="form-select select-examen">
                                    <option value="">Seleccione un examen...</option>
                                    <?php
                                    $ex = mysqli_query($conn, "SELECT ID_examen, nombre_examen FROM examenes ORDER BY nombre_examen ASC");
                                    while($f = mysqli_fetch_assoc($ex)) {
                                        echo "<option value='{$f['ID_examen']}'>{$f['nombre_examen']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="cantidad_examen[]" class="form-control" placeholder="Cant." title="Cantidad Máxima">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100 h-100" onclick="eliminarFila(this, '.examen-item')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none" onclick="agregarFilaExamen()">
                        <i class="fa-solid fa-circle-plus"></i> Añadir otro examen específico
                    </button>
                </div>

                <!-- SECCIÓN CATEGORÍAS (LÍMITES GLOBALES) -->
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-tags text-success"></i> Límites por Categoría (Global)</h5>
                        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria">
                            <i class="fa-solid fa-plus"></i> Nueva Categoría
                        </button>
                    </div>
                    <div id="contenedor-categorias">
                        <?php 
                        $has_cats = false;
                        while($cat_comp = mysqli_fetch_assoc($res_cat_exist)): 
                            $has_cats = true;
                        ?>
                        <div class="row g-2 mb-2 categoria-item p-2 border rounded bg-white shadow-sm">
                            <div class="col-md-6">
                                <select name="id_categoria_comp[]" class="form-select select-categoria">
                                    <option value="">Seleccione una categoría...</option>
                                    <?php
                                    $cat_query = mysqli_query($conn, "SELECT id_categoria, nombre_categoria FROM categorias_examenes ORDER BY nombre_categoria ASC");
                                    while($c = mysqli_fetch_assoc($cat_query)) {
                                        $selected = ($c['id_categoria'] == $cat_comp['id_categoria_componente']) ? 'selected' : '';
                                        echo "<option value='{$c['id_categoria']}' $selected>{$c['nombre_categoria']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="cantidad_categoria[]" class="form-control" placeholder="Cant." value="<?php echo $cat_comp['cantidad_maxima']; ?>" title="Cantidad Máxima Global">
                            </div>
                            <div class="col-md-2">
                                <input type="number" step="0.01" name="monto_categoria[]" class="form-control" placeholder="Monto $" value="<?php echo $cat_comp['monto_maximo']; ?>" title="Monto Máximo Cobertura ($)">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100 h-100" onclick="eliminarFila(this, '.categoria-item')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endwhile; ?>

                        <!-- Fila de ejemplo (Template) -->
                        <div class="row g-2 mb-2 categoria-item p-2 border rounded bg-white shadow-sm <?php echo $has_cats ? 'd-none' : ''; ?>" id="template-categoria">
                            <div class="col-md-6">
                                <select name="id_categoria_comp[]" class="form-select select-categoria">
                                    <option value="">Seleccione una categoría...</option>
                                    <?php
                                    $cat_list = mysqli_query($conn, "SELECT id_categoria, nombre_categoria FROM categorias_examenes ORDER BY nombre_categoria ASC");
                                    while($c = mysqli_fetch_assoc($cat_list)) {
                                        echo "<option value='{$c['id_categoria']}'>{$c['nombre_categoria']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="cantidad_categoria[]" class="form-control" placeholder="Cant." title="Cantidad Máxima Global">
                            </div>
                            <div class="col-md-2">
                                <input type="number" step="0.01" name="monto_categoria[]" class="form-control" placeholder="Monto $" title="Monto Máximo Cobertura ($)">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100 h-100" onclick="eliminarFila(this, '.categoria-item')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none text-success" onclick="agregarFilaCategoria()">
                        <i class="fa-solid fa-circle-plus"></i> Añadir límite para otra categoría
                    </button>
                </div>
            </div>
        </div>
        <div class="card-footer text-end bg-light">
            <button type="submit" class="btn btn-warning px-5 fw-bold"><i class="fa-solid fa-save"></i> Guardar Cambios del Plan</button>
        </div>
    </form>
</div>

<script>
function agregarFilaExamen() {
    const contenedor = document.getElementById('contenedor-examenes');
    const template = document.getElementById('template-examen');
    const nuevaFila = template.cloneNode(true);
    nuevaFila.classList.remove('d-none');
    nuevaFila.id = "";
    nuevaFila.querySelectorAll('input').forEach(input => input.value = '');
    nuevaFila.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
    contenedor.appendChild(nuevaFila);
}

function agregarFilaCategoria() {
    const contenedor = document.getElementById('contenedor-categorias');
    const template = document.getElementById('template-categoria');
    const nuevaFila = template.cloneNode(true);
    nuevaFila.classList.remove('d-none');
    nuevaFila.id = "";
    nuevaFila.querySelectorAll('input').forEach(input => input.value = '');
    nuevaFila.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
    contenedor.appendChild(nuevaFila);
}

function eliminarFila(btn, selector) {
    const contenedor = btn.closest('#contenedor-examenes, #contenedor-categorias');
    const filas = contenedor.querySelectorAll(selector + ':not(.d-none)');
    if (filas.length > 0) {
        btn.closest(selector).remove();
    } else {
        // Si es la última fila visible, no la borramos pero si la ocultamos si es necesario o limpiamos
        // Para editar, simplemente removemos cualquier fila extra.
        btn.closest(selector).remove();
    }
}
</script>

<?php 
// Incluir los modales para agregar exámenes o categorías al catálogo
include 'app/pagos/modales_creacion.php';
?>
