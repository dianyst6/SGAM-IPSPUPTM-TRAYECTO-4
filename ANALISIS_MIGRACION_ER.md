# Análisis del diagrama ER y plan de migración segura — IPSPUPTM

> **Fecha:** 11/11/2026
> **Base analizada:** `ipsp` en vivo (27 tablas) + código PHP/JS del proyecto.
> **Fuente del modelo nuevo:** `entidad relacion 3.png`, leído por OCR.
>
> ⚠️ **Limitación:** este modelo no puede visualizar imágenes. El contenido se reconstruyó con OCR, que captura nombres de tablas/columnas pero **no las líneas de relación, cardinalidades ni todas las tablas**. Antes de ejecutar SQL definitivo hay que confirmar los puntos de la sección 8 o aportar el DDL del modelo.

---

## 1. Resumen

El diagrama no es un cambio cosmético: propone **claves sustitutas**, **unificación del paciente en citas**, **pagos asociados a persona**, **separación de consultas** y **normalización de nombres**. La buena noticia es que la base tiene pocos datos (5–49 filas por tabla), así que migrar datos es trivial; **el riesgo real está en las consultas hardcodeadas**.

### Tablas que probablemente quedan igual
`citas_examenes`, `categorias_examenes`, `componentes_planes`, `usuarios`, `roles`, `preguntas_seguridad`, `respuestas_seguridad`, `bitacora`, `tipos_externos`, `pagos_contrato` (confirmar detalles). `comunidad_uptm` ya fue eliminada el 12/11/2026.

### Tablas que cambian
`persona`, `afiliados`, `beneficiarios`, `contrato_plan`, `citas`, `examenes`, `especialidades`, `planes`, `consumo_plan`, `pagos_externos`, `medicos`, `historias_medicas`, `historias_medicas_gine`.

### Tablas nuevas
`consultas` (probable).

### Tablas que probablemente desaparecen
`citas_afil`, `citas_benef`, `citas_uptm` → reemplazadas por `citas.id_persona`. ✅ **Aplicado el 12/11/2026** (las tres tablas fueron eliminadas).

---

## 2. Comparativa tabla por tabla

| Tabla actual | Entidad del diagrama (OCR) | Veredicto | Riesgo |
|---|---|---|---|
| `persona` (PK `cedula`) | `Persona`: `id_personas`, `cedula`, `nombre`, `apellido`, `fecha_nacimiento`, `genero`, `telefono`, `ocupacion`, `estado_civil`, `lugar_nacimiento`, `estado` | **CAMBIA**: PK sustituta, renombra `fechanacimiento`, agrega columnas; `correo` no aparece | 🔴 Alto |
| `afiliados` (`ID` PK, `cedula` UNI) | `Afiliado`: `id_afiliado`, `id_personas`, `categoria_p`, timestamps | **CAMBIA**: PK/FK | 🔴 Alto |
| `beneficiarios` (`cedula` PK, `ID` UNI) | `Beneficiario`: `id_beneficiario`, `id_personas`, `id_afiliado`, `parentesco` | **CAMBIA**: PK/FK | 🔴 Alto |
| `contrato_plan` (`ID_afiliado_contrato` → `afiliados.cedula`) | `Contrato`: `id_contrato`, `id_planes_contrato`, `id_afiliado` | **CAMBIA** nombres; estructura similar | 🟠 Medio |
| `citas` (sin paciente) | `Citas`: agrega `id_persona` | **CAMBIA**: unifica paciente | ✅ Aplicado 12/11/2026 |
| `citas_afil`, `citas_benef`, `citas_uptm` | No aparecen | **DESAPARECEN** (probable) | ✅ Eliminadas 12/11/2026 |
| `citas_examenes` | `citas_examenes` | **IGUAL** | 🟢 Bajo |
| `examenes` (`nombre_examen`, `precio`) | `Examenes`: `nombre`, sin `precio` | **CAMBIA**: renombra y posible quita de precio | 🔴 Alto |
| `especialidades` (`descuento`) | `Especialidades`: sin `descuento` | **CAMBIA**: posible quita de descuento | 🟠 Medio |
| `planes` (`monto_cobertura`) | `Planes`: sin `monto_cobertura` | **CAMBIA**: posible quita de cobertura | 🔴 Alto |
| `componentes_planes` | `Componentes` | **IGUAL** (confirmar) | 🟢 Bajo |
| `categorias_examenes` | `Categorias_examenes` | **IGUAL** | 🟢 Bajo |
| `consumo_plan` (incluye `ID_persona_plan`) | `Consumo`: no lo muestra | **CAMBIA**: posible quita | 🟠 Medio |
| `pagos_contrato` | `Pagos_contrato` | **IGUAL** (confirmar `tipo_pago`) | 🟢 Bajo |
| `pagos_externos` (`id_pago_ext`, `id_cita`) | `Pagos`: `id_pagos`, `id_persona`, timestamps; sin `id_cita` | **CAMBIA**: renombra y cambia FK | 🔴 Alto |
| `usuarios` | `Usuarios` | **IGUAL** | 🟢 Bajo |
| `roles` | No aparece | **SE MANTIENE** (confirmar) | 🟢 Bajo |
| `preguntas_seguridad` | `Preguntas` | **IGUAL** | 🟢 Bajo |
| `respuestas_seguridad` | `Respuestas_seguridad` | **IGUAL** | 🟢 Bajo |
| `medicos` (`especialidad`) | `Medicos`: `id_especialidad` | **CAMBIA** nombre | 🟢 Bajo |
| `historias_medicas` (`ci_paciente`, `tipo_paciente`, campos duplicados) | `historias_medicas`: `id_persona`, `id_consulta` | **CAMBIA fuerte** + `consultas` | 🔴 Alto |
| `historias_medicas_gine` (columnas con puntos) | Similar, normalizada | **CAMBIA fuerte** | 🔴 Alto |
| — | `consultas`: `id_consulta`, `motivo_consulta`, `enfermedad_actual`, `informacion_adicional` | **NUEVA** | 🟠 Medio |
| `comunidad_uptm` | No aparece | **SE MANTIENE** (confirmar) | 🟢 Bajo |
| `tipos_externos` | No aparece | **SE MANTIENE** (confirmar) | 🟢 Bajo |
| `bitacora` (`usuario`) | `Bitacora`: OCR leyó `username` | **IGUAL** (confirmar) | 🟢 Bajo |

---

## 3. Impacto real en el código

Búsqueda sobre 167 archivos PHP/JS (sin `vendor/`):

| Identificador | Coincidencias | Archivos |
|---|---:|---:|
| `citas_afil` | 46 | 17 (0 actuales: tabla eliminada) |
| `citas_benef` | 44 | 17 (0 actuales: tabla eliminada) |
| `citas_uptm` | 35 | 13 (0 actuales: tabla eliminada) |
| `cedula_afil` | 34 | 18 |
| `ci_paciente` | 45 | 9 |
| `tipo_paciente` | 55 | 15 |
| `ID_afiliado_contrato` | 36 | 22 |
| `nombre_examen` | 42 | 16 |
| `fechanacimiento` | 59 | 26 |
| `id_pago_ext` | 8 | 3 |
| `ID_persona_plan` | 9 | 6 |
| `pagos_externos` | 17 | 6 |

### Patrones que condicionan la migración
- **`INSERT INTO ...` con lista de columnas:** 36 → buena noticia, no dependen del orden.
- **`INSERT INTO ... VALUES` sin columnas:** 0 → no hay ese riesgo.
- **`SELECT *`:** 8 casos → agregar columnas no suele romper, pero revisar `gestion_planes.php` y `editar_plan.php`.
- **`UNION`:** 8 casos → son los más frágiles si cambia el número/orden de columnas:
  - `app/pagos/vistas/gestion_pagoscitas.php`
  - `app/pagos/get_afiliado_plan_limits.php`
  - `app/pagos/procesar_pago_poliza.php`
  - `app/citas/get_examenes.php`
  - `app/citas/modales/formulario/formulariomodal.php`
  - `app/citas/modales/formulario/guardar.php`
  - `app/ayuda/principal.php` (rota)
- **Triggers/vistas/procedimientos actuales:** ninguno → se pueden añadir sin conflictos.
- **Inconsistencia de FKs actual:** `contrato_plan` referencia `afiliados.cedula`, mientras `consumo_plan` referencia `persona.cedula`. `citas` ya referencia `persona.id_persona` (12/11/2026). La migración debe unificar esto con cuidado.

---

## 4. Estrategia recomendada: expandir → compatibilizar → migrar → contraer

### Regla de oro
**Nunca renombrar o eliminar en el mismo paso en que se cambia el código.** Primero se **añade** lo nuevo, se **rellena**, se **sincroniza**, se cambian las lecturas, se verifica, y solo al final se elimina lo viejo.

### Fase 0 — Preparación (sin cambios)
1. Backup completo: estructura + datos (`mysqldump`).
2. Copia de seguridad de la BD como `ipsp_backup_20261111`.
3. BD de ensayo `ipsp_staging` para probar cada script.
4. Congelar cambios de esquema durante la ventana de migración.
5. Guardar una copia del proyecto (no hay git en la carpeta actual).
6. Pruebas base: login, alta de paciente, cita, contrato, pago, reporte PDF/Excel, historia médica.

### Fase 1 — Ampliar el esquema (solo `ADD`, nada de `DROP`)
- Añadir `persona.id_personas` como **clave sustituta única**, **sin quitar `cedula` del PK**.
- Añadir columnas nuevas nullables en las tablas hijas (`id_personas`, `id_afiliado`, `id_persona`, etc.).
- Crear `consultas` y `historias_medicas.id_consulta`.
- Rellenar los nuevos campos con `UPDATE ... JOIN`.
- Añadir índices y FKs nuevas **sin borrar las antiguas**.
- Añadir `created_at`/`updated_at` donde falten.

> Clave: se puede cumplir el modelo lógico **sin cambiar el PK actual**. `cedula` sigue siendo clave de negocio `UNIQUE`; `id_personas` se usa para las relaciones nuevas. Así las consultas viejas que hacen `JOIN ... ON cedula` siguen funcionando.

### Fase 2 — Compatibilidad de lecturas
- Mantener nombres viejos y nuevos en paralelo.
- Para renombres simples, usar alias en el `SELECT` (`nombre_examen AS nombre`) o **columnas generadas** (`GENERATED ALWAYS AS ... STORED`) si solo se leen.
- Para tablas renombradas (`pagos_externos` → `pagos`), crear una **vista de compatibilidad** con el nombre viejo, o simplemente no renombrar físicamente y resolver el nombre en una capa de repositorio.
- No usar vistas para escrituras complejas: en MySQL/MariaDB las vistas con joins o columnas derivadas no son actualizables de forma fiable.

### Fase 3 — Migrar el código módulo por módulo
Orden recomendado (de menor a mayor riesgo):
1. **Catálogos:** `especialidades`, `examenes`, `planes`, `categorias_examenes`.
2. **Pagos:** `pagos_contrato`, `pagos_externos`.
3. **Pacientes:** `persona`, `afiliados`, `beneficiarios`.
4. **Citas:** `citas` + tablas puente.
5. **Historias:** `historias_medicas`, `historias_medicas_gine`, `consultas`.

En cada módulo:
- Crear un repositorio (`config/repositories/...`) y mover allí las consultas.
- Usar **prepared statements** (aprovechar para cerrar 1.4).
- Escribir en viejo y nuevo (**dual-write**) mientras dura la transición.
- Probar el módulo completo antes de pasar al siguiente.

### Fase 4 — Contraer
- Verificar conteos y checksums.
- Quitar dual-write/triggers.
- Eliminar columnas y tablas viejas **solo cuando `grep` no encuentre referencias**.
- Backup final y documentación.

---

## 5. Ejemplos SQL de la Fase 1 (no ejecutar aún)

### 5.1 `persona`: clave sustituta sin romper el PK actual
```sql
ALTER TABLE persona
  ADD COLUMN id_personas INT NOT NULL AUTO_INCREMENT UNIQUE FIRST,
  ADD COLUMN fecha_nacimiento DATE NULL AFTER fechanacimiento,
  ADD COLUMN estado_civil VARCHAR(40) NULL,
  ADD COLUMN lugar_nacimiento VARCHAR(120) NULL;

UPDATE persona SET fecha_nacimiento = fechanacimiento WHERE fecha_nacimiento IS NULL;
```
`cedula` sigue siendo PK; `id_personas` queda como clave alterna única. Las relaciones nuevas pueden apuntar a `id_personas`.

### 5.2 `afiliados` y `beneficiarios`
```sql
ALTER TABLE afiliados
  ADD COLUMN id_personas INT NULL,
  ADD INDEX idx_afiliados_persona (id_personas);

UPDATE afiliados a
JOIN persona p ON p.cedula = a.cedula
SET a.id_personas = p.id_personas
WHERE a.id_personas IS NULL;

ALTER TABLE beneficiarios
  ADD COLUMN id_personas INT NULL,
  ADD COLUMN id_afiliado INT NULL,
  ADD INDEX idx_benef_persona (id_personas),
  ADD INDEX idx_benef_afiliado (id_afiliado);

UPDATE beneficiarios b
JOIN persona p ON p.cedula = b.cedula
SET b.id_personas = p.id_personas
WHERE b.id_personas IS NULL;

UPDATE beneficiarios b
JOIN afiliados a ON a.ID = b.cedula_afil
SET b.id_afiliado = a.ID
WHERE b.id_afiliado IS NULL;
```

### 5.3 `citas`: rellenar `id_persona` desde las tablas puente — ✅ aplicado
La versión final ejecutada (12/11/2026) ya no usa `persona.id_personas` ni
`comunidad_uptm`: toma `id_persona` directamente de `afiliados`, `beneficiarios`
y `citas_uptm`. Ver `MIGRACION_PERSONA.md` §6.4.

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

### 5.4 `pagos_externos`
```sql
ALTER TABLE pagos_externos
  ADD COLUMN id_persona INT NULL,
  ADD COLUMN created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE pagos_externos pe
JOIN citas c ON c.id_cita = pe.id_cita
SET pe.id_persona = c.id_persona
WHERE pe.id_persona IS NULL;
```
Se mantiene `id_cita` durante la transición para no romper recibos ni reportes.

### 5.5 `historias_medicas` + `consultas`
```sql
CREATE TABLE consultas (
  id_consulta INT NOT NULL AUTO_INCREMENT,
  motivo_consulta TEXT NULL,
  enfermedad_actual TEXT NULL,
  informacion_adicional TEXT NULL,
  PRIMARY KEY (id_consulta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO consultas (id_consulta, motivo_consulta, enfermedad_actual, informacion_adicional)
SELECT id_historia, motivo_consulta, enfermedad_actual, info_adicional
FROM historias_medicas;

ALTER TABLE historias_medicas
  ADD COLUMN id_consulta INT NULL,
  ADD COLUMN id_persona INT NULL;

UPDATE historias_medicas SET id_consulta = id_historia WHERE id_consulta IS NULL;
```
Las columnas viejas se mantienen; el código viejo sigue leyendo de `historias_medicas`.

### 5.6 Renombres de bajo riesgo
```sql
-- medicos.especialidad → id_especialidad (mantener ambas durante la transición)
ALTER TABLE medicos ADD COLUMN id_especialidad INT NULL;
UPDATE medicos SET id_especialidad = especialidad WHERE id_especialidad IS NULL;
```
```sql
-- examenes.nombre_examen → nombre (solo lectura, columna generada)
ALTER TABLE examenes
  ADD COLUMN nombre VARCHAR(255) GENERATED ALWAYS AS (nombre_examen) STORED;
```
> Para escrituras, seguir usando `nombre_examen` hasta migrar el código; luego invertir.

---

## 6. Casos especiales y decisiones críticas

### 6.1 No cambiar el PK de `persona` en la primera fase
El diagrama muestra `id_personas` como PK. **No es necesario** quitar `cedula` del PK para adoptar el modelo: basta con `id_personas` como clave única y usarla en las nuevas FKs. Cambiar el PK implica recrear FKs y tocar todas las tablas hijas; hacerlo al final reduce el riesgo.

### 6.2 `citas`: tablas puente eliminadas
✅ **Resuelto el 12/11/2026.** Como solo había 4 citas y exactamente una relación por
cita, no hizo falta el periodo de doble escritura: se añadió `citas.id_persona`, se
rellenó, se migraron todas las lecturas y se eliminaron `citas_afil`, `citas_benef`
y `citas_uptm` en la misma ventana. El tipo de paciente se deduce por JOIN.

### 6.3 `pagos_externos` → `pagos`
Si el nuevo modelo elimina `id_cita`, se pierde la relación pago–cita que usan recibos y reportes. Mantener `id_cita` nullable durante la transición y añadir `id_persona`. Confirmar si el negocio quiere pagos por persona o por cita.

### 6.4 `historias_medicas` y `consultas`
La separación es correcta, pero hoy los formularios escriben todo en `historias_medicas`. Durante la transición:
- Mantener las columnas viejas.
- Crear `consultas` y escribir en ambas.
- Migrar lecturas y formularios.
- Eliminar duplicados al final.

### 6.5 Columnas que el OCR no muestra y son críticas
No eliminar sin confirmar:
- `persona.correo` (usado por `check_correo.php` y validaciones).
- `examenes.precio` (usado en planes y pagos).
- `especialidades.descuento` (usado en citas).
- `planes.monto_cobertura` (saldo de póliza).
- `pagos_contrato.tipo_pago`.
- `consumo_plan.ID_persona_plan`.

Si el diagrama realmente las quita, hay que definir **dónde se mudan** antes de tocar nada.

### 6.6 Tablas que el OCR no muestra
`roles`, `tipos_externos`, `comunidad_uptm` probablemente se mantienen. Si el nuevo modelo unifica la comunidad en `persona`, es una migración adicional que hay que planificar aparte.

---

## 7. Validación y rollback

### Antes de cada fase
- Backup de la tabla afectada (`CREATE TABLE ..._bak AS SELECT * FROM ...`).
- Script de rollback escrito y probado.

### Después de cada fase
- Conteos: `SELECT COUNT(*)` por tabla.
- Checksums: `CHECKSUM TABLE` o sumas de columnas clave.
- Huérfanos:
```sql
SELECT COUNT(*) FROM citas c
LEFT JOIN persona p ON p.id_personas = c.id_persona
WHERE c.id_persona IS NOT NULL AND p.id_personas IS NULL;
```
- Comparación viejo vs nuevo (shadow read): misma cantidad de filas y mismos totales.
- Pruebas funcionales: login, paciente, cita, contrato, pago, recibo, PDF, Excel, historia médica.

### Rollback
- Mientras no se eliminen columnas viejas, el rollback es simplemente dejar de usar las nuevas.
- Si se elimina algo, restaurar desde el backup de la fase.

---

## 8. Lo que necesito de ti para generar los scripts exactos

1. **El DDL del modelo nuevo.** En MySQL Workbench: `Database → Forward Engineer → Export SQL CREATE Script`. Eso elimina toda ambigüedad del OCR.
2. Confirmar estos puntos:
   - ¿`persona` conserva `correo`? ¿`fechanacimiento` se renombra a `fecha_nacimiento`?
   - ¿`examenes` conserva `precio`? ¿`especialidades` conserva `descuento`? ¿`planes` conserva `monto_cobertura`?
   - ¿Desaparecen `citas_afil`, `citas_benef` y `citas_uptm` en favor de `citas.id_persona`?
   - ¿`pagos_externos` pasa a `pagos` con `id_persona` y pierde `id_cita`?
   - ¿`historias_medicas` se separa en `consultas`?
   - ¿`roles`, `tipos_externos` y `comunidad_uptm` se mantienen?
   - ¿`bitacora.usuario` se renombra a `username` o el OCR leyó mal?
3. Confirmar si quieres **mantener `cedula` como clave de negocio** (recomendado) o cambiar el PK a `id_personas` definitivamente.
4. Indicar si hay ventana de mantenimiento o si se puede migrar en caliente.

Con eso puedo entregarte:
- Script SQL de migración por fases (con rollback).
- Mapa exacto de consultas a modificar, archivo por archivo.
- Capa de repositorios y prepared statements para no romper el sistema.
- Plan de pruebas y criterios de aceptación.
