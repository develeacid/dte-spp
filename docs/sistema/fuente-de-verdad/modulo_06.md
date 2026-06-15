# Módulo 6 — Evidencia y Gestión de Riesgos: Medios de Verificación y Supuestos

> **Nivel:** Avanzado — requiere haber completado los Módulos 1 al 5.
> **Diagramas de referencia:** D-11, D-12, D-19
> **Caso de estudio:** Este módulo corrige los errores **E-07** (MV no externo ni auditable), **E-08** (Supuesto dentro del control del ejecutor) y **E-11** (Supuesto que describe una acción interna). La MIR del PAPECEB llega a la **versión 5.0 — versión final corregida**. Se libera también el `caso_mir_correcto.md`.
> **Duración estimada:** 3.5 horas de instrucción + 1.5 horas de ejercicios

---

## Objetivos del módulo

Al concluir este módulo, el participante será capaz de:

1. Aplicar la regla CREMA para evaluar la calidad de cualquier Medio de Verificación.
2. Seleccionar fuentes de información adecuadas para cada nivel de la MIR.
3. Distinguir entre un Supuesto válido (condición externa) y una condición interna del programa.
4. Redactar Supuestos metodológicamente correctos para cada nivel de la MIR.
5. Aplicar la prueba del "si/entonces inverso" para validar un Supuesto.
6. Identificar el rol de los actores de auditoría, evaluación y control en la verificación de los indicadores.

---

## Introducción: la columna vertebral de la rendición de cuentas

Los Módulos anteriores construyeron los objetivos (columna 1) y los instrumentos de medición (columna 2). Ahora toca responder dos preguntas igualmente críticas:

- **¿Dónde está la evidencia?** — La columna 3 (Medios de Verificación) responde esta pregunta.
- **¿Qué puede salir mal fuera del control del programa?** — La columna 4 (Supuestos) responde esta pregunta.

Sin Medios de Verificación sólidos, los indicadores son números sin respaldo. Sin Supuestos bien definidos, la MIR carece de un sistema de gestión de riesgos. Juntas, estas dos columnas son las que hacen de la MIR un instrumento de rendición de cuentas real, no solo un ejercicio de planeación.

---

## 6.1 Medios de Verificación y la Regla CREMA

### ¿Qué es un Medio de Verificación?

Un **Medio de Verificación (MV)** es la fuente documental o sistema de información de donde se extraen los datos para calcular el indicador. No es el indicador mismo ni su fórmula: es el "dónde está el dato".

Cada indicador de la MIR debe tener al menos un Medio de Verificación. El MV responde la pregunta: *Si alguien quisiera verificar de forma independiente el valor que reportó la dependencia, ¿a qué documento o sistema acudiría?*

### Tipos de Medios de Verificación

**Fuentes externas e independientes**
Son las más robustas metodológicamente porque nadie puede manipularlas para mejorar artificialmente los resultados del programa. Son imprescindibles en los niveles de Fin y Propósito.

- Censos y encuestas del INEGI (ENIGH, ENOE, Censo de Población)
- Estadística 911 (SEP) para datos educativos
- SINAIS (SSA) para datos de salud
- CONEVAL para medición de pobreza
- CONAPO para índices de marginación
- Organismos internacionales (PNUD, OMS, BID, Banco Mundial)

**Registros administrativos propios**
Son válidos en los niveles de Componentes y Actividades, donde el programa produce los datos de su propia operación. Deben estar en sistemas institucionales formales y auditables.

- Padrón de beneficiarios del programa
- Registros de transferencias bancarias (sistema de tesorería)
- Actas de entrega firmadas por receptores
- Listas de asistencia a eventos y talleres
- Contratos y bitácoras de obra
- Informes físico-financieros trimestrales de la DGPOP

**Evaluaciones e inspecciones**
Documentos producidos por actores externos que verifican el desempeño del programa.

- Resultados de evaluaciones externas (CONEVAL, universidades)
- Informes de auditoría (ASF, contralorías estatales)
- Inspecciones técnicas certificadas

### La regla CREMA — Los cinco atributos del buen Medio de Verificación

Todo Medio de Verificación debe cumplir los cinco atributos CREMA para ser metodológicamente válido. Al igual que CREMAA para los indicadores, el fallo de cualquier atributo invalida el MV.

**C — Confiable**
La fuente produce datos precisos, consistentes y libres de sesgos sistemáticos. Sus métodos de recolección están documentados y son reproducibles. Un informe interno elaborado por el mismo equipo que opera el programa tiene un sesgo estructural de incentivos: quienes reportan tienen interés en mostrar buenos resultados.

*Prueba:* ¿Quién produce el dato? ¿Tiene incentivos para distorsionarlo? ¿Sus métodos son públicos y verificables?

**R — Relevante**
La fuente contiene exactamente los datos que necesita la fórmula del indicador. Una fuente puede ser confiable y muy completa pero no contener la variable específica que el indicador requiere.

*Prueba:* ¿Tiene la fuente la variable del numerador? ¿Tiene la variable del denominador? ¿Con el nivel de desagregación necesario (municipio, comunidad, grupo etario)?

**E — Económico**
El costo de acceder a la fuente y extraer los datos es razonable en relación con el beneficio informativo. No tiene sentido gastar más en obtener un dato que lo que vale la decisión que ese dato informa.

*Prueba:* ¿Es accesible sin costo o con costos razonables? ¿Requiere trabajo de campo propio o está disponible en sistemas existentes?

**M — Monitoreable**
La fuente es accesible para cualquier actor externo: auditor, evaluador, ciudadano, legislador. Si la fuente solo existe en los archivos internos de la dependencia y no está en un sistema oficial, el indicador no es verificable independientemente.

*Esta es la característica más crítica y la más frecuentemente violada.* Un "informe de seguimiento elaborado por la Dirección del Programa" puede contener datos correctos, pero si ese informe no está en un sistema público o auditable, nadie puede verificarlo.

*Prueba:* ¿Puede un auditor de la ASF acceder a esta fuente sin la colaboración del ejecutor del programa? ¿Está en un sistema institucional oficial?

**A — Asequible (disponible a tiempo)**
La fuente está disponible en el momento en que se necesita para calcular y reportar el indicador. Una fuente excelente que se publica con dos años de rezago no sirve para el seguimiento del ejercicio fiscal en curso.

*Prueba:* ¿Cuándo se publica o actualiza la fuente? ¿Es antes o después del período de reporte del indicador?

### Formato correcto para un Medio de Verificación

En la celda del MV de la MIR, la descripción debe incluir:

1. **Nombre específico de la fuente** (no "estadísticas del gobierno"; sí "Estadística 911, Sistema Nacional de Información Educativa — SEP")
2. **Organismo responsable** (quién la produce)
3. **Periodicidad de publicación** (anual, trimestral, etc.)
4. Si es un sistema en línea, agregar **dónde consultarla** (URL o sistema institucional)

| ❌ MV incorrecto | ✅ MV correcto |
|---|---|
| "Informes de la dependencia" | "Informe de Avance Físico-Financiero Trimestral, DGPOP — Sistema Estatal de Seguimiento del Gasto (publicación trimestral)" |
| "Registros del programa" | "Padrón de Beneficiarios PAPECEB — Sistema de Información de Programas Sociales, SEE. Corte semestral." |
| "Datos del INEGI" | "Estadística 911, Sistema Nacional de Información Educativa, SEP/SEE — Publicación anual, corte agosto" |
| "Encuesta de satisfacción" | "Encuesta de Satisfacción de Beneficiarios PAPECEB — aplicada por la Dirección de Evaluación de la SEE al cierre del ejercicio fiscal. Publicación en informe anual de resultados." |

### Coherencia entre Medio de Verificación y periodicidad del indicador

Esta conexión se desarrolló en el Módulo 5 (Error E-09) pero vale la pena consolidarla aquí como principio general:

> **El Medio de Verificación determina la periodicidad máxima posible del indicador.**

Si la Estadística 911 se publica anualmente, el indicador que depende de ella solo puede ser anual. Si el padrón de beneficiarios se actualiza semestralmente, el indicador basado en él puede ser semestral como máximo. Intentar una frecuencia mayor que la fuente produce un indicador con datos obsoletos o fabricados.

---

## 6.2 Supuestos: La Gestión de Riesgos Externos

### ¿Qué es un Supuesto?

Un **Supuesto** es una condición del entorno, **externa al control del programa**, que debe cumplirse para que la lógica causal de la MIR funcione. Es la respuesta a la pregunta: *¿qué factores fuera del alcance del ejecutor podrían impedir que este nivel de objetivo se logre aunque el programa opere correctamente?*

Los Supuestos no son los riesgos operativos del programa (esos se gestionan internamente). Son los riesgos del entorno: condiciones económicas, sociales, políticas, climáticas o normativas que el programa no controla pero que, si cambian adversamente, hacen fallar la cadena causal.

### La prueba del "si/entonces inverso"

Para validar si un Supuesto es correcto, se aplica esta prueba en dos pasos:

**Paso 1:** Pregunta: *¿Si este factor NO se cumple, el programa puede lograr su objetivo de todas formas?*
- Si la respuesta es **SÍ** → el Supuesto no es relevante; no debe estar en la MIR.
- Si la respuesta es **NO** → el Supuesto es válido porque su incumplimiento impide el logro del objetivo.

**Paso 2:** Pregunta: *¿El programa puede garantizar por sí mismo que este factor se cumple?*
- Si la respuesta es **SÍ** → no es un Supuesto; es una condición interna que el ejecutor debe gestionar.
- Si la respuesta es **NO** → confirma que es externo al programa y puede ser un Supuesto válido.

### El Supuesto válido: externo, relevante y con probabilidad razonable de cumplirse

Un Supuesto bien formulado cumple simultáneamente tres condiciones:

1. **Externo:** está fuera del control del equipo ejecutor del programa.
2. **Relevante:** si no se cumple, el nivel de objetivo no se logra aunque el programa opere perfectamente.
3. **Razonablemente probable:** tiene más probabilidad de cumplirse que de no cumplirse. Si la probabilidad de incumplimiento es casi segura, el programa tiene un problema de diseño que debe resolverse, no enmascararse con un Supuesto.

### Supuestos por nivel de la MIR

Los Supuestos operan como "condiciones habilitantes" en cada eslabón de la cadena causal. *(Ver Diagrama D-11)*

**Supuestos de Actividades**
Condiciones que deben cumplirse para que las Actividades produzcan los Componentes. Generalmente se refieren a:
- Disponibilidad de insumos externos (materiales que proveen terceros, servicios contratados)
- Condiciones logísticas o de acceso (vías de comunicación, seguridad en zonas de operación)
- Factores climáticos o estacionales que afectan la operación
- Estabilidad normativa (que no cambien las reglas de operación a mitad del ejercicio)

**Supuestos de Componentes**
Condiciones que deben cumplirse para que la entrega de los Componentes genere el Propósito. Generalmente se refieren a:
- Comportamiento o capacidad de los beneficiarios (que puedan usar el bien recibido)
- Condiciones del entorno que habilitan el uso del bien entregado
- Acciones de otros actores necesarias para que el bien tenga efecto

**Supuestos de Propósito**
Condiciones que deben mantenerse para que el logro del Propósito contribuya al Fin. Generalmente se refieren a:
- Estabilidad macroeconómica o social
- Continuidad de políticas complementarias de otros programas
- Ausencia de fenómenos adversos de gran escala (crisis, desastres, conflictos)

### Los errores más frecuentes en la redacción de Supuestos

| Error | Ejemplo incorrecto | ¿Por qué es un error? | Corrección |
|---|---|---|---|
| Condición interna | "El programa cuente con presupuesto suficiente" | El ejecutor gestiona su propio presupuesto | "La Secretaría de Finanzas autorice el calendario de ministraciones conforme a lo programado" |
| Acción del propio programa | "Se cuente con el personal operativo para ejecutar el programa" | Contratar personal es responsabilidad del ejecutor | "Los perfiles de puestos cubiertos cuenten con la certificación requerida por la normatividad de servicio civil" |
| Supuesto trivialmente cierto | "No ocurra una guerra en el estado" | Tiene probabilidad de incumplimiento prácticamente nula; no agrega información | Reemplazar por un riesgo real y relevante |
| Supuesto catastrófico | "El gobierno no reduzca el presupuesto del programa" | Si la probabilidad de esto es alta, el programa tiene un problema de diseño, no un supuesto | Rediseñar el programa o escalar el riesgo a decisión política |
| Mezclar dos condiciones en un solo Supuesto | "Las familias acudan a los talleres Y los maestros permanezcan en las escuelas" | Dos riesgos distintos deben ser dos Supuestos distintos | Separar en dos supuestos independientes |

### ¿Qué hacer si un Supuesto tiene alta probabilidad de fallar?

Cuando el equipo identifica que un Supuesto tiene probabilidad significativa de no cumplirse, tiene dos opciones:

**Opción 1 — Rediseñar el programa:** incorporar una Actividad o Componente que reduzca la probabilidad de que el factor falle. Si el riesgo puede gestionarse desde dentro del programa, deja de ser un Supuesto y se convierte en una Actividad.

**Opción 2 — Escalar la decisión:** si el factor externo no puede gestionarse desde el programa, el riesgo debe documentarse y escalarse a la dirección o al nivel político para una decisión sobre si el programa es viable con esa incertidumbre.

Lo que **no** se debe hacer es dejar el Supuesto en la MIR como si tuviera probabilidad razonable cuando en realidad representa una amenaza crítica al programa. Eso distorsiona la evaluación de riesgos.

---

## 6.3 Actores de Auditoría, Evaluación y Control Externo

### ¿Por qué importa conocer estos actores en el contexto de la MIR?

La MIR no se produce en un vacío institucional. Hay múltiples actores que la revisan, la evalúan y la auditan. Conocerlos permite diseñar una MIR que no solo cumpla los requisitos formales sino que resista la revisión de cada uno de estos actores.

### Los actores y su rol en la verificación

**SHCP — Secretaría de Hacienda y Crédito Público**
Es el actor rector del sistema a nivel federal. Emite los lineamientos metodológicos, valida las MIR de los programas federales y vincula los resultados de los indicadores con las decisiones de presupuestación. En el ámbito estatal, el equivalente es la Secretaría de Finanzas o la DGPOP.

*Lo que revisa en la MIR:* alineación con el PND, coherencia de la estructura lógica, pertinencia de los indicadores para los niveles que corresponden.

**CONEVAL — Consejo Nacional de Evaluación de la Política de Desarrollo Social**
Es la instancia técnica independiente más influyente en la evaluación de programas sociales. Realiza o coordina las evaluaciones externas y emite los Aspectos Susceptibles de Mejora (ASM) que las dependencias deben atender formalmente.

*Lo que revisa en la MIR:* calidad del diagnóstico, pertinencia del diseño, capacidad de los indicadores para medir el cambio real en la población, calidad de los Medios de Verificación.

**ASF — Auditoría Superior de la Federación**
Fiscaliza el uso del gasto público y verifica que los resultados reportados en la MIR sean reales y correspondan a los Medios de Verificación declarados. Realiza Auditorías de Desempeño específicas.

*Lo que revisa en la MIR:* que los valores reportados de los indicadores sean verídicos y calculables con las fuentes declaradas. Si el Medio de Verificación no es accesible o no permite calcular el indicador, la ASF genera una observación formal.

**Contraloría Ciudadana**
Los ciudadanos pueden revisar la información pública de los programas, replicar los cálculos de los indicadores (criterio Monitoreable) y denunciar inconsistencias. La transparencia de los Medios de Verificación es el mecanismo que hace posible este control social.

*Por eso importa el atributo M (Monitoreable) de CREMAA y del CREMA:* si los datos no son públicos y replicables, el control ciudadano es imposible.

**Control Legislativo**
La SHCP reporta trimestralmente al Congreso el avance físico y financiero de los programas, incluyendo los resultados de los indicadores de la MIR. El cumplimiento de la MIR es insumo directo de la Cuenta Pública anual, que el Congreso utiliza para evaluar el desempeño del Ejecutivo y tomar decisiones sobre el Presupuesto de Egresos del siguiente ejercicio.

### Las evaluaciones externas como mecanismo de mejora

Las evaluaciones externas son valoraciones independientes sobre el diseño, la operación y los resultados de los programas. Sus tipos principales son:

| Tipo de evaluación | ¿Qué valora? | ¿Cuándo se realiza? |
|---|---|---|
| **Evaluación de Diseño** | Pertinencia del diagnóstico, coherencia de la lógica de la MIR, adecuación de los indicadores | Al inicio del programa o cuando se rediseña |
| **Evaluación de Procesos** | Si los procedimientos operativos permiten la entrega eficiente de los Componentes | Durante la operación |
| **Evaluación de Consistencia y Resultados (ECR)** | Diseño + resultados acumulados + recomendaciones de mejora | Cada 2-3 años |
| **Evaluación de Impacto** | Si el programa causó los cambios observados en la población (causalidad, no correlación) | Cuando hay suficiente tiempo de operación y datos disponibles |

Los resultados de las evaluaciones generan **Aspectos Susceptibles de Mejora (ASM)**: compromisos formales de la dependencia para corregir problemas detectados. Los ASM se registran en el sistema institucional y son monitoreados por la SHCP y el CONEVAL.

---

## Corrección del caso de estudio: errores E-07, E-08 y E-11 ✅

### Corrección E-07 — Medio de Verificación del Propósito no es externo ni auditable

**Lo que decía la v4.0:**
> "Informe mensual de seguimiento elaborado por la Dirección del Programa." ⚠️

**¿Por qué estaba mal?**
Este MV falla en tres de los cinco atributos CREMA:

- **No Confiable:** el mismo equipo que opera el programa elabora el informe. Hay un conflicto de interés estructural: quienes reportan tienen incentivo para mostrar resultados positivos.
- **No Monitoreable:** un auditor externo no puede acceder de forma independiente a un informe interno de la Dirección del Programa. El dato no está en un sistema oficial abierto.
- **No Asequible (periodicidad incompatible):** un informe mensual no puede reportar la tasa de conclusión escolar, que solo existe una vez al año cuando cierra el ciclo.

Adicionalmente, al corregir el indicador del Propósito (E-06 y E-09 en el Módulo 5), la fuente correcta quedó definida: la Estadística 911.

**Versión corregida (v5.0):**
> "Estadística 911, Sistema Nacional de Información Educativa — Secretaría de Educación Pública / Secretaría de Educación Estatal. Publicación anual con corte en agosto. Disponible en el portal oficial de la SEP y en el sistema estatal de información educativa."

**¿Por qué es correcta?**
- **Confiable:** es producida por la SEP, institución independiente al programa.
- **Relevante:** contiene exactamente los datos de alumnos inscritos y alumnos que concluyen, desagregados por localidad.
- **Económica:** es de acceso público y gratuito.
- **Monitoreable:** cualquier auditor puede descargarla del portal de la SEP y calcular el indicador de forma independiente.
- **Asequible:** se publica anualmente en agosto, compatible con la periodicidad anual del indicador.

---

### Corrección E-08 — Supuesto del Propósito dentro del control del ejecutor

**Lo que decía la v4.0:**
> "El programa cuente con presupuesto suficiente para operar durante todo el ejercicio fiscal." ⚠️

**¿Por qué estaba mal?**
Aplicando la prueba del si/entonces inverso:
- *¿Si el programa no cuenta con presupuesto suficiente, puede lograr el Propósito?* No.
- *¿Puede el programa garantizar por sí mismo que tiene presupuesto suficiente?* **Sí, en gran medida.** La Dirección de Educación Básica, la Secretaría de Educación Estatal y la DGPOP son los responsables de gestionar el presupuesto. La disponibilidad presupuestal es una condición interna de gestión, no una condición externa.

Un Supuesto válido del nivel Propósito debe referirse a condiciones del entorno que afectan si los alumnos permanecen en la escuela una vez que ya recibieron los apoyos del programa.

**Versión corregida (v5.0):**
> "Las condiciones económicas y de seguridad en las comunidades rurales beneficiarias permiten que los alumnos continúen asistiendo a la escuela durante el ciclo escolar."

**¿Por qué es correcta?**
- **Externa:** las condiciones de seguridad y el contexto económico comunitario están fuera del control de la SEE.
- **Relevante:** si hay inseguridad grave o una crisis económica que obliga a los niños a trabajar, el programa puede entregar perfectamente todas las becas y aun así la deserción continuará.
- **Razonablemente probable:** en condiciones normales del estado, la seguridad y el contexto económico permiten la asistencia escolar. Es un riesgo real pero de probabilidad moderada, no catastrófica.

---

### Corrección E-11 — Supuesto de Actividades describe una acción del programa

**Lo que decía la v4.0:**
> "Se cuente con el personal operativo necesario para ejecutar el programa." ⚠️

**¿Por qué estaba mal?**
Aplicando la prueba:
- *¿Puede el ejecutor garantizar que cuenta con personal operativo?* **Sí.** Contratar, capacitar y asignar personal es una responsabilidad directa de la Dirección de Educación Básica. No es una condición externa: es una tarea de gestión.

Un Supuesto válido del nivel Actividades debe referirse a condiciones externas que afectan si las Actividades pueden realizarse. Para el PAPECEB, las Actividades más vulnerables a condiciones externas son el trabajo de campo en comunidades (A1.1) y los talleres (A3.2), que dependen de factores logísticos y de acceso.

**Versión corregida (v5.0):**
> "Las localidades beneficiarias son accesibles durante el ejercicio fiscal y los padres y madres de familia cuentan con disponibilidad para participar en las actividades del programa."

**¿Por qué es correcta?**
- **Externa:** la accesibilidad de las comunidades (caminos rurales, condiciones climáticas, temporadas agrícolas) está fuera del control de la SEE.
- **Relevante:** si las comunidades son inaccesibles en ciertos períodos o si los padres están en épocas de cosecha, el trabajo de campo (A1.1) y los talleres (A3.2) no pueden realizarse, impidiendo la producción de los Componentes.
- **Razonablemente probable:** en condiciones normales las localidades son accesibles. El riesgo es real pero manejable.

---

## Comparativa: MIR v4.0 vs. MIR v5.0 — Versión final

| Celda | Versión 4.0 | Versión 5.0 — FINAL |
|---|---|---|
| **FIN — RN** | ✅ Corregido M3 | Sin cambios |
| **PROPÓSITO — RN** | ✅ Corregido M3 | Sin cambios |
| **PROPÓSITO — Indicador** | ✅ Corregido M5 | Sin cambios |
| **PROPÓSITO — MV** | "Informe mensual Dirección Programa" ❌ | ✅ **"Estadística 911, SEP/SEE — anual"** |
| **PROPÓSITO — Supuesto** | "Presupuesto suficiente para operar" ❌ | ✅ **"Condiciones económicas y de seguridad permiten asistencia escolar"** |
| **C1, C2, C3 — RN** | ✅ Corregidos M4 | Sin cambios |
| **C1 — Indicador** | ✅ Corregido M5 | Sin cambios |
| **ACTIVIDADES — RN** | ✅ Corregido M4 | Sin cambios |
| **ACTIVIDADES — Supuesto** | "Personal operativo disponible" ❌ | ✅ **"Localidades accesibles y familias disponibles para participar"** |

> ✅ **Los 12 errores han sido corregidos. La MIR del PAPECEB está completa y metodológicamente válida.**

---

## Resumen del módulo

| Elemento | Regla clave |
|---|---|
| **Medio de Verificación** | Debe cumplir CREMA: Confiable, Relevante, Económico, Monitoreable, Asequible |
| **Independencia del MV** | Fin y Propósito: fuentes externas. Componentes y Actividades: registros administrativos en sistemas oficiales |
| **Periodicidad del MV** | Determina la frecuencia máxima posible del indicador |
| **Formato del MV** | Nombre específico + organismo + periodicidad de publicación |
| **Supuesto válido** | Externo + relevante + razonablemente probable de cumplirse |
| **Prueba del supuesto** | Si no se cumple, ¿el objetivo falla? (SÍ = válido) + ¿el programa lo puede garantizar? (NO = externo) |
| **Supuesto inválido** | Condición interna, acción del propio programa, o trivialmente cierta/catastrófica |
| **ASM** | Los resultados de evaluaciones externas generan compromisos formales de mejora |

---

## Ejercicios de autoevaluación

**Ejercicio 6.1 — Evaluación CREMA**
Evalúa los siguientes Medios de Verificación con los cinco atributos CREMA. Para cada uno señala qué atributos cumple, cuáles no, y propón un MV alternativo válido:

- a) "Base de datos interna de la Unidad Responsable con registros de entrega de apoyos."
- b) "Encuesta Nacional de Ingresos y Gastos de los Hogares (ENIGH), INEGI — publicación bianual."
- c) "Notas periodísticas sobre el programa en medios locales."
- d) "Padrón Único de Beneficiarios del programa, validado y publicado trimestralmente en el portal de transparencia de la dependencia."

**Ejercicio 6.2 — Supuestos: válido vs. inválido**
Para cada enunciado, determina si es un Supuesto válido o inválido. Si es inválido, clasifícalo (condición interna, acción del programa, trivialmente cierto o catastrófico) y reescríbelo correctamente:

- a) "Los beneficiarios cuenten con identificación oficial vigente para registrarse en el padrón."
- b) "El gobierno federal no elimine el programa."
- c) "El área de recursos humanos contrate al personal técnico de campo a tiempo."
- d) "Las condiciones climáticas permitan la ejecución de las obras de infraestructura programadas."
- e) "Los productores agrícolas apliquen correctamente las técnicas de riego aprendidas en la capacitación."
- f) "No ocurran desastres naturales en las zonas de intervención del programa."

**Ejercicio 6.3 — Coherencia horizontal completa**
Para la siguiente fila del nivel Componentes, evalúa la coherencia horizontal completa (RN ↔ Indicador ↔ MV ↔ Supuesto) e identifica todos los problemas:

| RN | Indicador | MV | Supuesto |
|---|---|---|---|
| "Microcréditos otorgados a mujeres emprendedoras de zonas rurales" | "Número de eventos de capacitación financiera realizados" | "Informe mensual de la coordinadora del programa" | "Las mujeres beneficiarias tengan acceso a internet para gestionar su crédito" |

**Ejercicio 6.4 — Diseño completo de una fila**
Para el siguiente objetivo de nivel Propósito, diseña la fila completa de la MIR: indicador, MV y supuesto.

> *Objetivo:* "Productores agrícolas de pequeña escala en municipios de alta marginación incrementan su rendimiento por hectárea de maíz blanco."

- Indica el tipo de indicador, la dimensión, la periodicidad y el sentido.
- Propón un MV válido con su fuente, organismo y periodicidad.
- Formula un Supuesto válido que aplique la prueba si/entonces inverso.

**Ejercicio 6.5 — Reflexión final del caso**
Revisando la evolución completa del caso MIR del PAPECEB de la v1.0 a la v5.0:
- a) ¿En cuántas de las cuatro columnas de la MIR se cometieron errores? ¿Qué sugiere eso sobre la distribución de los errores más frecuentes?
- b) ¿Cuáles errores son "más graves" desde la perspectiva de una auditoría de desempeño? Justifica.
- c) Si tuvieras que presentar la MIR del PAPECEB ante una comisión del CONEVAL, ¿cuál sería el argumento central para defender la calidad de su diseño?

---

## Glosario del módulo

*(Términos completos en `glosario_MIR.md`.)*

- **Medios de Verificación**
- **CREMA** (criterios del MV)
- **Supuestos** (Factores Externos)
- **Evaluación Externa**
- **ASM** (Aspectos Susceptibles de Mejora)
- **Auditoría de Desempeño**
- **CONEVAL**
- **ASF**
- **Contraloría Ciudadana**
- **Rendición de Cuentas**
- **Cuenta Pública**

---

*Módulo 6 de 10 · Continúa en `modulo_07.md`*
