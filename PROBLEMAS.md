# 🔧 Lista de problemas detectados — Sistema IPSPUPTM

> **Fecha del análisis:** 10/11/2026
> **Alcance:** análisis completo de código (módulos `app/`, `config/`, `vistas/`, `assets/js/`), base de datos `ipsp` en vivo y dump `ipsp.sql`.
> **Cómo usar este documento:** cada ítem tiene casilla `- [ ]` para marcarlo al corregirse. Las rutas son relativas a la raíz del proyecto. Los números de línea corresponden al estado del código en la rama `dianys` al momento del análisis.

---

## ✅ Correcciones aplicadas

### 10/11/2026 — Bypass de recuperación de contraseña (P1: 1.1, 1.6 parcial, 1.7 parcial, 2.2)
Se reescribió el flujo de recuperación para que la identidad viva **solo en la sesión del servidor**:
- `vistas/verificar_respuestas.php`: eliminada la rama GET que forzaba la validación con `?error=...&user_id=...`; ahora el GET solo continúa si existe `reset_autorizado` vigente (10 min). Límite de 5 intentos por sesión. Comparación de respuestas sin distinguir mayúsculas/espacios. Mensajes de error desde un array fijo y escapado (sin XSS). `replaceState` ya no expone `user_id`.
- `vistas/actualizar_contrasena.php`: exige POST + autorización vigente en sesión; el `user_id` se toma de `$_SESSION['reset_user_id']` (nunca del cliente). Valida coincidencia y longitud mínima de 8. Al completar, destruye la autorización y regenera el ID de sesión. Errores por sesión flash (sin `user_id` en la URL).
- `vistas/formulario_reset.php`: eliminado el `<input type="hidden" name="user_id">`.
- `vistas/verificar_usuario.php`: corregidos los 4 redirects a `recuperar_contrasena.php` → `recuperar_contraseña.php`; al iniciar un nuevo proceso descarta autorizaciones previas.
- **Probado:** ataque `?error=x&user_id=7` → 302 al inicio (bloqueado); flujo completo correcto; errores de validación conservan la autorización; reutilización del formulario bloqueada; límite de 5 intentos funciona. Contraseña del usuario de prueba restaurada tras las pruebas.

### 11/11/2026 — Guard central de sesión/rol en endpoints (P1: 1.3)
Se creó `config/auth_guard.php` y se aplicó a **55 endpoints** de reportes, configuración, pagos, comunidad y scripts de depuración de la raíz:
- **Sesión:** exige `$_SESSION['user_id']`; sin sesión redirige al login en GET normal (302) y responde **401 JSON** en AJAX/POST.
- **Rol real:** consulta `usuarios.role_id` en la BD en cada petición; no confía en el rol almacenado en la sesión ni en la cookie. Si la cuenta ya no existe, destruye la sesión y responde **401**.
- **Rol no autorizado:** **403** (texto plano o JSON según el tipo de petición).
- **`config/database.php`** pasó a ser idempotente para que el guard y el endpoint puedan incluirlo sin duplicar la conexión.
- **Scripts de raíz** (`check_*.php`, `*_migrate*.php`, `test_ajax.php`): solo rol 1, permitiendo ejecución por CLI para no romper migraciones.
- **Probado con usuarios temporales** admin/usuario/médico: sin sesión → 302/401; rol incorrecto → 403; roles correctos → PDF/Excel/descarga/JSON; el endpoint destructivo de bitácora se ejecutó solo como admin (datos respaldados y restaurados). Usuarios y datos de prueba eliminados; BD final idéntica (5 usuarios, 5 comunidad, 49 bitácora).
- **Observación:** los reportes Word fallaban por `ZipArchive`/writer de PHPWord (problema preexistente del entorno, ajeno al guard). **Resuelto el 12/11/2026:** se activó `extension=zip` en `php.ini` y se reinició Apache; el reporte Word descarga un `.docx` válido.

### 12/11/2026 — Guard en módulos afiliados y beneficiarios (P1: 1.3)
Se extendió `auth_guard([1, 2])` a los **14 endpoints** de `app/afiliados` y `app/beneficiarios`:
- **Escritura:** `guardar.php`, `actualizar.php`, `guardar_plan.php`, `eliminar.php` y `procesar_contrato.php`.
- **Consultas:** `getafiliados.php`, `getbeneficiarios.php`, `consultar_consumo.php`, `consultar_consumo_beneficiario.php` y `check_cedula.php`.
- **Vistas:** `principal.php` de ambos módulos también valida la sesión para bloquear el acceso directo.
- Se eliminaron los `session_start()` locales para no duplicar la sesión que ya inicia el guard.
- **Corrección 12/11/2026:** `auth_guard.php` ahora declara `global $conn` antes de `require_once database.php`. En endpoints incluidos dentro de una página que ya cargó `database.php` (por ejemplo `procesar_pago.php` dentro de `gestion_pagoscontrato.php`), el `require_once` no se ejecutaba de nuevo y `$conn` no existía dentro de la función (`Undefined variable $conn`). Con `global $conn` se reutiliza la conexión ya abierta.
- **Probado:** `home.php?vista=gestionpagoscontrato` con sesión válida → 200, sin fatal; `procesar_pago.php` incluido con `database.php` ya cargado → sin error; rol 3 → 403.
- **Probado:** GET sin sesión → 302; POST AJAX sin sesión → 401; rol 3 → 403; roles 1 y 2 → operaciones correctas; usuario inexistente → 401 y sesión destruida.

### 12/11/2026 — Edición de beneficiario: "faltan datos del formulario"
- **Síntoma:** al guardar la edición aparecía `No se pudo identificar al beneficiario: faltan datos del formulario...`, sobre todo al cambiar la cédula.
- **Causa:** el navegador seguía usando una versión en caché de `assets/js/accionesbeneficiarios.js` que no rellenaba los hidden `id_persona` ni `cedula_original`. Sin `id_persona` y con una cédula nueva, el backend no podía resolver la persona.
- **Corrección:**
  - Listener delegado en `document` que rellena los hidden al hacer clic en el botón Editar, sin depender de `shown.bs.modal`; además se rellenan como respaldo en el `submit`.
  - `app/beneficiarios/principal.php` carga el script con `?v=filemtime(...)` para que cada cambio del JS se reciba sin caché vieja.
  - `actualizar.php` mantiene el respaldo por cédula (`cedula_original` o cédula enviada) y muestra un mensaje más claro si no encuentra al beneficiario.
- **Probado:** cambiar cédula, editar sin hidden, y cédula inexistente sobre una copia de la base; la base real no se modificó.

### 12/11/2026 — Comunidad UPTM ahora vive en `persona` (tabla `comunidad_uptm` eliminada)
- **Cambio:** se eliminó la tabla `comunidad_uptm`. Comunidad UPTM se define por exclusión: personas sin fila en `afiliados` ni `beneficiarios`.
- **Esquema:** `persona.fechanacimiento` y `persona.genero` aceptan `NULL`; `citas_uptm.id_externo` pasó a `id_persona` con FK a `persona` (`ON DELETE RESTRICT`).
- **Datos:** los 3 registros que no existían se crearon en `persona` con `"No aplica"`; los 2 que ya eran afiliado/beneficiario se descartaron sin duplicar. La cita externa 69 quedó ligada a `id_persona = 10`.
- **Código:** módulo comunidad, citas, pagos externos, reportes Excel/PDF, historias médicas y estadísticas ahora consultan `persona`.
- **Corrección adicional:** se quitó el BOM UTF-8 de `config/database.php`, que generaba `headers already sent` en respuestas JSON.
- **Probado:** copia `ipsp_comunidad_test` y luego base real; vistas HTTP 200, alta/edición/duplicados/rechazos correctos, comunidad real = 3 y gráfico de inicio `[6, 3, 3]`.

### 12/11/2026 — Citas unificadas en `citas.id_persona` (tablas puente eliminadas)
- **Cambio:** las citas de afiliados, beneficiarios y comunidad UPTM ya no se guardan en tablas separadas. La relación única es `citas.id_persona`.
- **Esquema:** se añadió `id_persona` (`NOT NULL`, FK a `persona` con `ON DELETE RESTRICT ON UPDATE CASCADE`) y se eliminaron `citas_afil`, `citas_benef` y `citas_uptm`.
- **Tipo de paciente:** se deduce por JOIN a `afiliados`/`beneficiarios`; no se almacena una columna `tipo_paciente`.
- **Datos:** las 4 citas quedaron con persona: 68→6 (Afiliado), 69→10 (Comunidad), 70→1 (Afiliado), 71→10 (Comunidad). 0 citas sin persona y 0 tablas puente.
- **Código:** citas (alta, edición, anulación, exámenes, listado), pagos de póliza/externos, consumo familiar, eliminación de afiliados/beneficiarios, reportes de citas/pagos y estadística ahora consultan `citas.id_persona`.
- **Corrección adicional:** el PDF de pagos leía `$row['monto_final']` cuando el SQL devolvía el alias `monto`; corregido a `$row['monto']`.
- **Probado:** copia `ipsp_citas_test` (alta interna/externa, edición, anulación, examen, pago con póliza, consumo, eliminar afiliado/beneficiario) y luego base real; vistas HTTP 200 y reportes Excel/PDF correctos.
- **Nota de entorno:** el reporte Word fallaba porque `ZipArchive` estaba deshabilitado (`;extension=zip`). **Resuelto el 12/11/2026:** se activó en `php.ini`, se reinició Apache y se verificó la descarga de un `.docx` válido (HTTP 200, firma `PK`).

### 10/11/2026 — Registro público de administradores (P1: 1.2)
`Inicio/registro.php` ahora exige **sesión activa + rol 1 (administrador)** consultado en la BD antes de procesar cualquier alta. Además se añadieron validaciones reales en el servidor:
- `username`: 5–20 caracteres, solo letras/números/guion bajo.
- Contraseña: 8–20 caracteres y coincidencia con `confirm_password` (antes el servidor ignoraba la confirmación).
- `role_id`: solo 1, 2 o 3 (antes se aceptaba cualquier valor del cliente).
- Preguntas de seguridad: deben existir en la BD y ser distintas; respuestas de 3–20 caracteres.
- Rol 3 (médico): cédula de 6–8 dígitos no duplicada, especialidad existente y teléfono de 11 dígitos si se envía.
- Los mensajes de error se emiten con `json_encode` (sin inyección de HTML).
- **Probado:** sin sesión → 302 al login y no crea nada; sesión rol 2 → **403**; admin → alta de usuario (rol 2) y de médico (rol 3) correcta por API y por la interfaz real; `role_id=99` y contraseñas distintas → rechazados. Todos los usuarios/datos de prueba eliminados.
- **Nota:** `vistas/registrarse.php` (formulario público antiguo, sin enlace y sin preguntas de seguridad) ya no puede crear cuentas; conviene eliminarlo (pendiente en 1.9).

---

## 🔴 PRIORIDAD 1 — Seguridad crítica

### 1.1 Bypass total de la recuperación de contraseña — ✅ CORREGIDO (10/11/2026)
- [x] **`vistas/verificar_respuestas.php:11-19`** — un GET con `?error=x&user_id=N` fuerza `$respuestas_coinciden = true` sin verificar ninguna respuesta de seguridad y muestra el formulario de reset. **Cualquier persona puede cambiar la contraseña de cualquier usuario (incluido el admin id 7).**
- [x] **`vistas/actualizar_contrasena.php:5`** — confía ciegamente en el `user_id` que llega por POST, sin token de un solo uso.
- [x] **`vistas/verificar_respuestas.php:106`** — el `user_id` queda visible en la URL.
- **Solución aplicada:** la autorización vive en `$_SESSION` (`reset_autorizado`, `reset_user_id`, `reset_expira` = 10 min), se destruye tras el cambio, errores por sesión flash y límite de 5 intentos.

### 1.2 Registro de administradores sin autenticación — ✅ CORREGIDO (10/11/2026)
- [x] **`Inicio/registro.php:5-13`** — no comprueba sesión ni rol de quien llama (la URL es alcanzable sin autenticar). → ahora exige sesión + rol 1 verificado en BD.
- [x] **`Inicio/registro.php:9,45-47`** — acepta `role_id` del cliente sin filtro (incluido 1 = admin). → whitelist 1/2/3 y validación completa en servidor.
- **Solución aplicada:** guard de sesión/rol al inicio, validación de username/contraseña/confirmación/preguntas/rol y datos de médico; mensajes con `json_encode`.

### 1.3 Endpoints sin comprobación de sesión/rol — ✅ CORREGIDO (11/11/2026)
Los siguientes archivos procesaban peticiones sin validar `$_SESSION` (el control solo existía en `home.php` al enrutar vistas). Todos quedaron protegidos con `auth_guard([roles])`:
- [x] `app/reportes/pdf/*.php` (todos los generadores PDF)
- [x] `app/reportes/excel/*.php` (todos los generadores Excel)
- [x] `app/reportes/word/*.php` (todos los generadores Word)
- [x] `app/configuracion/respaldo.php` (descarga el dump completo de la BD) — solo rol 1
- [x] `app/configuracion/bitacora/eliminar.php` (borra TODO el histórico de auditoría) — solo rol 1
- [x] `app/configuracion/bitacora/descargar.php` — solo rol 1
- [x] `app/configuracion/check_correo.php` — roles 1 y 2
- [x] `app/configuracion/gestionusuario/verificar_usuario.php` — solo rol 1
- [x] `app/configuracion/gestionusuario/getusuario.php` — solo rol 1
- [x] `app/configuracion/gestionusuario/actualizar/actualizar.php` — solo rol 1
- [x] `app/configuracion/gestionusuario/eliminar/eliminar.php` — solo rol 1
- [x] `app/pagos/procesar_plan_completo.php` — roles 1 y 2
- [x] `app/pagos/procesar_edicion_plan.php` — roles 1 y 2
- [x] `app/pagos/procesar_pago_poliza.php` — roles 1 y 2
- [x] `app/pagos/procesar_consumo_externo.php` — roles 1 y 2
- [x] `app/pagos/modales/procesar_pago.php` — roles 1 y 2
- [x] `app/pagos/modales/obtener_saldo.php` — roles 1 y 2
- [x] `app/pagos/obtener_componentes_plan.php` — roles 1 y 2
- [x] `app/pagos/get_afiliado_plan_limits.php` — roles 1 y 2 (expone saldos y datos de afiliados por cédula)
- [x] `app/pagos/vistas/actualizar_examen.php` — roles 1 y 2
- [x] `app/pagos/guardar_examen_nuevo.php` — roles 1 y 2
- [x] `app/pagos/guardar_categoria_nueva.php` — roles 1 y 2
- [x] `app/pagos/vistas/gestion_categorias.php` — roles 1 y 2 (procesa su propio POST y borra por GET)
- [x] `app/pagos/eliminar_contrato.php` — roles 1 y 2 (borra contratos por GET)
- [x] `app/comunidaduptm/*` (todo el CRUD) — roles 1 y 2
- [x] `app/afiliados/*` (endpoints: `getafiliados.php`, `consultar_consumo.php`, `modales/formulario/guardar.php`, `modales/formulario/check_cedula.php`, `modales/actualizar/actualizar.php`, `modales/asignar_plan/guardar_plan.php`, `modales/eliminar/eliminar.php`, `procesar_contrato.php`) — roles 1 y 2 (12/11/2026)
- [x] `app/beneficiarios/*` (endpoints: `getbeneficiarios.php`, `consultar_consumo_beneficiario.php`, `modales/formulario/guardar.php`, `modales/formulario/check_cedula.php`, `modales/actualizar/actualizar.php`, `modales/eliminar/eliminar.php`) — roles 1 y 2 (12/11/2026)
- [x] Scripts de depuración de la raíz (`check_persona.php`, `check_schema.php`, `final_migrate.php`, `migrate_consumo_plan.php`, `scratch_schema.php`, `temp_migration.php`, `test_ajax.php`) — solo rol 1 por web, CLI permitido.
- **Solución aplicada:** `config/auth_guard.php` verifica sesión + rol real en `usuarios` y responde 302/401/403. Además se protegieron `ver_recibo.php`, `pagosexternos/formulario/guardar.php` y las lógicas de editar/eliminar/reactivar exámenes.
- **Probado:** ver entrada «Guard central de sesión/rol en endpoints» en Correcciones aplicadas.

### 1.4 Inyección SQL (concatenación de entrada del usuario)
- [ ] **`app/pagos/procesar_plan_completo.php:6-7,11`** — `$precio`, `$monto_cobertura` interpolados.
- [ ] **`app/pagos/procesar_plan_completo.php:21,26,37,42`** — arrays `id_examen[]`, `id_categoria_comp[]` sin validar.
- [ ] **`app/pagos/procesar_edicion_plan.php:8-9,13-18,32,36-37,48,53`** — idéntico caso.
- [ ] **`app/pagos/modales/obtener_saldo.php:5`** — `$_POST['id_contrato']` interpolado en 5 consultas.
- [ ] **`app/pagos/procesar_pago_poliza.php:7-8`** — `$id_cita`, `$id_contrato` interpolados (líneas 22, 32-35, 46).
- [ ] **`app/pagos/vistas/actualizar_examen.php:5-8`** — precio interpolado.
- [ ] **`app/configuracion/check_correo.php:10-13`** — concatenación con `mysqli_real_escape_string` en vez de prepared statement.
- [ ] **`app/reportes/excel/reporte_citas.php:20-22`** y `pdf/reporte_pagos.php:24-28`** — fechas escapadas pero interpoladas en el SQL.
- [ ] **`app/configuracion/bitacora/vistabitacora.php:19`** y **`gestionusuario/vistausuarios.php:51,57`** — `$offset`/`$userId` interpolados (controlados internamente, pero mala práctica).
- **Solución sugerida:** migrar todos a prepared statements (`$conn->prepare` + `bind_param`).

### 1.5 Respaldo de BD inseguro
- [ ] **`app/configuracion/respaldo.php:9`** — `exec("mysqldump --user=... --password=...")` expone credenciales en la línea de comandos/procesos, sin control de autenticación y con ruta absoluta `C:/xampp/mysql/bin/mysqldump` hardcodeada.

### 1.6 Respuestas de seguridad débiles
- [ ] **Tabla `respuestas_seguridad`** — respuestas guardadas en **texto plano** (nada de hash).
- [x] **`vistas/verificar_respuestas.php:38-44`** — comparación con `!==` sensible a mayúsculas (frustrante para usuarios legítimos). → ahora `mb_strtolower(trim(...))`.
- [ ] **`app/configuracion/gestionusuario/vistausuarios.php:63-66,74-77`** — las preguntas y respuestas de seguridad se muestran en texto plano en la tabla de usuarios y se inyectan en atributos `data-r1`/`data-r2` del modal de edición.
- **Solución sugerida:** hashear respuestas (p. ej. `strtolower` + `password_hash`), comparar case-insensitive, y no mostrar respuestas en la UI.

### 1.7 XSS (cross-site scripting)
- [x] **Reflejado — `vistas/verificar_respuestas.php:97-102`**: `$_GET['error']` impreso dentro de `<script>` sin escapar. → eliminado; mensajes desde array fijo con `htmlspecialchars(..., ENT_QUOTES)`.
- [ ] **Almacenado** (salida sin `htmlspecialchars`):
  - `app/pagos/editar_plan.php:34,43,47,51,55`
  - `app/pagos/vistas/gestion_planes.php:110-113,127,153`
  - `app/pagos/vistas/gestion_categorias.php:62-64`
  - `app/pagos/vistas/gestion_examenes.php:82-84`
  - `app/pagos/agregar_plan.php:46,81,123`
  - `app/pagos/modales_creacion.php:23`
  - atributos `data-bs-nombre`/`data-bs-especialidad` en `app/pagos/vistas/gestion_pagoscitas.php:140-142`
- [ ] **`app/pagos/vistas/gestion_examenes.php:115,121`** — `htmlspecialchars()` sin `ENT_QUOTES` dentro de string JS con comillas simples → un apóstrofe en el nombre rompe el JS.

### 1.8 CSRF ausente
- [ ] Ningún formulario ni endpoint usa token CSRF.
- [ ] Borrados por GET (sin confirmación server-side): `app/pagos/vistas/gestion_categorias.php:69`, `app/pagos/eliminar_contrato.php:5`.
- **Solución sugerida:** token CSRF en sesión + campo oculto en cada form; nunca mutar por GET.

### 1.9 Otros de seguridad
- [ ] **`vistas/registrarse.php`** — formulario de registro público accesible (la vista no se usa desde login pero existe); definir si debe existir.
- [ ] Credenciales BD en claro en `config/database.php` (root sin contraseña) — aceptable en XAMPP local, documentar para producción.

---

## 🟠 PRIORIDAD 2 — Bugs funcionales (rotos hoy)

### 2.1 Módulo Pagos
- [ ] **`app/pagos/vistas/gestion_pagoscontrato.php:142`** — incluye `procesar_pago.php` (handler POST) como partial de la vista: en cada render ejecuta el `mysqli_close($conn)` de la línea 79 de ese archivo y **cierra la conexión en medio del render**. Además procesaría un pago si se hiciese POST a la URL de la vista.
- [ ] **`app/pagos/modales/procesar_pago.php:12`** — lee `$_POST['numero_cuota']` que el formulario (`modal_registrar_pago.php`) no envía → warning de índice indefinido y `numero_cuota` NULL en la BD al registrar Pago Inicial.
- [ ] **`app/pagos/modales/procesar_pago.php:59-60` y `app/pagos/eliminar_contrato.php:14-25`** — escriben `$_SESSION['flash_msg']` pero (a) `procesar_pago.php` no llama `session_start()` y (b) **nadie lee `flash_msg`/`flash_type` en todo el sistema** → el feedback de éxito nunca se muestra.
- [ ] **`app/pagos/agregar_plan.php:38,233-237`** — el select "Plan Base" tiene `onchange` en el HTML **y** un `addEventListener('change')` → el handler se ejecuta dos veces (doble fetch y posible duplicación de filas por condición de carrera).
- [ ] **`app/pagos/obtener_componentes_plan.php:48`** — `usleep(500000)`: retardo artificial de medio segundo en producción.
- [ ] **`app/pagos/vistas/gestion_planes.php:4`** — `$rowsPerPage = 2` (valor de debug olvidado): solo muestra 2 planes por página.
- [ ] **`app/pagos/vistas/gestion_planesasignados.php:103-106`** — el `btn-group` de acciones está vacío (sin botones) → `eliminar_contrato.php` es código inaccesible desde la UI.
- [ ] **`app/pagos/vistas/gestion_pagoscitas.php:147`** — botón "Pagar Diferencia" sin handler ni enlace (placeholder).
- [ ] **`app/pagos/vistas/gestion_pagosexternos.php:144-149`** — botones "Ver" y "Editar" de pagos ya realizados apuntan a `#` (no implementados).
- [ ] **`app/pagos/editar_plan.php:244`** — `include 'app/pagos/modales_creacion.php'` con ruta relativa (el resto del proyecto usa absolutas); funciona por accidente.
- [ ] **`app/pagos/vistas/actualizar_examen.php:10`** — redirige con `&success=1` pero `gestion_examenes.php` nunca lo lee → sin feedback.
- [ ] **`home.php:113-117`** — `case 'gestionplanes'` duplicado apuntando a ruta inexistente (`/app/pagos/gestion_planes.php`); es código muerto (gana el primer case de la línea 62).
- [ ] **`home.php:108-112`** — `case 'plandepago'` apunta a `app/plan_pagos/principal.php`, carpeta inexistente (también declarado en `config/permisos.php:38`).

### 2.2 Flujo de recuperación de contraseña (rutas rotas → 404) — ✅ CORREGIDO (10/11/2026)
- [x] **`vistas/verificar_usuario.php:6,63,70,77`** y **`vistas/verificar_respuestas.php:17`** — redirects a `recuperar_contrasena.php` (sin "ñ"); el archivo real es `recuperar_contraseña.php`.
- [x] **`vistas/actualizar_contrasena.php:44`** — redirect a `recuperar_contrasena_form.php`, archivo inexistente. → ahora `recuperar_contraseña.php`.

### 2.3 Vista Ayuda rota (todos los roles la ven)
- [ ] **`app/ayuda/principal.php:372,387`** — usa `$preguntas` sin definirlo → error fatal al abrir la vista.
- [ ] **`app/ayuda/principal.php:265,274,283`** — botones que llaman `showPDFOptions()`, `showExcelOptions()`, `showWordOptions()` — funciones JS no definidas en ningún lado.
- [ ] **`app/ayuda/principal.php:256-293`** — documenta un flujo de reportes (PDF/Excel/Word, 4 reportes) que no coincide con la UI real de `reportes/principal.php` (6 reportes, solo PDF/Excel).

### 2.4 Gestión de usuarios
- [ ] **`app/configuracion/gestionusuario/eliminar/eliminar.php:13`** — comprueba `$_SESSION['usuario_id']` para evitar auto-eliminación, pero la clave nunca existe (el login define `user_id`) → el bloqueo jamás se dispara.
- [ ] **`app/configuracion/gestionusuario/getusuario.php:17-21`** — consulta columnas `pregunta_seguridad_id1, respuesta1, ...` que no existen en `usuarios` → la query siempre falla; además nadie lo invoca (código muerto).
- [ ] **`app/configuracion/gestionusuario/verificar_usuario.php:3`** — `ob_clean()` sin buffer activo → warning en cada llamada.
- [ ] **`app/configuracion/gestionusuario/vistausuarios.php:93`** y **`app/comunidaduptm/principal.php:83`** — paginación con `href="?page=N"` sin conservar `vista=` → al cambiar de página se cae a la vista por defecto.

### 2.5 Frontend JS
- [ ] **`assets/js/accionesafiliados.js:43-78`** — el listener `submit` del form de edición se agrega dentro de `cargarDatosModal()` (se ejecuta cada vez que se abre el modal) → handlers acumulados → **POST duplicados**.
- [ ] **`assets/js/script.js:1-3`** — usa selectores `.toggle-btn`/`#sidebar` que no existen (el layout usa `#custom-sidebar`/`customSidebarToggle`) → error JS en cada página (`layout.php:226` lo incluye).
- [ ] **`assets/js/usuarios.js`** — código muerto completo: llama a `get_usuario.php` (el real es `getusuario.php` y espera POST), `Inicio/registro_procesar.php` e `Inicio/login.php` (inexistentes), redirige a `gestionusuarios/` (plural inexistente), y espera JSON de un endpoint que responde con redirects. Ningún PHP lo referencia.
- [ ] **`assets/js/alertasafil.js`** — código muerto (nadie lo referencia).
- [ ] **`app/comunidaduptm/principal.php`** — incluye `eliminar/eliminamodal.php` pero ningún botón de la tabla dispara ese modal (UI de eliminar muerta).

---

## 🟡 PRIORIDAD 3 — Lógica de negocio con huecos

### 3.1 Descuento de póliza por cita (`procesar_pago_poliza.php`)
- [ ] **Sin validación server-side**: ni que el `monto` corresponda al costo real de la cita con descuento, ni el saldo global de la póliza, ni `monto_maximo` por categoría, ni `cantidad_maxima`, ni que la cita pertenezca a ese contrato/afiliado. El único control es `puede_pagar` en la vista (`gestion_pagoscitas.php:119`). Un POST artesanal puede sobregirar la póliza.
- [ ] **Líneas 7-8** — `$id_descuento` recibido y nunca usado; el `porcentaje_descuento` del modal tampoco se registra en BD (el descuento aplicado se pierde como dato).
- [ ] **Líneas 18-57** — la "transacción" es decorativa: mysqli por defecto no lanza excepciones ante fallos SQL, el `catch`/`rollback` casi nunca se activa.
- [ ] **Líneas 28-43** — reparto proporcional con `round(...,2)` por examen puede diferir por centavos del monto real pagado.
- [ ] **Líneas 31-37** — la consulta del paciente se re-ejecuta dentro del bucle por cada examen (innecesario).

### 3.2 Consumo externo (`procesar_consumo_externo.php`)
- [ ] Valida solo `monto_maximo` por categoría: **no** valida `cantidad_maxima`, ni saldo global (`monto_cobertura`), ni solvencia/pago inicial, ni pertenencia del contrato.
- [ ] **Línea 26** — `floatval` acepta **costos negativos**, que reducen el consumo acumulado y aumentan artificialmente la disponibilidad.
- [ ] **Líneas 49-50** — no inserta `fecha_consumo` (el path de póliza sí pone `NOW()`) → inconsistencia entre tipos de consumo.
- [ ] No registra bitácora (los otros procesadores de dinero sí).
- [ ] **Línea 7** — `intval($_POST['id_persona'])` sobre una cédula que `get_afiliado_plan_limits.php:31` envía como string; `procesar_pago_poliza.php:41` inserta la misma columna como string → divergencia int vs string si la cédula tuviera ceros a la izquierda.

### 3.3 Reglas de dinero solo en el cliente
- [ ] **`app/pagos/modales/modal_registrar_pago.php:212,261-280` + `procesar_pago.php:32`** — el ajuste de la "última cuota distinta" es solo visual; el servidor divide el total en partes iguales → lo mostrado no es lo almacenado.
- [ ] Validaciones de monto máximo del Pago Inicial y de abonos: solo en `onsubmit` JS, sin revalidación server-side.
- [ ] **`app/pagos/modales/modal_registrar_pago.php:81-82`** y **`pagosexternos/formulario/formulariomodal.php:52-53`** — `tasa_bcv` y `monto_bs` se capturan y envían pero **ningún procesador los guarda** (el equivalente en bolívares se pierde; la tasa se consulta desde `ve.dolarapi.com` en el cliente).
- [ ] **`app/pagos/modales/pagosexternos/formulario/guardar.php:20`** — rechaza pagos legítimos de $0.00; no verifica duplicados (misma cita pagada dos veces por POST repetido); `monto_base`/`monto_pago` confiados 100 % del cliente (el descuento de `tipos_externos` se aplica solo en JS).
- [ ] El descuento por tipo de externo depende de `data-descuento` del `<option>` (`formulariomodal.php:34`) y cálculo 100 % JS (líneas 113-134).

### 3.4 Planes y contratos
- [ ] **`app/pagos/procesar_edicion_plan.php:22-25`** — estrategia delete-all + re-insert de componentes **sin transacción**; si un INSERT falla, el plan queda sin componentes. Los `mysqli_query` de componentes ignoran errores silenciosamente (igual en `procesar_plan_completo.php:27,44`).
- [ ] Editar un plan **cambia retroactivamente los límites de contratos ya vendidos** (los consumos históricos no guardan snapshot de límites).
- [ ] **Contratos múltiples activos**: `get_afiliado_plan_limits.php:36-54` y `gestion_pagoscitas.php:30-57` toman el primer contrato activo arbitrario (sin ORDER BY) y el UNION de la vista **duplica la fila de la cita** por cada contrato activo.
- [ ] **Límites de plan inconexos** (3 capas que no se revalidan al procesar dinero):
  - `cantidad_maxima` → solo al crear citas (`app/citas/get_examenes.php:34-77`), **ningún procesador de pagos la revalida**.
  - `monto_maximo` por categoría → solo en consumo externo (server) y en `get_afiliado_plan_limits.php:141`.
  - Saldo global → solo en la vista de cobro de citas (`gestion_pagoscitas.php:114-117`), no en el servidor.

### 3.5 Gestión de usuarios
- [ ] **`app/configuracion/gestionusuario/actualizar/actualizar.php`** — no valida unicidad de username ni longitud/fortaleza de contraseña al editar.
- [ ] Validación de contraseña inconsistente: registro exige 8-20 (`vistausuarios.php:117-127`), el reset solo no-vacío (`actualizar_contrasena.php:16-19`), la edición de usuario no valida nada. → **reset ya exige mínimo 8** (10/11/2026); quedan registro/edición.
- [ ] **`app/configuracion/gestionusuario/vistausuarios.php:48-58`** — patrón N+1 (2 queries extra por usuario por página).
- [ ] **`app/pagos/vistas/gestion_pagoscitas.php:114`** — N+1 (una consulta de consumo por fila de la tabla).

### 3.6 Bitácora
- [ ] **`app/configuracion/bitacora/eliminar.php:4`** — `DELETE FROM bitacora` sin WHERE: borra todo el histórico de auditoría con un clic.
- [ ] **`app/configuracion/bitacora/bitacora.php:10`** — si el INSERT falla hace `echo` del error, lo que puede contaminar salidas JSON/redirects de los endpoints que la llaman.
- [ ] `registrarEnBitacora` vs `registrarenBitacora` — capitalización inconsistente en ~20 call sites (funciona en PHP, riesgo de mantenimiento).
- [ ] Acciones sin bitácora: consumos externos, registro/edición de planes, pagos de cuota (`procesar_pago.php`).

---

## 🟢 PRIORIDAD 4 — Inconsistencias y calidad de código

- [ ] **Rutas absolutas `C:/xampp/...` hardcodeadas** en todos los `include`/`require` (no portable; migrar a `__DIR__` o rutas relativas).
- [ ] **Mezcla mysqli procedural y OO** en los mismos archivos (p. ej. `procesar_pago_poliza.php` usa `$conn->query` y `mysqli_real_escape_string`).
- [ ] **Columna muerta**: `categorias_examenes.monto_maximo_cobertura` editable en la UI de categorías pero **no se usa en ningún cálculo** (los límites reales son `componentes_planes.monto_maximo` por plan).
- [ ] **Dependencia sin uso**: `shuchkin/simplexlsxgen` en `composer.json` (los Excel son PhpSpreadsheet).
- [ ] **3 motores de documentos mezclados** (FPDF, Dompdf solo para el manual, PhpSpreadsheet, PhpWord) sin criterio uniforme.
- [ ] **Reportes Word no expuestos en la UI** (solo por URL directa); no existen reportes Word de pagos ni gastos de plan.
- [ ] **`medicos.ci_medico` PK auto_increment** con valor actual 226.571.235 — como la PK es la cédula, el auto_increment sigue al CI más alto; funciona porque siempre se pasa la cédula explícita, pero es frágil.
- [x] **`comunidad_uptm.id_tipo_ext` nunca se llenaba** → la tabla se eliminó en la migración del 12/11/2026. `tipos_externos` sigue en uso solo en el formulario de pagos externos.
- [ ] **`historias_medicas_gine`** con nombres de columnas con puntos (`c.m`, `f.c`, `ex.fisico.t.a`, `año`) — complica migraciones/ORMs.
- [ ] **Scripts de debug/migración en la raíz**: `check_schema.php`, `scratch_schema.php`, `temp_migration.php`, `final_migrate.php`, `migrate_consumo_plan.php`, `test_ajax.php`, `check_persona.php` — accesibles por URL sin control (ver 1.3), deberían salir del webroot o protegerse.
- [ ] **`app/pagos/modales/modal_pago_poliza.php`** — fallback de tasa BCV prueba `pydolarve.org` (dependencia externa en el cliente para montos en Bs).
- [ ] Paginación inconsistente: 2 (planes), 10 (bitácora/usuarios), 15 (afiliados/citas/comunidad).
- [ ] `package.json` solo para ionicons pero los iconos usados son Font Awesome (dependencia posiblemente sin uso).
- [ ] Encoding: nombres con acentos mostrados con `?` en consola MySQL — verificar collation de la conexión en los reportes CLI/mysqldump (la BD usa utf8mb4, correcto).

---

## 📌 Resumen para planificación

| Prioridad | Categoría | Ítems | Impacto |
|---|---|---|---|
| 🔴 1 | Seguridad crítica | 9 grupos (~35 ítems) | Cualquiera puede tomar control de cuentas, crear admins, inyectar SQL, descargar la BD |
| 🟠 2 | Bugs funcionales | 25 | Funciones rotas visibles para el usuario (feedback de pagos, recuperación de contraseña, ayuda) |
| 🟡 3 | Lógica de negocio | 22 | Dinero: validaciones solo en cliente, límites inconexos, datos que se pierden |
| 🟢 4 | Calidad/inconsistencias | 14 | Deuda técnica, portabilidad, código muerto |

### Orden de ataque sugerido
1. ✅ **Cerrar el bypass de recuperación de contraseña** (1.1) — hecho.
2. ✅ **Auth guard central** para todos los endpoints (1.3) — hecho (55 endpoints).
3. **Arreglar los 3 bugs que rompen pagos hoy** (2.1 primeros 3 ítems).
4. Prepared statements en procesadores de dinero (1.4).
5. Rutas 404 del flujo de recuperación (2.2) y vista Ayuda (2.3).
6. El resto por prioridad.
