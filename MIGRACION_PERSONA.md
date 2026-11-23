# Migración: clave sustituta en `persona`

> **Fecha:** 11/11/2026
> **Base:** `ipsp` (MariaDB 10.4.32)
> **Estado:** ✅ aplicado y verificado en la base real
> **Alcance:** solo estructura de `persona`. No se tocó código PHP/JS.

---

## 1. Cambio aplicado

| Antes | Después |
|---|---|
| PK = `cedula` | PK = `id_persona` (auto_increment) |
| — | `cedula` pasa a `UNIQUE KEY uq_persona_cedula` (clave de negocio) |
| — | nueva columna `estado_civil VARCHAR(40) NULL` |

`cedula` **no se elimina**: sigue siendo única y sigue siendo el vínculo de todas las FKs y consultas actuales. Por eso nada se rompe.

### SQL ejecutado

```sql
ALTER TABLE persona
  ADD COLUMN id_persona INT NOT NULL AUTO_INCREMENT UNIQUE FIRST,
  ADD COLUMN estado_civil VARCHAR(40) NULL AFTER genero,
  ADD UNIQUE KEY uq_persona_cedula (cedula);

ALTER TABLE persona
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (id_persona);

ALTER TABLE persona
  DROP INDEX id_persona;
```

### Estructura resultante

```
CREATE TABLE persona (
  id_persona int(11) NOT NULL AUTO_INCREMENT,
  cedula int(11) NOT NULL,
  nombre varchar(50) NOT NULL,
  apellido varchar(50) NOT NULL,
  fechanacimiento date NOT NULL,
  genero enum('Masculino','Femenino','','') NOT NULL,
  estado_civil varchar(40) DEFAULT NULL,
  telefono varchar(20) NOT NULL,
  correo varchar(100) NOT NULL,
  ocupacion varchar(50) NOT NULL,
  estado text NOT NULL,
  PRIMARY KEY (id_persona),
  UNIQUE KEY uq_persona_cedula (cedula)
)
```

---

## 2. Backup

```
C:\Users\Gregory\AppData\Local\Temp\opencode\backup_ipsp_antes_persona_pk_20261111.sql
```

Dump completo (`--single-transaction --routines --events`), 44 KB.

### Rollback (probado en copia)

```sql
ALTER TABLE persona
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (cedula),
  DROP INDEX uq_persona_cedula,
  DROP COLUMN id_persona,
  DROP COLUMN estado_civil;
```

> Debe ejecutarse como un solo `ALTER`; si se separa, MariaDB rechaza dejar `id_persona` como `AUTO_INCREMENT` sin índice.

---

## 3. Validación realizada

> **Nota:** esta sección refleja el estado del **11/11/2026**. Desde la Fase C1
> (14/11/2026, §6.5) `consumo_plan.ID_persona_plan` ya no existe y, desde la
> Fase C2 (§6.6), tampoco `afiliados.cedula` ni `beneficiarios.cedula`: la única
> cédula del sistema es `persona.cedula`.

- `persona`: 8 filas antes y después.
- `afiliados` 5, `beneficiarios` 3, `consumo_plan` 1: sin cambios.
- Las 3 FKs que apuntaban a `persona.cedula` en ese momento:
  - `afiliados.cedula` → `persona.cedula`
  - `beneficiarios.cedula` → `persona.cedula`
  - `consumo_plan.ID_persona_plan` → `persona.cedula`
- 0 huérfanos en `afiliados`, `beneficiarios` y `consumo_plan`.
- `JOIN afiliados a JOIN persona p ON a.cedula = p.cedula` devuelve las 5 filas correctas con su `id_persona`.
- Rollback probado en `ipsp_rollback_test`: devuelve la tabla al estado original.

### Valores asignados

| id_persona | cedula | nombre |
|---:|---:|---|
| 1 | 3771659 | Ana Salazar |
| 2 | 10505948 | Teresa Pérez |
| 3 | 12345678 | alana perez |
| 4 | 22657594 | Luis Mendoza |
| 5 | 28296254 | genesis contreras |
| 6 | 30270492 | Gregory Pérez |
| 7 | 30492270 | luis balbo |
| 8 | 34234140 | Sebastian Pérez |

`estado_civil` queda `NULL` en las 8 filas (aún no hay formulario que la capture).

---

## 4. Fase A — propagar `id_persona` a las tablas hijas

> **Fecha:** 11/11/2026
> **Estado:** ✅ aplicado y verificado en la base real
> **Alcance:** solo estructura y datos. No se tocó código.

### SQL ejecutado

```sql
ALTER TABLE afiliados
  ADD COLUMN id_persona INT NULL AFTER ID,
  ADD UNIQUE KEY uq_afiliados_persona (id_persona);

ALTER TABLE beneficiarios
  ADD COLUMN id_persona INT NULL AFTER ID,
  ADD UNIQUE KEY uq_beneficiarios_persona (id_persona);

ALTER TABLE contrato_plan
  ADD COLUMN id_persona INT NULL AFTER ID_afiliado_contrato,
  ADD KEY idx_contrato_persona (id_persona);

UPDATE afiliados a
JOIN persona p ON p.cedula = a.cedula
SET a.id_persona = p.id_persona;

UPDATE beneficiarios b
JOIN persona p ON p.cedula = b.cedula
SET b.id_persona = p.id_persona;

UPDATE contrato_plan c
JOIN afiliados a ON a.cedula = c.ID_afiliado_contrato
SET c.id_persona = a.id_persona;

ALTER TABLE afiliados
  ADD CONSTRAINT fk_afiliados_persona
  FOREIGN KEY (id_persona) REFERENCES persona(id_persona)
  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE beneficiarios
  ADD CONSTRAINT fk_beneficiarios_persona
  FOREIGN KEY (id_persona) REFERENCES persona(id_persona)
  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE contrato_plan
  ADD CONSTRAINT fk_contrato_persona
  FOREIGN KEY (id_persona) REFERENCES persona(id_persona)
  ON UPDATE CASCADE;
```

### Resultado

| Tabla | `id_persona` | Índice | FK |
|---|---|---|---|
| `afiliados` | 5/5 rellenados, 0 nulos | `UNIQUE` | `CASCADE` |
| `beneficiarios` | 3/3 rellenados, 0 nulos | `UNIQUE` | `CASCADE` |
| `contrato_plan` | 7/7 rellenados, 0 nulos | índice normal | `RESTRICT` al borrar |

- Las cédulas se conservan; las FKs viejas siguen activas.
- Consultas viejas por cédula: 5 / 3 / 7.
- Consultas nuevas por `id_persona`: 5 / 3 / 7.
- Conteos sin cambios: `persona` 8, `afiliados` 5, `beneficiarios` 3, `contrato_plan` 7.
- Rollback probado en copia (`DROP FOREIGN KEY` + `DROP INDEX` + `DROP COLUMN`).

### Backup

```
C:\Users\Gregory\AppData\Local\Temp\opencode\backup_ipsp_antes_faseA_20261111.sql
```

---

## 5. Fase B — piloto del módulo afiliados

> **Fecha:** 11/11/2026
> **Estado:** ✅ implementado, probado en copia y aplicado en la base real
> **Regla:** el usuario sigue ingresando **cédula**; el backend resuelve `id_persona`.

### Helper nuevo

`config/persona_helper.php`

```php
buscarIdPersonaPorCedula(mysqli $conn, string $cedula): ?int
```

Devuelve el `id_persona` asociado a una cédula, o `null` si no existe. Centraliza la búsqueda para no repetirla en cada formulario.

### Archivos modificados

| Archivo | Cambio |
|---|---|
| `config/persona_helper.php` | **nuevo**: búsqueda cédula → id_persona |
| `app/afiliados/getafiliados.php` | resuelve id_persona y consulta por `a.id_persona`; devuelve `id_persona` en el JSON |
| `app/afiliados/modales/formulario/guardar.php` | resuelve/crea persona con `insert_id`; guarda `id_persona` en `afiliados` y `contrato_plan` |
| `app/afiliados/modales/actualizar/actualizar.php` | resuelve id_persona; actualiza `persona` y `afiliados` por `id_persona` |
| `app/afiliados/modales/asignar_plan/guardar_plan.php` | resuelve id_persona; desactiva y crea contratos por `id_persona` |
| `app/afiliados/modales/formulario/check_cedula.php` | devuelve `id_persona` junto a los indicadores de existencia |
| `app/afiliados/principal.php` | `JOIN persona p ON a.id_persona = p.id_persona`; agrega `p.id_persona` al SELECT; filtros activos/inactivos y `tiene_activo` comparan `cp.id_persona = a.id_persona` |

### Pruebas realizadas (base copia `ipsp_pilot_test`)

| Flujo | Resultado |
|---|---|
| Crear persona nueva + afiliado + contrato | ✅ persona 9, afiliado 58, contrato 21, todos con `id_persona = 9` |
| Crear afiliado sobre persona existente | ✅ reutiliza `id_persona = 2`, no duplica persona |
| Editar afiliado | ✅ actualiza `persona` y `afiliados` por `id_persona` |
| Asignar plan | ✅ nuevo contrato con `id_persona`; anteriores pasan a `Inactivo` |
| `getafiliados.php` existente / inexistente | ✅ devuelve `id_persona` / error controlado |
| `check_cedula.php` | ✅ devuelve `id_persona` |
| Consultas de `principal.php` (activos/inactivos/todos) | ✅ sin errores |

- Durante las pruebas del piloto la base real no se modificó (tenía 8 personas, 5 afiliados, 3 beneficiarios, 7 contratos).
- La base de prueba se eliminó al terminar.

### 5.1 Edición de cédula y edad mínima de 20 años

> **Fecha:** 11/11/2026
> **Estado:** ✅ aplicado en la base real

**Cambio de FKs** (necesario para que la cédula pueda cambiar en cascada):

```sql
ALTER TABLE contrato_plan DROP FOREIGN KEY contrato_plan_ibfk_2;
ALTER TABLE consumo_plan   DROP FOREIGN KEY consumo_plan_ibfk_2;

ALTER TABLE contrato_plan
  ADD CONSTRAINT contrato_plan_ibfk_2
  FOREIGN KEY (ID_afiliado_contrato) REFERENCES afiliados (cedula)
  ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE consumo_plan
  ADD CONSTRAINT consumo_plan_ibfk_2
  FOREIGN KEY (ID_persona_plan) REFERENCES persona (cedula)
  ON UPDATE CASCADE ON DELETE RESTRICT;
```

- Se ejecutaron en sentencias separadas porque MariaDB lanza `errno 121` al recrear una FK con el mismo nombre dentro del mismo `ALTER`.
- En ese momento, cambiar `persona.cedula` actualizaba automáticamente `afiliados.cedula`, `beneficiarios.cedula`, `contrato_plan.ID_afiliado_contrato` y `consumo_plan.ID_persona_plan`.
  > **Actualizado el 14/11/2026 (fases C1 y C2, ver 6.5 y 6.6):** `contrato_plan`
  > y `consumo_plan` ya no guardan cédulas, y las columnas duplicadas
  > `afiliados.cedula` y `beneficiarios.cedula` también fueron eliminadas. La
  > única cédula es `persona.cedula`; contratos y consumos apuntan a
  > `persona.id_persona`.
- Backup previo: `backup_ipsp_antes_cedula_editable_20261111.sql`.

**Código**

| Archivo | Cambio |
|---|---|
| `editmodal.php` | cédula editable, hidden `id_persona` y `cedula_original`, fecha máxima `hoy − 20 años`, correo validado por `id_persona` |
| `accionesafiliados.js` | llena los hidden y evita registrar el `submit` varias veces |
| `actualizar.php` | identifica por `id_persona`; valida formato, cédula duplicada, correo y edad mínima; actualiza `persona.cedula` |
| `guardar.php` | valida formato de cédula y edad mínima antes de crear |
| `formulariomodal.php` | fecha máxima `hoy − 20 años` |
| `check_correo.php` | acepta `id_persona_ignorada`; conserva `cedula_ignorada` para beneficiarios |

**Pruebas (copia `ipsp_cedula_test`)**

| Caso | Resultado |
|---|---|
| Cambiar 30270492 → 30270000 | ✅ cascada en persona, afiliados y 2 contratos |
| Restaurar 30270000 → 30270492 | ✅ |
| Cédula de Ana (con consumo) | ✅ también actualizó `consumo_plan` |
| Cédula duplicada | ✅ rechazada, sin cambios |
| Nacimiento 2010-01-01 | ✅ rechazado (menor de 20) |
| Nacimiento exactamente hace 20 años | ✅ permitido |
| Cédula no numérica | ✅ rechazada |
| Correo propio / ajeno | ✅ ignorado / detectado |
| Alta con menor de 20 | ✅ no creó registros |
| Alta válida | ✅ persona + afiliado + contrato con el mismo `id_persona` |

- Regla aplicada solo a afiliados; beneficiarios menores no se ven afectados.
- La copia de prueba se eliminó.
- La base real quedó con 9 personas y 6 afiliados (incluye el afiliado de prueba `90000001` creado en la validación manual).

**Caché del navegador:** `accionesafiliados.js` se sirve con `?v=20261111-2`. Sin ese versionado, el navegador podía conservar el JS anterior (que no llenaba `id_persona` ni `cedula_original`) y la edición de cédula respondía “la persona no existe”. Si se modifica ese JS de nuevo, hay que subir la versión del parámetro.

### 5.2 Consulta de consumo y modal de vista

> **Fecha:** 11/11/2026
> **Estado:** ✅ migrado y probado en la base real (solo lectura)

**Cambio en `app/afiliados/consultar_consumo.php`**

- Incluye `config/persona_helper.php` y resuelve `id_persona` desde `$_GET['cedula']`.
- La cédula sigue siendo la entrada del JS (`verResumenConsumo(cedula, ...)`); el backend ya no une por cédula.
- Si la cédula no existe, responde el mismo aviso de “No se encontró contrato activo”.
- Join principal: `INNER JOIN contrato_plan cp ON a.id_persona = cp.id_persona`.
- Filtro: `WHERE a.id_persona = ? AND cp.estado_contrato = 'Activo'` (`bind_param("i", ...)`).
- El historial une por `LEFT JOIN persona p ON c.id_persona = p.id_persona` desde la Fase C1 (6.5).

**Cambio en `app/afiliados/principal.php`**

- Los filtros “Con Contrato Activo” / “Sin Contrato / Inactivos” y la columna `tiene_activo` ahora comparan `cp.id_persona = a.id_persona`.

**Pruebas (base real, lectura)**

| Cédula | Caso | Resultado |
|---|---|---|
| `3771659` (Ana) | contrato activo con 1 consumo | ✅ plan y 1 fila de historial |
| `30270422` (Gregory) | contrato activo sin consumo | ✅ plan, historial vacío |
| `90000001` | afiliado de prueba, sin consumo | ✅ plan, historial vacío |
| `11111111` | cédula inexistente | ✅ aviso controlado, sin error |
| Filtros activos/inactivos | comparación vieja vs. nueva | ✅ 6 activos / 0 inactivos en ambas |

**Modal de vista:** no requiere cambios de backend. `vermodal.php` se llena desde `getafiliados.php`, que ya resuelve `id_persona` a partir de la cédula.

### Pendiente dentro de afiliados

`modales/eliminar/eliminar.php` no se migrará porque la decisión es **no eliminar afiliados** (son data de la clínica). `consumo_plan` ya usa `id_persona` desde la Fase C1 (6.5).

---

## 6. Fase B — módulo beneficiarios

> **Fecha:** 12/11/2026
> **Estado:** ✅ migrado y probado en copia `ipsp_benef_test`
> **Regla:** el usuario sigue ingresando **cédula**; el backend resuelve `id_persona`. Los beneficiarios **sí pueden ser menores** (no aplica la regla de 20 años de afiliados).

### Archivos modificados

| Archivo | Cambio |
|---|---|
| `app/beneficiarios/getbeneficiarios.php` | resuelve `id_persona` desde la cédula y consulta por `b.id_persona`; devuelve `id_persona` |
| `app/beneficiarios/principal.php` | joins por `id_persona`; filtros y `tiene_activo` por `cp.id_persona = a.id_persona`; `data-bs-id-persona` en el botón Editar |
| `app/beneficiarios/modales/actualizar/editmodal.php` | hidden `id_persona` y `cedula_original`; selects de afiliados por `id_persona`; validación de correo por `id_persona_ignorada` |
| `app/beneficiarios/modales/actualizar/actualizar.php` | identifica por `id_persona`; valida cédula/fecha/correo y afiliado relacionado; actualiza `persona` por `id_persona` (incluida la cédula) y `beneficiarios` por `id_persona` (incluido `id_afiliado`) |
| `app/beneficiarios/modales/formulario/guardar.php` | crea/reutiliza persona y guarda `id_persona` en `beneficiarios`; transacción, validaciones y bloqueo de duplicados |
| `app/beneficiarios/modales/formulario/formulariomodal.php` | select de afiliados por `id_persona` |
| `app/beneficiarios/modales/formulario/check_cedula.php` | prepared statements; conserva la cédula como entrada |
| `app/beneficiarios/consultar_consumo_beneficiario.php` | resuelve `id_persona` y une el contrato por `a.id_persona = cp.id_persona` |
| `assets/js/accionesbeneficiarios.js` | llena hidden `id_persona`/`cedula_original`; un solo listener de edición (evita envíos duplicados) |

- `principal.php` sirve el JS con `?v=20261112-1` para forzar la recarga.
- `vermodal.php` no requirió cambios: se llena desde `getbeneficiarios.php`.
- `modales/eliminar/eliminar.php` opera por cédula de entrada y resuelve `id_persona` (actualizado en C2, §6.6).
- Al cambiar la cédula de un beneficiario, solo se actualiza `persona.cedula`; desde C2 ya no existe `beneficiarios.cedula` (antes se sincronizaba por la FK `beneficiarios_ibfk_1`).

### Pruebas realizadas (copia `ipsp_benef_test`)

| Caso | Resultado |
|---|---|
| `getbeneficiarios.php` cédula `10505948` | ✅ JSON con `id_persona = 2`, `id_afiliado = 52`, parentesco |
| `getbeneficiarios.php` cédula inexistente | ✅ `Beneficiario no encontrado` |
| Consumo del beneficiario `22657594` | ✅ plan familiar visible, sin error |
| Consumo con cédula inexistente | ✅ aviso controlado |
| Filtros activos / inactivos / todos | ✅ 4 / 0 / 4 filas en la copia (incluye el alta de prueba) |
| Editar por `id_persona` (parentesco) | ✅ actualiza `beneficiarios` por id interno |
| Cambiar cédula `10505948 → 10505999` | ✅ cascada en `beneficiarios`, sin huérfanos |
| Cédula duplicada | ✅ rechazada |
| Fecha futura | ✅ rechazada |
| Cédula con letras | ✅ rechazada |
| Correo de otra persona | ✅ rechazado |
| Alta válida de menor de edad | ✅ persona 10 + beneficiario 23 con `id_persona = 10` |
| Alta duplicada / cédula de afiliado / fecha futura / correo duplicado | ✅ rechazadas sin crear registros |
| `check_cedula.php` | ✅ distingue beneficiario, afiliado y cédula libre |
| Cambiar titular de `52` a `54` | ✅ `beneficiarios.id_afiliado = 54`; `getbeneficiarios` devuelve el nuevo titular |
| Titular inexistente | ✅ rechazado, sin cambios |
| Edición sin `cedula_afil` | ✅ rechazada por campo obligatorio |

- La base real no se modificó durante estas pruebas: sigue con 9 personas y 3 beneficiarios.
- La copia `ipsp_benef_test` se eliminó al terminar.

### 6.1 auth_guard en afiliados y beneficiarios

> **Fecha:** 12/11/2026
> **Estado:** ✅ aplicado

Se agregó `auth_guard([1, 2])` al inicio de todos los endpoints de ambos módulos:

| Módulo | Endpoints protegidos |
|---|---|
| Afiliados | `getafiliados.php`, `consultar_consumo.php`, `modales/formulario/guardar.php`, `modales/formulario/check_cedula.php`, `modales/actualizar/actualizar.php`, `modales/asignar_plan/guardar_plan.php`, `modales/eliminar/eliminar.php`, `procesar_contrato.php` |
| Beneficiarios | `getbeneficiarios.php`, `consultar_consumo_beneficiario.php`, `modales/formulario/guardar.php`, `modales/formulario/check_cedula.php`, `modales/actualizar/actualizar.php`, `modales/eliminar/eliminar.php` |

- También se protegió `principal.php` de ambos módulos para bloquear el acceso directo a la vista sin sesión.
- Se eliminaron los `session_start()` locales de los endpoints porque `auth_guard` ya inicia la sesión.
- Los fragmentos de modal (`vermodal.php`, `editmodal.php`, `formulariomodal.php`, etc.) no llevan guard: se incluyen dentro de una vista ya protegida.

**Pruebas**

| Caso | Resultado |
|---|---|
| GET sin sesión a `consultar_consumo_beneficiario.php` | ✅ 302 al login |
| POST AJAX sin sesión a `getbeneficiarios.php` | ✅ 401 JSON |
| Rol 1 y rol 2 | ✅ datos y escritura correctos |
| Rol 3 (médico) | ✅ 403 |
| Sesión con usuario inexistente | ✅ 401 y sesión destruida |
| `principal.php` de ambos módulos con rol 1 | ✅ renderiza sin errores |
| `principal.php` con rol 3 | ✅ 403 |

### 6.2 Corrección: edición de beneficiario sin `id_persona`

> **Fecha:** 12/11/2026
> **Estado:** ✅ aplicado

**Síntoma:** al guardar la edición de un beneficiario aparecía
`No se pudo identificar al beneficiario: faltan datos del formulario...`

**Causa:** el navegador estaba ejecutando una versión antigua en caché de
`assets/js/accionesbeneficiarios.js`, que rellenaba los campos visibles pero **no** los hidden
`id_persona` / `cedula_original`. Al cambiar la cédula en el formulario, el backend ya no podía
resolver la persona: la cédula nueva no existe todavía y no había `id_persona` ni cédula original.

**Correcciones:**

| Archivo | Cambio |
|---|---|
| `assets/js/accionesbeneficiarios.js` | Listener delegado en `document` que rellena `id_persona`/`cedula_original` al hacer clic en `[data-bs-target="#editmodal"]`, sin depender de `shown.bs.modal`. En el `submit` se rellenan como respaldo si quedaron vacíos. Se guarda el último beneficiario seleccionado en memoria. |
| `app/beneficiarios/principal.php` | El `src` del script usa `?v=filemtime(...)`, así cada edición del JS fuerza recarga y evita cachés viejas. |
| `app/beneficiarios/modales/actualizar/actualizar.php` | Si no llega `id_persona`, se resuelve por `cedula_original` o, en su defecto, por la cédula enviada. Mensaje de error más claro. |

**Pruebas (copia `ipsp_benef_test`, base real intacta):**

| Caso | Resultado |
|---|---|
| Cambiar la cédula con `id_persona` | ✅ actualiza `persona.cedula` (y, antes de C2, `beneficiarios.cedula` por cascada) |
| Sin hidden, cédula existente | ✅ se resuelve por cédula (respaldo) |
| Sin hidden, cédula inexistente | ✅ mensaje claro, sin cambios en BD |
| Listener delegado sin Bootstrap cargado | ✅ `id_persona=2`, `cedula_original=10505948` |
| `home.php?vista=beneficiarios` | ✅ renderiza, script con `?v=` por fecha |

### Pendiente dentro de beneficiarios

- El select “Afiliado relacionado” ya actualiza `beneficiarios.id_afiliado` desde `actualizar.php`, con validación de que el afiliado exista. La bitácora registra el cambio de titular.
- `modales/eliminar/eliminar.php` ya resuelve `id_persona` y elimina por persona (C2, §6.6).

### 6.3 Migración de Comunidad UPTM a `persona`

> **Fecha:** 12/11/2026
> **Estado:** ✅ aplicado en copia y en base real `ipsp`

**Objetivo:** eliminar `comunidad_uptm` y guardar a esas personas como registros
comunes de `persona`. "Comunidad UPTM" pasa a definirse **por exclusión**:
personas sin fila en `afiliados` ni en `beneficiarios`.

**Decisiones acordadas con el usuario:**

| Tema | Decisión |
|---|---|
| Fecha de nacimiento y género | `NULL` en ambas columnas; el listado muestra "No aplica". |
| Botón Eliminar | Se quitó del módulo. No se borran personas desde la interfaz. |
| `citas_uptm.id_externo` | Renombrada a `id_persona` con FK a `persona.id_persona`. |
| Registros de comunidad ya afiliados/beneficiarios | No se duplican: se descarta su fila de comunidad y siguen en `persona`. |
| Cédula en el modal de edición | Solo lectura: es el identificador estable de historias médicas. |

**Esquema (SQL aplicado):**

```sql
ALTER TABLE persona
  MODIFY fechanacimiento DATE NULL,
  MODIFY genero ENUM('Masculino','Femenino') NULL;

INSERT INTO persona (cedula, nombre, apellido, fechanacimiento, genero,
                     telefono, correo, ocupacion, estado)
SELECT c.cedula, c.nombre, c.apellido, NULL, NULL,
       c.telefono, 'No aplica', 'No aplica', 'No aplica'
FROM comunidad_uptm c
LEFT JOIN persona p ON p.cedula = c.cedula
WHERE p.id_persona IS NULL;

ALTER TABLE citas_uptm DROP FOREIGN KEY citas_uptm_ibfk_2;
UPDATE citas_uptm cu
  JOIN comunidad_uptm c ON c.id = cu.id_externo
  JOIN persona p       ON p.cedula = c.cedula
SET cu.id_externo = p.id_persona;
ALTER TABLE citas_uptm CHANGE id_externo id_persona INT(11) NOT NULL;
ALTER TABLE citas_uptm
  ADD CONSTRAINT fk_citas_uptm_persona
  FOREIGN KEY (id_persona) REFERENCES persona(id_persona)
  ON DELETE RESTRICT ON UPDATE CASCADE;

DROP TABLE comunidad_uptm;
```

**Resultado en la base real:** 12 personas, 3 de comunidad (`12308793`,
`30960255`, `30270491`), cita 69 apuntando a `id_persona = 10`, tabla
`comunidad_uptm` eliminada.

**Código actualizado:**

| Módulo | Archivos | Cambio |
|---|---|---|
| Comunidad UPTM | `app/comunidaduptm/principal.php`, `getcomunidad.php`, `formulario/guardar.php`, `actualizar/actualizar.php`, `actualizar/editmodal.php`, `formulario/formulariomodal.php`, `assets/js/accionescomunidaduptm.js` | Listado por exclusión desde `persona`; alta de 4 campos con `"No aplica"`; edición por `id_persona`; sin botón Eliminar. |
| Citas | `buscar_externo.php`, `principal.php`, `getcitas.php`, `modales/actualizar/actualizar.php`, `modales/eliminar/eliminar.php`, `modales/formulario/guardar.php` | Usan `citas_uptm.id_persona` y `persona`; al agendar un externo se crea o reutiliza la persona. **Luego unificado en 6.4.** |
| Pagos y reportes | `app/pagos/vistas/gestion_pagosexternos.php`, `app/reportes/excel/reporte_pagos.php`, `app/reportes/pdf/reporte_pagos.php` | JOIN a `persona` por `id_persona`. |
| Historias médicas | `app/historias_medicas/principal.php`, `modales/ver/gethistoria.php`, `modales/formulario/formulariomodal.php`, `formulariomodal_ginecologia.php` | Selector y nombres desde `persona`; externos por exclusión. |
| Estadísticas | `config/estadistica.php` | Conteo de comunidad por exclusión (gráfico de inicio). |

**Pruebas (copia `ipsp_comunidad_test` y luego base real):**

| Caso | Resultado |
|---|---|
| SQL de migración en copia | ✅ 12 personas / 3 comunidad / cita a persona 10 / tabla eliminada |
| Alta de comunidad (4 campos) | ✅ persona con `NULL` en fecha/género y `"No aplica"` en el resto |
| Cédula duplicada (afiliado / beneficiario / comunidad) | ✅ rechazo con mensaje específico |
| Teléfono inválido | ✅ rechazo |
| Edición de comunidad | ✅ por `id_persona`, con respaldo por cédula |
| Edición de afiliado desde comunidad | ✅ rechazo por rol |
| Cita externa con cédula nueva | ✅ crea persona + relación `citas_uptm` |
| Cita externa con cédula de afiliado | ✅ rechazo |
| `getcomunidad`, `buscar_externo`, `getcitas` | ✅ JSON correcto |
| Vistas HTTP con sesión (comunidad, citas, pagos externos, inicio, historias, afiliados, beneficiarios) | ✅ HTTP 200, sin fatales ni warnings |
| Listado real de comunidad / gráfico de inicio | ✅ 3 registros / `[6, 3, 3]` |

**Corrección adicional:** se eliminó el BOM UTF-8 de `config/database.php`, que
provocaba `headers already sent` en los endpoints JSON cuando no había buffering.

### Pendiente dentro de comunidad

- `citas_uptm` ya no elimina en cascada (`ON DELETE RESTRICT`): si se intenta borrar una persona con citas, la BD lo impide. **Resuelto en 6.4:** la tabla `citas_uptm` ya no existe.
- `tipos_externos` sigue existiendo y se usa en el formulario de pagos externos, pero ya no está ligado a la comunidad.

### 6.4 Unificación de citas en `citas.id_persona`

> **Fecha:** 12/11/2026
> **Estado:** ✅ aplicado en copia `ipsp_citas_test` y en base real `ipsp`

**Objetivo:** que todas las citas (afiliado, beneficiario y comunidad UPTM) se guarden
en una sola relación. Se eliminan las tablas puente `citas_afil`, `citas_benef` y
`citas_uptm`.

**Decisiones acordadas con el usuario:**

| Tema | Decisión |
|---|---|
| Forma de la relación | `citas.id_persona` (1 cita = 1 paciente, sin tabla puente). |
| Tipo de paciente | Se deduce por JOIN a `afiliados`/`beneficiarios`; no se almacena. |
| Borrado de afiliado/beneficiario | Se conserva el comportamiento: el módulo borra primero sus citas. |
| FK | `ON DELETE RESTRICT ON UPDATE CASCADE`, `id_persona` `NOT NULL`. |

**Esquema (SQL aplicado):**

```sql
ALTER TABLE citas ADD COLUMN id_persona INT NULL AFTER id_especialidad;
ALTER TABLE citas ADD INDEX idx_citas_persona (id_persona);

UPDATE citas c
JOIN citas_afil ca ON ca.idcita = c.id_cita
JOIN afiliados a    ON a.ID = ca.id_afiliado
SET c.id_persona = a.id_persona
WHERE c.id_persona IS NULL;

UPDATE citas c
JOIN citas_benef cb  ON cb.idcita = c.id_cita
JOIN beneficiarios b ON b.ID = cb.id_beneficiario
SET c.id_persona = b.id_persona
WHERE c.id_persona IS NULL;

UPDATE citas c
JOIN citas_uptm cu ON cu.idcita = c.id_cita
SET c.id_persona = cu.id_persona
WHERE c.id_persona IS NULL;

ALTER TABLE citas MODIFY id_persona INT NOT NULL;
ALTER TABLE citas
  ADD CONSTRAINT fk_citas_persona
  FOREIGN KEY (id_persona) REFERENCES persona(id_persona)
  ON DELETE RESTRICT ON UPDATE CASCADE;

DROP TABLE citas_afil;
DROP TABLE citas_benef;
DROP TABLE citas_uptm;
```

**Resultado en la base real:** 4 citas, todas con `id_persona`:

| Cita | Persona | Tipo | Fecha |
|---|---:|---|---|
| 68 | 6 | Afiliado | 2026-05-08 |
| 69 | 10 | Comunidad UPTM | 2026-06-04 |
| 70 | 1 | Afiliado | 2026-06-21 |
| 71 | 10 | Comunidad UPTM | 2026-11-19 |

Control: 0 citas sin persona y 0 tablas puente restantes.

**Código actualizado:**

| Módulo | Archivos | Cambio |
|---|---|---|
| Citas | `modales/formulario/guardar.php`, `modales/formulario/formulariomodal.php`, `get_examenes.php`, `principal.php`, `getcitas.php`, `modales/actualizar/actualizar.php`, `modales/eliminar/eliminar.php`, `assets/js/accionescitas.js` | Guardan y consultan `citas.id_persona`; el selector interno envía `persona.id_persona`; el tipo se deduce por JOIN. |
| Pagos | `vistas/gestion_pagoscitas.php`, `vistas/gestion_pagosexternos.php`, `vistas/principalpagos.php`, `procesar_pago_poliza.php` | Sin `UNION` de tablas puente; comunidad = persona sin rol. |
| Consumo | `app/afiliados/consultar_consumo.php`, `app/beneficiarios/consultar_consumo_beneficiario.php` | Consumo familiar por `c.id_persona`. |
| Eliminación | `app/afiliados/modales/eliminar/eliminar.php`, `app/beneficiarios/modales/eliminar/eliminar.php` | Borran `citas` por `id_persona`; ya no tocan tablas puente. |
| Reportes | `app/reportes/{excel,pdf,word}/reporte_citas.php`, `app/reportes/{excel,pdf}/reporte_pagos.php` | Los de citas incluyen "Comunidad UPTM"; los de pagos filtran personas sin rol. |
| Estadística | `config/estadistica.php` | Los 3 conteos por tipo usan `citas.id_persona`. |

**Pruebas (copia y base real):**

| Caso | Resultado |
|---|---|
| SQL en copia y en real | ✅ 4 citas con `id_persona`, tablas puente eliminadas |
| Alta interna / externa | ✅ guarda persona; rechaza cédula con rol |
| Edición interna / externa | ✅ valida roles y cédula duplicada |
| Anulación de cita | ✅ |
| Exámenes y consumo de póliza | ✅ cupos y consumo familiar correctos |
| Pago con póliza | ✅ inserta `consumo_plan` |
| Eliminar afiliado / beneficiario | ✅ borra citas, rol y persona |
| Listados, pagos, reportes, estadística | ✅ HTTP 200 sin errores SQL |
| Reporte Word | ✅ resuelto: se activó `ZipArchive` (`extension=zip`) en `php.ini` y se reinició Apache |

### 6.5 Fase C1 — contratos, consumo y columnas de beneficiarios

> **Fecha:** 14/11/2026
> **Estado:** ✅ aplicado en copia `ipsp_fasec1_test` y en la base real `ipsp`

**Objetivo:** que `contrato_plan` y `consumo_plan` dejen de usar la cédula como
relación y apunten a `persona.id_persona`. Además, alinear los nombres de las
columnas de `beneficiarios` con el diagrama ER.

**Decisión de alcance:** esta ventana hizo **solo C1**. Las columnas
`afiliados.cedula` y `beneficiarios.cedula` se conservaron aquí; su eliminación
quedó para C2 (§6.6), junto con la migración de las lecturas que aún usaban cédula.

**Esquema aplicado:**

```sql
-- contrato_plan
ALTER TABLE contrato_plan MODIFY id_persona INT NOT NULL;
ALTER TABLE contrato_plan DROP FOREIGN KEY contrato_plan_ibfk_2;
ALTER TABLE contrato_plan DROP COLUMN ID_afiliado_contrato;

-- consumo_plan
ALTER TABLE consumo_plan ADD COLUMN id_persona INT NULL AFTER ID_contrato_plan;
UPDATE consumo_plan c JOIN persona p ON p.cedula = c.ID_persona_plan
SET c.id_persona = p.id_persona WHERE c.id_persona IS NULL;
ALTER TABLE consumo_plan MODIFY id_persona INT NOT NULL;
ALTER TABLE consumo_plan ADD INDEX idx_consumo_persona (id_persona);
ALTER TABLE consumo_plan
  ADD CONSTRAINT fk_consumo_persona
  FOREIGN KEY (id_persona) REFERENCES persona(id_persona)
  ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE consumo_plan DROP FOREIGN KEY consumo_plan_ibfk_2;
ALTER TABLE consumo_plan DROP COLUMN ID_persona_plan;

-- beneficiarios
ALTER TABLE beneficiarios
  CHANGE COLUMN ID id_beneficiario INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE beneficiarios
  CHANGE COLUMN cedula_afil id_afiliado INT(20) NOT NULL;
```

**Resultado en la base real:**

| Tabla | Antes | Después |
|---|---|---|
| `contrato_plan` | 8 contratos, `ID_afiliado_contrato` (cédula) | 8 contratos, `id_persona` `NOT NULL` (FK `fk_contrato_persona`) |
| `consumo_plan` | 1 consumo por cédula | 1 consumo con `id_persona = 1` (FK `fk_consumo_persona`) |
| `beneficiarios` | `ID`, `cedula_afil` | `id_beneficiario`, `id_afiliado` (FK `dependencia` a `afiliados.ID`) |

Controles: 0 contratos sin persona, 0 consumos sin persona, 0 beneficiarios sin
persona y 0 `id_afiliado` inválidos.

**Código actualizado:**

| Área | Archivos | Cambio |
|---|---|---|
| Contratos | `config/estadistica.php`, `test_ajax.php`, `app/afiliados/modales/formulario/guardar.php`, `app/afiliados/modales/asignar_plan/guardar_plan.php`, `app/afiliados/procesar_contrato.php` | Insertan y filtran por `contrato_plan.id_persona`; la cédula sigue siendo la entrada. |
| Pagos y recibos | `app/pagos/vistas/gestion_planesasignados.php`, `gestion_pagoscontrato.php`, `gestion_pagoscitas.php`, `modales/modal_registrar_pago.php`, `modales/ver_recibo.php`, `get_afiliado_plan_limits.php` | Joins del contrato por `cp.id_persona`; `get_afiliado_plan_limits` resuelve afiliado/beneficiario y devuelve el `id_persona` real. |
| Consumo | `app/pagos/procesar_pago_poliza.php`, `procesar_consumo_externo.php`, `app/afiliados/consultar_consumo.php` | Escriben/leen `consumo_plan.id_persona`; el historial une por `persona.id_persona`. |
| Reportes | `app/reportes/{excel,pdf}/reporte_pagos.php`, `reporte_gastos_plan.php` | Joins por `cp.id_persona` y `consumo_plan.id_persona`. |
| Citas | `app/citas/modales/formulario/formulariomodal.php`, `app/citas/get_examenes.php` | Contrato por `a.id_persona = cp.id_persona`. |
| Beneficiarios | `app/beneficiarios/*`, reportes de beneficiarios y citas, `app/ayuda/principal.php`, `app/citas/*` | Usan `b.id_beneficiario` y `b.id_afiliado`; los nombres de campos POST/JS (`cedula_afil`) se conservan. |

**Pruebas (copia y base real):**

| Caso | Resultado |
|---|---|
| SQL en copia y real | ✅ estructuras y FKs correctas |
| Alta de afiliado con plan | ✅ crea persona, afiliado y contrato con `id_persona` |
| Asignar plan | ✅ desactiva el anterior e inserta el nuevo por `id_persona` |
| Alta y edición de beneficiario | ✅ `id_beneficiario`/`id_afiliado`; cambio de titular correcto |
| Pago de contrato y recibo PDF | ✅ inserta pago y descarga recibo válido |
| Pago de cita con póliza | ✅ `consumo_plan.id_persona` con la persona de la cita |
| Consumo externo | ✅ registra `consumo_plan.id_persona` |
| Eliminar beneficiario | ✅ borra citas, beneficiario y persona |
| Vistas, consumo, exámenes y reportes | ✅ HTTP 200 sin errores SQL |

**Estado:** ✅ C2 aplicada el 14/11/2026 (§6.6).

### 6.6 Fase C2 — eliminación de las cédulas duplicadas

> **Fecha:** 14/11/2026
> **Estado:** ✅ aplicado en copia `ipsp_fasec2_test` y en la base real `ipsp`

**Objetivo:** dejar una sola fuente de cédula (`persona.cedula`) y que
`beneficiarios` use `id_beneficiario` como clave primaria.

**Esquema aplicado:**

```sql
-- beneficiarios: PK de cedula a id_beneficiario
ALTER TABLE beneficiarios DROP FOREIGN KEY beneficiarios_ibfk_1;
ALTER TABLE beneficiarios
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (id_beneficiario);
ALTER TABLE beneficiarios DROP INDEX Unico;
ALTER TABLE beneficiarios DROP COLUMN cedula;

-- afiliados: quitar la cédula duplicada
ALTER TABLE afiliados DROP FOREIGN KEY relacion;
ALTER TABLE afiliados DROP COLUMN cedula;
```

**Resultado en la base real:**

| Tabla | Antes | Después |
|---|---|---|
| `afiliados` | `cedula` (UNI, FK a `persona.cedula`) | sin `cedula`; `id_persona` (UNIQUE, FK a `persona`) |
| `beneficiarios` | PK `cedula`, `id_beneficiario` UNIQUE | PK `id_beneficiario`; sin `cedula`; `id_afiliado` FK a `afiliados.ID` |

Controles: 6 afiliados, 3 beneficiarios y 12 personas; 0 afiliados sin persona
y 0 beneficiarios sin persona.

**Código actualizado:**

| Área | Archivos | Cambio |
|---|---|---|
| Reportes | `app/reportes/{word,pdf,excel}/reporte_afiliados.php`, `reporte_beneficiarios.php`, `reporte_gastos_plan.php` | Unen `afiliados`/`beneficiarios` por `id_persona`; la cédula se toma de `persona`. |
| Beneficiarios | `app/beneficiarios/principal.php`, `app/beneficiarios/modales/formulario/guardar.php` | La cédula se lee de `p_b.cedula`; el alta ya no escribe `beneficiarios.cedula`. |
| Afiliados | `app/afiliados/modales/formulario/guardar.php` | El alta ya no escribe `afiliados.cedula`. |
| check_cedula | `app/{afiliados,beneficiarios}/modales/formulario/check_cedula.php` | Cuentan por `persona.cedula` con JOIN a `id_persona`. |
| Eliminación | `app/{afiliados,beneficiarios}/modales/eliminar/eliminar.php` | Resuelven `id_persona` desde la cédula y borran por `id_persona`. |
| Historias médicas | `app/historias_medicas/principal.php`, `modales/ver/gethistoria.php`, `modales/formulario/guardar.php`, `guardar_ginecologia.php` | `ci_paciente` se une a `persona.cedula` y el tipo se detecta por `id_persona`. |
| Ayuda | `app/ayuda/principal.php` | Select de pacientes por `id_persona`. |

**Pruebas (copia y base real):**

| Caso | Resultado |
|---|---|
| SQL en copia y real | ✅ columnas eliminadas, PK de `beneficiarios` cambiada |
| Rollback en copia | ✅ restaura columnas, índices, PK y FKs desde `persona` |
| `check_cedula` afiliados/beneficiarios | ✅ libre, afiliado y beneficiario |
| Alta de afiliado con plan | ✅ sin `afiliados.cedula` |
| Asignar plan | ✅ desactiva el anterior e inserta el nuevo |
| Alta, edición y eliminación de beneficiario | ✅ por `id_persona`/`id_beneficiario` |
| Pago de contrato y recibo PDF | ✅ pago y descarga válida |
| Pago de cita con póliza y consumo externo | ✅ `consumo_plan.id_persona` |
| Eliminar afiliado sin contrato | ✅ borra citas, afiliado y persona |
| Historias médicas general y ginecológica | ✅ alta, listado y detalle con nombre del paciente |
| Reportes Excel/PDF/Word | ✅ HTTP 200 sin errores SQL |
| Vistas y endpoints de lectura en real | ✅ HTTP 200 sin errores SQL |

**Pendiente:** ninguno del alcance de la Fase C. Las cédulas restantes en el
código son entradas de API/POST (`cedula`, `cedula_afil`) que se resuelven a
`id_persona`; no hay columnas `afiliados.cedula` ni `beneficiarios.cedula`.

---

## 7. Pendiente (otros módulos)

1. ✅ **Fase C completa:** C1 (`contrato_plan`/`consumo_plan` por `id_persona`) y C2 (sin cédulas duplicadas, PK de `beneficiarios` por `id_beneficiario`).
2. ✅ Citas, historias médicas, pagos y reportes ya usan `persona`. Las citas de los tres tipos están unificadas en `citas.id_persona` (6.4).
3. ✅ `contrato_plan` y `consumo_plan` ya apuntan a `persona.id_persona` (6.5).
4. ✅ `app/afiliados/procesar_contrato.php` se actualizó para resolver `id_persona` desde la cédula.

No queda ninguna referencia SQL a `ID_afiliado_contrato`, `ID_persona_plan`,
`cedula_afil` (como columna), `afiliados.cedula` ni `beneficiarios.cedula`.
Los nombres de campos POST/JS `cedula_afil` se conservan por compatibilidad.
