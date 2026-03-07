# Tickets para Notion — Sistema de Programas Presupuestales

**Fecha:** 2026-02-28
**Actualizacion:** 2026-03-02 — Incorpora cambios del Analisis de Cumplimiento PbR-SED
**Formato:** Cada ticket incluye ID, titulo, descripcion, criterios de aceptacion y rama Git sugerida.
**Estados Kanban:** Backlog | In Progress | In Review | Done

---

## Sprint 0: Infraestructura y Entorno

---

### S0-T0: Inicializar proyecto Laravel 12 en raíz

**Tipo:** chore
**Rama:** `chore/S0-T0-laravel-init`

**Descripcion:**
Ejecutar `composer create-project laravel/laravel .` directamente en la raiz del repositorio para que la estructura del framework sea el proyecto principal.

**Criterios de aceptacion:**
- [ ] Directorio raiz contiene la estructura completa de Laravel 12 (app, artisan, composer.json, etc.)
- [ ] `.env` inicial generado correctamente
- [ ] Git detecta los nuevos archivos de Laravel

---

### S0-T1: Configurar Laravel Sail con PostgreSQL y pgvector

**Tipo:** chore
**Rama:** `chore/S0-T1-sail-config`

**Descripcion:**
Instalar Laravel Sail y configurar el archivo `docker-compose.yml` para usar la imagen de PostgreSQL compatible con `pgvector` y el servicio de Redis.

**Servicios requeridos:**
- Laravel Sail (PHP 8.3+)
- PostgreSQL 16 (Imagen: `pgvector/pgvector:pg16`)
- Redis

**Criterios de aceptacion:**
- [ ] `php artisan sail:install` configurado para pgsql y redis
- [ ] `docker-compose.yml` modificado para usar `pgvector/pgvector:pg16`
- [ ] `./vendor/bin/sail up -d` levanta los servicios correctamente
- [ ] Conexión a base de datos PostgreSQL exitosa desde Sail

---

### S0-T2: Habilitar extensión pgvector en PostgreSQL

**Tipo:** chore
**Rama:** `chore/S0-T2-pgvector-setup`

**Descripcion:**
Crear una migración inicial en Laravel para habilitar la extensión `pgvector` en la base de datos PostgreSQL.

**Criterios de aceptacion:**
- [ ] Migración generada con `CREATE EXTENSION IF NOT EXISTS vector`
- [ ] `sail artisan migrate` ejecuta la migración sin errores
- [ ] La extensión `vector` está activa en el esquema de PostgreSQL
- [ ] Prueba manual: se permite crear una tabla con columna tipo `vector(1536)`

---

### S0-T4: Configurar Redis como driver de colas y caché

**Tipo:** chore
**Rama:** `chore/S0-T4-redis-setup`

**Descripcion:**
Configurar las variables de entorno para que Laravel utilice Redis para manejar el sistema de colas, caché y sesiones.

**Criterios de aceptacion:**
- [ ] `.env` configurado con `QUEUE_CONNECTION=redis` y `CACHE_STORE=redis`
- [ ] `sail artisan queue:work` procesa jobs de prueba
- [ ] Cache de Laravel operativa sobre Redis
- [ ] Sesiones de usuario persistidas en Redis

---

## Sprint 1: Identidad y Aislamiento

---

### S1-T1: Instalar Laravel Jetstream con Teams

**Tipo:** feat
**Rama:** `feat/S1-T1-jetstream-teams`

**Descripcion:**
Instalar Jetstream con el stack Livewire y la funcionalidad de Teams activada. Ejecutar migraciones base. Verificar que el flujo de registro, login y creacion de equipos funcione.

**Criterios de aceptacion:**
- [ ] `composer require laravel/jetstream` ejecutado
- [ ] Jetstream instalado con `--teams` flag
- [ ] `php artisan migrate` crea las tablas: users, teams, team_user, team_invitations, sessions, personal_access_tokens
- [ ] Flujo de registro crea usuario + team personal
- [ ] Login y logout funcionan correctamente
- [ ] Interfaz de gestion de equipos accesible

---

### S1-T2: Extender tabla teams con campos de Unidad Responsable

**Tipo:** feat
**Rama:** `feat/S1-T2-teams-campos-ur`

**Descripcion:**
Crear migracion para agregar campos especificos de Unidad Responsable a la tabla `teams` de Jetstream.

**Campos a agregar:**
- `clave_ur` (string, nullable, unique) — Clave oficial de la UR
- `titular` (string, nullable) — Nombre del titular
- `tipo_ur` (enum: sustantiva, apoyo) — Rol en ejecucion presupuestal
- `activa` (boolean, default: true)

**Criterios de aceptacion:**
- [ ] Migracion con `up()` y `down()` completos
- [ ] `migrate:fresh` ejecuta sin errores
- [ ] Modelo `Team` extendido con `$fillable` actualizado
- [ ] Seeder de prueba crea 3 UR de ejemplo (1 sustantiva, 1 apoyo, 1 inactiva)
- [ ] Actualizar documentacion del esquema de BD

---

### S1-T3: Integrar Spatie/laravel-permission

**Tipo:** feat
**Rama:** `feat/S1-T3-integracion-spatie-roles`

**Descripcion:**
Instalar el paquete `spatie/laravel-permission`. Crear los roles base y permisos granulares del sistema. Configurar seeders.

**Roles:**
- `admin`
- `planeador`
- `operador`

**Permisos:**
- `gestionar_catalogos`
- `crear_programa`
- `editar_mir`
- `capturar_avance`
- `revisar_avance`
- `aprobar_avance`
- `exportar_reportes`
- `administrar_usuarios`

**Asignacion:**
- admin: todos los permisos
- planeador: gestionar_catalogos, crear_programa, editar_mir, revisar_avance, aprobar_avance, exportar_reportes
- operador: capturar_avance, exportar_reportes (limitado)

**Criterios de aceptacion:**
- [ ] Paquete instalado y migraciones ejecutadas
- [ ] Seeder `RolesAndPermissionsSeeder` crea roles y permisos
- [ ] Modelo `User` usa el trait `HasRoles`
- [ ] Test: usuario con rol planeador tiene permiso `crear_programa`
- [ ] Test: usuario con rol operador NO tiene permiso `crear_programa`

---

### S1-T4: Activar 2FA obligatorio

**Tipo:** feat
**Rama:** `feat/S1-T4-2fa-obligatorio`

**Descripcion:**
Configurar Jetstream para que la autenticacion de doble factor sea obligatoria para todos los usuarios. Redirigir a configuracion de 2FA si el usuario no lo ha activado.

**Criterios de aceptacion:**
- [ ] Middleware que detecta si el usuario no tiene 2FA configurado
- [ ] Redireccion automatica a pagina de configuracion de 2FA
- [ ] Usuario no puede acceder a ninguna ruta protegida sin 2FA activo
- [ ] Flujo de activacion de 2FA funcional (QR + codigos de respaldo)

---

### S1-T5: Middleware de aislamiento Multi-UR (UR Coordinadora / Coadyuvante)

**Tipo:** feat
**Rama:** `feat/S1-T5-middleware-multi-ur`

**Descripcion:**
Crear middleware global con logica de aislamiento en dos niveles para soportar programas transversales con multiples Unidades Responsables. El middleware ya NO bloquea rigidamente por `team_id` del programa, sino que verifica el rol de la UR en cada programa.

**Logica del middleware:**
1. Si el usuario pertenece a la **UR Coordinadora** del programa: acceso completo (lectura + escritura en todo el programa)
2. Si el usuario pertenece a una **UR Coadyuvante** (registrada en `programa_team`): acceso read-only al programa general + acceso de captura/edicion exclusivamente en Componentes/Actividades asignados a su UR
3. Si no tiene ninguna relacion con el programa: acceso denegado (403)

**Criterios de aceptacion:**
- [ ] Middleware registrado globalmente para rutas autenticadas
- [ ] Consulta a `programa_team` para determinar el rol (coordinadora/coadyuvante) del equipo activo del usuario
- [ ] UR Coordinadora: acceso sin restricciones al programa
- [ ] UR Coadyuvante: solo lectura en el programa; escritura limitada a sus mir_niveles asignados (verificado por `mir_niveles.team_id`)
- [ ] Aislamiento total para usuarios sin relacion con el programa (403)
- [ ] Admin puede acceder a todos los programas sin restriccion
- [ ] Test: operador de UR Coadyuvante puede capturar avance en su componente asignado
- [ ] Test: operador de UR Coadyuvante recibe 403 al intentar editar componente de otra UR
- [ ] Test: operador de UR sin relacion recibe 403 al intentar acceder al programa

---

### S1-T7: Migracion tabla pivote programa_team (Multi-UR)

**Tipo:** feat
**Rama:** `feat/S1-T7-tabla-programa-team`

**Descripcion:**
Crear la tabla pivote `programa_team` que registra todos los equipos participantes en un programa presupuestario, diferenciando entre UR Coordinadora y UR Coadyuvante. Tambien agregar FK nullable `team_id` en `mir_niveles` para asignar componentes/actividades a URs especificas.

**Campos programa_team:**
- `programa_presupuestario_id` (FK)
- `team_id` (FK)
- `rol` (ENUM: `coordinadora`, `coadyuvante`)
- `timestamps`

**Cambio en mir_niveles:**
- Agregar columna `team_id` (FK nullable a teams) — indica la UR Coadyuvante responsable del nivel

**Criterios de aceptacion:**
- [ ] Migracion `programa_team` con constraint UNIQUE en `(programa_presupuestario_id, team_id)`
- [ ] Migracion alter table `mir_niveles` agrega `team_id` nullable con FK
- [ ] Enum `rol` con valores `coordinadora` y `coadyuvante`
- [ ] Modelo `ProgramaPresupuestario` tiene relacion `belongsToMany Team` via `programa_team` con campo pivot `rol`
- [ ] Seeder de prueba: Programa X con Educacion como coordinadora y Salud como coadyuvante del Componente 2
- [ ] `migrate:fresh --seed` sin errores
- [ ] Actualizar documentacion del esquema de BD

---

### S1-T6: Seeders de datos de prueba para desarrollo

**Tipo:** chore
**Rama:** `chore/S1-T6-seeders-desarrollo`

**Descripcion:**
Crear seeders completos para desarrollo local que generen un escenario realista de prueba, incluyendo un programa transversal multi-UR.

**Datos a generar:**
- 3 Teams/UR: "Secretaria de Educacion" (coordinadora), "Secretaria de Salud" (coadyuvante), "Secretaria de Seguridad"
- 2 usuarios por team: 1 planeador + 1 operador
- 1 usuario admin global
- 1 programa presupuestario con Educacion como UR Coordinadora y Salud como UR Coadyuvante del Componente 2

**Criterios de aceptacion:**
- [ ] `php artisan db:seed` ejecuta sin errores
- [ ] Cada usuario tiene rol y team asignado correctamente
- [ ] Login con cualquier usuario de prueba funciona
- [ ] 2FA puede configurarse para usuarios de prueba
- [ ] Escenario multi-UR: el operador de Salud puede ver el programa pero solo capturar en su componente

---

## Sprint 2: Cascada de Planes y Matriz de Alineacion

---

### S2-T1: Migraciones y modelos para catalogos ODS

**Ticket:** S2-T1 | **Tipo:** feat | **Rama:** `feat/S2-T1-catalogos-ods` | **Sprint:** 2 — Cascada de Planes | **Depende de:** S0-T2

---

## Contexto

Los Objetivos de Desarrollo Sostenible (ODS) de la Agenda 2030 son el nivel mas alto de la cascada de planes. Son **catalogos inmutables** (no editables por el usuario) que sirven como referencia para la Matriz de Alineacion. Se requieren dos tablas: `ods_objetivos` (17 objetivos) y `ods_metas` (169 metas). Ambas incluyen columnas `vector(1536)` para busqueda semantica con pgvector.

**Decisiones tecnicas:**
- Columnas `embedding` usan tipo `vector(1536)` de pgvector (requiere extension habilitada en S0-T2)
- Seeder carga datos reales desde archivo Markdown fuente en `docs/data/`
- Se usa `updateOrCreate()` en seeders para idempotencia
- Indice HNSW en columnas de embedding para performance en busquedas por similitud

---

## Pre-requisitos

- S0-T2 completado (extension pgvector habilitada en PostgreSQL)
- Extension `vector` activa verificable con `SELECT extname FROM pg_extension WHERE extname = 'vector'`

---

**Campos clave:**
- `ods_objetivos`: id, numero (1-17), nombre, descripcion, embedding (vector 1536)
- `ods_metas`: id, ods_objetivo_id (FK), clave ("1.1", "1.2"), descripcion, embedding

**Relaciones Eloquent:**
- `OdsObjetivo hasMany OdsMeta`
- `OdsMeta belongsTo OdsObjetivo`

**Criterios de aceptacion:**
- [ ] Migraciones con `up()` y `down()` completos — `down()` elimina tablas en orden inverso (metas antes que objetivos)
- [ ] Columnas `embedding` de tipo `vector(1536)` creadas correctamente — verificar con `\d ods_objetivos` en psql
- [ ] Modelos `App\Models\OdsObjetivo` y `App\Models\OdsMeta` con `$fillable`, `casts()` y relaciones definidas
- [ ] Seeder `OdsSeeder` carga los 17 ODS y sus 169 metas desde archivo Markdown fuente con `updateOrCreate()`
- [ ] `sail artisan migrate:fresh --seed` ejecuta sin errores
- [ ] `sail artisan migrate:rollback` revierte sin errores
- [ ] Tinker: `OdsObjetivo::count()` retorna 17, `OdsMeta::count()` retorna 169
- [ ] Archivo `docs/schema/ods.md` documenta estructura de tablas y relaciones

---

## Notas

- Los embeddings se generaran posteriormente via el pipeline de S2-T10; por ahora las columnas quedan en null
- El archivo fuente Markdown con los ODS debe colocarse en `docs/data/ods-agenda-2030.md`
- No crear indices HNSW en esta migracion — se crean en S2-T11 cuando el servicio de busqueda lo necesite

---

### S2-T2: Migraciones y modelos para catalogos PND

**Ticket:** S2-T2 | **Tipo:** feat | **Rama:** `feat/S2-T2-catalogos-pnd` | **Sprint:** 2 — Cascada de Planes | **Depende de:** S0-T2

---

## Contexto

El Plan Nacional de Desarrollo (PND) es el segundo nivel de la cascada de planes. Estructura jerarquica: Ejes → Objetivos → Estrategias. Catalogo inmutable cargado desde Markdown. Se replica el patron establecido en S2-T1 (columnas embedding, seeders idempotentes).

---

## Pre-requisitos

- S0-T2 completado (extension pgvector)
- Patron de migraciones con `vector(1536)` validado en S2-T1

---

**Tablas:**
- `pnd_ejes`: id, numero, nombre, descripcion, embedding (vector 1536)
- `pnd_objetivos`: id, pnd_eje_id (FK), clave, descripcion, embedding
- `pnd_estrategias`: id, pnd_objetivo_id (FK), clave, descripcion, embedding

**Relaciones Eloquent:**
- `PndEje hasMany PndObjetivo`
- `PndObjetivo hasMany PndEstrategia`
- `PndObjetivo belongsTo PndEje`
- `PndEstrategia belongsTo PndObjetivo`

**Criterios de aceptacion:**
- [ ] 3 migraciones con `up()` y `down()` completos — eliminacion en orden inverso de dependencia
- [ ] Columnas `embedding` de tipo `vector(1536)` en las 3 tablas
- [ ] 3 modelos (`App\Models\PndEje`, `PndObjetivo`, `PndEstrategia`) con `$fillable`, `casts()` y relaciones
- [ ] Seeder `PndSeeder` carga datos del PND vigente desde `docs/data/pnd.md` con `updateOrCreate()`
- [ ] `sail artisan migrate:fresh --seed` sin errores
- [ ] `sail artisan migrate:rollback` revierte las 3 tablas sin errores
- [ ] Tinker verifica conteos y relaciones: `PndEje::first()->objetivos->count()` > 0
- [ ] Archivo `docs/schema/pnd.md` documenta estructura

---

## Notas

- Mismo patron de seeder idempotente que S2-T1
- El archivo fuente `docs/data/pnd.md` debe contener el PND vigente con estructura parseada por headings

---

### S2-T3: Migraciones y modelos para PED

**Ticket:** S2-T3 | **Tipo:** feat | **Rama:** `feat/S2-T3-modelos-ped` | **Sprint:** 2 — Cascada de Planes | **Depende de:** S0-T2

---

## Contexto

El Plan Estatal de Desarrollo (PED) es la estructura central del sistema. Jerarquia de 6 niveles: Plan → Ejes → Temas → Objetivos Estrategicos → Estrategias → Lineas de Accion. A diferencia de ODS y PND (catalogos inmutables), el PED es **editable** por el planeador (CRUD en S2-T6). Solo un plan puede estar activo a la vez (constraint parcial unico en PostgreSQL).

**Decisiones tecnicas:**
- 6 migraciones independientes para facilitar rollback granular
- Constraint parcial `WHERE activo = true` en `ped_planes` para garantizar un solo plan activo (PostgreSQL nativo)
- Cada tabla incluye columna `embedding vector(1536)` para busqueda semantica futura
- Se usa `string` para claves/numeros (no integer) por flexibilidad en numeracion compuesta ("1.2.3")

---

## Pre-requisitos

- S0-T2 completado (extension pgvector)

---

**Tablas:**
- `ped_planes` (id, nombre, nivel_gobierno, periodo_inicio, periodo_fin, activo)
- `ped_ejes` (id, ped_plan_id, numero, nombre, descripcion, embedding)
- `ped_temas` (id, ped_eje_id, numero, nombre, descripcion, embedding)
- `ped_objetivos_estrategicos` (id, ped_tema_id, clave, descripcion, embedding)
- `ped_estrategias` (id, ped_objetivo_estrategico_id, clave, descripcion, embedding)
- `ped_lineas_accion` (id, ped_estrategia_id, clave, descripcion, embedding)

**Relaciones Eloquent (cascada HasMany):**
- Plan → Ejes → Temas → Objetivos → Estrategias → Lineas de Accion
- Cada modelo con `belongsTo` inverso

**Criterios de aceptacion:**
- [ ] 6 migraciones con `up()` y `down()` completos — eliminacion en orden inverso de dependencia
- [ ] Indice parcial unico en `ped_planes`: `CREATE UNIQUE INDEX ... ON ped_planes (activo) WHERE activo = true` — solo un plan activo
- [ ] 6 modelos con `$fillable`, `casts()`, relaciones `hasMany`/`belongsTo` completas
- [ ] Columnas `embedding vector(1536)` en tablas de ped_ejes a ped_lineas_accion
- [ ] FK con `cascadeOnDelete()` en cada nivel hijo
- [ ] Seeder `PedSeeder` con datos ficticios (1 plan, 3 ejes, 2 temas por eje, objetivos, estrategias, lineas)
- [ ] `sail artisan migrate:fresh --seed` sin errores
- [ ] Test: intentar activar 2 planes simultaneamente lanza `UniqueConstraintViolationException`
- [ ] Tinker: `PedPlan::where('activo', true)->first()->ejes->count()` retorna 3
- [ ] Archivo `docs/schema/ped.md` documenta las 6 tablas y relaciones

---

## Notas

- El constraint parcial unico se implementa con `DB::statement()` raw porque el Blueprint de Laravel no lo soporta nativamente
- Las claves/numeros usan `string` porque la numeracion compuesta ("1.2.3") no cabe en integer
- El seeder de datos ficticios sera reemplazado por datos reales via el importador Markdown (S2-T9)

---

### S2-T4: Migraciones y modelos para Programas Derivados

**Ticket:** S2-T4 | **Tipo:** feat | **Rama:** `feat/S2-T4-programas-derivados` | **Sprint:** 2 — Cascada de Planes | **Depende de:** S2-T3

---

## Contexto

Los programas derivados (sectoriales, especiales, institucionales, regionales) emanan del PED y representan instrumentos de planificacion intermedios. Cada programa derivado tiene objetivos propios que se vinculan con las lineas de accion del PED via la Matriz de Alineacion (S2-T5). El tipo de programa se almacena como ENUM nativo de PostgreSQL.

---

## Pre-requisitos

- S2-T3 completado (tabla `ped_planes` existente para FK)

---

**Tablas:**
- `programas_derivados`: id, ped_plan_id (FK), tipo (ENUM PostgreSQL: sectorial, especial, institucional, regional), nombre, descripcion, timestamps
- `programas_derivados_objetivos`: id, programa_derivado_id (FK), clave, descripcion, embedding (vector 1536), timestamps

**Relaciones Eloquent:**
- `ProgramaDerivado belongsTo PedPlan`
- `ProgramaDerivado hasMany ProgramaDerivadoObjetivo`
- `ProgramaDerivadoObjetivo belongsTo ProgramaDerivado`

**Criterios de aceptacion:**
- [ ] ENUM nativo PostgreSQL `tipo_programa_derivado` con 4 valores — verificar con `\dT tipo_programa_derivado` en psql
- [ ] Backed Enum PHP `App\Enums\TipoProgramaDerivado` con casos: SECTORIAL, ESPECIAL, INSTITUCIONAL, REGIONAL
- [ ] Columna `embedding vector(1536)` en `programas_derivados_objetivos`
- [ ] FK `ped_plan_id` con `cascadeOnDelete()` — si se elimina el PED, se eliminan programas derivados
- [ ] 2 modelos con `$fillable`, `casts()` (tipo casteado a Enum PHP) y relaciones
- [ ] Seeder con al menos 2 programas derivados de tipos distintos y 3 objetivos
- [ ] `sail artisan migrate:fresh --seed` sin errores
- [ ] `sail artisan migrate:rollback` revierte sin errores (incluye DROP TYPE del ENUM)
- [ ] Archivo `docs/schema/programas-derivados.md` documenta estructura

---

## Notas

- El `down()` debe incluir `DB::statement('DROP TYPE IF EXISTS tipo_programa_derivado')` ya que PostgreSQL no elimina el ENUM automaticamente al eliminar la tabla
- El cast de Enum se hace a nivel PHP (Backed Enum) para consistencia con el patron establecido en S1-T2

---

### S2-T5: Tablas pivote para Matriz de Alineacion

**Ticket:** S2-T5 | **Tipo:** feat | **Rama:** `feat/S2-T5-matriz-alineacion` | **Sprint:** 2 — Cascada de Planes | **Depende de:** S2-T1, S2-T2, S2-T3, S2-T4

---

## Contexto

La Matriz de Alineacion vincula los niveles de la cascada de planes entre si. Son 3 tablas pivote muchos-a-muchos que permiten trazar la cadena completa: Linea de Accion PED → Objetivo PED → PND → ODS. Estas relaciones son la base para la herencia automatica de alineacion en la MIR (Sprint 4).

**Decisiones tecnicas:**
- Constraints UNIQUE compuestos para evitar duplicidad en alineaciones
- Relaciones `belongsToMany` con `withTimestamps()` en los modelos existentes
- Tests que validan tanto la unicidad como la navegabilidad de la cadena completa via Eloquent

---

## Pre-requisitos

- S2-T1 completado (tablas ODS)
- S2-T2 completado (tablas PND)
- S2-T3 completado (tablas PED)
- S2-T4 completado (tablas Programas Derivados)

---

**Tablas pivote:**
- `alineacion_ped_pnd` (ped_objetivo_estrategico_id ↔ pnd_objetivo_id) — Unique compuesto
- `alineacion_pnd_ods` (pnd_objetivo_id ↔ ods_meta_id) — Unique compuesto
- `alineacion_linea_programa_derivado` (ped_linea_accion_id ↔ programa_derivado_objetivo_id) — Unique compuesto

**Relaciones `belongsToMany` a agregar en modelos existentes:**
- `PedObjetivoEstrategico` ↔ `PndObjetivo`
- `PndObjetivo` ↔ `OdsMeta`
- `PedLineaAccion` ↔ `ProgramaDerivadoObjetivo`

**Criterios de aceptacion:**
- [ ] 3 migraciones con indices unicos compuestos en la combinacion de FKs
- [ ] FK con `cascadeOnDelete()` en ambas columnas de cada pivote
- [ ] Relaciones `belongsToMany` con `withTimestamps()` configuradas en los 6 modelos involucrados
- [ ] Seeder `AlineacionSeeder` crea al menos 3 alineaciones de ejemplo entre niveles
- [ ] Test: intentar duplicar una alineacion lanza `UniqueConstraintViolationException`
- [ ] Test: desde una `PedLineaAccion`, se puede recorrer via Eloquent: `$linea->estrategia->objetivoEstrategico->pndObjetivos->first()->odsMetas` — cadena completa hasta ODS
- [ ] `sail artisan migrate:fresh --seed` sin errores
- [ ] Archivo `docs/schema/matriz-alineacion.md` documenta las 3 tablas pivote y ejemplos de cadena

---

## Notas

- Los nombres de tablas pivote usan prefijo `alineacion_` para agruparlas logicamente
- La navegacion de la cadena completa requiere eager loading (`with()`) para evitar N+1 en produccion
- La Matriz de Alineacion es central para S4-T8 (alineacion automatica MIR ↔ planes)

---

### S2-T6: CRUD de PED con interfaz Livewire

**Ticket:** S2-T6 | **Tipo:** feat | **Rama:** `feat/S2-T6-crud-ped-livewire` | **Sprint:** 2 — Cascada de Planes | **Depende de:** S2-T3, S1-T3

---

## Contexto

Interfaz de administracion para que el planeador capture y edite toda la jerarquia del PED (6 niveles). Se implementa como componente Livewire con vista de arbol colapsable. Es la primera interfaz CRUD del sistema y establece patrones de UI reutilizables para S2-T7 y S2-T8.

**Decisiones tecnicas:**
- Componente Livewire con Alpine.js para interacciones de arbol (collapse/expand)
- Formularios inline (no modales) para edicion rapida
- Proteccion via middleware `permission:gestionar_catalogos` (definido en S1-T3)
- Confirmacion antes de eliminar nodos padre con hijos dependientes
- Validacion de campos requeridos con Form Request de Laravel

---

## Pre-requisitos

- S2-T3 completado (modelos y migraciones PED)
- S1-T3 completado (permiso `gestionar_catalogos` registrado)

---

**Componentes Livewire:**
- `PedManager` — Componente principal con vista de arbol colapsable
- Formularios inline para crear/editar nodos en cada nivel
- Confirmacion de eliminacion con advertencia de hijos dependientes

**Criterios de aceptacion:**
- [ ] Ruta protegida con middleware `permission:gestionar_catalogos`
- [ ] Componente Livewire `App\Livewire\PedManager` renderiza arbol colapsable con todos los niveles
- [ ] CRUD completo en cada nivel: crear, editar, eliminar con formularios inline
- [ ] Al eliminar un nodo padre, modal de confirmacion muestra conteo de hijos dependientes
- [ ] Validacion: `nombre`/`descripcion` requeridos, longitud maxima 500 caracteres
- [ ] Al crear/editar, se actualiza el arbol sin recarga de pagina completa (Livewire reactivo)
- [ ] Responsive (Tailwind) — funcional en pantallas >= 768px
- [ ] Test Livewire: usuario sin permiso `gestionar_catalogos` recibe 403
- [ ] Test Livewire: crear un eje y verificar que aparece en el arbol

---

## Notas

- El arbol usa Alpine.js `x-show` para collapse/expand, no recarga Livewire por cada toggle (performance)
- Patron reutilizable: este componente establece la base para S2-T7 (CRUD Programas Derivados)
- Los embeddings de nodos editados se regeneran via el pipeline S2-T10 (Observer)

---

### S2-T7: CRUD de Programas Derivados con interfaz Livewire

**Ticket:** S2-T7 | **Tipo:** feat | **Rama:** `feat/S2-T7-crud-programas-derivados` | **Sprint:** 2 — Cascada de Planes | **Depende de:** S2-T4, S2-T6, S1-T3

---

## Contexto

Interfaz para gestionar los programas derivados (sectoriales, especiales, institucionales, regionales) y sus objetivos. Reutiliza patrones de UI establecidos en S2-T6. Los programas derivados se vinculan al PED activo y se filtran por tipo.

---

## Pre-requisitos

- S2-T4 completado (modelos y migraciones de Programas Derivados)
- S2-T6 completado (patrones de UI Livewire establecidos)
- S1-T3 completado (permiso `gestionar_catalogos`)

---

**Componentes Livewire:**
- `ProgramasDerivadosManager` — Listado filtrable por tipo con CRUD inline
- Formulario de creacion/edicion de programa derivado con selector de tipo (Enum)
- Sub-seccion para gestionar objetivos de cada programa

**Criterios de aceptacion:**
- [ ] Ruta protegida con middleware `permission:gestionar_catalogos`
- [ ] Componente Livewire `App\Livewire\ProgramasDerivadosManager` con listado y CRUD
- [ ] Filtro por tipo de programa (selector: sectorial, especial, institucional, regional, todos)
- [ ] CRUD completo de programas derivados: crear, editar, eliminar
- [ ] CRUD de objetivos por programa derivado (formularios inline anidados)
- [ ] Vinculacion automatica al PED activo (`ped_plan_id` del plan con `activo = true`)
- [ ] Validacion: nombre requerido, tipo requerido (validado contra Enum PHP)
- [ ] Test Livewire: crear programa derivado y verificar que aparece en listado filtrado
- [ ] Test: usuario sin permiso `gestionar_catalogos` recibe 403

---

## Notas

- Si no existe un PED activo, la interfaz muestra mensaje informativo y bloquea la creacion
- Al eliminar un programa derivado con objetivos, se confirma eliminacion en cascada

---

### S2-T8: Interfaz de Matriz de Alineacion

**Ticket:** S2-T8 | **Tipo:** feat | **Rama:** `feat/S2-T8-interfaz-matriz-alineacion` | **Sprint:** 2 — Cascada de Planes | **Depende de:** S2-T5, S2-T6, S1-T3

---

## Contexto

Interfaz para que el planeador configure la Matriz de Alineacion, creando vinculos muchos-a-muchos entre niveles de la cascada de planes. La interfaz debe mostrar la cadena resultante para que el usuario visualice la trazabilidad completa (PED → PND → ODS). Es la pieza clave para la alineacion automatica de MIR en Sprint 4.

**Decisiones tecnicas:**
- 3 secciones independientes para cada tipo de alineacion (PED↔PND, PND↔ODS, Linea↔Programa Derivado)
- Selectores dinamicos con busqueda (no drag-and-drop en v1 — complejidad innecesaria)
- Al vincular, la cadena completa se recalcula y muestra en tiempo real
- Eager loading obligatorio para evitar N+1 al renderizar cadenas

---

## Pre-requisitos

- S2-T5 completado (tablas pivote de alineacion)
- S2-T6 completado (PED disponible para seleccion)
- S1-T3 completado (permiso `gestionar_catalogos`)

---

**Criterios de aceptacion:**
- [ ] Ruta protegida con middleware `permission:gestionar_catalogos`
- [ ] Vista con 3 secciones: PED↔PND, PND↔ODS, Linea de Accion↔Programa Derivado
- [ ] Selectores dinamicos para agregar vinculos — selects con busqueda (Livewire)
- [ ] Boton de eliminar vinculo con confirmacion
- [ ] Al crear un vinculo PED↔PND, se muestra la cadena resultante completa hasta ODS (si existe vinculo PND↔ODS)
- [ ] Herencia automatica visible: al seleccionar una Linea de Accion, se muestran los ODS heredados via la cadena de relaciones
- [ ] Eager loading (`with()`) en queries para evitar N+1 — verificar con Debugbar o query log
- [ ] Test Livewire: crear vinculo PED↔PND y verificar que aparece en la lista
- [ ] Test: herencia automatica funciona — la cadena completa es navegable

---

## Notas

- En v1 se usan selectores con busqueda textual; drag-and-drop es mejora futura (Sprint 9+)
- La cadena de herencia se calcula con Eloquent lazy eager loading (`load()`) al momento de mostrar
- Esta interfaz es prerequisito para S4-T8 (alineacion automatica MIR → planes)

---

### S2-T9: Importador de PED desde Markdown

**Ticket:** S2-T9 | **Tipo:** feat | **Rama:** `feat/S2-T9-importador-markdown-ped` | **Sprint:** 2 — Cascada de Planes | **Depende de:** S2-T3, S1-T3

---

## Contexto

El PED estatal se publica como documento de texto estructurado. Este importador permite al planeador subir un archivo Markdown con la jerarquia completa del PED y convertirlo en registros de la base de datos, evitando captura manual de cientos de nodos. El flujo incluye previsualizacion y edicion antes de confirmar.

**Decisiones tecnicas:**
- Parser de Markdown basado en headings (`#` = Plan, `##` = Eje, `###` = Tema, etc.)
- Servicio `PedMarkdownParser` separado de la logica Livewire para testabilidad
- Transaccion de BD: todo o nada al confirmar importacion
- Los registros creados disparan el Observer de embeddings (S2-T10) automaticamente

---

## Pre-requisitos

- S2-T3 completado (modelos PED para persistencia)
- S1-T3 completado (permiso `gestionar_catalogos`)

---

**Formato Markdown esperado:**
```markdown
# Plan Estatal de Desarrollo 2022-2027

## Eje 1: Seguridad y Justicia
### Tema 1.1: Prevencion del delito
#### Objetivo 1.1.1: Reducir la incidencia delictiva juvenil
##### Estrategia 1.1.1.1: Programas de intervencion temprana
- Linea de Accion 1.1.1.1.1: Implementar talleres en zonas de riesgo
- Linea de Accion 1.1.1.1.2: Crear centros comunitarios
```

**Flujo del importador:**
1. Usuario sube archivo Markdown via formulario Livewire
2. `PedMarkdownParser` parsea el archivo y genera arbol en memoria
3. Vista de previsualizacion muestra arbol resultante (editable)
4. Usuario confirma → transaccion BD crea todos los registros en cascada
5. Redireccion a S2-T6 (CRUD PED) para revision

**Criterios de aceptacion:**
- [ ] Servicio `App\Services\PedMarkdownParser` parsea Markdown con la estructura definida
- [ ] Componente Livewire con upload de archivo `.md` (validacion de tipo MIME)
- [ ] Previsualizacion de arbol resultante antes de confirmar — editable inline
- [ ] Al confirmar, creacion en transaccion BD (`DB::transaction()`) — rollback si falla cualquier insert
- [ ] Manejo de errores de formato con mensajes claros: "Linea X: se esperaba heading nivel 3, se encontro nivel 5"
- [ ] Solo accesible con permiso `gestionar_catalogos`
- [ ] Test unitario: `PedMarkdownParser` parsea correctamente un Markdown de ejemplo y retorna array estructurado
- [ ] Test unitario: Markdown mal formado lanza excepcion con mensaje descriptivo
- [ ] Test integracion: importacion completa crea todos los registros esperados en BD

---

## Notas

- El parser es tolerante con espacios extra y lineas vacias entre secciones
- Si ya existe un PED activo, se desactiva el anterior al confirmar la importacion (solo un plan activo)
- El servicio `PedMarkdownParser` es independiente de Livewire para poder reutilizarse en comandos artisan

---

### S2-T10: Pipeline de generacion de embeddings

**Ticket:** S2-T10 | **Tipo:** feat | **Rama:** `feat/S2-T10-pipeline-embeddings` | **Sprint:** 2 — Cascada de Planes | **Depende de:** S2-T1, S2-T2, S2-T3, S0-T4

---

## Contexto

Los embeddings vectoriales son la base del motor de busqueda semantica del sistema. Este ticket implementa el pipeline completo: servicio que encapsula la llamada al API de embeddings, job asincrono que se despacha a la cola Redis, y observers en los modelos de planes que disparan la generacion automaticamente al crear/actualizar descripciones.

**Decisiones tecnicas:**
- Servicio `EmbeddingService` registrado en el Service Container como singleton
- Job `GenerateEmbedding` con `readonly` constructor properties (Laravel 11+ pattern)
- Observers en modelos de ODS, PND y PED que despachan el job al crear/actualizar campo `descripcion`
- Retry automatico con backoff exponencial si el API falla
- El registro se guarda sin embedding si el API falla — no bloquea al usuario

---

## Pre-requisitos

- S0-T4 completado (Redis como driver de colas configurado)
- S2-T1, S2-T2, S2-T3 completados (tablas con columnas `embedding`)
- API key del proveedor de embeddings configurada en `.env`

---

**Componentes a crear:**
- `App\Services\EmbeddingService` — Encapsula llamada al API del LLM, retorna array de floats
- `App\Jobs\GenerateEmbedding` — Job dispatched a cola Redis, recibe modelo y campo
- `App\Observers\EmbeddingObserver` — Observer registrado en modelos con columna `embedding`

**Criterios de aceptacion:**
- [ ] `EmbeddingService` registrado como singleton en `AppServiceProvider` con interfaz mockeable
- [ ] `EmbeddingService::generate(string $text): array` retorna array de 1536 floats
- [ ] `GenerateEmbedding` job usa cola `embeddings` (separada de `default`) para no saturar cola principal
- [ ] Job con `$tries = 3` y `$backoff = [10, 60, 300]` (backoff exponencial)
- [ ] Observer en modelos `OdsObjetivo`, `OdsMeta`, `PndEje`, `PndObjetivo`, `PndEstrategia`, y los 6 modelos PED
- [ ] Observer solo despacha job si el campo `descripcion` cambio (`$model->isDirty('descripcion')`)
- [ ] Al crear un registro, el embedding se genera automaticamente via job en cola
- [ ] Al actualizar la descripcion, el embedding se regenera
- [ ] Si el API falla despues de 3 intentos, el job se marca como `failed` y el registro queda con embedding null
- [ ] Test con mock del `EmbeddingService`: crear un `OdsObjetivo` y verificar que el job se despacha
- [ ] Test: actualizar `descripcion` despacha nuevo job; actualizar `nombre` no despacha job
- [ ] `.env.example` documentado con variables: `EMBEDDING_API_KEY`, `EMBEDDING_API_URL`, `EMBEDDING_MODEL`

---

## Notas

- Usar cola separada `embeddings` permite ajustar workers independientemente: `sail artisan queue:work --queue=embeddings`
- El `EmbeddingService` debe tener una interfaz (`EmbeddingServiceInterface`) para facilitar mocking en tests
- En ambiente `testing`, el Observer puede desactivarse con `Model::withoutEvents()` para tests que no necesitan embeddings
- El endpoint del API de embeddings es configurable para permitir cambio de proveedor sin tocar codigo

---

### S2-T11: Servicio de busqueda semantica por similitud

**Ticket:** S2-T11 | **Tipo:** feat | **Rama:** `feat/S2-T11-busqueda-semantica` | **Sprint:** 2 — Cascada de Planes | **Depende de:** S2-T10

---

## Contexto

Servicio que realiza busquedas por similitud de cosenos usando pgvector. Es el motor de sugerencias inteligentes del sistema: se usa en la Matriz de Alineacion para sugerir correspondencias, y en la MIR (Sprint 4) para sugerir alineacion con la cascada de planes. Utiliza el operador `<=>` de pgvector (distancia coseno) e indices HNSW para performance.

**Decisiones tecnicas:**
- Servicio `SemanticSearchService` con metodo generico `findSimilar()` que funciona contra cualquier tabla con columna `embedding`
- Indices HNSW (Hierarchical Navigable Small World) en todas las columnas de embedding — mejor performance que IVFFlat para datasets < 1M registros
- DTO `SimilarityResult` para tipar los resultados con score de similitud
- Umbral minimo de similitud configurable para filtrar resultados irrelevantes

---

## Pre-requisitos

- S2-T10 completado (pipeline de embeddings funcional para generar vectors)
- Registros con embeddings generados en al menos una tabla

---

**Componentes a crear:**
- `App\Services\SemanticSearchService` — Servicio de busqueda por similitud
- `App\DTOs\SimilarityResult` — DTO con modelo, score y distancia
- Migracion para crear indices HNSW en todas las tablas con columna `embedding`

**Metodo principal:**
```
findSimilar(string $texto, string $modelClass, int $limite = 5, float $umbralMinimo = 0.7): Collection<SimilarityResult>
```

**Flujo interno:**
1. Genera embedding del texto de entrada via `EmbeddingService`
2. Ejecuta query: `SELECT *, 1 - (embedding <=> $vector) as score FROM tabla WHERE 1 - (embedding <=> $vector) >= $umbral ORDER BY embedding <=> $vector LIMIT $limite`
3. Retorna coleccion de `SimilarityResult` ordenados por score descendente

**Criterios de aceptacion:**
- [ ] `SemanticSearchService` registrado en Service Container
- [ ] Metodo `findSimilar()` funciona contra todas las tablas con embeddings (ODS, PND, PED, Programas Derivados)
- [ ] Migracion crea indices HNSW en todas las columnas `embedding` — verificar con `\di` en psql
- [ ] DTO `SimilarityResult` con propiedades: `model`, `score` (float 0-1), `distance` (float)
- [ ] Umbral minimo de similitud configurable via parametro (default 0.7)
- [ ] Limite de resultados configurable via parametro (default 5)
- [ ] Test con mock: buscar "reducir pobreza" contra ODS retorna resultados ordenados por score
- [ ] Test: busqueda con umbral alto (0.99) retorna coleccion vacia
- [ ] Test: busqueda con limite 1 retorna exactamente 1 resultado
- [ ] Configuracion en `config/embedding.php`: `similarity_threshold`, `max_results`, `hnsw_ef_search`

---

## Notas

- Los indices HNSW tienen parametros `m` y `ef_construction` que afectan precision vs velocidad — usar defaults de pgvector para iniciar
- El operador `<=>` retorna distancia (0 = identico), no similitud — el score se calcula como `1 - distancia`
- En tests, se puede usar un mock de `EmbeddingService` que retorna vectores predefinidos para simular busquedas
- Este servicio se reutiliza en S4-T8 (alineacion automatica MIR) y S5-T5 (vinculacion de importados)

---

## Sprint 3: Metodologia de Marco Logico (Etapas 1-4)

---

### S3-T1: Migracion y modelo para Programas Presupuestarios

**Ticket:** S3-T1 | **Tipo:** feat | **Rama:** `feat/S3-T1-modelo-programa-presupuestario` | **Sprint:** 3 — Marco Logico | **Depende de:** S1-T1, S1-T2, S1-T5

---

## Contexto

La tabla `programas_presupuestarios` es la cabecera de todo el flujo de Marco Logico. Cada programa pertenece a una UR (Team), tiene un ejercicio fiscal, y progresa por estados (borrador → activo → cerrado). En S1-T5 se creo un modelo stub de `ProgramaPresupuestario` con campos minimos para el middleware; aqui se crea la **migracion definitiva** con todos los campos del dominio y se actualiza el modelo.

**Decisiones tecnicas:**
- Se reemplaza el stub de S1-T5 con la migracion completa — crear migracion `alter` para agregar columnas al stub
- Backed Enums PHP para `origen` (nuevo/importado) y `estado` (borrador/activo/cerrado)
- SoftDeletes para preservar historial de programas eliminados
- Scope `scopeForTeam($teamId)` para aislamiento por UR en queries
- Indice compuesto en `(team_id, ejercicio_fiscal)` para consultas frecuentes

---

## Pre-requisitos

- S1-T1 completado (tabla `teams` existente)
- S1-T2 completado (campos de UR en teams)
- S1-T5 completado (stub de `ProgramaPresupuestario` y tabla `programa_team`)

---

**Campos programas_presupuestarios:**
- id, team_id (FK → teams), clave_programa (string, unique), nombre, ejercicio_fiscal (integer), origen (enum: nuevo/importado), estado (enum: borrador/activo/cerrado), created_by (FK → users), timestamps, softDeletes

**Relaciones Eloquent:**
- `belongsTo Team` (UR propietaria)
- `belongsTo User` (creador, via `created_by`)
- `hasMany MirNivel` (niveles de la MIR — Sprint 4)
- `hasMany Arbol` (arboles de problema/objetivos)
- `belongsToMany Team` via `programa_team` (relacion multi-UR ya existente de S1-T5)

**Criterios de aceptacion:**
- [ ] Migracion `alter` agrega columnas al stub existente: `clave_programa`, `nombre`, `ejercicio_fiscal`, `origen`, `estado`, `created_by`, `softDeletes`
- [ ] Backed Enums: `App\Enums\OrigenPrograma` (NUEVO, IMPORTADO) y `App\Enums\EstadoPrograma` (BORRADOR, ACTIVO, CERRADO)
- [ ] Modelo actualizado con `$fillable`, `casts()` a Enums, SoftDeletes, y todas las relaciones
- [ ] Scope `scopeForTeam(int $teamId)` filtra por `team_id` — verificar con `ProgramaPresupuestario::forTeam(1)->get()`
- [ ] FK `team_id` con `cascadeOnDelete()`, FK `created_by` con `nullOnDelete()`
- [ ] Indice compuesto en `(team_id, ejercicio_fiscal)` — verificar con `\di` en psql
- [ ] Ruta de creacion protegida con middleware `permission:crear_programa`
- [ ] `sail artisan migrate:fresh --seed` sin errores
- [ ] Archivo `docs/schema/programas-presupuestarios.md` documenta estructura completa

---

## Notas

- El stub de S1-T5 creo la tabla con columnas minimas (`nombre`, `clave`). Esta migracion agrega las columnas restantes sin recrear la tabla
- La relacion `belongsToMany Team` via `programa_team` ya existe desde S1-T5 — no duplicar
- El `estado` determina la editabilidad: solo `borrador` permite modificaciones en la MIR

---

### S3-T2: Migraciones y modelos para Arboles (Problema/Objetivos)

**Ticket:** S3-T2 | **Tipo:** feat | **Rama:** `feat/S3-T2-modelos-arboles` | **Sprint:** 3 — Marco Logico | **Depende de:** S3-T1

---

## Contexto

Los arboles de problemas y objetivos son las Etapas 1-3 de la Metodologia de Marco Logico. Se representan como grafos jerarquicos con estructura Adjacency List (parent_id recursivo). Cada programa tiene dos arboles vinculados: el arbol de problemas (causas/efectos) y el arbol de objetivos (medios/fines), donde cada nodo del segundo referencia su nodo origen en el primero.

**Decisiones tecnicas:**
- Tabla `arboles` como contenedor (tipo: problema/objetivos) vinculado a programa
- Tabla `arbol_nodos` con Adjacency List: `parent_id` recursivo y `nodo_origen_id` para trazabilidad entre arboles
- Backed Enum PHP `TipoNodo` con 10 valores (5 para problemas, 5 para objetivos)
- Backed Enum PHP `TipoArbol` (problema, objetivos)
- Campo `orden` para mantener posicion visual de nodos hermanos

---

## Pre-requisitos

- S3-T1 completado (tabla `programas_presupuestarios` para FK)

---

**Tablas:**
- `arboles`: id, programa_presupuestario_id (FK), tipo (enum: problema/objetivos), timestamps
- `arbol_nodos`: id, arbol_id (FK), parent_id (FK recursiva, nullable), tipo_nodo (enum), descripcion (text), nodo_origen_id (FK nullable → arbol_nodos), orden (integer), timestamps

**Enum `TipoNodo`:** problema_central, causa_directa, causa_indirecta, efecto_directo, efecto_indirecto, objetivo_central, medio_directo, medio_indirecto, fin_directo, fin_indirecto

**Relaciones Eloquent:**
- `Arbol belongsTo ProgramaPresupuestario`
- `Arbol hasMany ArbolNodo`
- `ArbolNodo belongsTo Arbol`
- `ArbolNodo hasMany children` (self-referencing via `parent_id`)
- `ArbolNodo belongsTo parent` (self-referencing)
- `ArbolNodo belongsTo nodoOrigen` (via `nodo_origen_id` — vinculo problema→objetivo)

**Criterios de aceptacion:**
- [ ] Backed Enum `App\Enums\TipoNodo` con 10 casos y `App\Enums\TipoArbol` con 2 casos
- [ ] Migracion `arboles` con FK a `programas_presupuestarios` y `cascadeOnDelete()`
- [ ] Migracion `arbol_nodos` con FK recursiva `parent_id`, FK `nodo_origen_id`, indice en `parent_id`
- [ ] Relacion recursiva funcional: `ArbolNodo::first()->children` y `->parent` resuelven correctamente
- [ ] `nodo_origen_id` vincula nodos de arbol de objetivos con su nodo fuente en arbol de problemas
- [ ] Seeder crea un arbol de problemas de ejemplo con 1 problema central, 2 causas directas, 1 causa indirecta, 2 efectos
- [ ] `sail artisan migrate:fresh --seed` sin errores
- [ ] Tinker: `ArbolNodo::where('tipo_nodo', 'problema_central')->first()->children->count()` retorna > 0
- [ ] Archivo `docs/schema/arboles.md` documenta estructura y relaciones recursivas

---

## Notas

- La FK recursiva `parent_id` es nullable (el nodo raiz no tiene padre)
- `nodo_origen_id` es nullable: solo se llena en nodos del arbol de objetivos que fueron transformados desde el de problemas
- El campo `orden` permite reordenar nodos hermanos sin alterar el parent_id

---

### S3-T3: Migraciones y modelos para Alternativas

**Ticket:** S3-T3 | **Tipo:** feat | **Rama:** `feat/S3-T3-modelos-alternativas` | **Sprint:** 3 — Marco Logico | **Depende de:** S3-T2

---

## Contexto

La Etapa 4 del Marco Logico agrupa los medios del arbol de objetivos en estrategias candidatas (alternativas). El planeador selecciona la alternativa mas viable, que se convierte en la base para la Estructura Analitica del Programa (EAP) en Sprint 4. Las alternativas no seleccionadas quedan registradas con sus nodos como referencia historica.

---

## Pre-requisitos

- S3-T2 completado (tablas `arboles` y `arbol_nodos`)

---

**Tablas:**
- `alternativas`: id, programa_presupuestario_id (FK), nombre (string), descripcion (text, nullable), seleccionada (boolean, default: false), justificacion_seleccion (text, nullable), timestamps
- `alternativa_nodos` (pivote): id, alternativa_id (FK), arbol_nodo_id (FK), timestamps — Unique compuesto en (alternativa_id, arbol_nodo_id)

**Relaciones Eloquent:**
- `Alternativa belongsTo ProgramaPresupuestario`
- `Alternativa belongsToMany ArbolNodo` via `alternativa_nodos`
- `ArbolNodo belongsToMany Alternativa` via `alternativa_nodos`

**Criterios de aceptacion:**
- [ ] Migracion `alternativas` con FK a `programas_presupuestarios` y `cascadeOnDelete()`
- [ ] Migracion `alternativa_nodos` con unique compuesto en `(alternativa_id, arbol_nodo_id)`
- [ ] Campo `seleccionada` (boolean, default false) marca la alternativa elegida
- [ ] Campo `justificacion_seleccion` (text, nullable) documenta la decision — requerido solo cuando `seleccionada = true`
- [ ] Relaciones `belongsToMany` con `withTimestamps()` funcionales en ambos modelos
- [ ] Seeder crea 2 alternativas de ejemplo, una seleccionada con justificacion
- [ ] Test: intentar asignar el mismo nodo a la misma alternativa dos veces lanza excepcion de unicidad
- [ ] `sail artisan migrate:fresh --seed` sin errores

---

## Notas

- Solo una alternativa por programa deberia tener `seleccionada = true` — validar a nivel de aplicacion (no constraint BD) para flexibilidad
- Los nodos asignados a alternativas son exclusivamente del arbol de objetivos (medios directos e indirectos)
- La justificacion es obligatoria al seleccionar pero no al crear — validacion condicional en Form Request

---

### S3-T4: Interfaz Etapa 1 — Definicion del problema

**Ticket:** S3-T4 | **Tipo:** feat | **Rama:** `feat/S3-T4-etapa1-definicion-problema` | **Sprint:** 3 — Marco Logico | **Depende de:** S3-T2, S3-T8

---

## Contexto

Primera interfaz del flujo de Marco Logico. El planeador define el problema central del programa presupuestario. La IA asiste con sugerencias de redaccion (el problema debe ser una situacion no deseada, sin verbos de accion ni soluciones implicitas). El resultado se persiste como nodo `problema_central` en el arbol de problemas.

**Decisiones tecnicas:**
- Componente Livewire con textarea para captura del problema
- Boton de asistencia IA opcional — usa `LlmService::suggest()` (S3-T8) via job en cola
- Guardado automatico con debounce para no perder trabajo
- El estado se persiste en BD (no en sesion) para permitir retomar

---

## Pre-requisitos

- S3-T2 completado (modelo `Arbol` y `ArbolNodo` para persistir nodos)
- S3-T8 completado (servicio `LlmService` para asistencia IA)

---

**Flujo:**
1. Usuario escribe la situacion no deseada en un textarea
2. Boton "Validar con IA" envia el texto al `LlmService::suggest()` via job en cola
3. IA retorna sugerencia de redaccion (sin verbos, sin soluciones, claro y concreto)
4. Usuario acepta la sugerencia o conserva su texto original
5. Se guarda como nodo `problema_central` en el arbol de problemas

**Criterios de aceptacion:**
- [ ] Componente Livewire `App\Livewire\Etapa1DefinicionProblema` con textarea y contador de caracteres (max 1000)
- [ ] Boton "Validar con IA" despacha job asincrono — no bloquea la interfaz (loading state visible)
- [ ] Respuesta de IA se muestra como sugerencia editable en panel lateral o inferior
- [ ] Usuario puede aceptar sugerencia (reemplaza texto) o descartarla (mantiene original)
- [ ] Al confirmar, se crea `Arbol` tipo `problema` y nodo `problema_central` vinculado
- [ ] Guardado automatico cada 5 segundos si el texto cambio (debounce Livewire)
- [ ] Estado persistente: al salir y regresar, el texto se recupera de BD
- [ ] Solo accesible con permiso `editar_mir` y para programas en estado `borrador`
- [ ] Test Livewire: renderizar componente con programa existente muestra texto guardado
- [ ] Test: boton IA dispara job (verificar con `Queue::fake()`)

---

## Notas

- La asistencia IA es siempre opcional — el usuario puede completar todo el flujo sin usar IA
- El prompt para la IA debe incluir reglas de la SHCP para redaccion de problemas centrales
- Si el programa ya tiene un arbol de problemas con nodo central, se carga para edicion en lugar de crear uno nuevo

---

### S3-T5: Interfaz Etapa 2 — Arbol del problema

**Ticket:** S3-T5 | **Tipo:** feat | **Rama:** `feat/S3-T5-etapa2-arbol-problema` | **Sprint:** 3 — Marco Logico | **Depende de:** S3-T4, S3-T8

---

## Contexto

Segunda interfaz del flujo de Marco Logico. El planeador construye el arbol de problemas agregando causas (directas e indirectas) y efectos (directos e indirectos) alrededor del problema central definido en Etapa 1. La IA puede sugerir nodos adicionales y validar que los nodos propuestos no sean soluciones disfrazadas de problemas.

**Decisiones tecnicas:**
- Visualizacion de arbol con CSS Grid/Flexbox (problema central al centro, causas abajo, efectos arriba)
- CRUD de nodos via Livewire con formularios inline
- Reordenamiento de nodos hermanos con campo `orden`
- Sugerencias de IA via `LlmService::suggest()` con contexto del arbol existente

---

## Pre-requisitos

- S3-T4 completado (problema central definido, arbol de problemas creado)
- S3-T8 completado (servicio `LlmService`)

---

**Componentes:**
- Visualizacion de arbol jerarquico (problema central al centro, causas abajo, efectos arriba)
- Botones contextuales para agregar causa directa/indirecta y efecto directo/indirecto
- Formulario inline para describir cada nodo (textarea + guardar/cancelar)
- Boton "Sugerir con IA" que propone causas/efectos adicionales basados en el contexto

**Criterios de aceptacion:**
- [ ] Componente Livewire `App\Livewire\Etapa2ArbolProblema` renderiza arbol visual correctamente
- [ ] Layout: efectos arriba → problema central al centro → causas abajo
- [ ] CRUD de nodos: agregar, editar, eliminar con confirmacion, reordenar (campo `orden`)
- [ ] Causas indirectas se anidan visualmente bajo causas directas (relacion `parent_id`)
- [ ] Efectos indirectos se anidan bajo efectos directos
- [ ] Boton "Sugerir con IA" envia arbol existente como contexto y propone nodos adicionales
- [ ] IA valida que los nodos propuestos no sean soluciones disfrazadas — respuesta incluye advertencia si detecta verbo de accion
- [ ] Estado persistente entre sesiones — todos los nodos se persisten en BD inmediatamente
- [ ] Solo accesible con permiso `editar_mir` y para programas en estado `borrador`
- [ ] Test: agregar causa directa y verificar que aparece como hijo del problema central
- [ ] Test: eliminar nodo con hijos muestra advertencia y elimina en cascada

---

## Notas

- El tipo de nodo (`TipoNodo`) se asigna automaticamente segun el boton usado y la posicion en el arbol
- La IA recibe como contexto: problema central + todos los nodos existentes para sugerencias coherentes
- No se permite agregar nodos si no existe un problema central (redirigir a Etapa 1)

---

### S3-T6: Interfaz Etapa 3 — Arbol de objetivos

**Ticket:** S3-T6 | **Tipo:** feat | **Rama:** `feat/S3-T6-etapa3-arbol-objetivos` | **Sprint:** 3 — Marco Logico | **Depende de:** S3-T5, S3-T8

---

## Contexto

Tercera interfaz del flujo de Marco Logico. El arbol de objetivos se genera automaticamente transformando cada nodo negativo del arbol de problemas en su version positiva. La IA sugiere la redaccion positiva para cada transformacion. Los nodos se vinculan via `nodo_origen_id` para mantener trazabilidad completa.

**Decisiones tecnicas:**
- Generacion automatica batch: al entrar a Etapa 3, se crea el arbol de objetivos con todos los nodos
- Cada nodo muestra la transformacion: texto original (tachado) → texto sugerido (positivo)
- La IA usa `LlmService::transform()` para convertir redacciones negativas a positivas
- Vista lado a lado (split view) para comparar ambos arboles

---

## Pre-requisitos

- S3-T5 completado (arbol de problemas con al menos problema central y causas/efectos)
- S3-T8 completado (servicio `LlmService` con metodo `transform()`)

---

**Flujo:**
1. Al entrar, el sistema genera automaticamente el arbol de objetivos desde el de problemas
2. Cada nodo muestra: texto original (tachado) → texto sugerido (positivo)
3. Usuario revisa y aprueba/edita cada transformacion individualmente
4. Los nodos se vinculan via `nodo_origen_id`

**Mapeo de tipos:**
- problema_central → objetivo_central
- causa_directa → medio_directo
- causa_indirecta → medio_indirecto
- efecto_directo → fin_directo
- efecto_indirecto → fin_indirecto

**Criterios de aceptacion:**
- [ ] Componente Livewire `App\Livewire\Etapa3ArbolObjetivos` con vista split (problema | objetivo)
- [ ] Transformacion automatica genera todos los nodos del arbol de objetivos con `nodo_origen_id` vinculado
- [ ] Mapeo de `TipoNodo` correcto (problema_central → objetivo_central, causa_directa → medio_directo, etc.)
- [ ] IA genera redaccion positiva via `LlmService::transform()` — job asincrono por cada nodo
- [ ] Cada nodo muestra: texto original tachado + texto sugerido editable
- [ ] Usuario puede aprobar (acepta sugerencia), editar (modifica sugerencia) o rechazar (escribe su propia version)
- [ ] Al re-entrar, no se duplican nodos — si el arbol de objetivos ya existe, se carga para edicion
- [ ] Solo accesible con permiso `editar_mir` y para programas en estado `borrador`
- [ ] Test: generar arbol de objetivos y verificar que cada nodo tiene `nodo_origen_id` correcto
- [ ] Test: verificar que el conteo de nodos del arbol de objetivos coincide con el de problemas

---

## Notas

- Si el arbol de problemas se modifica despues de generar el de objetivos, se debe advertir al usuario sobre la desincronizacion
- Las transformaciones de IA se ejecutan en batch (un job por nodo) para no bloquear la interfaz
- Los nodos del arbol de objetivos son independientes despues de crearse — editarlos no modifica el arbol de problemas

---

### S3-T7: Interfaz Etapa 4 — Seleccion de alternativas

**Ticket:** S3-T7 | **Tipo:** feat | **Rama:** `feat/S3-T7-etapa4-alternativas` | **Sprint:** 3 — Marco Logico | **Depende de:** S3-T3, S3-T6, S3-T8

---

## Contexto

Cuarta y ultima interfaz de la Metodologia de Marco Logico. El planeador agrupa los medios del arbol de objetivos en estrategias candidatas (alternativas), la IA evalua la viabilidad de cada una, y el planeador selecciona la alternativa ganadora con justificacion. Solo los nodos de la alternativa seleccionada pasan a la Estructura Analitica del Programa (EAP) en Sprint 4.

**Decisiones tecnicas:**
- Los nodos a agrupar son exclusivamente medios (directos e indirectos) del arbol de objetivos
- Agrupacion via checkboxes (mas accesible que drag-and-drop)
- IA evalua viabilidad tecnica, institucional y presupuestal de cada agrupacion
- Las ramas no seleccionadas se visualizan como "podadas" (tachadas/grises) pero no se eliminan

---

## Pre-requisitos

- S3-T3 completado (modelos `Alternativa` y `alternativa_nodos`)
- S3-T6 completado (arbol de objetivos con medios disponibles)
- S3-T8 completado (servicio `LlmService`)

---

**Flujo:**
1. Se muestran los medios (directos e indirectos) del arbol de objetivos
2. Usuario agrupa medios en 2-3 alternativas con checkboxes
3. IA evalua viabilidad tecnica, institucional y presupuestal de cada agrupacion
4. Usuario selecciona la alternativa ganadora y documenta justificacion (obligatoria)
5. Las ramas no seleccionadas se marcan como "podadas" (visual, no eliminadas)

**Criterios de aceptacion:**
- [ ] Componente Livewire `App\Livewire\Etapa4Alternativas` lista medios del arbol de objetivos
- [ ] Solo muestra nodos de tipo `medio_directo` y `medio_indirecto` para agrupacion
- [ ] Crear/nombrar alternativas y asignarles nodos via checkboxes
- [ ] Un nodo puede pertenecer a multiples alternativas (la agrupacion no es exclusiva)
- [ ] Boton "Evaluar con IA" genera evaluacion de viabilidad por alternativa — resultado mostrado en panel
- [ ] Seleccion de alternativa final: `seleccionada = true` con campo `justificacion_seleccion` obligatorio
- [ ] Validacion: no se puede seleccionar una alternativa sin justificacion
- [ ] Visualizacion: ramas podadas (medios no incluidos en alternativa seleccionada) se muestran tachadas/grises
- [ ] Solo los nodos de la alternativa seleccionada estaran disponibles en Etapa 5 (S4-T1)
- [ ] Solo accesible con permiso `editar_mir` y para programas en estado `borrador`
- [ ] Test: seleccionar alternativa sin justificacion falla con error de validacion
- [ ] Test: verificar que solo los nodos de la alternativa seleccionada se retornan para EAP

---

## Notas

- Las alternativas no seleccionadas no se eliminan — quedan como registro historico del analisis
- La evaluacion de IA es informativa, no vinculante — el planeador decide con base en su criterio
- Al cambiar la alternativa seleccionada, se debe advertir si ya existe una EAP construida (S4-T1)

---

### S3-T8: Servicio centralizado de llamadas al LLM

**Ticket:** S3-T8 | **Tipo:** feat | **Rama:** `feat/S3-T8-servicio-llm` | **Sprint:** 3 — Marco Logico | **Depende de:** S0-T4

---

## Contexto

Servicio centralizado que encapsula todas las llamadas al modelo de lenguaje. Es la unica puerta de entrada al API del LLM en toda la aplicacion. Todos los tickets que usan IA (S3-T4 a S3-T7, Sprint 4 validaciones, Sprint 6 justificaciones) dependen de este servicio. En Sprint 8 se refactorizara con metodos de dominio especificos (S8-T1).

**Decisiones tecnicas:**
- Interfaz `LlmServiceInterface` + implementacion concreta para facilitar mocking en tests
- Registrado como singleton en el Service Container
- Todas las llamadas se ejecutan via jobs en cola Redis (nunca sincronas en request HTTP)
- Rate limiting por usuario/sesion configurable via `config/llm.php`
- Logging completo de cada interaccion: prompt, respuesta, tokens consumidos, duracion, usuario

---

## Pre-requisitos

- S0-T4 completado (Redis como driver de colas configurado)
- API key del proveedor LLM configurada en `.env`

---

**Metodos iniciales:**
- `suggest(string $prompt, array $context = []): string` — Sugerencia de texto libre
- `validate(string $text, array $rules): ValidationResult` — Validacion contra reglas semanticas
- `transform(string $text, string $instruction): string` — Transformacion de texto (ej: negativo → positivo)

**Componentes a crear:**
- `App\Contracts\LlmServiceInterface` — Interfaz con los 3 metodos
- `App\Services\LlmService` — Implementacion concreta
- `App\Jobs\LlmRequest` — Job generico para ejecutar llamadas en cola
- `App\DTOs\ValidationResult` — DTO con `passes: bool`, `message: string`, `suggestion: ?string`
- `config/llm.php` — Configuracion: api_key, api_url, model, rate_limit, timeout

**Criterios de aceptacion:**
- [ ] Interfaz `App\Contracts\LlmServiceInterface` definida con los 3 metodos
- [ ] `LlmService` registrado como singleton en `AppServiceProvider` vinculado a la interfaz
- [ ] Job `LlmRequest` se despacha a cola `llm` (separada de `default` y `embeddings`)
- [ ] Rate limiting: maximo N llamadas por usuario por minuto — configurable en `config/llm.php`
- [ ] Manejo de errores: timeout (30s default), API caido (retry con backoff), respuesta invalida (log + excepcion)
- [ ] Logging en tabla `llm_logs`: prompt (truncado a 500 chars), respuesta, tokens_input, tokens_output, duracion_ms, user_id, tipo_operacion
- [ ] Migracion para tabla `llm_logs` incluida
- [ ] `.env.example` con variables: `LLM_API_KEY`, `LLM_API_URL`, `LLM_MODEL`, `LLM_RATE_LIMIT`
- [ ] Test con mock: `suggest()` retorna string, `validate()` retorna `ValidationResult`, `transform()` retorna string
- [ ] Test: rate limiting bloquea la 6ta llamada en 1 minuto (configurable)
- [ ] Test: llamada fallida se registra en `llm_logs` con status `failed`

---

## Notas

- La cola `llm` debe tener workers dedicados: `sail artisan queue:work --queue=llm`
- En ambiente `testing`, se puede usar `LlmService::fake()` (similar a `Queue::fake()`) para evitar llamadas reales
- Los prompt templates se versionaran en S8-T1 como archivos dedicados — por ahora se hardcodean en el servicio
- El rate limiting se aplica por usuario autenticado, no por IP — evita bloquear a toda una UR

---

## Sprint 4: MIR y Validaciones

---

### S4-T1: Interfaz Etapa 5 — Estructura Analitica del Programa (EAP)

**Ticket:** S4-T1 | **Tipo:** feat | **Rama:** `feat/S4-T1-etapa5-eap` | **Sprint:** 4 — MIR y Validaciones | **Depende de:** S3-T7, S4-T2

---

## Contexto

La EAP es el puente entre el Marco Logico (arboles) y la MIR. Mapea automaticamente los nodos del arbol de objetivos (filtrados por la alternativa seleccionada) a la estructura de 4 niveles de la MIR: Fin, Proposito, Componentes, Actividades. El usuario puede ajustar el mapeo antes de confirmar.

**Decisiones tecnicas:**
- Mapeo automatico basado en `TipoNodo`: fines → Fin, objetivo_central → Proposito, medios_directos → Componentes, medios_indirectos → Actividades
- Solo nodos de la alternativa seleccionada (S3-T7) pasan al mapeo
- Al confirmar, se crean registros en `mir_niveles` (S4-T2) con `arbol_nodo_id` para trazabilidad
- Validacion basica de logica vertical antes de confirmar

---

## Pre-requisitos

- S3-T7 completado (alternativa seleccionada con nodos asignados)
- S4-T2 completado (tabla `mir_niveles` para persistir el mapeo)

---

**Criterios de aceptacion:**
- [ ] Componente Livewire `App\Livewire\Etapa5Eap` muestra estructura resultante del mapeo automatico
- [ ] Mapeo: fin_directo/fin_indirecto → Fin, objetivo_central → Proposito, medio_directo → Componente, medio_indirecto → Actividad
- [ ] Solo incluye nodos de la alternativa con `seleccionada = true`
- [ ] Vista previa: arbol visual de 4 niveles con el texto de cada nodo
- [ ] Usuario puede ajustar manualmente: reasignar nivel, eliminar nodo, reordenar
- [ ] Validacion: al menos 1 nodo por nivel (Fin, Proposito, Componente, Actividad) antes de confirmar
- [ ] Al confirmar, se crean registros en `mir_niveles` con `arbol_nodo_id`, `tipo_nivel`, `resumen_narrativo`
- [ ] Solo accesible con permiso `editar_mir` y para programas en estado `borrador`
- [ ] Test: mapeo automatico genera la cantidad correcta de niveles segun nodos de alternativa
- [ ] Test: confirmar EAP crea registros en `mir_niveles` con `arbol_nodo_id` no-null

---

## Notas

- Si no hay alternativa seleccionada, redirigir a Etapa 4 (S3-T7)
- El Resumen Narrativo se prellenada con la `descripcion` del nodo de arbol — el usuario lo editara en la MIR (S4-T4)
- La relacion `parent_id` en `mir_niveles` se establece: Actividades → Componente padre

---

### S4-T2: Migraciones y modelos para MIR

**Ticket:** S4-T2 | **Tipo:** feat | **Rama:** `feat/S4-T2-modelos-mir` | **Sprint:** 4 — MIR y Validaciones | **Depende de:** S3-T1, S1-T7

---

## Contexto

La MIR (Matriz de Indicadores para Resultados) es el artefacto central del sistema. La tabla `mir_niveles` almacena los 4 niveles (Fin, Proposito, Componente, Actividad) con relacion recursiva (Actividad → Componente). Incluye FKs de alineacion a la cascada de planes y trazabilidad al arbol de objetivos. La tabla `mir_versiones` guarda snapshots JSONB para versionado.

**Decisiones tecnicas:**
- Backed Enum `TipoNivel`: fin, proposito, componente, actividad
- Relacion recursiva `parent_id` para vincular actividades a componentes
- FK `arbol_nodo_id` nullable para trazabilidad al arbol de objetivos
- FKs de alineacion nullable: `ped_objetivo_estrategico_id`, `programa_derivado_objetivo_id`, `ped_linea_accion_id`
- FK `team_id` nullable para asignacion de UR Coadyuvante (ver S4-T12, ya definida en S1-T7)
- `mir_versiones` con campo JSONB `snapshot` para guardar estado completo serializado

---

## Pre-requisitos

- S3-T1 completado (tabla `programas_presupuestarios` para FK)
- S1-T7 completado (tabla `programa_team` y relacion multi-UR)

---

**Campos mir_niveles:**
- id, programa_presupuestario_id (FK), tipo_nivel (enum), parent_id (FK recursiva, nullable), resumen_narrativo (text), supuestos (text, nullable), arbol_nodo_id (FK nullable → arbol_nodos), team_id (FK nullable → teams), orden (integer), timestamps
- FKs de alineacion (todas nullable): ped_objetivo_estrategico_id, programa_derivado_objetivo_id, ped_linea_accion_id

**Campos mir_versiones:**
- id, programa_presupuestario_id (FK), etiqueta (string), snapshot (jsonb), created_by (FK → users), timestamps

**Criterios de aceptacion:**
- [ ] Backed Enum `App\Enums\TipoNivel` con casos: FIN, PROPOSITO, COMPONENTE, ACTIVIDAD
- [ ] Migracion `mir_niveles` con todas las FKs, `cascadeOnDelete()` en `programa_presupuestario_id`
- [ ] Relacion recursiva `parent_id` funcional: `MirNivel::find(x)->children` y `->parent`
- [ ] FK `arbol_nodo_id` nullable — trazabilidad al arbol de objetivos
- [ ] FK `team_id` nullable — asignacion de UR Coadyuvante (reutiliza la definida en S1-T7)
- [ ] Migracion `mir_versiones` con campo `snapshot` tipo JSONB
- [ ] Modelo `MirNivel` con `$fillable`, `casts()`, relaciones completas
- [ ] Modelo `MirVersion` con relacion a programa y usuario creador
- [ ] Indice en `(programa_presupuestario_id, tipo_nivel)` para queries frecuentes
- [ ] `sail artisan migrate:fresh --seed` sin errores
- [ ] Archivo `docs/schema/mir.md` documenta ambas tablas

---

## Notas

- La FK `team_id` en `mir_niveles` ya fue definida conceptualmente en S1-T7 — aqui se implementa la columna real
- El `parent_id` solo es relevante para actividades (que pertenecen a un componente) — componentes, proposito y fin no usan parent_id
- Los snapshots JSONB incluyen: niveles, indicadores, variables, medios de verificacion — todo serializado

---

### S4-T3: Migraciones y modelos para Indicadores

**Ticket:** S4-T3 | **Tipo:** feat | **Rama:** `feat/S4-T3-modelos-indicadores` | **Sprint:** 4 — MIR y Validaciones | **Depende de:** S4-T2

---

## Contexto

Los indicadores son la Columna 2 de la MIR. Cada nivel tiene uno o mas indicadores con ficha tecnica completa. Los campos `tipo`, `dimension` y `frecuencia` tienen restricciones por nivel que se validan via el motor poka-yoke (S4-T11). Este ticket crea las tablas y modelos; la validacion condicional se implementa en S4-T11.

**Decisiones tecnicas:**
- Backed Enums PHP para `tipo`, `dimension`, `frecuencia`, `sentido` — sin ENUM nativo PostgreSQL para facilitar cambios
- Tabla `indicador_variables` con campo `simbolo` (max 5 chars) para evaluacion matematica con MathExecutor
- Tabla `catalogo_unidades_medida` como catalogo inmutable con seeder CONAC
- Tabla `medios_verificacion` (Columna 3 de la MIR) vinculada a indicador
- Tabla `cremaa_validaciones` con 6 campos boolean (C, R, E, M, A, A) + observacion por letra

---

## Pre-requisitos

- S4-T2 completado (tabla `mir_niveles` para FK de indicadores)

---

**Tablas:**
- `indicadores`: id, mir_nivel_id (FK), nombre, formula_texto, tipo, dimension, frecuencia, sentido (ascendente/descendente), linea_base (decimal), anio_base (integer), meta (decimal), rango_verde_min/max, rango_amarillo_min/max, activo_seguimiento (boolean, default false), timestamps
- `indicador_variables`: id, indicador_id (FK), nombre, simbolo (string 5), unidad_medida_id (FK nullable), comportamiento (enum: acumulable/continua), timestamps
- `catalogo_unidades_medida`: id, clave (string), nombre, timestamps
- `medios_verificacion`: id, indicador_id (FK), nombre, descripcion, area_generadora, frecuencia_disponibilidad, timestamps
- `cremaa_validaciones`: id, indicador_id (FK), c_claro (bool), r_relevante (bool), e_economico (bool), m_monitoreable (bool), a_adecuado (bool), a_aportacion_marginal (bool), observacion_c/r/e/m/a1/a2 (text nullable), validado_at, timestamps

**Restricciones por nivel (implementadas en S4-T11):**

| Campo | Fin | Proposito | Componente | Actividad |
|-------|-----|-----------|------------|----------|
| Tipo | Estrategico (forzado) | Estrategico (forzado) | Editable | Gestion (forzado) |
| Dimension | Eficacia | Eficacia, Eficiencia | Eficacia, Eficiencia, Calidad | Eficacia, Eficiencia, Economia |
| Frecuencia | Anual, Bianual, Sexenal | Semestral, Anual | Trimestral, Semestral | Mensual, Trimestral |

**Criterios de aceptacion:**
- [ ] 5 migraciones con `up()` y `down()` completos
- [ ] Backed Enums: `TipoIndicador` (ESTRATEGICO, GESTION), `DimensionIndicador` (4 valores), `FrecuenciaIndicador` (6 valores), `SentidoIndicador` (ASCENDENTE, DESCENDENTE), `ComportamientoVariable` (ACUMULABLE, CONTINUA)
- [ ] Modelos con `$fillable`, `casts()` a Enums, relaciones completas
- [ ] `Indicador belongsTo MirNivel`, `hasMany IndicadorVariable`, `hasMany MedioVerificacion`, `hasOne CremaaValidacion`
- [ ] `IndicadorVariable belongsTo Indicador`, `belongsTo CatalogoUnidadMedida`
- [ ] Seeder `CatalogoUnidadesMedidaSeeder` con al menos 20 unidades CONAC comunes
- [ ] Campo `activo_seguimiento` default false — se activa cuando el indicador esta completo (Sprint 5)
- [ ] Rangos de semaforo: `rango_verde_min/max`, `rango_amarillo_min/max` — rojo se infiere como complemento
- [ ] `sail artisan migrate:fresh --seed` sin errores
- [ ] Archivo `docs/schema/indicadores.md` documenta las 5 tablas y restricciones por nivel

---

## Notas

- Las restricciones por nivel NO se implementan aqui — se implementan en S4-T11 (Form Request + UI dinamica)
- El campo `formula_texto` almacena la formula legible ("A / B x 100"); la evaluacion matematica usa `indicador_variables.simbolo`
- Los rangos de semaforo se definen como porcentajes: verde (80-100), amarillo (60-79.9), rojo (<60) — pero son configurables por indicador
- `activo_seguimiento = false` por default para no generar periodos de captura en indicadores incompletos

---

### S4-T4: Interfaz de captura MIR 4x4

**Ticket:** S4-T4 | **Tipo:** feat | **Rama:** `feat/S4-T4-interfaz-mir-4x4` | **Sprint:** 4 — MIR y Validaciones | **Depende de:** S4-T1, S4-T2, S4-T3, S4-T11, S4-T12

---

## Contexto

Interfaz principal del sistema: la MIR en formato de matriz 4 filas x 4 columnas. Columna 1 (Resumen Narrativo) viene prellenada desde la EAP. El usuario captura Columnas 2 (Indicadores), 3 (Medios de Verificacion) y 4 (Supuestos). La UI aplica las reglas condicionales del motor poka-yoke (S4-T11) e integra la asignacion de UR Coadyuvante (S4-T12).

**Decisiones tecnicas:**
- Componente Livewire con sub-componentes por celda para modularidad
- Guardado automatico con Livewire `wire:model.lazy` (debounce)
- Los dropdowns de tipo/dimension/frecuencia se filtran dinamicamente segun el nivel (S4-T11)
- Campo de asignacion de UR visible solo en filas de Componente y Actividad

---

## Pre-requisitos

- S4-T1 completado (EAP genera `mir_niveles` con Resumen Narrativo prellenado)
- S4-T2 completado (modelos MIR)
- S4-T3 completado (modelos de indicadores y medios de verificacion)
- S4-T11 completado (motor poka-yoke para restricciones de indicador por nivel)
- S4-T12 completado (asignacion de UR Coadyuvante)

---

**Criterios de aceptacion:**
- [ ] Componente Livewire `App\Livewire\MirEditor` renderiza matriz visual 4 filas x 4 columnas
- [ ] Col 1 (Resumen Narrativo): prellenada desde EAP, editable con textarea
- [ ] Col 2 (Indicadores): formulario de ficha tecnica integrado con restricciones de S4-T11 — dropdowns filtrados por nivel
- [ ] Col 3 (Medios de Verificacion): formulario con nombre, area generadora, frecuencia
- [ ] Col 4 (Supuestos): textarea libre
- [ ] Multiples componentes por nivel Componente (agregar/quitar filas hijas)
- [ ] Multiples indicadores por nivel (agregar/quitar dentro de cada fila)
- [ ] Campo de asignacion de UR Coadyuvante visible solo en filas de Componente y Actividad (S4-T12)
- [ ] Guardado automatico: cambios se persisten sin boton "Guardar" explicito (Livewire reactivo)
- [ ] Solo accesible con permiso `editar_mir` y para programas en estado `borrador`
- [ ] Test Livewire: renderizar MIR con datos existentes muestra todos los niveles e indicadores
- [ ] Test: agregar un indicador a Componente y verificar que se persiste en BD

---

## Notas

- El Resumen Narrativo es la Columna 1 pero se valida sintacticamente en S4-T5 (no aqui)
- El guardado automatico usa debounce de 2 segundos para no saturar el servidor
- La interfaz debe funcionar offline-first: si la conexion se pierde, los cambios se reenvian al reconectar (mejora futura)

---

### S4-T5: Validacion sintactica SHCP del Resumen Narrativo

**Ticket:** S4-T5 | **Tipo:** feat | **Rama:** `feat/S4-T5-validacion-sintaxis-shcp` | **Sprint:** 4 — MIR y Validaciones | **Depende de:** S4-T4, S3-T8

---

## Contexto

La SHCP define formulas sintacticas obligatorias para el Resumen Narrativo de cada nivel de la MIR. La IA audita el cumplimiento y sugiere reescrituras. Es una validacion semantica (no estructural) — el motor poka-yoke (S4-T11) se encarga de las restricciones estructurales. Esta validacion es **advertencia**, no bloqueo.

---

## Pre-requisitos

- S4-T4 completado (interfaz MIR con Resumen Narrativo capturado)
- S3-T8 completado (servicio `LlmService` con metodo `validate()`)

---

**Reglas sintacticas SHCP por nivel:**
- **Fin:** "Contribuir a [Impacto] mediante [Solucion]"
- **Proposito:** "[Poblacion] + [Verbo presente/participio] + [Condicion]"
- **Componente:** "[Bien/Servicio] + [Participio -ado/-ido]"
- **Actividad:** "[Sustantivo deverbal] + [Complemento]"

**Criterios de aceptacion:**
- [ ] Boton "Validar sintaxis" junto al Resumen Narrativo de cada nivel en la MIR
- [ ] La IA analiza via `LlmService::validate()` con reglas SHCP del nivel correspondiente
- [ ] Resultado visual: icono verde (cumple) o amarillo (no cumple) junto al campo
- [ ] Explicacion especifica del fallo mostrada en tooltip o panel lateral
- [ ] Sugerencia de reescritura que respeta la formula — usuario puede aceptar o ignorar
- [ ] No bloquea el guardado (es advertencia informativa, no error de validacion)
- [ ] Resultado se puede re-ejecutar tras editar el texto
- [ ] Test: enviar texto que NO cumple formula del Fin y verificar que la IA retorna `passes: false`
- [ ] Test con mock: verificar que el prompt enviado incluye la regla sintactica correcta segun nivel

---

## Notas

- La validacion se ejecuta on-demand (boton), no automaticamente al escribir — evita saturar el API de IA
- El prompt template debe ser especifico por nivel — no enviar las 4 reglas juntas
- Esta validacion complementa la del motor poka-yoke (S4-T11) que valida tipo/dimension/frecuencia

---

### S4-T6: Validacion CREMAA desglosada

**Ticket:** S4-T6 | **Tipo:** feat | **Rama:** `feat/S4-T6-validacion-cremaa` | **Sprint:** 4 — MIR y Validaciones | **Depende de:** S4-T3, S3-T8

---

## Contexto

CREMAA (Claro, Relevante, Economico, Monitoreable, Adecuado, Aportacion Marginal) son los 6 criterios de calidad que debe cumplir todo indicador segun la metodologia PbR-SED. La IA evalua cada criterio individualmente y guarda el resultado en `cremaa_validaciones`. Es una herramienta de mejora continua, no un bloqueo.

---

## Pre-requisitos

- S4-T3 completado (tabla `cremaa_validaciones` y modelo `CremaaValidacion`)
- S3-T8 completado (servicio `LlmService` con metodo `validate()`)

---

**Criterios CREMAA:**
- **C** (Claro): el indicador es preciso e inequivoco
- **R** (Relevante): refleja una dimension importante del objetivo
- **E** (Economico): la informacion para calcularlo es accesible sin costo excesivo
- **M** (Monitoreable): puede ser sometido a verificacion independiente
- **A** (Adecuado): proporciona base suficiente para evaluar el desempeno
- **A** (Aportacion Marginal): agrega informacion no proporcionada por otros indicadores

**Criterios de aceptacion:**
- [ ] Boton "Validar CREMAA" en la ficha del indicador dentro de la interfaz MIR
- [ ] IA evalua los 6 criterios individualmente via `LlmService::validate()` — un call con 6 evaluaciones
- [ ] Resultado visual: 6 letras C-R-E-M-A-A, cada una en verde (cumple) o rojo (no cumple)
- [ ] Al hacer clic en una letra roja, se muestra: explicacion del fallo + sugerencia de mejora
- [ ] Resultado se persiste en `cremaa_validaciones`: 6 booleans + 6 textos de observacion
- [ ] Se registra `validado_at` timestamp al ejecutar la validacion
- [ ] No bloquea guardado del indicador — es herramienta informativa
- [ ] Re-ejecutable: tras modificar el indicador, se puede re-validar (sobreescribe resultado anterior)
- [ ] Test con mock: verificar que los 6 criterios se evaluan y el resultado se persiste correctamente

---

## Notas

- El prompt de IA debe incluir: nombre del indicador, formula, variables, resumen narrativo del nivel, medios de verificacion — contexto completo
- La validacion CREMAA es independiente de las restricciones poka-yoke (tipo/dimension/frecuencia) de S4-T11
- En el futuro (Sprint 8), el prompt se versionara como archivo dedicado

---

### S4-T7: Validacion de logica vertical y horizontal

**Ticket:** S4-T7 | **Tipo:** feat | **Rama:** `feat/S4-T7-validacion-logica` | **Sprint:** 4 — MIR y Validaciones | **Depende de:** S4-T4, S3-T8

---

## Contexto

Validacion integral de la coherencia interna de la MIR. La **logica vertical** verifica que la cadena causal (Actividades → Componentes → Proposito → Fin) sea coherente. La **logica horizontal** verifica que cada fila este internamente consistente (indicador mide el objetivo, medio verifica el indicador). La IA analiza ambas dimensiones y genera un reporte clasificado.

---

## Pre-requisitos

- S4-T4 completado (MIR con datos en las 4 columnas)
- S3-T8 completado (servicio `LlmService`)

---

**Logica vertical (cadena causal):**
- Actividades → producen → Componentes (bienes/servicios)
- Componentes → logran → Proposito (resultado directo)
- Proposito → contribuye a → Fin (impacto de largo plazo)
- La IA analiza si la relacion causal entre niveles es coherente y suficiente

**Logica horizontal (consistencia por fila):**
- El indicador realmente mide lo descrito en el Resumen Narrativo
- El medio de verificacion puede proporcionar los datos necesarios para calcular el indicador
- La frecuencia del medio es compatible con la frecuencia del indicador

**Criterios de aceptacion:**
- [ ] Boton "Validar MIR completa" visible en la interfaz MIR
- [ ] IA analiza logica vertical (cadena causal entre los 4 niveles) + logica horizontal (consistencia por fila)
- [ ] Reporte de validacion con hallazgos organizados por nivel y tipo
- [ ] Cada hallazgo clasificado con severidad: error critico (rojo), advertencia (amarillo), sugerencia (verde)
- [ ] Hallazgos incluyen explicacion y sugerencia de correccion
- [ ] No bloquea guardado — se muestra como panel de resultados prominente
- [ ] Reporte persistible: se puede guardar el resultado de la validacion para referencia futura
- [ ] Test con mock: enviar MIR con incoherencia vertical (actividades no producen componentes) y verificar que la IA detecta la ruptura

---

## Notas

- La validacion completa puede tomar 10-30 segundos (depende de la complejidad de la MIR) — usar job asincrono con indicador de progreso
- Los hallazgos de logica vertical se reutilizan en la evaluacion de cierre (S7-T5)
- La IA nunca emite juicios de valor sobre las UR — solo analiza coherencia logica

---

### S4-T8: Alineacion automatica MIR ↔ Matriz de Alineacion

**Ticket:** S4-T8 | **Tipo:** feat | **Rama:** `feat/S4-T8-alineacion-mir` | **Sprint:** 4 — MIR y Validaciones | **Depende de:** S2-T5, S2-T11, S4-T2

---

## Contexto

Al capturar la MIR, el sistema sugiere la alineacion con la cascada de planes usando busqueda semantica (S2-T11) y la Matriz de Alineacion (S2-T5). La herencia automatica funciona en cascada: al seleccionar un Objetivo Estrategico del PED, se heredan las alineaciones PND y ODS configuradas en la Matriz.

---

## Pre-requisitos

- S2-T5 completado (tablas pivote de Matriz de Alineacion)
- S2-T11 completado (servicio de busqueda semantica)
- S4-T2 completado (FKs de alineacion en `mir_niveles`)

---

**Flujo:**
1. Al capturar/editar el Resumen Narrativo del Fin, el sistema busca por similitud semantica los Objetivos Estrategicos del PED mas cercanos
2. Al seleccionar uno, hereda automaticamente: PND (via `alineacion_ped_pnd`) y ODS (via `alineacion_pnd_ods`)
3. Al capturar Componentes/Actividades, sugiere Lineas de Accion del PED
4. Se llenan las FKs de alineacion en `mir_niveles`

**Criterios de aceptacion:**
- [ ] Al editar Resumen Narrativo del Fin, panel lateral muestra top-5 Objetivos Estrategicos del PED por similitud (score visible)
- [ ] Al seleccionar un Objetivo PED, se llenan automaticamente FKs: `ped_objetivo_estrategico_id`, y se muestran PND/ODS heredados
- [ ] Herencia automatica via Matriz de Alineacion: PED → PND → ODS se resuelve por relaciones `belongsToMany`
- [ ] Al capturar Componentes, se sugieren Lineas de Accion del PED por similitud
- [ ] Usuario puede aceptar sugerencia, rechazarla, o buscar manualmente con selector
- [ ] FKs de alineacion guardadas en `mir_niveles`: `ped_objetivo_estrategico_id`, `programa_derivado_objetivo_id`, `ped_linea_accion_id`
- [ ] Vista de cadena completa: "Linea de Accion → Estrategia → Obj. PED → PND → ODS" junto al nivel alineado
- [ ] Test: alinear un Fin con Obj. PED y verificar que PND/ODS se heredan correctamente
- [ ] Test: verificar que FKs se persisten en `mir_niveles`

---

## Notas

- La busqueda semantica requiere que los registros tengan embeddings generados (S2-T10) — si no existen, mostrar solo busqueda manual
- La herencia es informativa: si la Matriz de Alineacion no tiene vinculos configurados, no se heredan PND/ODS
- El score de similitud se muestra al usuario para transparencia (no caja negra)

---

### S4-T9: Extraccion de variables de formulas

**Ticket:** S4-T9 | **Tipo:** feat | **Rama:** `feat/S4-T9-extraccion-variables` | **Sprint:** 4 — MIR y Validaciones | **Depende de:** S4-T3, S3-T8

---

## Contexto

Las formulas de indicadores contienen variables con nombres descriptivos que deben extraerse, asignarseles un simbolo matematico (A, B, C...) y registrarse en `indicador_variables` para su evaluacion matematica con MathExecutor en Sprint 6. La IA extrae las variables automaticamente; el usuario las revisa, edita y clasifica.

---

## Pre-requisitos

- S4-T3 completado (tablas `indicadores` e `indicador_variables`)
- S3-T8 completado (servicio `LlmService` con metodo `suggest()`)

---

**Ejemplo:**
- Formula: `(Alumnos inscritos / Egresados secundaria) x 100`
- Variables extraidas: A = "Alumnos inscritos", B = "Egresados secundaria"
- Formula simbolica resultante: `(A / B) * 100`

**Criterios de aceptacion:**
- [ ] Boton "Extraer variables" junto al campo de formula en la ficha del indicador
- [ ] IA identifica variables via `LlmService::suggest()` y asigna simbolos secuenciales (A, B, C...)
- [ ] Resultado mostrado como tabla editable: simbolo | nombre descriptivo | unidad de medida | comportamiento
- [ ] Se crean registros en `indicador_variables` con `simbolo` (max 5 chars) y `nombre`
- [ ] Usuario puede editar nombres, simbolos y clasificar comportamiento (acumulable/continua)
- [ ] Selector de unidad de medida vinculado a `catalogo_unidades_medida` (seeder CONAC)
- [ ] Si la IA no puede extraer variables (formula ambigua), se muestra advertencia y se habilita captura manual
- [ ] Advertencia como hueco critico si el indicador no tiene variables — afecta `activo_seguimiento`
- [ ] Test con mock: formula "(A inscritos / B egresados) x 100" extrae 2 variables correctamente

---

## Notas

- El campo `simbolo` se usa en Sprint 6 para evaluar la formula con MathExecutor: `MathExecutor->evaluate("(A / B) * 100", ['A' => 500, 'B' => 200])`
- La clasificacion de comportamiento (acumulable/continua) afecta como se suman los avances en seguimiento
- Si la formula cambia despues de extraer variables, se debe re-ejecutar la extraccion

---

### S4-T10: Snapshots y versionado de MIR

**Ticket:** S4-T10 | **Tipo:** feat | **Rama:** `feat/S4-T10-snapshots-mir` | **Sprint:** 4 — MIR y Validaciones | **Depende de:** S4-T2

---

## Contexto

El versionado permite crear borradores alternos de la MIR sin destruir el trabajo actual. El planeador puede guardar el estado completo como snapshot JSONB en `mir_versiones`, explorar alternativas, y restaurar versiones anteriores si es necesario.

**Decisiones tecnicas:**
- El snapshot serializa: todos los `mir_niveles`, indicadores, variables, medios de verificacion del programa
- Restaurar un snapshot reemplaza los datos actuales dentro de una transaccion BD
- Cada snapshot tiene etiqueta descriptiva (definida por el usuario) y timestamp
- Advertencia al regresar a etapas anteriores (arbol) si ya existe una MIR construida

---

## Pre-requisitos

- S4-T2 completado (tabla `mir_versiones` con campo JSONB)

---

**Criterios de aceptacion:**
- [ ] Boton "Crear snapshot" en la interfaz MIR — solicita etiqueta descriptiva
- [ ] Snapshot guarda estado completo en `mir_versiones.snapshot` (JSONB): niveles, indicadores, variables, medios
- [ ] Listado de versiones con: etiqueta, fecha, usuario creador
- [ ] Boton "Restaurar" reemplaza la MIR actual con los datos del snapshot — con confirmacion modal
- [ ] Restauracion envuelta en `DB::transaction()` — rollback si falla cualquier paso
- [ ] Al regresar a Etapas 1-4 (arbol), advertencia: "Modificar el arbol puede invalidar la MIR actual"
- [ ] Opcion "Crear borrador alterno": guarda snapshot automatico antes de modificar el arbol
- [ ] Solo accesible con permiso `editar_mir`
- [ ] Test: crear snapshot, modificar MIR, restaurar snapshot y verificar que los datos originales vuelven
- [ ] Test: restaurar snapshot con datos invalidos (FK rota) realiza rollback completo

---

## Notas

- Los snapshots no se eliminan automaticamente — el planeador los gestiona manualmente
- El tamaño del JSONB puede crecer significativamente en programas complejos — monitorear en produccion
- No se versionan los arboles de problemas/objetivos — esos tienen su propio flujo de edicion

---

### S4-T11: Form Requests y UI dinamica condicional — Motor Poka-Yoke del Indicador

**Ticket:** S4-T11 | **Tipo:** feat | **Rama:** `feat/S4-T11-form-requests-condicional-indicador` | **Sprint:** 4 — MIR y Validaciones | **Depende de:** S4-T3

---

## Contexto

Motor de reglas condicionales que implementa las restricciones metodologicas de la Seccion 5.5 del plan. El formulario de la ficha tecnica del indicador muestra campos y opciones diferentes segun el nivel de la MIR. La validacion se aplica en doble capa: frontend (Livewire/Alpine.js filtra opciones) y backend (Laravel Form Request rechaza valores invalidos). **Esta validacion es responsabilidad exclusiva del sistema, no de la IA** — la IA se concentra en validaciones semanticas (S4-T5, S4-T6, S4-T7).

**Decisiones tecnicas:**
- Clase de configuracion `IndicadorNivelConfig` centraliza las reglas por nivel
- Alpine.js filtra dinamicamente los dropdowns segun el nivel (sin roundtrip al servidor)
- Form Request `StoreIndicadorRequest` valida en backend contra las mismas reglas
- Campo `tipo` se renderiza como label readonly en Fin/Proposito/Actividad, como select solo en Componente

---

## Pre-requisitos

- S4-T3 completado (Enums y modelos de indicadores)

---

**Reglas por nivel:**

| Campo | Fin | Proposito | Componente | Actividad |
|-------|-----|-----------|------------|----------|
| Tipo de indicador | Estrategico (bloqueado) | Estrategico (bloqueado) | Editable (Estrategico/Gestion) | Gestion (bloqueado) |
| Dimensiones disponibles | Solo Eficacia | Eficacia, Eficiencia | Eficacia, Eficiencia, Calidad | Eficacia, Eficiencia, Economia |
| Frecuencias disponibles | Anual, Bianual, Sexenal | Semestral, Anual | Trimestral, Semestral | Mensual, Trimestral |

**Criterios de aceptacion:**
- [ ] Clase `App\Rules\IndicadorNivelConfig` centraliza mapeo nivel → opciones permitidas para tipo/dimension/frecuencia
- [ ] Livewire: campo Tipo renderiza como `<span>` readonly en Fin/Proposito/Actividad, como `<select>` en Componente
- [ ] Alpine.js: dropdown Dimension filtra opciones segun propiedad `nivel` — sin llamada al servidor
- [ ] Alpine.js: dropdown Frecuencia muestra solo opciones validas segun nivel
- [ ] `App\Http\Requests\StoreIndicadorRequest`: valida `tipo` segun `nivel` con `Rule::in()` dinamico
- [ ] Form Request valida `dimension` contra set permitido para el `nivel` recibido
- [ ] Form Request valida `frecuencia` contra set permitido para el `nivel` recibido
- [ ] Test: guardar Economia en nivel Fin retorna 422 con mensaje descriptivo
- [ ] Test: guardar frecuencia Mensual en nivel Fin retorna 422
- [ ] Test: guardar Calidad en nivel Componente retorna 200 (exitoso)
- [ ] Test: guardar tipo Gestion en nivel Fin retorna 422
- [ ] UI no muestra mensajes de error para opciones que no aparecen — las opciones invalidas simplemente no existen en el dropdown

---

## Notas

- La clase `IndicadorNivelConfig` es la unica fuente de verdad para las restricciones — UI y backend la consultan
- Los prompts de IA (S8-T1) NO incluiran instrucciones para validar tipo/dimension/frecuencia — es responsabilidad de este motor
- Este patron poka-yoke previene errores en origen en lugar de detectarlos despues

---

### S4-T12: Asignacion de UR Coadyuvante por Componente/Actividad

**Ticket:** S4-T12 | **Tipo:** feat | **Rama:** `feat/S4-T12-asignacion-ur-coadyuvante` | **Sprint:** 4 — MIR y Validaciones | **Depende de:** S4-T2, S1-T5, S1-T7

---

## Contexto

En programas transversales, diferentes URs pueden ser responsables de diferentes Componentes/Actividades. Este ticket implementa la asignacion desde la interfaz MIR: el planeador selecciona una UR Coadyuvante para un Componente o Actividad especifica, y el sistema registra la relacion en `programa_team` (con rol `coadyuvante`) y en `mir_niveles.team_id`. Esto activa los permisos del middleware Multi-UR (S1-T5).

---

## Pre-requisitos

- S4-T2 completado (campo `team_id` en `mir_niveles`)
- S1-T5 completado (middleware `AislamientoMultiUR` lee `mir_niveles.team_id`)
- S1-T7 completado (tabla `programa_team` con rol `coordinadora`/`coadyuvante`)

---

**Criterios de aceptacion:**
- [ ] Selector de UR (dropdown de teams con `activa = true`) visible solo en filas de Componente y Actividad de la MIR
- [ ] El campo es opcional: si no se asigna, la responsabilidad recae en la UR Coordinadora del programa
- [ ] Al asignar una UR: se actualiza `mir_niveles.team_id` + se crea/actualiza `programa_team` con `rol = coadyuvante`
- [ ] Al quitar la asignacion: `mir_niveles.team_id = null` + verificar si la UR tiene otros niveles asignados; si no, eliminar de `programa_team`
- [ ] Solo accesible con permiso `editar_mir` (planeador)
- [ ] La UR Coadyuvante asignada aparece como etiqueta/badge visible junto al Componente en la vista MIR
- [ ] Notificacion (Laravel Notification) al operador de la UR Coadyuvante cuando se le asigna un componente
- [ ] El operador de la UR Coadyuvante puede capturar avance en sus niveles asignados (verificado por middleware S1-T5)
- [ ] Test: asignar UR Coadyuvante a Componente y verificar que `programa_team` se actualiza
- [ ] Test: quitar asignacion de UR sin otros niveles y verificar que se elimina de `programa_team`
- [ ] Test: operador de UR Coadyuvante puede acceder a su Componente pero recibe 403 en Componente de otra UR

---

## Notas

- La notificacion es opcional (configurable) — en produccion puede generar ruido si se reasignan URs frecuentemente
- La logica de "verificar si tiene otros niveles" previene eliminar prematuramente una UR que aun es responsable de otra Actividad
- Este ticket cierra el ciclo Multi-UR: S1-T5 (middleware), S1-T7 (tabla pivote), S4-T12 (asignacion desde MIR)

---

## Sprint 5: Importacion de Programas Existentes

---

### S5-T1: Parser de Markdown para MIR existentes

**Ticket:** S5-T1 | **Tipo:** feat | **Rama:** `feat/S5-T1-parser-markdown-mir` | **Sprint:** 5 — Importacion | **Depende de:** S4-T2, S4-T3

---

## Contexto

Las dependencias gubernamentales ya tienen MIRs construidas en ejercicios anteriores. Este importador permite incorporarlas al sistema desde archivos Markdown, evitando recaptura manual. El parser extrae los 4 niveles de la MIR con sus indicadores y fichas tecnicas. Reutiliza el patron de importacion establecido en S2-T9 (importador PED).

**Decisiones tecnicas:**
- Servicio `MirMarkdownParser` independiente de Livewire para testabilidad
- Formato de plantilla definido en documento de diseno con secciones por nivel y campos de ficha tecnica
- Transaccion BD al confirmar importacion
- Los indicadores importados se crean con `activo_seguimiento = false` hasta pasar diagnostico (S5-T3)

---

## Pre-requisitos

- S4-T2 completado (tabla `mir_niveles` para persistir niveles)
- S4-T3 completado (tablas de indicadores, variables, medios de verificacion)

---

**Criterios de aceptacion:**
- [ ] Servicio `App\Services\MirMarkdownParser` parsea Markdown con estructura de MIR
- [ ] Parsea los 4 niveles: Fin, Proposito, Componentes (multiples), Actividades (multiples por componente)
- [ ] Extrae indicadores con campos de ficha tecnica: nombre, formula, tipo, dimension, frecuencia, sentido, linea_base, meta
- [ ] Componente Livewire con upload de archivo `.md` y previsualizacion de arbol resultante
- [ ] Usuario puede editar/corregir antes de confirmar importacion
- [ ] Al confirmar, creacion en `DB::transaction()`: mir_niveles + indicadores + variables (si detectables)
- [ ] Programa importado se crea con `origen = importado` y `estado = borrador`
- [ ] Manejo de errores de formato con mensajes claros: "Seccion X: campo 'frecuencia' no reconocido"
- [ ] Test: parsear Markdown de ejemplo y verificar que se crean N mir_niveles y M indicadores correctos
- [ ] Test: Markdown mal formado lanza excepcion con mensaje descriptivo

---

## Notas

- El formato de plantilla Markdown debe documentarse en `docs/templates/plantilla-mir.md`
- Los campos que no se puedan parsear se dejan en null — el diagnostico (S5-T3) los detectara como huecos
- La extraccion de variables de formula (S4-T9) se puede ejecutar post-importacion

---

### S5-T2: Parser de CSV/Excel para MIR existentes

**Ticket:** S5-T2 | **Tipo:** feat | **Rama:** `feat/S5-T2-parser-csv-excel-mir` | **Sprint:** 5 — Importacion | **Depende de:** S4-T2, S4-T3

---

## Contexto

Importador alternativo para MIRs en formato tabular (CSV o Excel). Muchas dependencias manejan sus MIRs en hojas de calculo. El usuario define el mapeo de columnas del archivo a campos del sistema antes de importar.

**Decisiones tecnicas:**
- Paquete `maatwebsite/laravel-excel` para lectura de archivos .xlsx y .csv
- Paso intermedio de mapeo de columnas: el usuario asocia columnas del archivo con campos del sistema
- Misma logica de persistencia que S5-T1 (reutilizar servicio de creacion de registros)

---

## Pre-requisitos

- S4-T2, S4-T3 completados (tablas destino)
- Paquete `maatwebsite/laravel-excel` instalado

---

**Criterios de aceptacion:**
- [ ] Acepta archivos CSV (UTF-8) y Excel (.xlsx) via upload Livewire
- [ ] Paso 1: upload del archivo — validacion de formato y tamaño (max 5MB)
- [ ] Paso 2: mapeo de columnas — UI muestra columnas del archivo y selectores con campos del sistema
- [ ] Paso 3: previsualizacion del resultado del mapeo (primeras 10 filas)
- [ ] Paso 4: confirmacion — creacion de registros en `DB::transaction()`
- [ ] Misma logica de creacion de `mir_niveles` e `indicadores` que S5-T1
- [ ] Manejo de filas vacias, celdas con formato inesperado, y encoding
- [ ] Test: importar CSV de ejemplo con 4 niveles y 6 indicadores — verificar conteos en BD
- [ ] Test: archivo con columna faltante muestra error claro en paso de mapeo

---

## Notas

- El mapeo de columnas se puede guardar como preset para reutilizar en futuras importaciones
- Las celdas vacias se tratan como null — el diagnostico (S5-T3) las detectara
- Excel con multiples hojas: se importa solo la primera hoja (o la seleccionada por el usuario)

---

### S5-T3: Diagnostico de completitud

**Ticket:** S5-T3 | **Tipo:** feat | **Rama:** `feat/S5-T3-diagnostico-completitud` | **Sprint:** 5 — Importacion | **Depende de:** S5-T1

---

## Contexto

Tras importar un programa, es necesario evaluar que tan completo esta. El diagnostico clasifica los huecos en **criticos** (bloquean el seguimiento del indicador) y **menores** (no bloquean pero reducen calidad). Los indicadores con huecos criticos se marcan como `activo_seguimiento = false` hasta completarse.

---

## Pre-requisitos

- S5-T1 o S5-T2 completado (programa importado en BD)

---

**Huecos criticos (bloquean seguimiento):**
- Variables de formula no identificadas (no se puede calcular el avance)
- Frecuencia de medicion ausente (no se pueden generar periodos)
- Linea base y anio base faltantes (no se puede contextualizar el avance)

**Huecos menores (no bloquean):**
- Sintaxis del resumen narrativo no cumple SHCP
- Supuestos faltantes (Col 4 de MIR)
- Medios de verificacion incompletos

**Criterios de aceptacion:**
- [ ] Servicio `App\Services\CompletenessChecker` analiza un programa y retorna lista de huecos clasificados
- [ ] Reporte visual: tabla con columnas — Elemento | Campo | Estado (OK/Faltante/Critico) | Accion sugerida
- [ ] Indicadores con al menos 1 hueco critico: `activo_seguimiento = false` (automatico)
- [ ] Indicadores sin huecos criticos: `activo_seguimiento = true` (automatico)
- [ ] Enlace directo desde cada hueco al formulario de captura del campo faltante
- [ ] Resumen: "X de Y indicadores listos para seguimiento" con barra de progreso
- [ ] El diagnostico se puede re-ejecutar tras completar huecos
- [ ] Test: programa con indicador sin variables → indicador marcado `activo_seguimiento = false`
- [ ] Test: programa con indicador completo → indicador marcado `activo_seguimiento = true`

---

## Notas

- El diagnostico se ejecuta automaticamente post-importacion pero tambien es accesible como boton manual
- `activo_seguimiento` es el flag que determina si se generan periodos de captura en Sprint 6
- Los huecos menores se muestran como advertencias — no afectan `activo_seguimiento`

---

### S5-T4: Flujo asistido para completar huecos

**Ticket:** S5-T4 | **Tipo:** feat | **Rama:** `feat/S5-T4-completar-huecos` | **Sprint:** 5 — Importacion | **Depende de:** S5-T3, S3-T8

---

## Contexto

Interfaz tipo wizard que guia al usuario por los huecos pendientes de un programa importado. Ordenados por prioridad (criticos primero), cada hueco presenta un formulario de captura con sugerencia de IA basada en el contexto ya importado. Al completar todos los huecos criticos de un indicador, se activa automaticamente para seguimiento.

---

## Pre-requisitos

- S5-T3 completado (diagnostico identifica los huecos)
- S3-T8 completado (servicio `LlmService` para sugerencias)

---

**Criterios de aceptacion:**
- [ ] Componente Livewire `App\Livewire\CompletarHuecos` con lista de huecos ordenados por prioridad
- [ ] Huecos criticos primero (badge rojo), luego menores (badge amarillo)
- [ ] Al seleccionar un hueco, formulario de captura contextual con el campo especifico
- [ ] Boton "Sugerir con IA" genera sugerencia basada en datos importados del mismo indicador/nivel
- [ ] Al completar todos los huecos criticos de un indicador, se cambia `activo_seguimiento = true` automaticamente
- [ ] Progreso visible: barra de completitud con porcentaje y conteo de huecos restantes
- [ ] Navegacion: "Siguiente hueco" / "Anterior" para flujo secuencial
- [ ] Test: completar hueco critico y verificar que el conteo se actualiza
- [ ] Test: completar todos los huecos criticos de un indicador y verificar `activo_seguimiento = true`

---

## Notas

- La IA sugiere basandose exclusivamente en datos del mismo programa — no inventa contexto externo
- El flujo es opcional — el usuario puede completar huecos directamente desde la interfaz MIR (S4-T4)
- Este flujo es especialmente util para programas importados con muchos campos vacios

---

### S5-T5: Vinculacion de programas importados con cascada de planes

**Ticket:** S5-T5 | **Tipo:** feat | **Rama:** `feat/S5-T5-vinculacion-importados-planes` | **Sprint:** 5 — Importacion | **Depende de:** S2-T11, S4-T8

---

## Contexto

Los programas importados pueden llegar sin alineacion con la cascada de planes, o con alineacion que no coincide con la Matriz configurada en el sistema. Este ticket detecta el estado de alineacion y ofrece dos flujos: sugerencia (si no tiene) o validacion (si ya tiene).

---

## Pre-requisitos

- S2-T11 completado (servicio de busqueda semantica para sugerencias)
- S4-T8 completado (logica de alineacion MIR ↔ planes)

---

**Criterios de aceptacion:**
- [ ] Deteccion automatica: programa sin FKs de alineacion en `mir_niveles` → flujo de sugerencia
- [ ] Deteccion automatica: programa con FKs de alineacion → flujo de validacion contra Matriz
- [ ] Flujo sugerencia: usa `SemanticSearchService` para sugerir Objetivos PED por similitud con Resumen Narrativo del Fin
- [ ] Flujo validacion: verifica que las FKs de alineacion correspondan a vinculos existentes en la Matriz de Alineacion
- [ ] Advertencias claras si la alineacion existente contradice la Matriz (ej: Obj. PED no tiene vinculo PND configurado)
- [ ] El usuario puede aceptar sugerencias, modificarlas, o ignorar advertencias
- [ ] Test: programa sin alineacion muestra sugerencias con score de similitud
- [ ] Test: programa con alineacion invalida muestra advertencia especifica

---

## Notas

- La validacion contra Matriz es informativa — no bloquea la importacion
- Los programas importados de ejercicios anteriores pueden tener alineaciones a PED/PND desactualizados
- Este ticket reutiliza la logica de S4-T8 pero aplicada post-importacion en lugar de durante la captura

---

### S5-T6: Calendarizacion de metas al activar programa

**Ticket:** S5-T6 | **Tipo:** feat | **Rama:** `feat/S5-T6-calendarizacion-metas` | **Sprint:** 5 — Importacion | **Depende de:** S4-T3, S6-T1

---

## Contexto

Al cambiar el estado de un programa de `borrador` a `activo`, el sistema genera automaticamente los registros de `metas_periodo` para cada indicador con `activo_seguimiento = true`. La meta anual se distribuye en periodos segun la frecuencia del indicador. El usuario puede ajustar la distribucion antes de confirmar.

**Decisiones tecnicas:**
- Los periodos se generan en la tabla `metas_periodo` (definida en S6-T1)
- La distribucion default es uniforme (meta_anual / num_periodos)
- Para variables acumulables: la suma de metas por periodo debe igualar la meta anual
- Las etiquetas de periodo son legibles: "Q1 2026", "Ene 2026", "S1 2026"

---

## Pre-requisitos

- S4-T3 completado (indicadores con `frecuencia` y `meta` definidas)
- S6-T1 completado (tabla `metas_periodo` para persistir periodos)

---

**Criterios de aceptacion:**
- [ ] Al activar programa (`estado = borrador → activo`), se generan `metas_periodo` para cada indicador activo
- [ ] Generacion automatica de periodos con fechas de inicio/fin segun frecuencia: mensual (12), trimestral (4), semestral (2), anual (1)
- [ ] Distribucion default uniforme: `meta_periodo = meta_anual / num_periodos`
- [ ] Interfaz de ajuste: tabla editable donde el usuario modifica la distribucion por periodo
- [ ] Validacion: la suma de metas por periodo = meta anual (para variables acumulables, no para continuas)
- [ ] Etiquetas legibles: "Q1 2026", "Ene 2026", "S1 2026", "Anual 2026"
- [ ] Solo indicadores con `activo_seguimiento = true` generan periodos
- [ ] Test: activar programa con indicador trimestral genera 4 periodos con meta uniforme
- [ ] Test: ajustar distribucion y verificar que la suma sigue igualando la meta anual

---

## Notas

- La calendarizacion se ejecuta una sola vez al activar — no se regeneran periodos automaticamente
- Si se agrega un indicador despues de activar, se debe calendarizar manualmente
- Las variables continuas (ej: tasa de desempleo) no requieren que la suma de periodos iguale la meta anual

---

## Sprint 6: Seguimiento y Captura Periodica

---

### S6-T1: Migraciones para tablas de seguimiento

**Ticket:** S6-T1 | **Tipo:** feat | **Rama:** `feat/S6-T1-migraciones-seguimiento` | **Sprint:** 6 — Seguimiento | **Depende de:** S4-T3, S5-T6

---

## Contexto

Las tablas de seguimiento son la infraestructura para la captura periodica de avances. `metas_periodo` almacena las metas distribuidas por periodo (generadas en S5-T6). `avances` registra cada captura con su semaforo, justificacion y estado. `avance_variables` almacena los valores capturados por variable. `avance_evidencias` registra archivos adjuntos con hash SHA-256 de integridad. `desbloqueos` permite el flujo excepcional de edicion post-aprobacion.

**Decisiones tecnicas:**
- `avances.historial_observaciones` como JSONB para almacenar timeline de observaciones del revisor
- `avance_evidencias.hash_archivo` generado con SHA-256 al subir para verificacion de integridad
- `desbloqueos` con relacion polimorfica (`desbloqueableType`/`desbloqueableId`) para aplicar a avances o evidencias
- Backed Enums para `estado_avance`: EN_CAPTURA, EN_REVISION, OBSERVADO, APROBADO
- Indices en `(indicador_id, meta_periodo_id)` y `(estado)` para queries frecuentes

---

## Pre-requisitos

- S4-T3 completado (tabla `indicadores` e `indicador_variables`)
- S5-T6 completado (tabla `metas_periodo` generada al activar programa)

---

**Tablas:**
- `metas_periodo`: id, indicador_id (FK), periodo_inicio (date), periodo_fin (date), etiqueta (string), meta_periodo (decimal), timestamps
- `avances`: id, indicador_id (FK), meta_periodo_id (FK), valor_calculado (decimal nullable), semaforo (enum), semaforo_justificado (enum nullable), justificacion_ia (text nullable), justificacion_final (text nullable), estado (enum), historial_observaciones (jsonb), congelado_at (timestamp nullable), created_by (FK), timestamps
- `avance_variables`: id, avance_id (FK), indicador_variable_id (FK), valor (decimal), timestamps
- `avance_evidencias`: id, avance_id (FK), nombre_archivo (string), ruta_almacenamiento (string), area_generadora (string nullable), fecha_documento (date nullable), hash_archivo (string 64), mime_type (string), tamano_bytes (integer), timestamps
- `desbloqueos`: id, desbloqueable_type (string), desbloqueable_id (bigint), motivo (text), solicitante_id (FK → users), aprobador_id (FK nullable → users), estado (enum: pendiente/aprobado/rechazado), resuelto_at (timestamp nullable), timestamps

**Criterios de aceptacion:**
- [ ] 5 migraciones con `up()` y `down()` completos — eliminacion en orden inverso de dependencia
- [ ] Backed Enums: `EstadoAvance` (EN_CAPTURA, EN_REVISION, OBSERVADO, APROBADO), `SemaforoColor` (VERDE, AMARILLO, ROJO, SIN_DATO), `EstadoDesbloqueo` (PENDIENTE, APROBADO, RECHAZADO)
- [ ] `avances.historial_observaciones` como JSONB — estructura: `[{fecha, usuario, comentario, accion}]`
- [ ] `avance_evidencias.hash_archivo` string(64) para SHA-256
- [ ] `desbloqueos` con campos polimorficos `desbloqueable_type` y `desbloqueable_id`
- [ ] Indices en `avances(indicador_id, meta_periodo_id)`, `avances(estado)`, `avance_evidencias(avance_id)`
- [ ] Modelos con `$fillable`, `casts()` a Enums, relaciones completas
- [ ] `sail artisan migrate:fresh --seed` sin errores
- [ ] Archivo `docs/schema/seguimiento.md` documenta las 5 tablas

---

## Notas

- `congelado_at` se registra cuando el avance pasa a estado APROBADO — indica inmutabilidad
- El historial JSONB permite agregar entradas sin alterar las anteriores — append-only
- Los archivos de evidencia se almacenan en disco `private` (no `public`) — acceso via controller autenticado

---

### S6-T2: Calendario de captura y notificaciones

**Ticket:** S6-T2 | **Tipo:** feat | **Rama:** `feat/S6-T2-calendario-notificaciones` | **Sprint:** 6 — Seguimiento | **Depende de:** S6-T1, S5-T6

---

## Contexto

El sistema detecta automaticamente cuando un periodo de captura se abre (fecha actual >= periodo_inicio) y notifica a los operadores de la UR responsable. El planeador ve indicadores con captura vencida. La deteccion se ejecuta via comando artisan programado en el scheduler de Laravel.

---

## Pre-requisitos

- S6-T1 completado (tablas de seguimiento)
- S5-T6 completado (periodos generados en `metas_periodo`)

---

**Criterios de aceptacion:**
- [ ] Comando artisan `app:detectar-periodos-abiertos` schedulable diariamente
- [ ] Detecta `metas_periodo` donde `periodo_inicio <= today` y no existe avance creado
- [ ] Notificacion Laravel (database + email opcional) a operadores de la UR responsable del indicador
- [ ] La UR responsable se determina por `mir_niveles.team_id` (coadyuvante) o el `team_id` del programa (coordinadora)
- [ ] Vista operador `App\Livewire\MisIndicadoresPendientes`: lista de indicadores con periodos abiertos sin captura
- [ ] Vista planeador `App\Livewire\IndicadoresVencidos`: indicadores donde `periodo_fin < today` y avance no existe o no esta aprobado
- [ ] Comando registrado en `routes/console.php` con `Schedule::command()->daily()`
- [ ] Test: crear meta_periodo con fecha pasada y verificar que el comando detecta el periodo
- [ ] Test: notificacion se envia al operador correcto de la UR responsable

---

## Notas

- Las notificaciones usan el canal `database` por default — email configurable via `.env`
- La deteccion es idempotente: si ya se envio notificacion para un periodo, no se reenvia
- Los indicadores con `activo_seguimiento = false` se ignoran

---

### S6-T3: Formulario de captura de avance

**Ticket:** S6-T3 | **Tipo:** feat | **Rama:** `feat/S6-T3-formulario-captura-avance` | **Sprint:** 6 — Seguimiento | **Depende de:** S6-T1, S4-T9

---

## Contexto

Interfaz principal de captura periodica. El operador ingresa los valores de cada variable del indicador para un periodo especifico. El sistema calcula el resultado aplicando la formula con MathExecutor, determina el semaforo segun los rangos configurados, y si el resultado es amarillo o rojo, solicita justificacion obligatoria.

**Decisiones tecnicas:**
- Paquete `creativecodesolution/math-executor` para evaluar formulas con simbolos mapeados
- Los campos se generan dinamicamente segun las variables del indicador (definidas en S4-T9)
- El semaforo se calcula considerando el sentido del indicador (ascendente/descendente)
- El operador puede ajustar el semaforo manualmente con justificacion

---

## Pre-requisitos

- S6-T1 completado (tablas `avances` y `avance_variables`)
- S4-T9 completado (variables extraidas con simbolos para evaluacion matematica)

---

**Flujo:**
1. Operador selecciona indicador y periodo desde vista "Mis indicadores pendientes"
2. Sistema muestra campos por cada variable (nombre descriptivo + simbolo)
3. Al capturar valores, el sistema calcula el resultado con MathExecutor: `evaluate($formula, ['A' => val1, 'B' => val2])`
4. Semaforo calculado automaticamente segun rangos y sentido del indicador
5. Si amarillo/rojo: textarea de justificacion se habilita (obligatorio para enviar)

**Criterios de aceptacion:**
- [ ] Componente Livewire `App\Livewire\CapturaAvance` con campos dinamicos segun variables del indicador
- [ ] Cada campo muestra: nombre descriptivo + simbolo + unidad de medida
- [ ] Calculo automatico en tiempo real con MathExecutor — resultado visible al capturar valores
- [ ] Semaforo calculado: verde (rango_verde), amarillo (rango_amarillo), rojo (fuera de ambos rangos)
- [ ] Sentido del indicador considerado: ascendente (mayor = mejor) vs descendente (menor = mejor)
- [ ] Si semaforo amarillo/rojo: campo de justificacion obligatorio para enviar
- [ ] El usuario puede ajustar el semaforo manualmente con justificacion (campo `semaforo_justificado`)
- [ ] Se crean registros en `avances` y `avance_variables` al guardar
- [ ] Solo accesible con permiso `capturar_avance` y solo para indicadores con `activo_seguimiento = true`
- [ ] Middleware Multi-UR (S1-T5): operador solo puede capturar en indicadores de su UR
- [ ] Test: capturar valores y verificar que el calculo con MathExecutor es correcto
- [ ] Test: semaforo rojo activa campo de justificacion obligatorio

---

## Notas

- MathExecutor requiere el paquete `creativecodesolution/math-executor` — instalar via composer
- Si la formula es invalida o las variables no coinciden, mostrar error claro — no calcular
- El calculo se ejecuta en frontend (preview) y backend (validacion) para consistencia

---

### S6-T4: Generacion de justificaciones con IA

**Ticket:** S6-T4 | **Tipo:** feat | **Rama:** `feat/S6-T4-justificaciones-ia` | **Sprint:** 6 — Seguimiento | **Depende de:** S6-T3, S3-T8

---

## Contexto

Cuando un indicador cae en amarillo o rojo, la IA genera un borrador de justificacion acotado exclusivamente a tres fuentes: los supuestos del nivel (Col. 4 de la MIR), la magnitud numerica de la desviacion, y el historial del indicador. La IA **nunca inventa contexto externo** — esto es un requerimiento critico de auditoria.

---

## Pre-requisitos

- S6-T3 completado (captura de avance con semaforo calculado)
- S3-T8 completado (servicio `LlmService`)

---

**Fuentes exclusivas para la IA:**
1. Supuestos del nivel correspondiente (Col. 4 de la MIR) — `mir_niveles.supuestos`
2. Magnitud numerica de la desviacion: `meta_periodo - valor_calculado` con porcentaje
3. Historial del indicador: avances del ejercicio anterior (si existen)

**Criterios de aceptacion:**
- [ ] Al detectar semaforo amarillo/rojo, boton "Generar borrador con IA" se habilita
- [ ] IA genera borrador via `LlmService::suggest()` con las 3 fuentes como contexto
- [ ] El borrador cita explicitamente los supuestos de la MIR (Col. 4)
- [ ] El borrador incluye la magnitud de desviacion en terminos numericos
- [ ] El usuario puede editar libremente el borrador antes de guardar como justificacion final
- [ ] `avances.justificacion_ia` guarda el borrador original de la IA (inmutable, para auditoria)
- [ ] `avances.justificacion_final` guarda la version editada por el usuario
- [ ] La IA nunca inventa contexto externo — el prompt lo restringe explicitamente
- [ ] Test con mock: verificar que el prompt contiene los supuestos y la magnitud de desviacion
- [ ] Test: verificar que `justificacion_ia` se guarda separada de `justificacion_final`

---

## Notas

- Guardar ambas justificaciones (IA y final) permite auditar cuanto modifico el usuario el borrador
- El prompt debe incluir instruccion explicita: "No inventes datos ni contexto que no este en los supuestos proporcionados"
- Si no hay supuestos en Col. 4, la IA lo menciona: "Los supuestos del nivel no estan documentados"

---

### S6-T5: Adjuntar medios de verificacion (evidencia)

**Ticket:** S6-T5 | **Tipo:** feat | **Rama:** `feat/S6-T5-adjuntar-evidencia` | **Sprint:** 6 — Seguimiento | **Depende de:** S6-T1

---

## Contexto

El operador adjunta archivos de evidencia (PDF, Excel, imagenes) que respaldan los valores reportados en el avance. Cada archivo se registra con metadatos (area generadora, fecha) y hash SHA-256 para verificacion de integridad. Los archivos se almacenan en disco privado (no accesible publicamente).

---

## Pre-requisitos

- S6-T1 completado (tabla `avance_evidencias`)

---

**Criterios de aceptacion:**
- [ ] Upload de archivos via Livewire con validacion: tipos permitidos (PDF, XLSX, XLS, JPG, PNG), tamaño max 10MB
- [ ] Campos de metadatos por archivo: nombre descriptivo, area generadora, fecha del documento
- [ ] Generacion automatica de hash SHA-256 al subir — almacenado en `hash_archivo`
- [ ] Almacenamiento en disco `private` de Laravel (no accesible via URL publica)
- [ ] Descarga via controller autenticado que verifica permisos (mismo middleware Multi-UR)
- [ ] Advertencia si el nombre del medio adjuntado difiere del registrado en la MIR (Col. 3) — no bloquea
- [ ] Multiples archivos por avance (sin limite fijo, pero max 10 recomendado)
- [ ] Test: subir archivo y verificar que `hash_archivo` se genera correctamente
- [ ] Test: intentar descargar archivo sin autenticacion retorna 401

---

## Notas

- El hash SHA-256 permite verificar que el archivo no fue modificado post-aprobacion
- Los archivos congelados (post-aprobacion) no se pueden reemplazar ni eliminar (S6-T7)
- Considerar limpieza periodica de evidencias de ejercicios cerrados (mejora futura)

---

### S6-T6: Maquina de estados del reporte de avance

**Ticket:** S6-T6 | **Tipo:** feat | **Rama:** `feat/S6-T6-maquina-estados-avance` | **Sprint:** 6 — Seguimiento | **Depende de:** S6-T3, S6-T5

---

## Contexto

El reporte de avance progresa por 4 estados con transiciones controladas: En Captura (editable) → En Revision (bloqueado) → Observado (devuelto con comentarios) → Aprobado (congelado). Cada transicion tiene permisos especificos y genera notificaciones. El historial de observaciones se almacena como JSONB append-only.

**Decisiones tecnicas:**
- Backed Enum `EstadoAvance` con las transiciones permitidas codificadas
- Transiciones validadas a nivel de modelo (no solo UI) para seguridad
- Historial JSONB append-only: cada observacion agrega una entrada, nunca modifica las anteriores
- Notificaciones Laravel por canal `database` con email opcional

---

## Pre-requisitos

- S6-T3 completado (formulario de captura crea avances en estado EN_CAPTURA)
- S6-T5 completado (evidencias adjuntas al avance)

---

**Transiciones:**
- `EN_CAPTURA → EN_REVISION`: operador "envia" el reporte (permiso `capturar_avance`)
- `EN_REVISION → OBSERVADO`: planeador rechaza con comentarios (permiso `revisar_avance`)
- `EN_REVISION → APROBADO`: planeador aprueba (permiso `aprobar_avance`)
- `OBSERVADO → EN_CAPTURA`: operador reabre para corregir (permiso `capturar_avance`)

**Criterios de aceptacion:**
- [ ] Backed Enum `EstadoAvance` con metodo `transicionesPosibles(): array` que retorna estados validos desde el actual
- [ ] Modelo `Avance` con metodo `transicionar(EstadoAvance $nuevoEstado, ?string $comentario)` — valida transicion
- [ ] Transicion a `EN_REVISION`: bloquea campos del avance para edicion por operador
- [ ] Transicion a `OBSERVADO`: agrega comentario al `historial_observaciones` JSONB, reabre edicion para operador
- [ ] Transicion a `APROBADO`: registra `congelado_at = now()`, bloquea todo (variables, justificacion, archivos)
- [ ] Transicion invalida lanza excepcion con mensaje descriptivo
- [ ] Notificacion al operador cuando su reporte pasa a OBSERVADO
- [ ] Notificacion al planeador cuando hay reportes en EN_REVISION
- [ ] Solo planeador puede aprobar (permiso `aprobar_avance`)
- [ ] Historial de observaciones visible como timeline en la UI
- [ ] Test: transicion EN_CAPTURA → APROBADO directamente lanza excepcion (transicion invalida)
- [ ] Test: transicion a OBSERVADO agrega entrada al historial JSONB

---

## Notas

- El operador no puede saltar de EN_CAPTURA a APROBADO — debe pasar por EN_REVISION
- El historial JSONB permite reconstruir toda la conversacion revisor-operador
- Post-APROBADO, la unica forma de editar es via desbloqueo excepcional (S6-T7)

---

### S6-T7: Congelamiento y desbloqueo excepcional

**Ticket:** S6-T7 | **Tipo:** feat | **Rama:** `feat/S6-T7-congelamiento-desbloqueo` | **Sprint:** 6 — Seguimiento | **Depende de:** S6-T6

---

## Contexto

La inmutabilidad post-aprobacion es un requerimiento de auditoria. Una vez aprobado, ningun campo del avance ni sus evidencias pueden modificarse. Sin embargo, existen situaciones excepcionales que requieren correccion. El flujo de desbloqueo permite que un operador solicite la reapertura, y solo un admin puede aprobarla. Todo queda registrado en la tabla `desbloqueos` para trazabilidad.

---

## Pre-requisitos

- S6-T6 completado (maquina de estados con estado APROBADO y `congelado_at`)

---

**Criterios de aceptacion:**
- [ ] Avance aprobado: todos los campos inmutables — validacion a nivel de modelo antes de `save()`
- [ ] Evidencias congeladas: no se pueden reemplazar, eliminar ni agregar nuevas
- [ ] Boton "Solicitar desbloqueo" visible para operador en avances aprobados
- [ ] Formulario de solicitud: motivo (text, obligatorio)
- [ ] Panel de admin: lista de solicitudes pendientes con motivo, solicitante, fecha
- [ ] Admin aprueba: avance regresa a estado EN_CAPTURA, se registra en `desbloqueos`
- [ ] Admin rechaza: solicitud se cierra con motivo de rechazo, avance permanece congelado
- [ ] Al desbloquear, se agrega entrada al `historial_observaciones` JSONB: "Desbloqueado por [admin] - Motivo: [motivo]"
- [ ] Solo admin puede aprobar desbloqueos (permiso `administrar_usuarios` o rol `admin`)
- [ ] Registro en `desbloqueos`: motivo, solicitante_id, aprobador_id, estado, resuelto_at
- [ ] Test: intentar modificar avance aprobado lanza excepcion
- [ ] Test: desbloqueo aprobado cambia estado de avance a EN_CAPTURA

---

## Notas

- El desbloqueo es excepcional — en operacion normal no deberia ser frecuente
- La relacion polimorfica en `desbloqueos` permite extender a otros modelos en el futuro (ej: desbloquear MIR)
- El admin que aprueba queda registrado para trazabilidad de auditoria

---

### S6-T8: Vista de seguimiento para planeadores

**Ticket:** S6-T8 | **Tipo:** feat | **Rama:** `feat/S6-T8-vista-seguimiento-planeador` | **Sprint:** 6 — Seguimiento | **Depende de:** S6-T3, S6-T6

---

## Contexto

Panel consolidado donde el planeador ve el estado de seguimiento de todos los programas de su UR. Incluye tabla resumen con semaforos, drill-down a historial de capturas, y vista comparativa con ejercicio anterior. Es la interfaz principal del planeador durante el ciclo de seguimiento.

---

## Pre-requisitos

- S6-T3 completado (avances capturados con semaforos)
- S6-T6 completado (estados de avance para filtrado)

---

**Criterios de aceptacion:**
- [ ] Componente Livewire `App\Livewire\SeguimientoPlaneador` con tabla resumen por programa
- [ ] Tabla: Programa | Nivel | Indicador | Meta periodo | Avance | Semaforo | Estado
- [ ] Expandible: al clic en un indicador, muestra historial de capturas, variables, justificacion, evidencia
- [ ] Filtros: por programa, por estado de avance (EN_CAPTURA/EN_REVISION/OBSERVADO/APROBADO), por semaforo (verde/amarillo/rojo)
- [ ] Vista comparativa: ejercicio actual vs anterior side-by-side (si existen datos del ejercicio anterior)
- [ ] Indicadores sin avance en periodo actual resaltados visualmente
- [ ] Contadores en header: X verdes | Y amarillos | Z rojos | W sin dato
- [ ] Solo accesible con permiso `revisar_avance`
- [ ] El planeador solo ve programas de su UR (aislamiento por team via scope)
- [ ] Test: verificar que el planeador solo ve programas de su UR
- [ ] Test: filtrar por semaforo rojo y verificar que solo se muestran indicadores rojos

---

## Notas

- La tabla resumen debe usar eager loading (`with()`) para evitar N+1 — programas con muchos indicadores
- La vista comparativa requiere datos del ejercicio anterior — si no existen, mostrar "Sin datos anteriores"
- Esta vista alimenta la evaluacion de cierre (Sprint 7)

---

## Sprint 7: Evaluacion y Reportes

---

### S7-T1: Migraciones para evaluacion

**Ticket:** S7-T1 | **Tipo:** feat | **Rama:** `feat/S7-T1-migraciones-evaluacion` | **Sprint:** 7 — Evaluacion y Reportes | **Depende de:** S6-T1, S4-T3

---

## Contexto

Las tablas de evaluacion soportan el cierre del ejercicio fiscal. `evaluaciones_programa` almacena el indice de desempeno calculado y conteos de semaforo por programa. `anexos_transversales` es un catalogo inmutable de tematicas transversales (Genero, NNA, Cambio Climatico, Anticorrupcion). La pivote `indicador_anexo_transversal` permite etiquetar indicadores con multiples tematicas.

---

## Pre-requisitos

- S6-T1 completado (tablas de seguimiento con avances y semaforos)
- S4-T3 completado (tabla `indicadores`)

---

**Tablas:**
- `evaluaciones_programa`: id, programa_presupuestario_id (FK), ejercicio_fiscal (integer), indice_desempeno (decimal 5,2), semaforos_verde (integer), semaforos_amarillo (integer), semaforos_rojo (integer), semaforos_sin_dato (integer), calculado_at (timestamp), calculado_por (FK → users nullable), timestamps
- `anexos_transversales`: id, clave (string, unique), nombre (string), descripcion (text nullable), activo (boolean, default true), timestamps
- `indicador_anexo_transversal` (pivote): indicador_id (FK), anexo_transversal_id (FK) — Unique compuesto, timestamps

**Criterios de aceptacion:**
- [ ] 3 migraciones con `up()` y `down()` completos
- [ ] Seeder `AnexosTransversalesSeeder` con catalogo base: Genero, NNA (Ninos, Ninas y Adolescentes), Cambio Climatico, Anticorrupcion
- [ ] Pivote M:M con unique compuesto en `(indicador_id, anexo_transversal_id)`
- [ ] `evaluaciones_programa` con indice compuesto en `(programa_presupuestario_id, ejercicio_fiscal)` — unique
- [ ] Modelo `EvaluacionPrograma` con `belongsTo ProgramaPresupuestario`
- [ ] Modelo `AnexoTransversal` con `belongsToMany Indicador`
- [ ] Modelo `Indicador` actualizado con `belongsToMany AnexoTransversal`
- [ ] `sail artisan migrate:fresh --seed` sin errores
- [ ] Archivo `docs/schema/evaluacion.md` documenta las 3 tablas

---

## Notas

- Los anexos transversales son extensibles — el admin puede agregar nuevos via seeder o interfaz futura
- `indice_desempeno` es un valor 0-100 calculado por el comando de S7-T3
- Solo una evaluacion por programa por ejercicio fiscal (unique compuesto)

---

### S7-T2: Etiquetado de indicadores con Anexos Transversales

**Ticket:** S7-T2 | **Tipo:** feat | **Rama:** `feat/S7-T2-etiquetado-anexos-transversales` | **Sprint:** 7 — Evaluacion y Reportes | **Depende de:** S7-T1, S4-T4

---

## Contexto

Cada indicador puede contribuir a una o mas tematicas transversales (Genero, NNA, Cambio Climatico, Anticorrupcion). El etiquetado permite filtrar indicadores por tematica en los paneles de evaluacion transversal (S7-T6). Se integra como checkboxes en la ficha del indicador dentro de la MIR.

---

## Pre-requisitos

- S7-T1 completado (tabla pivote `indicador_anexo_transversal`)
- S4-T4 completado (interfaz MIR donde se editan indicadores)

---

**Criterios de aceptacion:**
- [ ] Checkboxes de anexos transversales visibles en la ficha del indicador (dentro de la interfaz MIR S4-T4)
- [ ] Guardado en tabla pivote `indicador_anexo_transversal` via relacion `belongsToMany`
- [ ] Etiquetas visibles como badges en la vista de MIR junto a cada indicador
- [ ] Etiquetas visibles en el panel de seguimiento del planeador (S6-T8)
- [ ] Al importar un programa (S5-T1/S5-T2), los anexos transversales se pueden asignar post-importacion
- [ ] Solo editable con permiso `editar_mir`
- [ ] Test: etiquetar un indicador con 2 anexos y verificar que ambos se persisten
- [ ] Test: `AnexoTransversal::find(1)->indicadores->count()` retorna conteo correcto

---

## Notas

- El etiquetado es opcional — no todos los indicadores contribuyen a tematicas transversales
- Los checkboxes se cargan desde la tabla `anexos_transversales` (no hardcodeados) para extensibilidad

---

### S7-T3: Calculo del Indice de Desempeno General

**Ticket:** S7-T3 | **Tipo:** feat | **Rama:** `feat/S7-T3-indice-desempeno` | **Sprint:** 7 — Evaluacion y Reportes | **Depende de:** S7-T1, S6-T3

---

## Contexto

El Indice de Desempeno General es un valor 0-100 que resume el rendimiento de un programa presupuestario al cierre del ejercicio fiscal. Se calcula como promedio ponderado del porcentaje de avance de metas, donde los niveles superiores (Fin, Proposito) tienen mayor peso que los inferiores (Componentes, Actividades).

**Decisiones tecnicas:**
- Los pesos por nivel son configurables via `config/evaluacion.php` (no hardcodeados)
- Solo considera indicadores con `activo_seguimiento = true`
- El calculo se ejecuta via comando artisan para poder schedularse o ejecutarse manualmente
- Los conteos de semaforo se calculan junto con el indice

---

## Pre-requisitos

- S7-T1 completado (tabla `evaluaciones_programa`)
- S6-T3 completado (avances capturados con valores y semaforos)

---

**Pesos por nivel (configurables):**
- Fin: 40%
- Proposito: 30%
- Componentes: 20%
- Actividades: 10%

**Formula:** `indice = Σ (peso_nivel × promedio_avance_nivel)` donde `avance_nivel = valor_calculado / meta × 100`

**Criterios de aceptacion:**
- [ ] Comando artisan `app:calcular-indice-desempeno {programa_id} {ejercicio_fiscal}`
- [ ] Calcula indice 0-100 como promedio ponderado del avance por nivel
- [ ] Conteo de semaforos: verde, amarillo, rojo, sin_dato — guardados en `evaluaciones_programa`
- [ ] Pesos configurables en `config/evaluacion.php` con keys: `peso_fin`, `peso_proposito`, `peso_componente`, `peso_actividad`
- [ ] Solo considera indicadores con `activo_seguimiento = true`
- [ ] Si no hay avances para un nivel, ese nivel se excluye y los pesos se redistribuyen proporcionalmente
- [ ] Resultado guardado en `evaluaciones_programa` con `calculado_at` y `calculado_por`
- [ ] Comando ejecutable en batch: `app:calcular-indice-desempeno --all --ejercicio=2026`
- [ ] Test: programa con todos los indicadores al 100% → indice = 100
- [ ] Test: programa con Fin al 80% y resto al 100% → indice = 0.4×80 + 0.3×100 + 0.2×100 + 0.1×100 = 92

---

## Notas

- La redistribucion de pesos cuando un nivel no tiene datos evita sesgos en programas con pocos indicadores
- El comando se puede registrar en el scheduler para ejecucion automatica al cierre del trimestre/ejercicio
- Los pesos por default (40/30/20/10) reflejan la importancia metodologica de la cadena causal

---

### S7-T4: Evaluacion por programa — Vista de cierre

**Ticket:** S7-T4 | **Tipo:** feat | **Rama:** `feat/S7-T4-evaluacion-programa` | **Sprint:** 7 — Evaluacion y Reportes | **Depende de:** S7-T3, S7-T5

---

## Contexto

Pantalla de evaluacion integral al cierre del ejercicio fiscal para un programa especifico. Consolida toda la informacion de seguimiento en 5 secciones: resumen ejecutivo, tablero de semaforos, comparativa interanual, analisis de desviaciones, e indicadores cronicos en rojo.

---

## Pre-requisitos

- S7-T3 completado (indice de desempeno calculado)
- S7-T5 completado (analisis de logica vertical con IA)

---

**Secciones:**
1. **Resumen ejecutivo:** nombre del programa, alineacion (PED → PND → ODS), objetivo central (Proposito), indice de desempeno
2. **Tablero de semaforos consolidado:** contadores verde/amarillo/rojo/sin dato + grafica de pastel
3. **Comparativa vs ejercicio anterior:** tendencias por indicador (mejoro/empeoro/estable) con flechas visuales
4. **Analisis de desviaciones:** justificaciones aprobadas agrupadas por tema, supuestos incumplidos
5. **Indicadores cronicos en rojo:** indicadores con semaforo rojo en 2+ ejercicios consecutivos

**Criterios de aceptacion:**
- [ ] Componente Livewire `App\Livewire\EvaluacionPrograma` con las 5 secciones
- [ ] Seccion 1: datos de alineacion resueltos via relaciones Eloquent (cadena completa PED → ODS)
- [ ] Seccion 2: contadores de semaforo + grafica visual (Chart.js o similar via Alpine.js)
- [ ] Seccion 3: tendencia por indicador calculada comparando avance actual vs anterior — iconos: ↑ mejoro, ↓ empeoro, → estable
- [ ] Seccion 4: justificaciones aprobadas agrupadas — supuestos mas frecuentemente incumplidos destacados
- [ ] Seccion 5: indicadores con semaforo rojo en 2+ ejercicios resaltados con badge "Cronico"
- [ ] Solo accesible con permiso `exportar_reportes`
- [ ] Test: programa con ejercicio anterior muestra comparativa correcta
- [ ] Test: programa sin ejercicio anterior muestra "Sin datos anteriores" en seccion 3

---

## Notas

- Los datos de ejercicio anterior se obtienen buscando avances con `ejercicio_fiscal - 1` del mismo indicador
- La deteccion de indicadores cronicos requiere query historica — considerar performance con indices
- Esta vista es la fuente para el reporte PDF de evaluacion anual (S7-T7)

---

### S7-T5: Validacion de logica vertical al cierre

**Ticket:** S7-T5 | **Tipo:** feat | **Rama:** `feat/S7-T5-logica-vertical-cierre` | **Sprint:** 7 — Evaluacion y Reportes | **Depende de:** S7-T3, S3-T8

---

## Contexto

Al generar la evaluacion de cierre, la IA analiza los semaforos consolidados por nivel y detecta **rupturas en la cadena causal**. Una ruptura indica que los resultados de un nivel no se reflejan coherentemente en el siguiente, lo cual puede indicar problemas de diseno del programa o factores externos no controlados.

---

## Pre-requisitos

- S7-T3 completado (semaforos calculados por nivel)
- S3-T8 completado (servicio `LlmService`)

---

**Patrones de ruptura:**
- Actividades verdes + Componente rojo = **problema de diseno** (las actividades no producen el componente esperado)
- Componente verde + Proposito rojo = **factores externos** (supuestos no cumplidos, el entorno cambio)
- Proposito verde + Fin rojo = **horizonte temporal** (el impacto requiere mas tiempo)
- Todo verde pero Fin rojo = **indicador de Fin mal seleccionado** (no mide lo correcto)

**Criterios de aceptacion:**
- [ ] Analisis automatico ejecutado al generar evaluacion (post-calculo de indice en S7-T3)
- [ ] IA analiza via `LlmService::validate()` los patrones de semaforo entre niveles adyacentes
- [ ] Reporte de rupturas con: niveles involucrados, patron detectado, explicacion, diagnostico sugerido
- [ ] Diagnostico clasificado: problema de ejecucion, problema de diseno, factores externos
- [ ] La IA no emite juicios de valor sobre las UR — solo analiza coherencia logica
- [ ] Resultado persistido en `evaluaciones_programa` como JSONB o tabla auxiliar
- [ ] Visible en Seccion 4 de la vista de evaluacion (S7-T4)
- [ ] Test con mock: semaforos Actividades=verde, Componente=rojo → IA detecta ruptura
- [ ] Test: programa con cadena coherente (todos verdes) → sin rupturas reportadas

---

## Notas

- Este analisis reutiliza la logica de S4-T7 (validacion vertical durante captura) pero aplicada a datos reales post-cierre
- La IA recibe: semaforos consolidados por nivel + supuestos + justificaciones aprobadas como contexto
- El resultado alimenta recomendaciones para el siguiente ejercicio fiscal

---

### S7-T6: Paneles de evaluacion transversal

**Ticket:** S7-T6 | **Tipo:** feat | **Rama:** `feat/S7-T6-evaluacion-transversal` | **Sprint:** 7 — Evaluacion y Reportes | **Depende de:** S7-T3, S7-T2, S2-T5

---

## Contexto

Vision global que cruza todos los programas del estado. Cuatro vistas complementarias permiten evaluar el desempeno desde diferentes perspectivas: por Eje del PED, por ODS, por Unidad Responsable, y por Anexo Transversal. Los datos se derivan de la Matriz de Alineacion (herencia funcional PED → PND → ODS).

---

## Pre-requisitos

- S7-T3 completado (indices de desempeno calculados por programa)
- S7-T2 completado (indicadores etiquetados con anexos transversales)
- S2-T5 completado (Matriz de Alineacion para herencia de vinculos)

---

**Vistas:**
1. **Por Eje del PED:** conteo de semaforos e indice promedio por eje — drill-down a programas
2. **Por ODS:** indicadores que contribuyen a cada ODS via herencia de la Matriz de Alineacion
3. **Por Unidad Responsable:** ranking de URs por indice de desempeno promedio
4. **Por Anexo Transversal:** indicadores filtrados por tematica (Genero, NNA, etc.) cruzando dependencias

**Criterios de aceptacion:**
- [ ] 4 componentes Livewire independientes, accesibles desde menu de evaluacion
- [ ] Vista PED: tabla de ejes con contadores de semaforo + indice promedio — expandible a programas
- [ ] Vista ODS: 17 ODS con indicadores vinculados via Matriz de Alineacion (herencia PED → PND → ODS)
- [ ] Vista UR: ranking de teams/URs ordenado por indice de desempeno — datos de `evaluaciones_programa`
- [ ] Vista Anexo: filtro por tematica transversal que cruza todas las URs — usa pivote de S7-T1
- [ ] Cada vista con filtros por ejercicio fiscal y por programa
- [ ] Eager loading obligatorio en queries que recorren la Matriz de Alineacion
- [ ] Solo accesible con permiso `exportar_reportes`
- [ ] Test: vista PED muestra ejes con contadores calculados correctamente
- [ ] Test: vista ODS hereda indicadores via cadena de alineacion

---

## Notas

- Las vistas ODS y PED dependen de la Matriz de Alineacion configurada en S2-T8 — si no hay vinculos, se muestra "Sin alineacion"
- El ranking de URs es informativo — no punitivo. La interfaz debe comunicar esto claramente
- Considerar caching (Redis) para las queries transversales que cruzan muchos programas

---

### S7-T7: Exportacion a PDF y Excel

**Ticket:** S7-T7 | **Tipo:** feat | **Rama:** `feat/S7-T7-exportacion-pdf-excel` | **Sprint:** 7 — Evaluacion y Reportes | **Depende de:** S7-T4, S7-T6

---

## Contexto

Generacion de reportes exportables en formatos oficiales. Los reportes son el producto final del sistema — lo que las dependencias entregan a los organos de fiscalizacion y al publico. Se usan paquetes `barryvdh/laravel-dompdf` para PDF y `maatwebsite/laravel-excel` para Excel.

**Decisiones tecnicas:**
- PDF con DomPDF (template Blade dedicado por reporte con CSS print-friendly)
- Excel con Laravel Excel (hojas separadas por seccion)
- Encabezado institucional configurable (logo, nombre de dependencia, periodo)
- Jobs en cola para reportes pesados (programas con muchos indicadores)

---

## Pre-requisitos

- S7-T4 completado (datos de evaluacion por programa)
- S7-T6 completado (datos de evaluacion transversal)

---

**Reportes a generar:**
1. MIR en formato oficial (4x4 con indicadores completos)
2. Fichas tecnicas de indicadores (una pagina por indicador)
3. Reporte de avance trimestral por programa (semaforos + justificaciones del trimestre)
4. Reporte de evaluacion anual por programa (5 secciones de S7-T4)
5. Reporte transversal por Eje PED / ODS / Anexo (tablas consolidadas)

**Criterios de aceptacion:**
- [ ] Paquetes instalados: `barryvdh/laravel-dompdf`, `maatwebsite/laravel-excel`
- [ ] Cada reporte incluye: sello de tiempo, periodo evaluado, ejercicio fiscal, nombre de la UR
- [ ] PDF con template Blade dedicado + CSS print-friendly — encabezado institucional configurable en `config/reportes.php`
- [ ] Excel con hojas separadas por seccion (ej: Hoja 1 = Resumen, Hoja 2 = Indicadores, Hoja 3 = Avances)
- [ ] Boton de descarga en cada vista relevante (MIR, seguimiento, evaluacion)
- [ ] Reportes pesados (>50 indicadores) generados via job en cola con notificacion al completar
- [ ] Archivos temporales limpiados automaticamente despues de 24 horas
- [ ] Solo accesible con permiso `exportar_reportes`
- [ ] Test: generar PDF de MIR y verificar que el archivo se crea con tamaño > 0
- [ ] Test: generar Excel de avance trimestral y verificar que tiene las hojas esperadas

---

## Notas

- El encabezado institucional (logo, nombre) se configura una vez y aplica a todos los reportes
- Los templates Blade de PDF van en `resources/views/reportes/pdf/`
- En produccion, considerar almacenar reportes generados en S3/MinIO para descarga posterior

---

### S7-T8: Exportacion de datos abiertos con Diccionario

**Ticket:** S7-T8 | **Tipo:** feat | **Rama:** `feat/S7-T8-datos-abiertos` | **Sprint:** 7 — Evaluacion y Reportes | **Depende de:** S7-T3

---

## Contexto

La Ley General de Transparencia y Acceso a la Informacion Publica requiere que los datos de programas presupuestales esten disponibles en formatos abiertos. Este ticket implementa la exportacion en CSV y JSON empaquetados con un Diccionario de Datos que describe cada campo, facilitando la reutilizacion por terceros.

---

## Pre-requisitos

- S7-T3 completado (datos de evaluacion disponibles)

---

**Criterios de aceptacion:**
- [ ] Exportacion CSV con codificacion UTF-8 BOM (compatibilidad con Excel en español)
- [ ] Exportacion JSON estructurado con schema legible
- [ ] Diccionario de datos generado automaticamente: tabla con columnas — nombre_campo, tipo_dato, descripcion, ejemplo
- [ ] El diccionario se genera desde los `$fillable` y `$casts` de los modelos Eloquent + metadata manual
- [ ] Empaquetado en ZIP: `datos_programas_2026.csv` + `datos_programas_2026.json` + `diccionario_datos.csv`
- [ ] Filtrable por: ejercicio fiscal, programa, UR
- [ ] Datos incluyen: programas, indicadores, avances, semaforos, indices de desempeno
- [ ] No se exportan datos sensibles (usuarios, emails, tokens)
- [ ] Cumple formato de la Plataforma Nacional de Transparencia (si aplica)
- [ ] Solo accesible con permiso `exportar_reportes`
- [ ] Test: generar ZIP y verificar que contiene los 3 archivos esperados
- [ ] Test: CSV parseable correctamente con pandas/Excel

---

## Notas

- UTF-8 BOM es necesario para que Excel en español abra los CSV correctamente sin problemas de encoding
- El Diccionario de Datos se puede versionar: si cambia el esquema, se regenera automaticamente
- Considerar endpoint publico (sin autenticacion) para los datos abiertos en una fase futura

---

## Sprint 8: Orquestacion IA (Transversal)

---

### S8-T1: Refactorizar LlmService con patrones avanzados (Alcance Reducido y Enfocado)

**Ticket:** S8-T1 | **Tipo:** refactor | **Rama:** `refactor/S8-T1-llm-service-avanzado` | **Sprint:** 8 — Orquestacion IA | **Depende de:** S3-T8, S4-T5, S4-T6, S4-T7, S4-T9, S6-T4, S7-T5

---

## Contexto

Consolidacion del servicio de IA creado en S3-T8 con todos los metodos especificos del dominio. Gracias al motor poka-yoke (S4-T11), la IA ya NO necesita validar Tipo, Dimension o Frecuencia de indicadores — esas restricciones son responsabilidad del sistema. El `LlmService` refactorizado se concentra exclusivamente en auditoria semantica y causal de mayor valor.

**Decisiones tecnicas:**
- Cada metodo tiene su prompt template versionado como archivo dedicado en `resources/prompts/`
- Los prompts se cargan desde archivos Blade para permitir variables dinamicas
- La interfaz `LlmServiceInterface` se extiende con los 8 metodos de dominio
- Los metodos genericos (`suggest`, `validate`, `transform`) de S3-T8 se mantienen como base
- Logging unificado en tabla `llm_logs` (ya creada en S3-T8)

---

## Pre-requisitos

- S3-T8 completado (servicio base `LlmService`)
- S4-T5, S4-T6, S4-T7, S4-T9 completados (validaciones que usan IA)
- S6-T4 completado (generacion de justificaciones)
- S7-T5 completado (deteccion de rupturas causales)

---

**Metodos consolidados (alcance exclusivo de la IA):**
- `suggestNarrativeSyntax($nivel, $texto)` — Validacion sintactica SHCP semantica (S4-T5)
- `validateCremaa($indicador)` — Validacion CREMAA letra por letra (S4-T6)
- `validateVerticalLogic($mir)` — Congruencia causal entre niveles (S4-T7)
- `validateHorizontalLogic($nivel)` — Indicador mide el objetivo; medio verifica el indicador (S4-T7)
- `extractVariables($formula)` — Extraccion de variables de formulas (S4-T9)
- `generateJustification($avance, $supuestos, $historial)` — Borradores de justificacion (S6-T4)
- `suggestAlignment($texto, $nivel)` — Sugerencias de alineacion semantica (S4-T8)
- `detectCausalBreaks($evaluacion)` — Deteccion de rupturas causales al cierre (S7-T5)

**Lo que la IA ya NO valida (delegado al sistema en S4-T11):**
- Tipo de indicador por nivel (bloqueado por Form Request)
- Dimension de medicion por nivel (filtrado en UI y validado en backend)
- Frecuencia de medicion por nivel (dropdowns restringidos y validacion backend)

**Criterios de aceptacion:**
- [ ] 8 metodos de dominio implementados en `LlmService`, cada uno delegando a un prompt template dedicado
- [ ] Prompt templates en `resources/prompts/` como archivos Blade: `narrative-syntax.blade.php`, `cremaa.blade.php`, etc.
- [ ] Los prompts NO incluyen instrucciones para validar Tipo, Dimension o Frecuencia (verificar explicitamente)
- [ ] Tests unitarios con mocks para cada uno de los 8 metodos — verificar que el prompt correcto se envia
- [ ] Logging unificado: cada llamada registrada en `llm_logs` con `tipo_operacion` correspondiente
- [ ] Rate limiting por usuario/sesion configurable en `config/llm.php`
- [ ] Metodos base (`suggest`, `validate`, `transform`) de S3-T8 preservados como fundacion
- [ ] Documentacion: `docs/ia/metodos-llm.md` describe cada metodo, su prompt, y sus fuentes de datos

---

## Notas

- Los archivos Blade en `resources/prompts/` permiten: variables dinamicas, herencia de templates, y versionado via git
- Al versionar prompts como archivos, se puede hacer diff y review de cambios en los prompts
- El rate limiting unificado previene que un usuario sature el API de IA desde multiples interfaces

---

### S8-T2: Pipeline de embeddings batch

**Ticket:** S8-T2 | **Tipo:** feat | **Rama:** `feat/S8-T2-embeddings-batch` | **Sprint:** 8 — Orquestacion IA | **Depende de:** S2-T10

---

## Contexto

Comando artisan para generar o regenerar embeddings en batch para todos los registros que no los tengan o necesiten actualizacion. Complementa el pipeline individual (S2-T10) que genera embeddings uno por uno al crear/actualizar registros. Este comando procesa en bulk para: carga inicial de datos, regeneracion tras cambio de modelo de embeddings, o recuperacion de embeddings fallidos.

---

## Pre-requisitos

- S2-T10 completado (servicio `EmbeddingService` funcional)

---

**Criterios de aceptacion:**
- [ ] Comando artisan `app:embeddings-generate` procesa todos los registros con `embedding IS NULL`
- [ ] Procesamiento en chunks de 50 registros para no saturar el API (configurable via `--chunk-size`)
- [ ] Pausa configurable entre chunks (`--delay=1000` ms) para respetar rate limits del API
- [ ] Procesa todas las tablas con columna `embedding`: ODS, PND, PED (6 tablas), Programas Derivados
- [ ] Flag `--force` regenera todos los embeddings (incluso los que ya tienen valor)
- [ ] Flag `--table=ods_objetivos` procesa solo una tabla especifica
- [ ] Reporte al finalizar: "N generados, M fallidos, X omitidos (ya tenian embedding)"
- [ ] Registros fallidos se registran en log con el error especifico
- [ ] Registrable en scheduler: `Schedule::command('app:embeddings-generate')->dailyAt('02:00')`
- [ ] Test: ejecutar comando con mock de `EmbeddingService` y verificar que procesa registros con embedding null
- [ ] Test: flag `--force` procesa registros que ya tienen embedding

---

## Notas

- El delay entre chunks es critico para APIs con rate limits estrictos — ajustar segun proveedor
- La ejecucion nocturna via scheduler evita competir con el uso interactivo del API durante horario laboral
- Considerar barra de progreso con `$this->output->progressBar()` para ejecucion manual

---

### S8-T3: Monitoreo y metricas de uso de IA

**Ticket:** S8-T3 | **Tipo:** feat | **Rama:** `feat/S8-T3-monitoreo-ia` | **Sprint:** 8 — Orquestacion IA | **Depende de:** S3-T8, S8-T1

---

## Contexto

Dashboard administrativo para monitorear el consumo del servicio de IA. Los datos provienen de la tabla `llm_logs` (creada en S3-T8). Permite al admin: controlar costos, detectar abusos, identificar operaciones lentas, y planificar capacidad.

---

## Pre-requisitos

- S3-T8 completado (tabla `llm_logs` con datos de cada interaccion)
- S8-T1 completado (todos los metodos de dominio logueados uniformemente)

---

**Metricas a mostrar:**
- Llamadas por dia/semana/mes (grafica de linea temporal)
- Tokens consumidos (input + output) con costo estimado
- Tiempo promedio de respuesta por tipo de operacion
- Tasa de error (porcentaje de llamadas fallidas)
- Uso por tipo de operacion: validacion, sugerencia, justificacion, alineacion, etc.
- Uso por usuario y por UR (team)

**Criterios de aceptacion:**
- [ ] Componente Livewire `App\Livewire\Admin\MonitoreoIA` con dashboard de metricas
- [ ] Grafica temporal de llamadas por dia (Chart.js via Alpine.js)
- [ ] Tabla de tokens consumidos con costo estimado (configurable: `config/llm.php` → `cost_per_1k_tokens`)
- [ ] Tabla de tiempo promedio por tipo de operacion
- [ ] Indicador de tasa de error con alerta visual si > 5%
- [ ] Filtros: rango de fechas, tipo de operacion, usuario, UR
- [ ] Top 5 usuarios por consumo de tokens
- [ ] Solo accesible para rol `admin`
- [ ] Queries optimizadas con indices en `llm_logs(created_at, tipo_operacion, user_id)`
- [ ] Test: verificar que las metricas se calculan correctamente con datos de ejemplo en `llm_logs`

---

## Notas

- La tabla `llm_logs` ya existe desde S3-T8 — verificar que tiene todos los campos necesarios para las metricas
- El costo estimado es aproximado — depende del modelo y proveedor; configurable en `config/llm.php`
- Considerar exportar metricas a CSV para reportes de gestion (boton de descarga)
