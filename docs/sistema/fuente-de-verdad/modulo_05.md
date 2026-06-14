# Módulo 5 — Diseño de Indicadores de Desempeño

> **Nivel:** Avanzado — es el núcleo técnico del temario. Requiere haber completado los Módulos 1 al 4.
> **Diagramas de referencia:** D-13, D-14, D-15, D-16, D-17, D-18, D-19
> **Caso de estudio:** Este módulo corrige los errores **E-05, E-06, E-09, E-10 y E-12**. El MIR incorrecto pasa a la **versión 4.0**, con 9 de 12 errores resueltos.
> **Duración estimada:** 5 horas de instrucción + 2 horas de ejercicios

---

## Objetivos del módulo

Al concluir este módulo, el participante será capaz de:

1. Clasificar un indicador como estratégico o de gestión según el nivel de la MIR al que pertenece.
2. Seleccionar la dimensión de medición correcta (Eficacia, Eficiencia, Calidad, Economía) para cada nivel.
3. Aplicar los seis atributos CREMAA para validar la calidad de cualquier indicador.
4. Construir la fórmula de un indicador, definir sus variables y establecer una línea base y meta realistas.
5. Redactar un nombre de indicador neutral, sin dirección implícita.
6. Configurar correctamente la semaforización respetando las cuatro reglas técnicas.
7. Determinar la periodicidad adecuada de un indicador en función de su nivel y su Medio de Verificación.
8. Llenar los campos de una Ficha Técnica del Indicador.

---

## Introducción: el indicador como contrato de rendición de cuentas

Un indicador de desempeño es mucho más que un número. Es un **compromiso público verificable**: el gobierno declara que persigue un resultado específico, define con precisión cómo lo medirá y se compromete a reportar el valor alcanzado frente a la meta que él mismo estableció.

Un indicador mal diseñado rompe ese contrato de tres formas: puede reportar un "éxito" que no ocurrió (si mide algo diferente al objetivo), puede ser manipulado sin que nadie lo detecte (si no es monitoreable) o puede ser imposible de cumplir (si la meta no es realista). Este módulo enseña a construir indicadores que cumplan con el contrato.

---

## 5.1 Tipos de Indicadores: Estratégicos y de Gestión *(Ver Diagrama D-13)*

### La distinción fundamental

Todo indicador de la MIR pertenece a una de dos categorías que responden preguntas radicalmente distintas:

**Indicador Estratégico — "¿Qué cambio logramos en la sociedad?"**
Mide el **impacto externo** del programa: cambios en la condición de vida de la población, en fenómenos sociales o en el entorno. Sus datos provienen de fuentes **externas e independientes** al programa (INEGI, CONEVAL, encuestas nacionales, estadísticas sectoriales publicadas). El programa no produce el dato: lo toma de una fuente que existiría aunque el programa no existiera.

**Indicador de Gestión — "¿Cómo estamos operando el programa?"**
Mide el **funcionamiento interno**: cuántos bienes se entregaron, cuánto presupuesto se ejerció, cuántas acciones se realizaron. Sus datos provienen de los **registros propios** del programa (padrones, informes de avance, bases de datos internas). El programa produce el dato porque registra su propia operación.

### Aplicación por nivel de la MIR *(Ver Diagrama D-13)*

| Nivel | Tipo de indicador | Fuente de datos | Ejemplo |
|---|---|---|---|
| **🔵 FIN** | Siempre **Estratégico** | INEGI, CONEVAL, organismos internacionales | Índice de Desarrollo Humano municipal |
| **🟢 PROPÓSITO** | Siempre **Estratégico** | Encuestas, Estadística 911, padrones auditados | Tasa de conclusión escolar en comunidades rurales |
| **🟡 COMPONENTES** | **Estratégico** si entrega directa a población (subsidios, becas) / **De Gestión** si entrega indirecta | Padrón de beneficiarios / Registros administrativos | % de becas entregadas vs. programadas |
| **🔴 ACTIVIDADES** | Siempre **De Gestión** | Registros internos, informes operativos | % de presupuesto ejercido vs. asignado |

### La excepción documentada del nivel Componentes

El nivel Componentes es el único con posibilidad de alojar indicadores de cualquiera de los dos tipos. La regla:

- Si el Componente entrega **directamente** a la población (becas, subsidios, apoyos económicos, bienes tangibles): el indicador es **estratégico**, porque el bien entregado impacta directamente a una persona.
- Si el Componente es un **proceso o servicio a otra institución** (elaboración de un sistema informático, capacitación a funcionarios, infraestructura para uso institucional): el indicador es **de gestión**, porque el resultado no impacta directamente a la población final.

---

## 5.2 Dimensiones de Medición *(Ver Diagrama D-14)*

Las dimensiones de medición responden **cuatro preguntas distintas** sobre el desempeño de un programa. No todas aplican en todos los niveles.

### Eficacia — "¿Logré lo que me propuse?"

Mide el **grado de cumplimiento** de los objetivos y metas. Es la única dimensión **obligatoria en todos los niveles** de la MIR. Sin eficacia, no hay forma de saber si el programa está cumpliendo su misión.

- Se expresa típicamente como porcentaje de cumplimiento: *(resultado obtenido / meta programada) × 100*.
- Aplica en Fin, Propósito, Componentes y Actividades.
- Es el punto de partida: si un programa no puede medir su eficacia, no debería existir como programa presupuestario.

### Eficiencia — "¿Logré el resultado con los recursos adecuados?"

Mide la **relación entre los productos obtenidos y los insumos utilizados**. Permite comparar el costo de producir los resultados y detectar si el programa opera de forma económicamente racional.

- Se expresa como costo unitario, relación insumo/producto o variación de costo respecto al periodo anterior.
- Aplica en **Propósito, Componentes y Actividades**. **No se recomienda en Fin**: los impactos estratégicos de largo plazo (reducir la pobreza, mejorar el IDH) no se evalúan en términos de costo unitario porque no son producibles directamente por el programa.
- Ejemplo: "Costo promedio por alumno que concluye el ciclo escolar."

### Calidad — "¿Los beneficiarios quedaron satisfechos?"

Evalúa los **atributos del bien o servicio entregado**: oportunidad, precisión, accesibilidad y satisfacción del usuario. Responde si el programa está entregando lo que la población necesita de la manera en que lo necesita.

- Se mide principalmente con encuestas de satisfacción, indicadores de oportunidad (tiempos de respuesta) o tasas de rechazo/devolución.
- Aplica principalmente en **Componentes**, donde la entrega del bien o servicio ocurre y puede evaluarse.
- **No se recomienda en Propósito o Fin**: la satisfacción inmediata no garantiza que el problema social de fondo se resolvió de forma permanente. Un beneficiario puede estar satisfecho con recibir una beca pero seguir abandonando la escuela.
- Ejemplo: "Porcentaje de beneficiarios satisfechos con el proceso de entrega de becas."

### Economía — "¿Se administró bien el presupuesto?"

Evalúa la **capacidad del ente público para generar y administrar sus recursos financieros** de forma eficaz. Mide si el dinero se gasta como se planeó, sin subejercicios ni sobreejercicio.

- Se expresa como porcentaje de presupuesto ejercido vs. asignado, tasa de subejercicio, o recaudación vs. meta.
- Aplica **exclusivamente en Actividades**. Es el nivel donde se gestionan los insumos y se controla el gasto operativo.
- **No aplica en niveles superiores**: los niveles de Componentes, Propósito y Fin no evalúan administración presupuestal sino resultados de la intervención.
- Ejemplo: "Porcentaje de presupuesto operativo ejercido respecto al asignado."

### Mapa de aplicabilidad

| | Eficacia | Eficiencia | Calidad | Economía |
|---|:---:|:---:|:---:|:---:|
| **🔵 FIN** | ✅ Siempre | ❌ No | ❌ No | ❌ No |
| **🟢 PROPÓSITO** | ✅ Siempre | ✅ Sí | ❌ No recomendada | ❌ No |
| **🟡 COMPONENTES** | ✅ Siempre | ✅ Sí | ✅ **Principal** | ❌ No |
| **🔴 ACTIVIDADES** | ✅ Siempre | ✅ Sí | ✅ Sí | ✅ **Único nivel** |

---

## 5.3 Atributos CREMAA: Los Seis Criterios de Calidad *(Ver Diagrama D-15)*

Todo indicador debe cumplir los seis atributos CREMAA para ser metodológicamente válido. Un indicador que falla en cualquiera de ellos no debería aparecer en una MIR.

### C — Claro

El indicador tiene **una sola interpretación posible**. Cualquier persona que lea el nombre del indicador debe entender exactamente qué se está midiendo, sin ambigüedad.

*Señal de fallo:* si dos personas diferentes leen el nombre y describen cosas distintas, el indicador no es Claro.

| ❌ Ambiguo | ✅ Claro |
|---|---|
| "Índice de bienestar de los beneficiarios" | "Porcentaje de beneficiarios con ingreso per cápita superior a la línea de bienestar" |
| "Avance del programa" | "Porcentaje de metas trimestrales cumplidas respecto a las programadas" |

### R — Relevante

El indicador mide lo que **realmente importa** para el objetivo, no solo lo que es fácil de medir. Un resultado relevante es aquel que, si cambia, refleja un cambio real en el problema que el programa atiende.

*Señal de fallo:* medir el número de reuniones realizadas cuando el objetivo es "fortalecer la coordinación interinstitucional". Las reuniones son fáciles de contar pero no demuestran que la coordinación mejoró.

### E — Económico

El **costo de obtener el dato** es menor que el **beneficio informativo** que aporta. Producir un indicador tiene un costo: diseñar la encuesta, aplicarla, procesarla, verificarla. Si ese costo supera el valor de la información, el indicador no cumple este atributo.

*Señal de fallo:* un indicador que requiere una encuesta a 50,000 personas dos veces al año para medir un fenómeno que podría aproximarse con datos administrativos existentes.

### M — Monitoreable

El indicador puede ser **verificado y replicado por cualquier persona ajena al programa**. Los datos están en sistemas institucionales auditables y accesibles. Si un auditor externo aplica la misma fórmula con los mismos datos del Medio de Verificación, debe obtener exactamente el mismo resultado que reportó la dependencia.

*Esta es la característica más frecuentemente fallida.* Un indicador construido con datos que solo existen en una hoja de cálculo interna, que no están en un sistema oficial, o que dependen de la interpretación del operador no es Monitoreable.

*Tres pasos de la prueba de Monitoreabilidad:*
1. ¿Puede el auditor acceder a la fuente señalada en el Medio de Verificación?
2. ¿Puede aplicar la fórmula del indicador con esos datos?
3. ¿Obtiene el mismo número que reportó la dependencia?

Si alguno de los tres pasos falla, el indicador no es Monitoreable aunque el número sea correcto.

### A — Adecuado

El indicador es **suficiente por sí solo** para medir el objetivo al que está asociado. No requiere de otro indicador para ser interpretado. Si un solo número no alcanza para capturar el objetivo, se necesita un segundo indicador, no complementar este.

*Señal de fallo:* un indicador que mide solo la cobertura (cuántas personas recibieron el servicio) cuando el objetivo incluye también la calidad del servicio. La cobertura no es adecuada sola para ese objetivo.

### A — Aporte Marginal

El indicador agrega **información nueva** que no está ya capturada por otro indicador de la misma MIR. Cada indicador debe justificar su existencia demostrando que aporta algo que los otros no miden.

*Señal de fallo:* tener en el mismo nivel dos indicadores que miden variaciones del mismo fenómeno ("número de becas entregadas" y "porcentaje de cobertura de becas" miden esencialmente lo mismo con distintas escalas).

---

## 5.4 Fórmulas, Variables, Línea Base y Metas

### La fórmula del indicador

La fórmula es la expresión matemática que relaciona las variables para producir el valor del indicador. Las fórmulas más comunes en la MIR son:

| Tipo de fórmula | Expresión | Cuándo usarla |
|---|---|---|
| **Porcentaje / Tasa** | (Variable A / Variable B) × 100 | Cuando se mide el logro de una parte sobre un total |
| **Tasa de variación** | ((Valor actual - Valor anterior) / Valor anterior) × 100 | Cuando se mide el cambio respecto a un periodo previo |
| **Promedio** | Suma de valores / Número de observaciones | Cuando se mide el nivel típico de un fenómeno |
| **Razón** | Variable A / Variable B | Cuando se compara una magnitud contra otra sin escalar a 100 |
| **Número absoluto** | Conteo directo | Solo cuando el total es el objetivo (no recomendado en Propósito o Fin) |

**Regla sobre el nombre del indicador:** el nombre debe ser **neutral**. No debe incluir palabras que impliquen dirección ("tasa de incremento", "tasa de reducción", "índice de mejora"). La dirección se define en el campo **Sentido** de la Ficha Técnica, no en el nombre.

### Las variables

Cada variable de la fórmula debe definirse con tres elementos:

- **Nombre:** cómo se llama la variable en el sistema de datos.
- **Descripción:** qué cuenta o mide exactamente esa variable (con criterios de inclusión y exclusión).
- **Fuente:** de dónde proviene el dato (el sistema, registro o publicación específica).

*Ejemplo para el indicador "Tasa de conclusión del ciclo escolar en comunidades rurales":*

| Variable | Nombre | Descripción | Fuente |
|---|---|---|---|
| Numerador (A) | Alumnos que concluyen | Número de alumnos inscritos en escuelas de localidades rurales con alta marginación que concluyen el ciclo escolar completo | Estadística 911, SEP — corte agosto |
| Denominador (B) | Alumnos inscritos | Total de alumnos inscritos al inicio del ciclo escolar en el mismo universo | Estadística 911, SEP — corte septiembre |

### La Línea Base

La **Línea Base** es el valor del indicador en el periodo inmediatamente anterior al inicio del programa, o del periodo de medición anterior. Cumple tres funciones:

1. **Documenta el punto de partida:** demuestra cuál era la situación antes de la intervención.
2. **Hace creíble la meta:** una meta solo es evaluable si se conoce desde dónde se parte. Sin línea base, cualquier meta parece arbitraria.
3. **Permite calcular el impacto:** la diferencia entre el valor final del indicador y la línea base es la magnitud del cambio atribuible al programa.

*Nota importante:* si el programa es nuevo y no tiene periodo previo de operación, la Línea Base puede ser el diagnóstico estadístico del problema (el valor del indicador antes de que el programa interviniera, obtenido de fuentes externas).

### La Meta

La meta es el **valor cuantitativo comprometido** que el programa se propone alcanzar en el periodo. Una buena meta es:

- **Específica:** expresa un número concreto, no un rango o una dirección.
- **Realista:** alcanzable con el presupuesto y la capacidad operativa disponible.
- **Retadora:** supone un avance real respecto a la línea base, no solo mantener el statu quo sin esfuerzo.
- **Vinculada a la población objetivo:** para indicadores de cobertura, la meta máxima posible es el 100% de la Población Objetivo.
- **Expresada en la misma unidad del indicador:** si el indicador es un porcentaje, la meta es un porcentaje.

---

## 5.5 La Ficha Técnica del Indicador *(Ver Diagrama D-18)*

La Ficha Técnica es el **documento individual** que acompaña a cada indicador de la MIR. Mientras la celda de indicadores en la MIR solo muestra el nombre, la Ficha Técnica contiene toda la información necesaria para entenderlo, calcularlo, auditarlo y replicarlo.

### Estructura de la Ficha Técnica

**Bloque 1 — Identificación**

| Campo | Contenido |
|---|---|
| Programa Presupuestario | Nombre completo y clave del programa |
| Nivel en la MIR | Fin / Propósito / Componente / Actividad |
| Nombre del Indicador | Nombre preciso, neutral, sin dirección |
| Definición | Descripción de qué mide el indicador (máx. 240 caracteres) |

**Bloque 2 — Características de Medición**

| Campo | Opciones |
|---|---|
| Tipo | Estratégico / De Gestión |
| Dimensión | Eficacia / Eficiencia / Calidad / Economía |
| Fórmula | Expresión matemática completa |
| Unidad de medida | % / Tasa / Promedio / Número absoluto / Índice |
| Frecuencia | Mensual / Trimestral / Semestral / Anual / Bianual / Trianual / Sexenal |
| Sentido | Ascendente / Descendente |

**Bloque 3 — Variables (Metadatos)**

Por cada variable de la fórmula:

| Campo | Contenido |
|---|---|
| Nombre de la variable | — |
| Descripción | Qué cuenta exactamente, con criterios de inclusión/exclusión |
| Fuente de datos | Sistema, registro o publicación específica |

**Bloque 4 — Línea Base y Meta**

| Campo | Contenido |
|---|---|
| Valor de línea base | Número + unidad |
| Año/periodo de línea base | Ejercicio fiscal o año de la medición |
| Meta programada | Número + unidad |
| Año de la meta | Ejercicio fiscal comprometido |

**Bloque 5 — Semaforización**

| Rango | Valor mínimo | Valor máximo |
|---|---|---|
| 🟢 Verde (Satisfactorio) | ___ | ___ |
| 🟡 Amarillo (Alerta) | ___ | ___ |
| 🔴 Rojo (Crítico — bajo) | ___ | ___ |
| 🔴 Rojo (Crítico — sobrecumplimiento) | ___ | ___ |

---

## 5.6 Sentido del Indicador *(Ver Diagrama D-16)*

### ¿Qué es el sentido?

El **sentido** define en qué dirección debe moverse el valor del indicador para que el desempeño sea positivo. Es la configuración más básica y más frecuentemente mal aplicada del diseño de indicadores.

El semáforo no puede funcionar sin saber en qué dirección debe moverse el indicador. Si no se define el sentido, el sistema no sabe si un aumento es bueno o malo.

### Sentido Ascendente — "Más es mejor"

El programa busca **incrementar** un valor positivo: más beneficiarios atendidos, mayor cobertura, mayor productividad, mayor tasa de conclusión escolar.

- **Regla técnica:** la meta debe ser **mayor** que la línea base.
- **Semáforo:** Verde cuando el resultado ≥ meta. Rojo cuando el resultado es muy bajo (incumplimiento grave). También Rojo cuando el resultado es muy alto (sobrecumplimiento atípico — señal de meta mal calculada).
- **Ejemplos:** Tasa de conclusión escolar, porcentaje de cobertura, número de beneficiarios atendidos.

### Sentido Descendente — "Menos es mejor"

El programa busca **reducir** un fenómeno negativo: menor deserción, menor mortalidad, menor tiempo de espera, menor tasa de accidentes.

- **Regla técnica:** la meta debe ser **menor** que la línea base.
- **Semáforo (invertido):** Verde cuando el resultado ≤ meta (el problema disminuyó). Rojo cuando el resultado es alto (el problema empeoró). También Rojo cuando el resultado es muy bajo respecto a la meta (sobrecumplimiento atípico — posible error de medición o meta inicial demasiado alta).
- **Ejemplos:** Tasa de deserción escolar, tasa de mortalidad, índice de delincuencia.

### El nombre del indicador debe ser neutral

El sentido lo define el **comportamiento de los valores**, no el nombre. Incluir "incremento", "reducción", "mejora" o "aumento" en el nombre del indicador viola el criterio de neutralidad y es un error metodológico.

| ❌ Nombre con dirección | ✅ Nombre neutral |
|---|---|
| "Tasa de incremento de alumnos becados" | "Tasa de conclusión del ciclo escolar en comunidades rurales" |
| "Tasa de reducción de accidentes viales" | "Tasa de accidentes viales en carreteras estatales" |
| "Índice de mejora de la satisfacción del usuario" | "Índice de satisfacción del usuario con los servicios del programa" |
| "Porcentaje de aumento en la cobertura" | "Porcentaje de población objetivo atendida" |

---

## 5.7 Periodicidad de los Indicadores *(Ver Diagrama D-19)*

### La regla general

A **mayor jerarquía del objetivo**, mayor debe ser el intervalo de medición. Los impactos sociales de largo plazo (Fin) no pueden observarse mensualmente; los procesos operativos (Actividades) sí.

| Nivel | Periodicidad mínima normativa | Periodicidades válidas |
|---|---|---|
| **FIN** | 1 vez en el sexenio | Anual, Trianual, Sexenal |
| **PROPÓSITO** | 1 vez al año | Anual, Bianual, Trianual |
| **COMPONENTES** | 1 vez por semestre | Trimestral, Semestral, Anual |
| **ACTIVIDADES** | 1 vez por trimestre | Mensual, Trimestral, Semestral |

### La regla crítica: compatibilidad con el Medio de Verificación

**Un indicador no puede medirse con más frecuencia que la fuente que lo alimenta.**

Si el Medio de Verificación es la Estadística 911 de la SEP, que se publica una vez al año, el indicador solo puede ser anual. No importa que el ejecutor quisiera reportar trimestralmente: los datos no existen con esa frecuencia.

Esta incompatibilidad es uno de los errores más frecuentes en el diseño de indicadores y produce uno de los problemas más graves en el seguimiento: indicadores que no pueden reportarse en los momentos en que el sistema los requiere.

*Prueba de compatibilidad:*
1. ¿Con qué frecuencia se actualiza o publica la fuente del Medio de Verificación?
2. ¿Es esa frecuencia igual o mayor que la del indicador?
3. Si la respuesta a (2) es "menor", hay incompatibilidad y debe ajustarse la periodicidad del indicador, no la fuente.

---

## 5.8 Semaforización: Rangos y Reglas Técnicas *(Ver Diagrama D-17)*

### ¿Para qué sirve el semáforo?

El sistema de semaforización convierte el valor numérico de un indicador en una señal de gestión inmediata. No requiere que el funcionario interprete el número: el color dice directamente si el desempeño es satisfactorio, está en riesgo o requiere acción correctiva urgente.

### Los tres rangos

| Color | Nombre | Interpretación | Acción requerida |
|---|---|---|---|
| 🟢 **Verde** | Satisfactorio | El programa avanza como se planeó | Seguimiento rutinario |
| 🟡 **Amarillo** | Alerta | El desempeño está en riesgo pero es recuperable | Análisis de causas + acciones correctivas |
| 🔴 **Rojo** | Crítico | Incumplimiento grave o sobrecumplimiento atípico | Reporte ejecutivo + acciones urgentes |

### El doble Rojo

Un concepto que frecuentemente sorprende: el semáforo tiene **dos tipos de Rojo**:

- **Rojo por incumplimiento (bajo):** el resultado es significativamente menor a la meta. Señal de problemas operativos, presupuestales o de diseño.
- **Rojo por sobrecumplimiento (alto):** el resultado es significativamente mayor a la meta. No es motivo de celebración: es una señal de que la meta estaba mal calculada. Una meta subestimada distorsiona la planeación y el presupuesto.

### Configuración para indicador ASCENDENTE (ejemplo con meta = 97.5%)

| Color | Rango | Interpretación |
|---|---|---|
| 🔴 Rojo (bajo) | < 80.0% | Incumplimiento grave |
| 🟡 Amarillo | 80.0% – 84.9% | Alerta; requiere acciones correctivas |
| 🟢 Verde | 85.0% – 112.0% | **La meta (97.5%) debe estar DENTRO de este rango** |
| 🔴 Rojo (alto) | > 112.1% | Sobrecumplimiento atípico; meta subvalorada |

### Las 4 Reglas Técnicas Estrictas

**Regla 1 — La meta siempre cae dentro del rango Verde.**
Si la meta es 97.5% y el Verde va de 85% a 112%, la meta está dentro del Verde. ✅
Si la meta es 97.5% y el Verde va de 98% a 100%, la meta queda fuera del Verde. ❌ La Regla 1 se viola.

**Regla 2 — Sin solapamiento entre rangos.**
Los rangos no pueden compartir valores. Si el Verde termina en 84.9%, el Amarillo debe iniciar en 85.0%, no en 84.9%.

**Regla 3 — Usa la unidad de medida del indicador.**
Si el indicador está en porcentaje, los rangos van en porcentaje. Si está en número absoluto, los rangos van en número absoluto. No mezclar unidades.

**Regla 4 — El rango Rojo no inicia en cero.**
Si hay un programa funcionando, siempre se garantiza algún resultado mínimo. Iniciar el Rojo en cero implicaría que un resultado de 1% y un resultado de 50% reciben el mismo color, lo que pierde toda utilidad diagnóstica.

---

## Corrección del caso de estudio: errores E-05, E-06, E-09, E-10, E-12 ✅

### Corrección E-05 — Indicador del Fin es de gestión

**Lo que decía la v3.0:**
> "Número de alumnos que reciben becas en el estado durante el ejercicio fiscal." ⚠️

**¿Por qué estaba mal?**
Este indicador mide cuántas becas otorgó el programa: es un dato de operación interna, producido por el propio programa y almacenado en el padrón del programa. Es un indicador **de gestión** en el nivel más operativo. El Fin debe medirse con un indicador **estratégico** que capture el impacto de largo plazo al que el programa contribuye: la mejora del IDH o la reducción de la pobreza intergeneracional. Los datos deben provenir de una fuente **externa e independiente**.

**Versión corregida (v4.0):**
> Indicador: **"Índice de Desarrollo Humano municipal en comunidades con presencia del PAPECEB"**
> Tipo: Estratégico | Dimensión: Eficacia | Fuente: PNUD / CONAPO (publicación trianual) | Periodicidad: Trianual | Sentido: Ascendente

---

### Corrección E-06 — Nombre del indicador del Propósito incluye dirección

**Lo que decía la v3.0:**
> "Tasa de **incremento** de alumnos becados en comunidades rurales." ⚠️

**¿Por qué estaba mal?**
Dos problemas: (1) "incremento" en el nombre viola el criterio de neutralidad; la dirección pertenece al campo Sentido, no al nombre. (2) "alumnos becados" mide a quienes recibieron el apoyo del programa, no el resultado en la población: mide operación, no cambio de estado. El indicador del Propósito debe medir si los alumnos **permanecen y concluyen** el ciclo, no si recibieron becas.

**Versión corregida (v4.0):**
> Indicador: **"Tasa de conclusión del ciclo escolar en comunidades rurales e indígenas del estado"**
> Fórmula: (Alumnos que concluyen el ciclo en comunidades rurales / Alumnos inscritos al inicio del ciclo en comunidades rurales) × 100
> Tipo: Estratégico | Dimensión: Eficacia | Fuente: Estadística 911, SEP | Periodicidad: Anual | Sentido: Ascendente

---

### Corrección E-09 — Periodicidad incompatible con el Medio de Verificación

**Lo que decía la v3.0:**
> Medio de Verificación del Propósito: "Informe mensual de seguimiento elaborado por la Dirección del Programa" — periodicidad implícita: mensual. ⚠️

**¿Por qué estaba mal?**
La fuente real para medir la tasa de conclusión escolar es la **Estadística 911**, que la SEP publica **una vez al año** con cierre en agosto. Es imposible medir mensualmente si los alumnos concluyen el ciclo escolar, porque el ciclo dura un año. La periodicidad del indicador debe ser **anual**, compatible con la fuente.

**Versión corregida (v4.0):**
> Indicador del Propósito: Periodicidad **Anual**
> Medio de Verificación: **"Estadística 911, Sistema Nacional de Información Educativa — Secretaría de Educación Pública / Secretaría de Educación Estatal. Publicación anual, corte agosto."**

*(La corrección del MV completo se desarrolla en el Módulo 6.)*

---

### Corrección E-10 — Semaforización excluye la meta del rango verde

**Lo que decía la configuración incorrecta:**
> Meta programada: 97.5% de cobertura de Población Objetivo
> Rango verde: 98.0% – 100% *(la meta cae en el Amarillo)*

**¿Por qué estaba mal?**
Violación directa de la Regla Técnica 1: la meta siempre debe caer **dentro del rango Verde**. Con esta configuración, el programa podría cumplir exactamente su meta (97.5%) y recibir semáforo Amarillo, lo que contradice la lógica completa del sistema: si cumpliste la meta, el semáforo debe ser Verde.

**Versión corregida (v4.0) — Indicador del Propósito (tasa de conclusión escolar, meta = 71.5%):**

> *Nota: la línea base del PAPECEB es 71.6% de deserción → la tasa de conclusión base es aprox. 100% - 28.4% = 71.6%. La meta para el primer año de operación es 75.0% de conclusión (reducir la deserción de 28.4% a 25.0%).*

| Color | Rango | Interpretación |
|---|---|---|
| 🔴 Rojo (bajo) | Menor a 60.0% | Incumplimiento grave |
| 🟡 Amarillo | 60.0% – 69.9% | Alerta; requiere acciones correctivas |
| 🟢 Verde | 70.0% – 86.0% | **La meta (75.0%) cae dentro de este rango** ✅ |
| 🔴 Rojo (alto) | Mayor a 86.1% | Sobrecumplimiento atípico |

---

### Corrección E-12 — Indicador de C1 mide insumos, no entrega

**Lo que decía la v3.0:**
> "Número de **familias** que recibieron transferencias en el bimestre." ⚠️

**¿Por qué estaba mal?**
Dos problemas simultáneos: (1) el Componente 1 entrega **becas a alumnos**, no transferencias a familias; la unidad de medida incorrecta genera ambigüedad entre el beneficiario real (alumno) y el receptor del pago (madre o tutor). (2) "familias que recibieron transferencias" mide el insumo financiero (la transferencia), no el bien entregado (la beca al alumno). El indicador debe medir el **grado de entrega del Componente** respecto a lo programado, expresado en la unidad del bien entregado.

**Versión corregida (v4.0):**
> Indicador: **"Porcentaje de becas entregadas respecto a las programadas"**
> Fórmula: (Número de alumnos con beca entregada en el ejercicio / Número de alumnos programados para recibir beca) × 100
> Tipo: Estratégico (entrega directa a población) | Dimensión: Eficacia | Fuente: Padrón de Beneficiarios PAPECEB | Periodicidad: Semestral | Sentido: Ascendente

---

## Comparativa: MIR v3.0 vs. MIR v4.0

| Celda | Versión 3.0 | Versión 4.0 |
|---|---|---|
| **FIN — Indicador** | "Número de alumnos que reciben becas…" ❌ | **"Índice de Desarrollo Humano municipal en comunidades con presencia del PAPECEB"** ✅ |
| **PROPÓSITO — Indicador** | "Tasa de incremento de alumnos becados…" ❌ | **"Tasa de conclusión del ciclo escolar en comunidades rurales e indígenas del estado"** ✅ |
| **PROPÓSITO — MV** | "Informe mensual de la Dirección del Programa" ❌ | **"Estadística 911, SEP/SEE — publicación anual"** ✅ *(parcial; se completa en Módulo 6)* |
| **PROPÓSITO — Periodicidad** | Mensual (implícita) ❌ | **Anual** ✅ |
| **C1 — Indicador** | "Número de familias que recibieron transferencias" ❌ | **"Porcentaje de becas entregadas respecto a las programadas"** ✅ |
| **Semaforización del Propósito** | Meta (97.5%) fuera del Verde ❌ | **Meta (75.0%) dentro del Verde (70.0%–86.0%)** ✅ |

> **Errores restantes por corregir:** E-07, E-08, E-11 (Módulo 6 — Medios de Verificación y Supuestos)

---

## Ficha Técnica completa del indicador del Propósito (PAPECEB)

> Ejemplo de ficha completa para el indicador corregido en este módulo.

---

**BLOQUE 1 — IDENTIFICACIÓN**

| Campo | Valor |
|---|---|
| Programa | Programa de Apoyo a la Permanencia y Conclusión de la Educación Básica (PAPECEB) |
| Nivel en la MIR | Propósito |
| Nombre del Indicador | Tasa de conclusión del ciclo escolar en comunidades rurales e indígenas del estado |
| Definición | Porcentaje de alumnos inscritos al inicio del ciclo escolar en escuelas de localidades rurales e indígenas con alta o muy alta marginación que concluyen el ciclo escolar completo. |

**BLOQUE 2 — CARACTERÍSTICAS DE MEDICIÓN**

| Campo | Valor |
|---|---|
| Tipo | Estratégico |
| Dimensión | Eficacia |
| Fórmula | (A / B) × 100 |
| Unidad de medida | Porcentaje (%) |
| Frecuencia | Anual |
| Sentido | Ascendente |

**BLOQUE 3 — VARIABLES**

| Variable | Nombre | Descripción | Fuente |
|---|---|---|---|
| A (Numerador) | Alumnos que concluyen | Número de alumnos de primaria y secundaria en localidades rurales e indígenas de alta o muy alta marginación (CONAPO) que concluyen el ciclo escolar completo | Estadística 911, SEP/SEE — corte agosto |
| B (Denominador) | Alumnos inscritos | Total de alumnos inscritos al inicio del ciclo en el mismo universo de localidades | Estadística 911, SEP/SEE — corte septiembre del ciclo anterior |

**BLOQUE 4 — LÍNEA BASE Y META**

| Campo | Valor |
|---|---|
| Línea base | 71.6% |
| Año de línea base | 2024 |
| Meta programada | 75.0% |
| Año de la meta | 2025 |

**BLOQUE 5 — SEMAFORIZACIÓN**

| Color | Rango |
|---|---|
| 🟢 Verde (Satisfactorio) | 70.0% – 86.0% |
| 🟡 Amarillo (Alerta) | 60.0% – 69.9% |
| 🔴 Rojo (Crítico — bajo) | Menor a 60.0% |
| 🔴 Rojo (Sobrecumplimiento) | Mayor a 86.1% |

---

## Resumen del módulo

| Tema | Regla clave |
|---|---|
| Tipo de indicador | FIN y PROPÓSITO: siempre estratégico. ACTIVIDADES: siempre de gestión. COMPONENTES: depende del tipo de entrega. |
| Dimensión | Eficacia en todos los niveles. Economía solo en Actividades. Calidad principalmente en Componentes. |
| CREMAA | Un solo fallo en cualquier atributo invalida el indicador. El más frecuente: Monitoreable. |
| Nombre neutral | Sin "incremento", "reducción", "mejora" en el nombre. El sentido va en la Ficha Técnica. |
| Periodicidad | Compatible con la fuente del MV. Mayor jerarquía = mayor intervalo. |
| Semaforización | La meta siempre cae en Verde. Sin solapamiento. No iniciar en cero. Usar unidad del indicador. |

---

## Ejercicios de autoevaluación

**Ejercicio 5.1 — Clasificación de indicadores**
Clasifica cada indicador como Estratégico o de Gestión y justifica:
- a) "Porcentaje de municipios con cobertura de agua potable superior al 90%"
- b) "Número de contratos de obras públicas formalizados en el trimestre"
- c) "Tasa de mortalidad infantil por cada 1,000 nacidos vivos"
- d) "Porcentaje de expedientes de beneficiarios validados respecto al total recibido"

**Ejercicio 5.2 — CREMAA**
Para el indicador "Número de reuniones interinstitucionales realizadas sobre coordinación de programas sociales", evalúa los seis atributos CREMAA. Señala cuáles cumple y cuáles no, y propón un indicador alternativo que los cumpla todos.

**Ejercicio 5.3 — Nombre neutral**
Reescribe los siguientes nombres de indicadores de forma neutral:
- a) "Tasa de aumento en la productividad agrícola de los beneficiarios"
- b) "Porcentaje de reducción de hogares en pobreza extrema"
- c) "Índice de mejora en la calidad del aire en zonas urbanas"

**Ejercicio 5.4 — Semaforización**
Un indicador de gestión para Actividades tiene meta de 95% de presupuesto ejercido y sentido ascendente. Diseña la semaforización completa respetando las 4 reglas técnicas. Justifica cada rango.

**Ejercicio 5.5 — Ficha Técnica**
Elabora la Ficha Técnica completa (los 5 bloques) para el siguiente indicador: "Porcentaje de talleres de sensibilización impartidos respecto a los programados" (indicador del Componente 3 del PAPECEB). Usa los datos del `caso_programa_base.md` para la línea base y la meta.

---

## Glosario del módulo

*(Términos completos en `glosario_MIR.md`.)*

- **Indicador de Desempeño**
- **Indicador Estratégico**
- **Indicador de Gestión**
- **Eficacia / Eficiencia / Calidad / Economía**
- **CREMAA**
- **Línea Base**
- **Meta**
- **Fórmula**
- **Unidad de Medida**
- **Variables**
- **Sentido del Indicador**
- **Periodicidad**
- **Ficha Técnica**
- **Semaforización**

---

*Módulo 5 de 10 · Continúa en `modulo_06.md`*
