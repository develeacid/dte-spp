# Arquitectura: Embudo de Poblaciones ↔ Padrón de Beneficiarios

> Este documento describe la conexión arquitectónica entre el Paso 5 del Módulo de Planeación
> (Embudo de Poblaciones) y el Padrón Único de Beneficiarios. Ambos son la misma realidad
> en dos momentos del tiempo: el embudo es la **teoría**, el padrón es la **evidencia**.

---

## 1. El Embudo como Promesa, el Padrón como Cumplimiento

```
PLANEACIÓN (números estadísticos)          OPERACIÓN (personas reales)
─────────────────────────────────          ──────────────────────────────
Población de Referencia  100,000           → universo del estado
Población Potencial       20,000           → tienen el problema (INEGI/CONEVAL)
Población Objetivo         5,000           → capacidad + presupuesto del año
                                ↓
                    Padrón de Beneficiarios
                    Población Atendida      4,850  ← registros con status=aprobado
                                                     georreferenciados en PostGIS
```

Durante la planeación, el Planeador captura **estimaciones** (enteros, fuente estadística).
Cuando el programa arranca, el Padrón aporta el cuarto nivel con nombres, CURP y coordenadas GPS.

---

## 2. Modelo de Datos

### Tabla `poblaciones_programa` (Paso 5 de Planeación)

```sql
CREATE TABLE poblaciones_programa (
    id                   BIGSERIAL PRIMARY KEY,
    programa_id          BIGINT NOT NULL REFERENCES programas_presupuestarios(id),
    unidad_medida        VARCHAR(100) NOT NULL,   -- "Niños", "MIPYMES", "Familias"
    referencia_cantidad  INTEGER NOT NULL,
    potencial_cantidad   INTEGER NOT NULL,
    objetivo_cantidad    INTEGER NOT NULL,
    fuente_referencia    TEXT,                    -- cita INEGI/CONEVAL/estudio
    anio_ejercicio       SMALLINT NOT NULL,
    created_at           TIMESTAMP,
    updated_at           TIMESTAMP,

    -- Restricción matemática dura (PbR)
    CONSTRAINT chk_embudo_logico CHECK (
        objetivo_cantidad <= potencial_cantidad AND
        potencial_cantidad <= referencia_cantidad AND
        referencia_cantidad > 0 AND
        potencial_cantidad > 0 AND
        objetivo_cantidad > 0
    )
);
```

### Cálculo de Población Atendida (desde Padrón — no se almacena aquí)

```sql
-- Se calcula en tiempo real desde el Padrón
SELECT COUNT(*) AS atendida
FROM padron_beneficiarios
WHERE programa_id = :programa_id
  AND anio_ejercicio = :anio
  AND status = 'aprobado';
```

---

## 3. Los 3 KPIs de Evaluación (calculados automáticamente)

Una vez que el Padrón tiene registros reales, el sistema calcula estos indicadores sin
intervención humana, alimentando los semáforos del Módulo de Seguimiento.

### 3.1 Índice de Cobertura

```
Cobertura = (Población Atendida [Padrón] / Población Objetivo [Embudo]) × 100

Ejemplo: (4,850 / 5,000) × 100 = 97% → VERDE
```

**Semáforo:**
- Verde: ≥ 90%
- Amarillo: 70% – 89%
- Rojo: < 70%

### 3.2 Error de Exclusión (¿A quiénes les fallamos?)

```
Error Exclusión = Población Objetivo [Embudo] - Población Atendida [Padrón]

Ejemplo: 5,000 - 4,850 = 150 personas no alcanzadas
```

Identifica personas elegibles que no recibieron el beneficio. Causas típicas:
falta de difusión, reglas demasiado estrictas, recortes presupuestales.

### 3.3 Error de Inclusión (¿Entregamos a quien no debíamos?)

```
Error Inclusión = registros del Padrón que no cumplen criterios del Embudo

Detección: cruce geográfico PostGIS entre coordenadas del Padrón
           y polígonos de cobertura definidos en las Reglas de Operación
```

Si el Embudo declaró "Niños en municipios sierra" pero el Padrón tiene domicilios
aprobados en la capital, el sistema marca automáticamente esos registros como
**candidatos a error de inclusión** para revisión del Revisor.

---

## 4. Flujo de Datos entre Módulos

```
Módulo Planeación (Paso 5)
  └─ poblaciones_programa.objetivo_cantidad = 5,000
        │
        ▼
Módulo Tracking / Padrón Único
  └─ padron_beneficiarios WHERE status = 'aprobado' → COUNT = 4,850
        │
        ▼
Módulo Seguimiento / Evaluación
  └─ Cobertura = 97% → VERDE
  └─ Error Exclusión = 150
  └─ Error Inclusión = cruces PostGIS automáticos
        │
        ▼
Módulo Rendición de Cuentas
  └─ API pública: GeoJSON + CSV anonimizado con métricas
  └─ Cuenta Pública: evidencia auditable con hash criptográfico
```

---

## 5. Conexión con la MIR (Módulo de Programación)

El Padrón se integra con la MIR en tres formas directas:

| Nivel MIR | Conexión con Padrón |
|---|---|
| **Actividad** | "Administración del padrón de beneficiarios" es una Actividad formal con indicador de gestión |
| **Medio de Verificación** | El padrón (snapshot criptográfico) es la fuente oficial de evidencia para indicadores de cobertura |
| **Variables de Fórmula** | Numerador de indicadores de resultado = `COUNT(padron WHERE status=aprobado)` desagregado por sexo, edad, vulnerabilidad |

---

## 6. Restricciones Arquitectónicas

| Regla | Implementación |
|---|---|
| `Objetivo ≤ Potencial ≤ Referencia` | `CHECK` en PostgreSQL + validación Livewire al guardar |
| `Objetivo > 0` | `CHECK` en PostgreSQL + required en formulario |
| `unidad_medida` consistente con el Padrón | El tipo de beneficiario del Padrón (catálogo SIPPRES) debe corresponder a la unidad del Embudo |
| Solo un Embudo activo por programa/año | `UNIQUE(programa_id, anio_ejercicio)` |

---

## 7. Estado de Implementación

| Componente | Estado |
|---|---|
| Tabla `poblaciones_programa` | ❌ Pendiente — migración no creada |
| Paso 5 en el Wizard de Planeación | ❌ Pendiente — no existe el componente |
| Cálculo automático de KPIs en Seguimiento | ❌ Pendiente — depende del Padrón |
| Padrón de Beneficiarios | ⚠️ En diseño — ver `contexto-padron-de-beneficiarios.md` |
| Cruce geográfico Error de Inclusión (PostGIS) | ❌ Pendiente — fase posterior |
