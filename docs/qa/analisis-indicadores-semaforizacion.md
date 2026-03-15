# Analisis de Indicadores, Semaforizacion e Involucrados

> Generado: 2026-03-15

## 1. Las 4 Dimensiones del Indicador

El marco PbR-SED define exactamente 4 dimensiones. Cada una responde a una
pregunta distinta y depende de fuentes de informacion diferentes.

### 1.1 EFICACIA — "¿Logramos la meta?"

Mide el grado de cumplimiento de objetivos. No importa cuanto costo ni cuanto
tardo; solo responde: ¿se alcanzo la meta programada?

- **Inputs**: Poblacion objetivo/meta programada (MetaPeriodo) vs poblacion
  atendida/bienes entregados
- **Fuente**: Base operativa del programa, padron de beneficiarios
- **Semaforo verde**: El programa resolvio el problema en la magnitud prometida
- **Semaforo rojo**: Error de exclusion — no se llego a la poblacion objetivo
- **Ejemplo**: (MYPES con credito entregado / MYPES programadas) * 100
- **Sentido tipico**: Ascendente

### 1.2 EFICIENCIA — "¿A que costo lo logramos?"

Mide la relacion entre bienes/servicios entregados y recursos utilizados.
Busca maximizar el rendimiento: hacer mas con menos.

- **Inputs**: Variables fisicas (bienes producidos) + variables administrativas
  (dinero, horas-hombre, insumos) — requiere cruzar datos operativos con ERP/SIIF
- **Fuente**: BD operativa + sistema financiero (presupuesto ejercido por componente)
- **Semaforo verde**: Procesos optimizados, menor costo por resultado
- **Semaforo rojo**: Burocracia excesiva, desperdicio de insumos, costo operativo
  que se comio el presupuesto del subsidio
- **Ejemplo**: Presupuesto ejercido en componente / Numero de certificados emitidos
- **Sentido tipico**: Descendente (menor costo = mejor) o Ascendente (mas unidades/peso)

### 1.3 ECONOMIA — "¿Se manejo bien el dinero?"

Mide la capacidad de la UR para administrar, generar o recuperar recursos
financieros. Se enfoca puramente en liquidez y presupuesto.

- **Inputs**: Variables estrictamente contables: aprobado, devengado, pagado,
  recaudacion, cartera vencida
- **Fuente**: Exclusivamente sistema financiero (SIIF/SAP/modulo presupuesto)
- **Semaforo verde**: Finanzas sanas, ejecucion precisa del gasto, buena recaudacion
- **Semaforo rojo**: Subejercicio (dinero ocioso) o incapacidad para recuperar creditos
- **Ejemplo**: (Monto creditos recuperados / Monto creditos vencidos exigibles) * 100
- **Sentido tipico**: Regular (100% de ejecucion = ideal; sub y sobreejercicio = malo)

### 1.4 CALIDAD — "¿Fue bueno el servicio?"

Mide atributos de bienes/servicios entregados respecto a normas, estandares o
satisfaccion del ciudadano. Un programa puede ser eficaz (entrego 1000 despensas)
pero de pesima calidad (frijoles caducados).

- **Inputs**: Tiempos de respuesta, quejas, encuestas de satisfaccion,
  certificaciones ISO, logs de auditoria (timestamps de pendiente→aprobado)
- **Fuente**: Buzon de quejas, encuestas, logs del sistema, certificaciones
- **Semaforo verde**: Cumple estandares de Mejora Regulatoria, ciudadano conforme
- **Semaforo rojo**: Servicio lento, trato deficiente, producto defectuoso
- **Ejemplo**: Sumatoria dias habiles (recepcion→dictamen) / Total solicitudes dictaminadas
- **Sentido tipico**: Regular (rango ideal) o Descendente (menos tiempo = mejor)

## 2. Cruce Dimension x Nivel MIR (Enfoque Estrategico vs Gestion)

La clasificacion por enfoque cruza directamente con los niveles de la MIR:

```
                    Enfoque ESTRATEGICO          Enfoque GESTION
                    "¿Le cambiamos la vida       "¿Trabajo bien la oficina?"
                     al ciudadano?"
                    ─────────────────────        ────────────────────────
Nivel FIN           Eficacia (impacto)           ---
                    Fuente: INEGI, CONEVAL
                    Frecuencia: anual+

Nivel PROPOSITO     Eficacia, Eficiencia         ---
                    Fuente: evaluaciones,
                    padron unico
                    Frecuencia: semestral+

Nivel COMPONENTE    Eficacia, Eficiencia,        Eficacia, Eficiencia,
                    Calidad                      Calidad
                    Fuente: BD operativa          Fuente: BD operativa
                    Frecuencia: trimestral+       Frecuencia: trimestral+

Nivel ACTIVIDAD     ---                          Eficacia, Eficiencia,
                                                 Economia
                                                 Fuente: BD transaccional,
                                                 ERP financiero
                                                 Frecuencia: mensual+
```

### Reglas de negocio implementadas (IndicadorReglasService)

| Nivel | Tipo (fijo?) | Dimensiones permitidas | Frecuencias permitidas |
|-------|:---:|---|---|
| FIN | estrategico (fijo) | eficacia | anual, bianual, sexenal |
| PROPOSITO | estrategico (fijo) | eficacia, eficiencia | semestral, anual |
| COMPONENTE | estrategico/gestion (flexible) | eficacia, eficiencia, calidad | trimestral, semestral |
| ACTIVIDAD | gestion (fijo) | eficacia, eficiencia, economia | mensual, trimestral |

**Observacion clave**: Economia SOLO aparece en Actividad porque es la dimension
que mide el manejo operativo del dinero — responsabilidad directa del nivel mas
bajo de ejecucion.

## 3. Semaforizacion Completa

El sistema tiene DOS motores de semaforizacion independientes:

### 3.1 Semaforo de Indicadores MIR (SemaforoService)

Calcula el color comparando `resultado` del avance vs `meta_periodo` o rangos
explicitos del indicador.

#### Modo A: Con rangos explicitos (el planeador define umbrales)

**Ascendente (mas es mejor)**:
```
resultado >= rango_verde_min     → VERDE
resultado >= rango_amarillo_min  → AMARILLO
resultado < rango_amarillo_min   → ROJO
```

**Descendente (menos es mejor)**:
```
resultado <= rango_verde_max     → VERDE
resultado <= rango_amarillo_max  → AMARILLO
resultado > rango_amarillo_max   → ROJO
```

**Regular (rango ideal)**:
```
rango_verde_min <= resultado <= rango_verde_max       → VERDE
rango_amarillo_min <= resultado <= rango_amarillo_max  → AMARILLO
fuera de ambos rangos                                  → ROJO
```

#### Modo B: Sin rangos (fallback con meta del periodo)

**Ascendente**: resultado/meta >= 90% → verde, >= 70% → amarillo, < 70% → rojo
**Descendente**: resultado <= meta → verde, <= meta*1.3 → amarillo, > 1.3 → rojo
**Regular**: desviacion <= 10% meta → verde, <= 30% → amarillo, > 30% → rojo

### 3.2 Semaforo Financiero (SemaforoFinancieroService)

Calcula el color comparando `pagado` vs `monto_programado` por trimestre.
Es intrinsecamente de sentido REGULAR (sub y sobreejercicio son malos).

**Configuracion (config/presupuesto.php)**:
```
Verde:    ratio 0.85 - 1.15  (85%-115% de lo programado)
Amarillo: ratio 0.60 - 1.30  (60%-130%)
Rojo:     ratio < 0.60 o > 1.30
```

**Alerta subejercicio**: >= 20% del presupuesto no ejercido

#### Semaforo consolidado por programa

Promedio ponderado de semaforos de todas las partidas, ponderado por monto_efectivo:
- verde=1, amarillo=0.5, rojo=0
- Resultado >= 0.7 → verde, >= 0.4 → amarillo, < 0.4 → rojo

### 3.3 Semaforo Combinado Fisico-Financiero

El `SemaforoFinancieroService::combinado()` cruza ambos semaforos:

| Fisico | Financiero | Combinado | Interpretacion |
|--------|-----------|-----------|----------------|
| verde | verde | **verde** | Todo bien |
| verde | amarillo | **amarillo** | Se entrega pero hay desfase financiero |
| verde | rojo | **rojo** | Caso critico: financiero arrastra |
| amarillo | verde | **amarillo** | Peor de los dos |
| rojo | verde | **rojo** | Se gasta pero NO se entrega |
| rojo | rojo | **rojo** | Crisis total |

**Regla especial**: financiero verde + fisico rojo → rojo siempre
(se gasta el dinero sin entregar resultados = el peor escenario para auditoria)

### 3.4 Indice de Eficiencia Presupuestaria

`PresupuestoResumenService::indiceEficiencia()`:
```
indice = (% avance fisico) / (% avance financiero)
```

| Indice | Interpretacion |
|--------|---------------|
| > 1.0 | Se logra MAS resultado con MENOS gasto — eficiente |
| = 1.0 | Avance fisico y financiero alineados |
| < 1.0 | Se gasta MAS de lo que se entrega — ineficiente |

## 4. Involucrados por Dimension

### Quien DEFINE el indicador (Fase de Planeacion)

| Dimension | Quien define | Quien valida | Fuente de datos |
|-----------|-------------|-------------|-----------------|
| Eficacia | Planeador (editar_mir) | IA (CREMAA + sintaxis) | Padron operativo |
| Eficiencia | Planeador (editar_mir) | IA (CREMAA) | BD operativa + presupuesto |
| Economia | Planeador (editar_mir) | IA (CREMAA) | Sistema financiero |
| Calidad | Planeador (editar_mir) | IA (CREMAA) | Encuestas, logs, quejas |

### Quien CAPTURA el avance (Fase de Seguimiento)

| Dimension | Quien captura | Donde obtiene los datos | Quien revisa |
|-----------|--------------|------------------------|-------------|
| Eficacia | Operador (capturar_avance) | Conteo de beneficiarios/bienes | Planeador |
| Eficiencia | Operador (capturar_avance) | Cruce: bienes entregados / gasto ejercido | Planeador |
| Economia | Operador (capturar_avance) | Datos contables del modulo presupuesto | Planeador |
| Calidad | Operador (capturar_avance) | Encuestas, tiempos de proceso, quejas | Planeador |

**Caso especial — Economia**: El operador necesita datos del modulo de presupuesto
para alimentar las variables de la formula. Actualmente los dos sistemas (Indicador MIR
y AvanceFinanciero) estan desconectados. El operador debe obtener los montos
manualmente del panel presupuestal.

### Quien GESTIONA los datos financieros

| Accion | Rol | Permiso |
|--------|-----|---------|
| Crear partidas presupuestales | Analista Financiero | gestionar_presupuesto |
| Calendarizar gasto trimestral | Analista Financiero | gestionar_presupuesto |
| Capturar avance financiero | Analista Financiero | capturar_avance_financiero |
| Ver panel presupuestal | Planeador, A. Juridico, Admin | ver_datos_financieros |
| Exportar cuenta publica | Planeador, A. Financiero, Admin | exportar_cuenta_publica |

## 5. Validacion CREMAA de Indicadores

Cada indicador puede validarse contra 6 criterios de calidad (via IA):

| Criterio | Pregunta | Aplica a |
|----------|---------|----------|
| **C**laro | ¿La definicion es precisa y sin ambiguedad? | Todos |
| **R**elevante | ¿Mide lo que importa del objetivo? | Todos |
| **E**conomico | ¿Es viable medirlo con recursos disponibles? | Todos |
| **M**onitoreable | ¿Se puede verificar de forma independiente? | Todos |
| **A**decuado | ¿Es apropiado para el nivel MIR? | Todos |
| **A**portante | ¿Aporta informacion util para la toma de decisiones? | Todos |

Cada criterio tiene: booleano (cumple/no) + observacion textual.

## 6. Catalogo de Sentidos y su Uso por Dimension

| Sentido | Cuando se usa | Dimension tipica | Ejemplo |
|---------|--------------|-----------------|---------|
| Ascendente | Mas resultado = mejor | Eficacia | % cobertura de vacunacion |
| Descendente | Menos resultado = mejor | Eficiencia, Calidad | Costo por servicio, tiempo de respuesta |
| Regular | Hay un rango ideal | Economia | % ejecucion presupuestal (100% = ideal) |

## 7. Dos Sistemas de Semaforizacion en Paralelo

```
┌─────────────────────────────────────────────────────────────────┐
│                    INDICADORES MIR                              │
│                                                                 │
│  Indicador ──► MetaPeriodo ──► Avance (Operador captura)       │
│       │              │             │                            │
│  formula_texto   meta_periodo   resultado                      │
│  variables(A,B)  fecha_cierre   semaforo_calculado              │
│                                                                 │
│  SemaforoService.calcular(resultado, indicador, meta)          │
│  → verde/amarillo/rojo segun sentido y rangos                  │
│                                                                 │
│  Dimensiones: EFICACIA | EFICIENCIA | CALIDAD | ECONOMIA       │
│  Sentidos: ASCENDENTE | DESCENDENTE | REGULAR                  │
└──────────────────────────┬──────────────────────────────────────┘
                           │
          SemaforoFinancieroService.combinado()
          → semaforo combinado fisico-financiero
                           │
┌──────────────────────────┴──────────────────────────────────────┐
│                   PRESUPUESTO                                   │
│                                                                 │
│  PartidaPresupuestal ──► MetaGastoTrimestral                   │
│       │                       │                                 │
│  monto_aprobado          monto_programado                      │
│  monto_modificado                                               │
│       │                                                         │
│  AvanceFinanciero (A. Financiero captura)                      │
│       │                                                         │
│  comprometido → devengado → pagado                             │
│                                                                 │
│  SemaforoFinancieroService.calcular(partida, trimestre)        │
│  → pagado/programado: 85-115% verde, 60-130% amarillo, rojo   │
│                                                                 │
│  Sentido implicito: REGULAR (sub y sobreejercicio son malos)   │
└─────────────────────────────────────────────────────────────────┘
```

## 8. Gap Analysis: Lo que Falta

### 8.1 Puente Indicador MIR ↔ Presupuesto

Los indicadores de dimension ECONOMIA en la MIR necesitan datos del modulo
presupuestal (montos comprometidos, devengados, pagados). Actualmente:
- El operador captura el avance MIR manualmente
- El analista financiero captura el avance presupuestal por separado
- No hay forma automatica de que una variable del indicador MIR lea datos
  del modulo presupuestal

**Impacto para seeders**: Los indicadores de Economia del QaTestingSeeder
deberian tener formulas cuyas variables se correspondan con datos del
PresupuestoTestSeeder (ej: "Pagado/Programado*100").

### 8.2 Indicadores de Eficiencia que cruzan fisico-financiero

Indicadores como "Costo promedio por certificado emitido" requieren:
- Numerador: gasto del componente (dato financiero)
- Denominador: certificados emitidos (dato operativo/MIR)

El sistema actual no impide crear estos indicadores, pero el operador debe
buscar manualmente el dato financiero. Los seeders deberian incluir al menos
1 indicador de eficiencia con este cruce.

### 8.3 Semaforo financiero NO alimenta al QaTestingSeeder

El PresupuestoTestSeeder crea partidas y avances financieros, pero no genera
semaforos financieros ni el semaforo combinado. Los seeders deberian crear
escenarios que cubran:
- Programa con ejecucion sana (85-115%) → verde
- Programa con subejercicio (< 60%) → rojo
- Programa con sobreejercicio (> 130%) → rojo
- El cruce: indicador MIR verde + financiero rojo = rojo combinado

### 8.4 Datos no seedeados para el flujo completo

| Dato | QaTestingSeeder | PresupuestoTestSeeder | Brecha |
|------|:---:|:---:|--------|
| Indicadores con formula_texto | No | - | Necesario para CapturaAvance |
| IndicadorVariable (A, B, C) | No | - | Necesario para formula |
| MedioVerificacion | No | - | Necesario para MIR completa |
| CremaaValidacion | No | - | Necesario para calidad |
| AvanceVariable (valores) | No | - | Necesario para calculo real |
| Semaforo financiero | - | No | No se calcula |
| Semaforo combinado | - | No | No se calcula |
| Indice eficiencia | - | No | No se calcula |
| Desviacion calendarizada | - | No | No se calcula |

### 8.5 Cobertura de escenarios por dimension

**Estado actual del QaTestingSeeder**:

| Dimension | Indicadores en seeder | Escenarios de semaforo |
|-----------|:---:|---|
| Eficacia | ~20 | verde (95%), rojo (V-shape ISM-001), ascendente (FSP-003) |
| Eficiencia | ~4 | Solo verde consistente (95% de meta) |
| Calidad | ~2 | Solo verde (1 con rangos: satisfaccion productores) |
| Economia | ~1 | Solo verde (campaña promocion DDT-004) |

**Lo que deberia tener para cubrir todos los escenarios**:

| Dimension | Sentido | Escenario | Color esperado |
|-----------|---------|-----------|:-:|
| Eficacia | Ascendente | Cumple >= 90% meta | verde |
| Eficacia | Ascendente | Cumple 70-89% meta | amarillo |
| Eficacia | Ascendente | Cumple < 70% meta | rojo |
| Eficacia | Descendente | Resultado <= meta | verde |
| Eficacia | Descendente | Resultado 100-130% meta | amarillo |
| Eficacia | Descendente | Resultado > 130% meta | rojo |
| Eficiencia | Descendente | Costo < umbral verde | verde |
| Eficiencia | Descendente | Costo en rango amarillo | amarillo |
| Eficiencia | Ascendente | Rendimiento > umbral | verde |
| Economia | Regular | 85-115% ejecucion | verde |
| Economia | Regular | 60-85% o 115-130% | amarillo |
| Economia | Regular | < 60% o > 130% | rojo |
| Calidad | Regular | Satisfaccion en rango ideal | verde |
| Calidad | Regular | Satisfaccion fuera de rango | amarillo |
| Calidad | Descendente | Tiempo respuesta < umbral | verde |
| Calidad | Descendente | Tiempo respuesta > umbral | rojo |

### 8.6 Indicadores transversales (Anexos)

El sistema ya tiene `indicador_anexo_transversal` (pivot) y AnexoTransversal.
Los seeders no vinculan ningun indicador a anexos transversales. Deberian
incluir al menos genero, derechos humanos y cambio climatico como tags
en indicadores seleccionados de los 4 programas.
