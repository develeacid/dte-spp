# Registro consolidado de brechas verificadas

> Registro consolidado de brechas verificadas adversarialmente contra el codigo (Fase 3, 2026-06-14). Cada brecha fue confirmada o refutada leyendo el codigo real.

Este documento es la fuente de verdad del estado de cobertura del temario MIR / PbR-SED frente al codigo de los sistemas `dte-spp` y `geobase`. Los veredictos provienen de una verificacion adversarial: cada afirmacion del Informe de Brechas fue contrastada con archivos, migraciones, servicios, prompts y rutas reales.

> **Bitacora de cierre:** las brechas que se van resolviendo se marcan inline con `✅ RESUELTA (fecha)` en su fila de origen y se registran en la seccion **§6 Registro de brechas resueltas**. Al 2026-07-11 hay **10 brechas resueltas**: 6 del sprint "captura del editor MIR" + M07 req 20 (calidad del padron) + M01 req 7/req 27 (pagina Ayuda) + M02 req 4 (Ficha de Informacion Basica). Nota: a partir de M01 se trabaja en **orden de requerimiento** (modulo → req), no por severidad.

---

## 1. Resumen ejecutivo

### Conteo por veredicto

| Veredicto | Cantidad |
| --- | --- |
| Real (brecha confirmada) | 51 |
| Parcial (matizada) | 11 |
| Falso positivo (ya implementado) | 0 |
| **Total** | **62** |

> Nota: ninguna afirmacion del Informe resulto ser un falso positivo limpio (capacidad totalmente presente y mal marcada como ausente). El caso mas cercano es la brecha M03 req 9 (codificacion de Actividades), donde el codigo jerarquico **si existe y si se renderiza** en modulos de tracking/evaluacion, y solo falta inyectarlo en la fila del MirEditor: se clasifica como **parcial** porque el doc sobrestima la ausencia. La seccion 5 documenta estos matices para corregir el Informe.

### Conteo por severidad (sobre el total de brechas)

| Severidad documentada | Cantidad |
| --- | --- |
| Alta | 6 |
| Media | 17 |
| Baja | 35 |
| No indicada | 4 |

### Conteo por sistema

| Sistema | Cantidad |
| --- | --- |
| dte-spp | 42 |
| geobase | 9 |
| ambos | 8 |
| ninguno (contenido didactico puro) | 3 |

---

## 2. Brechas reales (ordenadas por severidad)

### Severidad alta

| Modulo | Req | Brecha | Severidad | Sistema | Evidencia | Recomendacion |
| --- | --- | --- | --- | --- | --- | --- |
| M05 Indicadores | #15, #12 | ✅ RESUELTA (2026-07-11) — Sentido no editable en el editor MIR (Etapa 7); solo se fija por import/CompletarHuecos o queda en default ascendente | alta | dte-spp | `MirEditor.php:264-269` valida solo nombre/tipo/dimension/frecuencia, sin `sentido`; `mir-nivel-row.blade.php` no tiene control; `CompletarHuecos.php:131` si captura sentido via import; columna existe (`create_indicadores_table.php:20`) | Agregar select Ascendente/Descendente en `mir-nivel-row.blade.php` y campo `sentido` al validador de `guardarIndicador()`. El dato y el SemaforoService ya existen |
| M07 Padron | req 20 | ✅ RESUELTA (2026-07-11) — Indicador de calidad del padron ausente: no se calcula ni reporta el % de registros con informacion completa y verificada | alta | ambos | `PadronPrograma.php` `cargarKpis()/mapearVivo()/mapearDesagregados()` (219-289) solo exponen total + desagregados; sin % completos/verificados en geobase ni dte-spp | Agregar KPI/panel `% registros completos y verificados` en `PadronPrograma` (dte-spp) o reporte en geobase |
| M07 Padron | req 23 | P-03 elegibilidad ROP latente: `estaCableado(P03)=false`; requiere modelo ROP en geobase (C-098) | alta | geobase | `PadronErrorCode.php:67-73` retorna false para P03/P04/P06; docblock confirma `P-03 requiere modelo ROP (C-098)`; unica elegibilidad cableada es geografica | Decision arquitectonica documentada (C-098, sprint M pendiente). No cableado hoy = brecha real-latente |
| M07 Padron | req 24 | P-04 duplicidad interinstitucional PUBP latente: `estaCableado(P04)=false`; sin fuente de datos PUBP | alta | geobase | `PadronErrorCode.php:71` (P03/P04/P06 => false); `DuplicateDetectionService` cubre P-02 (CURP intra-padron) pero no PUBP cross-institucional | Requiere acuerdo y fuente de datos PUBP interinstitucional. Brecha real-latente |
| M07 Padron | req 26 | P-06 verificacion CURP en RENAPO latente: `estaCableado(P06)=false`; falta regex 18-char + adapter RENAPO (sprint F2-01) | alta | geobase | `PadronErrorCode.php:71` (P06 => false); CURP solo validado por longitud (`StoreBeneficiaryApiRequest.php:21` max:18, `StoreBeneficiaryRequest.php:28` min:10/max:18) | Decision arquitectonica documentada (sprint F2-01). Misma raiz que req 2 |
| M09 Presupuestacion | req 5 | Modalidad del programa (S/U/E/B) no capturada: no existe campo modalidad en ProgramaPresupuestario; DS-01 escribe modalidad=null y solo se fakea en factory | alta | dte-spp | `ProgramasPublisher.php:34` (`modalidad => null` hardcoded); migracion de programas sin columna modalidad; `PubProgramaFactory.php:19` fakea randomElement; no existe enum Modalidad en `app/Enums/` | Agregar columna/enum modalidad a ProgramaPresupuestario, captura en alta/edicion y propagarla en ProgramasPublisher. Existe boolean `requiere_rop` pero no clasifica la modalidad |

### Severidad media

| Modulo | Req | Brecha | Severidad | Sistema | Evidencia | Recomendacion |
| --- | --- | --- | --- | --- | --- | --- |
| M02 Marco Logico | req 5 | Analisis de Involucrados ausente: no hay modulo, modelo ni etapa para mapear beneficiarios/ejecutores/aliados/opositores | media | dte-spp | `rg -li 'involucrad\|interesad\|stakeholder'` sobre app/ y resources/ sin resultados; el wizard MML salta de SeleccionAlternativas a EmbudoPoblaciones | Anadir entidad/etapa de involucrados entre diagnostico y alternativas que alimente MirSupuesto |
| M02 Marco Logico | req 28 | Teoria de Cambio ausente: no existe documento narrativo de la hipotesis causal | media | dte-spp | `rg -li 'teoria.?cambio\|theory.?of.?change'` sin resultados; no hay campo en ProgramaPresupuestario ni seccion en MirEditor | Anadir campo/artefacto narrativo de Teoria de Cambio (Fase 5) o seccion del MirEditor |
| M02 Marco Logico | req 17 | Criterios de seleccion incompletos: el prompt evaluar-alternativa cubre 3 de 6 criterios; faltan tiempo, impacto, complementariedad; no hay matriz capturable | media | dte-spp | `evaluar-alternativa.blade.php:14-23` solo viabilidad_tecnica/institucional/presupuestal; sin tiempo, impacto, complementariedad ni campos estructurados | Extender el prompt y/o anadir matriz de criterios estructurada con las 6 dimensiones del temario |
| M02 Marco Logico | req 4 | ✅ RESUELTA (2026-07-11) — Cinco preguntas del diagnostico no estructuradas: no existe Ficha de Informacion Basica ni formulario de las 5 preguntas | media | dte-spp | `DefinicionProblema.php` solo expone prop `descripcion` (21) + sugerencia/validacion IA; captura solo el problema central, sin magnitud/situacion/focalizacion/bienes | Anadir Ficha de Informacion Basica con las 5 preguntas estructuradas en/antes de Fase 1 |
| M02 Marco Logico | req 26 | Desagregacion de poblaciones parcial en planeacion: las 3 poblaciones se capturan en totales; la desagregacion demografica solo existe en geobase (DS-G02) | media | ambos | `PoblacionPrograma.php` fillable solo `*_cantidad/_fuente/_justificacion` totales (14-26); en geobase vive en `pub_desagregacion_demografica` | Decision arquitectonica: desagregacion vive en geobase. Para la obligacion metodologica en planeacion anadir campos a PoblacionPrograma |
| M04 Resumen Narrativo | #8, #16 (E-04) | ✅ RESUELTA (2026-07-11, accessor + render) — Codificacion A[n].[m] de Actividades no se muestra ni se valida; el modelo soporta vinculacion (componente_id + orden) pero no hay accessor que produzca 'A1.1' | media | dte-spp | `MirNivel.php` sin accessor de codigo; `MirEditor::agregarActividad:202-216` crea con componente_id+orden sin computar codigo; `mir-nivel-row.blade.php` no renderiza 'A1.1' | Agregar accessor `getCodigoAttribute` que componga `A{componente.orden}.{orden}` y mostrarlo; opcional validar formato en import |
| M04 Resumen Narrativo | #14 | Coherencia RN <-> Supuesto no entra en `validarHorizontal()`: el servicio envia al LLM solo RN + indicadores + medios, no los supuestos | media | dte-spp | `MirLogicaValidacionService.php:66-114` carga `indicadores.mediosVerificacion` y arma data sin `supuestosEstructurados`; el prompt `validar-logica-horizontal` no recibe supuestos | Incluir `supuestosEstructurados` en la consulta y pasarlos al prompt como tercera prueba horizontal |
| M05 Indicadores | #8, #13 | ✅ RESUELTA (2026-07-11) — Valor de la linea base no capturable en la UI; el editor solo expone `guardarLineaBaseAnio()` (ano) | media | dte-spp | `MirEditor.php:560-570` solo persiste `linea_base_anio`; no existe `guardarLineaBase()`; columna `linea_base` existe (migration:21, decimal 12,4) y es fillable (`Indicador.php:34`) | Agregar input numerico para el valor de linea base + setter en MirEditor. Modelo y BD ya lo soportan |
| M05 Indicadores | #11 | ✅ RESUELTA (2026-07-11, migracion + UI + ficha) — Campo Definicion del indicador inexistente (Ficha Bloque 1, <=240 ch); el modelo Indicador no tiene `definicion` | media | dte-spp | `rg 'definicion'` en `Indicador.php` sin resultados; migracion de indicadores (12-34) no incluye columna; sin migracion posterior que la agregue | Crear migracion con columna `definicion` (text, validar <=240 ch en app) + textarea en editor y ficha tecnica |
| M05 Indicadores | #17 | ✅ RESUELTA (2026-07-11) — Catalogo de frecuencias divergente del temario: falta TRIANUAL; `frecuenciasPermitidas()` no incluye Trianual (Fin/Proposito), Anual en Componentes ni Semestral en Actividades | media | dte-spp | `FrecuenciaMedicion.php:7-12` sin TRIANUAL; `IndicadorReglasService.php:48-56` FIN=[ANUAL,BIANUAL,SEXENAL], PROPOSITO=[SEMESTRAL,ANUAL], COMPONENTE=[TRIMESTRAL,SEMESTRAL], ACTIVIDAD=[MENSUAL,TRIMESTRAL] | Agregar case TRIANUAL al enum y ampliar `frecuenciasPermitidas()` segun el temario |
| M08 Cierre Fiscal | req 5/7/9 | El snapshot IAFF nace solo al exportar el Avance Trimestral; su seccion fisica se limita a observaciones con semaforo !=verde, no a la tabla completa de indicadores; Seccion 4 no es narrativa editable | media | dte-spp | `IaffSnapshotService::armarPayload():65-81` sin tabla de indicadores; `IaffConsolidacionService::observaciones():50-51` filtra a !='verde'; `HistorialIaff.php` solo `firmar()`; unica via de generacion es `ExportController::persistirIaff()` | Anadir `generar()` invocable desde HistorialIaff y extender `armarPayload()` con tabla completa de Seccion 2 + narrativa editable de Seccion 4 |
| M08 Cierre Fiscal | req 22/24/25 | Las 4 fases del cierre fiscal (prevalidacion/consolidacion/firma/cerrado) no mapean 1:1 a las del temario (conciliacion/calculo definitivo/informe/lecciones); calculo definitivo e informe consolidado no son pasos explicitos | media | dte-spp | `EstadoCierreFiscal.php:7-10` define PREVALIDACION/CONSOLIDACION/FIRMA/CERRADO; `CierreFiscalService` solo iniciar/avanzar/validarGate; `validarGate:52-56` solo valida IAFF Q4 firmado | Renombrar/re-semantizar las fases o anadir steps de calculo definitivo e informe consolidado (hoy dispersos en AcumuladoAnual, CuentaPublica, exports) |
| M08 Cierre Fiscal | req 27 | No hay captura estructurada de lecciones aprendidas en el cierre fiscal (Fase 4 del temario) | media | dte-spp | `rg -in 'lecciones aprendidas\|leccion_aprendida'` sin resultados; CierreFiscal solo persiste estado/historial JSONB | Anadir entidad `LeccionAprendida` vinculada al CierreFiscal (o reusar ASM como insumo estructurado) |
| M09 Presupuestacion | req 6/req 23 | Clasificador por objeto del gasto no estructurado por capitulo: `clave_partida` es string(20) libre, sin catalogo COG ni desglose, sin reglas del cap. 4000 | media | dte-spp | `create_partidas_presupuestales_table.php:16` (`string('clave_partida',20)`); partida solo descripcion/monto_aprobado/monto_modificado; sin ClasificadorObjetoGasto/CatalogoCog; `ModificacionPresupuestalService` sin logica 4000 | Modelar catalogo COG (capitulo/subcapitulo) y validaciones de adecuacion por capitulo; el candado del cap.4000 depende del ROP versionado (req 8) |
| M10 Evaluacion | req 8 | Programa Anual de Evaluacion (PAE): no existe entidad ni pantalla para programar que programas se evaluan en el ejercicio | media | dte-spp | `rg` sobre app/ y database/ no halla modelo/migracion PAE/PAEE; el dominio Evaluation/ no tiene entidad de planeacion previa; EvaluacionExterna registra evaluaciones ya ejecutadas | Crear entidad catalogo en Evaluation/ que liste programas a evaluar por ejercicio, o documentar como documento normativo externo |
| M10 Evaluacion | req 26 | Reporte semestral de avance ASM: el avance se sobrescribe, no hay historial versionado ni periodicidad semestral forzada | media | dte-spp | `create_asms_table.php:35-37` columnas directas porcentaje_avance/observacion_ultimo_avance/status (se sobrescriben); no existe tabla asm_avances; `AsmFormData` escribe sin versionar | Tabla `asm_avances` con `fecha_corte` (analoga a revisiones_meta) en vez de sobrescribir en asms |

### Severidad baja

| Modulo | Req | Brecha | Severidad | Sistema | Evidencia | Recomendacion |
| --- | --- | --- | --- | --- | --- | --- |
| M01 Fundamentos | req 27 | ✅ RESUELTA (2026-07-11) — No existe pagina de glosario de terminos (PbR, GpR, SED, Ciclo Presupuestario, etc.) en la aplicacion | baja | ninguno | `rg -i 'glosario\|glossary'` sobre app/resources/routes sin coincidencias; solo existe `help-label.blade.php` (tooltip de campo); sin item de navegacion ayuda/glosario | Contenido didactico del temario (glosario_MIR.md), no funcionalidad. Cerrar opcional: vista estatica de ayuda/glosario en la navegacion |
| M02 Marco Logico | req 18 | No duplicidad ausente: la seleccion de alternativas no revisa duplicidad/complementariedad contra otros programas | baja | dte-spp | `rg -in 'duplicid\|complementar'` sobre `SeleccionAlternativas.php` y `evaluar-alternativa.blade.php` sin coincidencias; el prompt solo evalua 3 dimensiones de viabilidad | Agregar criterio de duplicidad/complementariedad y revision cruzada contra programas existentes |
| M02 Marco Logico | req 15 | Prueba si/entonces no explicita en arbol de objetivos: la coherencia vertical se valida en la MIR (etapa 7), no en etapa 3 | baja | dte-spp | `rg -in 'si.?entonces\|logica.?vertical'` sobre `ArbolObjetivosBuilder.php` sin resultados; la validacion existe en `MirLogicaValidacionService.php` (etapa 7), no en etapa 3 | Si se requiere la prueba guiada en etapa 3, anadir UI dedicada. La coherencia existe en otra etapa |
| M02 Marco Logico | req 10 | Verificabilidad causal sin campo de evidencia: los nodos del arbol no tienen campo de fuente/evidencia | baja | dte-spp | `create_arbol_nodos_table.php`: columnas arbol_id/parent_id/tipo_nodo/descripcion/nodo_origen_id/orden, sin fuente/evidencia/dato | Anadir columna fuente/evidencia opcional a `arbol_nodos` si se quiere exigir verificabilidad |
| M02 Marco Logico | req 11 | Arbol no exhaustivo sin tope: no hay regla/advertencia que limite el numero de causas raiz | baja | dte-spp | `ArbolProblemaBuilder.php`: `agregarNodo` solo valida `required\|min:10\|max:500` (80); sin conteo/advertencia sobre numero de causas | Anadir advertencia blanda al superar ~5 causas si se desea reforzar la regla 4 |
| M02 Marco Logico | req 24 | Brecha de cobertura sin etiqueta: modelada (potencial - objetivo) pero sin accessor/etiqueta explicita, a diferencia de la brecha de desempeno | baja | dte-spp | `PoblacionPrograma.php` define `brechaAtendida` (objetivo-atendida, 59-66) pero NO `brechaCobertura` (potencial-objetivo); el blade embudo solo muestra brecha_atendida | Anadir accessor `brechaCobertura` (potencial-objetivo) y mostrarlo en el embudo, simetrico a brecha_atendida |
| M02 Marco Logico | req 6/29 | Supuestos sin trazabilidad a involucrados/Teoria de Cambio: existen estructurados pero no se derivan de esos analisis | baja | dte-spp | `MirSupuesto.php` sin campos de origen/involucrado/teoria; los booleans es_externo/es_relevante/probabilidad_razonable existen sin FK ni trazabilidad a las entidades ausentes (#5 y #28) | Dependiente de #5 y #28: una vez existan involucrados/Teoria de Cambio, vincularlos a MirSupuesto |
| M04 Resumen Narrativo | #7 | La sintaxis de Actividad valida la prosa (sustantivo deverbal, prohibe infinitivo) pero no verifica que incluya/derive el codigo A[n].[m] | baja | dte-spp | `validar-sintaxis-actividad.blade.php`: reglas sustantivo deverbal/no infinitivo/una oracion; JSON is_valid/issues/suggestion; sin mencion del codigo A[n].[m] | Consecuencia directa de #8: si no se computa el codigo, el prompt no puede validar su presencia. Depende de implementar primero el accessor |
| M04 Resumen Narrativo | #4, #6 | Necesidad/suficiencia de componentes y 'dos poblaciones/atribucion' del Proposito quedan a juicio del LLM; UNIQUE BD impide dos PROPOSITOS pero no dos poblaciones en un enunciado | baja | dte-spp | `validar-logica-vertical.blade.php:22-23` son reglas evaluadas por el LLM, no codigo determinista; `add_unique_constraints_mir.php:26` crea indice unico de 1 PROPOSITO por programa pero nada sobre dos poblaciones en un enunciado | Cobertura por IA razonable para reglas semanticas. UNIQUE de un PROPOSITO confirmado. No requiere accion salvo reforzar con heuristicas opcionales |
| M05 Indicadores | #10, #16 | Sin validacion de nombre neutral del indicador; `guardarIndicador()` valida solo `required\|string\|max:255`, no detecta terminos direccionales | baja | dte-spp | `MirEditor.php:265` (`nombre => required\|string\|max:255`); `rg 'incremento\|reduccion\|aumento\|mejora\|neutral\|direccional'` en `IndicadorReglasService.php` y app/database sin resultados | Anadir advertencia de diagnostico (no bloqueo) en `IndicadorReglasService` que detecte terminos direccionales en el nombre |
| M06 MV/Supuestos | req 6 | Formato estructurado del MV: campos separados existen pero no hay validacion de completitud ni deteccion de citas vagas; falta nombre especifico + organismo + periodicidad como formato obligatorio | baja | dte-spp | `MirEditor::guardarMedioVerificacion:321-328`: reglas solo `nombre=required`, organismo/url/frecuencia nullable; sin regla de completitud ni deteccion de citas vagas | Agregar advertencia/regla que exija organismo+frecuencia para FIN/PROPOSITO y detecte nombres genericos. El modelo ya separa los 4 campos |
| M06 MV/Supuestos | req 10 | Prueba 'si/entonces inverso' no asistida: los 3 atributos capturan el resultado pero no hay wizard que guie las 2 preguntas ni validacion IA del supuesto (a diferencia del MV con 'Validar CREMA') | baja | dte-spp | No existe `validarSupuesto` en `MirEditor.php` (solo agregar/guardar/eliminar); `guardarSupuesto:168-173` valida descripcion+3 booleans sin IA; no existe prompt `validar-supuesto.blade.php` | Anadir boton 'Validar supuesto' con prompt IA analogo a validar-crema-mv, o wizard de 2 preguntas |
| M06 MV/Supuestos | req 12 | No hay deteccion automatica de los 5 errores frecuentes de redaccion de supuestos; 'mezclar dos condiciones' no tiene contraparte | baja | dte-spp | `guardarSupuesto:168-175` no valida texto de redaccion; `rg 'mezclar\|condicion interna\|catastrofico\|trivial\|redaccion'` sin coincidencias en validacion; sin prompt validar-supuesto | Implementar validacion IA de redaccion del supuesto que detecte los 5 antipatrones (mismo prompt sugerido en req 10) |
| M06 MV/Supuestos | req 18 | Actores de verificacion (SHCP/CONEVAL/ASF/Contraloria Ciudadana/Control Legislativo) sin representacion en UI: sus funciones estan implementadas pero no hay glosario ni pantalla que los enumere | baja | ambos | `rg 'ASF\|Auditoria Superior\|Contraloria Ciudadana\|Control Legislativo'` en resources/views/ (sin prompts) sin vista; menciones SHCP/CONEVAL solo en prompts IA y padron-programa | Opcional: anadir seccion de ayuda/glosario in-app. El sistema ya respeta las funciones a nivel de datos/reglas |
| M08 Cierre Fiscal | req 12 | El campo 'causa' es texto libre en CapturaAvance; no hay clasificacion interna/externa ni enlace al supuesto fallido del nivel | baja | dte-spp | `CapturaAvance.php:33` struct {dato,causa,accion,proyeccion} todos string; regla `causa=required\|string\|min:5` (193); sin campo es_interna/supuesto_id ni relacion a mir_supuestos | Anadir selector interna/externa y FK opcional al supuesto del nivel en `avances.analisis_desviacion` o columna propia |
| M08 Cierre Fiscal | req 13 | La ventana de captura es unica y configurable (`tracking.dias_ventana_captura`, default 30), no los plazos diferenciados por trimestre (T4 +45 dias) del temario | baja | dte-spp | `CalendarioService.php:27-28` calcula fecha_cierre uniforme; sin rama por trimestre ni constante 45; `CalendarizacionService` solo persiste fechas provistas | Parametrizar dias por trimestre (map/config array) en CalendarioService para soportar 30/30/30/45 |
| M08 Cierre Fiscal | req 21 | No existe entidad ni UI de minuta de reunion de seguimiento (operativo/gestion/directivo) | baja | dte-spp | `rg -in 'minuta\|reunion_seguimiento'` sin entidad/modelo/migracion; unica coincidencia 'minutas' es un string descriptivo de MV en `Fase1PlaneacionMmlSeeder.php:712` | Crear entidad Reunion/Minuta en dominio Tracking con tipo (operativo/gestion/directivo). Instrumento de gestion interna, no normativo duro |
| M08 Cierre Fiscal | req 35 | Se asigna UR Coadyuvante a componentes/actividades pero no responsable nominal por tarea ni vista Gantt como describe el POA del temario | baja | dte-spp | `MirEditor::asignarUrCoadyuvante:920-949` asigna team con rol 'coadyuvante' a nivel; `rg -in 'gantt\|responsable_nominal\|responsable.*tarea'` sin resultados | Modelar responsable nominal por actividad/tarea y una vista de calendario/Gantt para cumplir el POA del temario |
| M09 Presupuestacion | req 7 | Diagnostico no exportable como documento consolidado: se construye via MML pero no hay export 'Documento de diagnostico' unico ni control de antiguedad (revisar cada 3 anos) | baja | dte-spp | `ExportController.php:34-39` solo soporta mir/ficha-tecnica/avance-trimestral/evaluacion-anual/transversal/fmye, sin 'diagnostico'; sin control de antiguedad del diagnostico | Agregar export consolidado de diagnostico (problema + arboles + embudo) y campo de fecha/antiguedad con alerta de revision trienal |
| M09 Presupuestacion | req 25 | Causa interna vs externa de desviacion no tipificada: el analisis captura dato/causa/accion/proyeccion pero no clasifica la causa | baja | dte-spp | `CapturaAvance.php:33-38` array con solo 4 keys; reglas 192-195 y persistencia 252-255 igual; sin key tipo_causa ni causa_interna/externa | Agregar enum `tipo_causa` (interna/externa) al JSONB `analisis_desviacion` para soportar justificacion normativa de revisiones de meta a la baja |
| M10 Evaluacion | req 2 | Independencia del evaluador: solo se captura `evaluador_externo` como texto libre; sin registro estructurado ni declaracion de no conflicto de interes | baja | dte-spp | `EvaluacionExternaFormData.php:19,35` campo string `evaluador_externo` (`required\|string\|max:255`); sin FK a catalogo ni campo de declaracion de conflicto | Principio normativo (no regla de datos). Para cerrar: modelo Evaluador (RFC/contrato) + booleano/declaracion de no-conflicto |
| M10 Evaluacion | req 9 | Terminos de Referencia (TdR): no hay captura de alcance/metodologia/productos/calendario como documento previo; la metodologia es texto libre dentro del informe | baja | dte-spp | `EvaluacionExternaFormData.php` sin campos de TdR; la metodologia vive como texto del `InformeEvaluacion`, posterior al contrato; `rg 'terminos referencia/tdr'` vacio | Agregar seccion/campos TdR en EvaluacionExterna antes del informe. Puede considerarse documento normativo previo al contrato |
| M10 Evaluacion | req 10 | Ciclo de comentarios al borrador: `EstadoEvaluacionExterna` solo tiene en_proceso/concluida; no hay estado borrador ni flujo de comentarios de la UR | baja | dte-spp | `EstadoEvaluacionExterna.php:7-8` solo EN_PROCESO y CONCLUIDA; `InformeEvaluacionEditor` edita inline AJAX sin estado intermedio ni entidad de comentarios | Estados adicionales (borrador/en_revision_ur) + tabla de comentarios. Paso procesal CONEVAL fuera del sistema |
| M10 Evaluacion | req 27 | Verificacion de ASM: se captura evidencia_url y fecha_cumplimiento, pero no hay rol verificador externo ni estado 'verificado' distinto de 'cumplido' | baja | dte-spp | `StatusAsm.php:7-9` solo PENDIENTE/EN_PROCESO/CUMPLIDO (sin VERIFICADO); `AsmFormData.php:34,42` capturan fecha_cumplimiento/evidencia_url sin verificador_id | Estado VERIFICADO + rol verificador. La verificacion CONEVAL es paso aparte; puede documentarse como paso normativo externo |

### Severidad no indicada

| Modulo | Req | Brecha | Severidad | Sistema | Evidencia | Recomendacion |
| --- | --- | --- | --- | --- | --- | --- |
| M03 Arquitectura MIR | req 10 | Informacion presupuestal embebida en la celda de Actividades: el costeo existe pero vive en ClavePresupuestalEditor / CapturaAvanceFinanciero, no en la celda MIR; sin puente visible costo<->Componente | no_indicada | dte-spp | `mir-editor.blade.php` y `MirEditor.php` sin referencia a costo/presupuesto/partida/financiero; modulos de costeo separados (`ClavePresupuestalEditor.php`, `CapturaAvanceFinanciero.php`) sin vinculo a mir_nivel/componente | El temario pide costeo por Componente visible en la celda; cerrar requiere vinculo modelo costo<->componente y mostrarlo en la fila. Brecha de integracion UX |
| M03 Arquitectura MIR | req 19 | Ficha de Informacion Basica como documento unico: sus contenidos existen dispersos en etapas 1/5/6 del wizard pero no hay pantalla/exportable nominal que los consolide | no_indicada | dte-spp | `grep 'informacion.basica\|ficha.basica\|FichaBasica'` vacio; constituyentes en DefinicionProblema (e1)/EmbudoPoblaciones (e5)/AlineacionEstrategica (e6); exports PDF no incluyen ficha-informacion-basica | Crear vista/export que agregue problema+poblaciones+justificacion+alineacion. Baja prioridad funcional (informacion ya capturable) |
| M03 Arquitectura MIR | req 22 | EAPp / Teoria de Cambio como artefacto explicito: el flujo arbol->alternativas->MIR existe (con prellenado) pero no hay pantalla nominal 'Estructura Analitica / Teoria de Cambio' | no_indicada | dte-spp | `grep 'eapp\|teoria.de.cambio\|estructura.analitica'` vacio; el paso intermedio existe como `SeleccionAlternativas.php` (e4) que prellena MirEditor, sin componente ni export con ese nombre | El flujo causal existe operativamente; cerrar = pantalla/export que narre la cadena causal. Brecha de artefacto explicito, no de capacidad |
| M07 Padron | req 2 | CURP solo valida longitud (min:10/max:18), sin regex RENAPO de 18 caracteres ni adapter; ligado a P-06 latente | no_indicada | geobase | `StoreBeneficiaryApiRequest.php:21` (`max:18`) y `StoreBeneficiaryRequest.php:28` (`min:10\|max:18`); sin regla `regex:` con patron CURP de 18 chars | Agregar regla regex del patron CURP RENAPO en ambos FormRequests + adapter RENAPO (sprint F2-01) |
| M07 Padron | req 7 | Grupo etario sin catalogo por ROP en captura; solo se exige fecha_nacimiento y los buckets se materializan en agregacion, no como catalogo configurable por programa | no_indicada | geobase | `ReportQueryBuilder.php:18-24` buckets `grupo_edad` hardcodeados via CASE sobre AGE(fecha_nacimiento); `StoreBeneficiaryApiRequest.php:28` solo pide fecha_nacimiento; sin catalogo de grupos por ROP | Si se requiere grupos por ROP, agregar catalogo configurable por programa. Hoy los buckets son la PP mexicana fija. Brecha acotada |
| M07 Padron | req 14 | Actividad A1.2 cruce de elegibilidad parcial: CONAPO existe como dato pero no como regla bloqueante; PUBP y RENAPO no integrados (depende de P-03/P-04/P-06) | no_indicada | geobase | `MarginacionIndice(Localidad).php` existen como modelos+import pero no se referencian en Requests/Rules/EnrollmentService como regla bloqueante; la elegibilidad cableada es solo geografica (`EnrollmentService.php:84,115`) | A1.1 y A1.3 cableadas; A1.2 depende de P-03/P-04/P-06 latentes. Decision arquitectonica (C-098 ROP en geobase) |
| M07 Padron | req 16 | EnrollmentStatus no distingue baja temporal (con reprogramacion) de baja definitiva; CANCELADO/RECHAZADO cubren baja documentada pero no 'no recibio en el periodo pero sigue elegible' | no_indicada | geobase | `EnrollmentStatus.php:7-13` enum SOLICITADO/EN_REVISION/APROBADO/RECHAZADO/OBSERVADO_DOMICILIO/FINALIZADO/CANCELADO; `isTerminal()` incluye RECHAZADO/FINALIZADO/CANCELADO; sin 'baja_temporal' ni reprogramacion | Anadir estado/causal 'baja temporal' al enum con transicion de reactivacion/reprogramacion si el negocio lo requiere |
| M07 Padron | req 19 | Archivo de Bajas sin vista dedicada: bajas consultables por filtro de estado en EnrollmentList, pero no hay pantalla/exportable 'Archivo de Bajas' con causal normativa | no_indicada | geobase | `EnrollmentList.php:81` filtra por status y `:100` lista cases; `grep 'Archivo de Bajas/ArchivoBajas'` vacio; sin vista ni export dedicado de bajas | Crear vista/reporte 'Archivo de Bajas' filtrando RECHAZADO/CANCELADO con causal + export para auditoria, o reporte en dte-spp (workaround via filtro existe) |

---

## 3. Brechas parciales (matizadas)

| Modulo | Req | Brecha | Severidad | Sistema | Evidencia | Por que se matiza |
| --- | --- | --- | --- | --- | --- | --- |
| M01 Fundamentos | req 7 | ✅ RESUELTA (2026-07-11) — Marco normativo federal/estatal (LFPRH art.111, LGCG art.46-III-C, lineamientos SHCP-CONEVAL) no se expone como pagina informativa/didactica; el sistema solo registra fundamentos legales por programa | baja | dte-spp | `FundamentoForm.php` (form scoped a programa: tipo/ordenamiento/articulo/descripcion/nivel_jerarquia); `routes/web/juridico.php` sin ruta ayuda/glosario/marco-normativo; PERO `CatalogoOrdenamientosSeeder.php` SI siembra LGCG y LFPRH como ordenamientos seleccionables | El sistema SI conoce LFPRH/LGCG como catalogo referenciable y permite registrar articulos por programa; lo que falta es solo la capa didactica/informativa. Contenido conceptual del temario, no funcionalidad de negocio |
| M02 Marco Logico | req 27 | EAPp no es artefacto explicito: existe implicita (arbol objetivos->MIR, dashboard jerarquico) pero no como documento EAPp nombrado/exportable | baja | dte-spp | `rg -li 'eapp\|estructura.?analitica'` sin resultados, pero la estructura Fin->Proposito->Componentes->Actividades se materializa via enum TipoNodo (`ArbolObjetivosBuilder::transformarNodo`) y MirEditor | La capacidad subyacente existe; falta solo el documento/exportable nombrado EAPp. Decision de diseno: si el temario exige el artefacto de verificacion, generar un export nombrado desde el arbol |
| M02 Marco Logico | req 26 | Desagregacion de poblaciones parcial: las 3 poblaciones se capturan en totales; la desagregacion demografica solo existe en geobase (DS-G02 padron), no en la cuantificacion planeada | media | ambos | `PoblacionPrograma.php` fillable solo totales (14-26); en geobase vive en `pub_desagregacion_demografica` (CLAUDE.md) | La capacidad existe pero en geobase, no en la planeacion. Decision arquitectonica: desagregacion como dato de cobertura |
| M03 Arquitectura MIR | req 9 | ✅ RESUELTA (2026-07-11) — Codificacion visible de Actividades (A1.1, A1.2...): la pertenencia es relacional (componente_id) y hay orden, pero `mir-nivel-row.blade.php` solo muestra el badge generico 'Actividad' sin el codigo jerarquico | no_indicada | dte-spp | `Trazabilidad.php:47-69` (`clave()` => 'PROG-c1-a1', `claveActividad()`) y `nivelCorto():96-104` ('A{act} . C{comp}') SI computan el codigo; se renderiza en `indicador-badge.blade.php:7` y en tracking; PERO `mir-nivel-row.blade.php:11-16` solo muestra `$tipoEnum?->label()` sin llamar trazabilidad() | El codigo jerarquico SI existe y SI se renderiza como badge en tracking/evaluacion; solo falta inyectarlo en la celda del MirEditor. El doc sobrestima la ausencia (no es que no exista, es que no se rinde EN ESA vista) |
| M05 Indicadores | #3 | Dimensiones bloqueadas como reglas duras vs. recomendaciones del temario; `dimensionesPermitidas()` bloquea duro por nivel (p.ej. Calidad no disponible en Proposito/Fin) | baja | dte-spp | `IndicadorReglasService.php:38-46` retorna listas cerradas por nivel y `MirEditor.php:267` las aplica como regla dura (`required\|in:...`); el doc reconoce que coincide con la intencion normativa | El bloqueo duro coincide con la intencion normativa MIR; la 'brecha' es solo que el temario lo presenta como soft. No es carencia funcional sino decision de rigidez. Si se requiere capturar excepciones, convertir a advertencia |
| M05 Indicadores | #13 | Sin campo 'Ano de la meta' separado; la meta se vincula al ejercicio via calendarizacion (CalendarizarMetas) y no como atributo ano de la meta | baja | dte-spp | `rg 'anio_meta'` en `Indicador.php` y el partial sin resultados; la meta se calendariza por ejercicio (C-146 / CalendarizarMetas), el ano esta implicito en el MetaPeriodo | El ano de la meta esta modelado via calendarizacion por ejercicio (MetaPeriodo), no como atributo plano. La informacion existe en otra entidad; para la ficha bastaria derivar el ano del periodo |
| M07 Padron | req 28 | P-08 valida monto como tope (monto_entregado <= monto_unitario) via MontoNoExcedeComponente, no como campo calculado/no editable desde ROP (ROP no modelada, C-098) | no_indicada | geobase | `MontoNoExcedeComponente.php:23-40` falla si value > monto_unitario; docblock 10-15 declara que es 'proxy de las ROP mientras no exista ReglasOperacion (C-098)'; `estaCableado(P08)=true` (`PadronErrorCode.php:70`) | P-08 esta cableado como validacion preventiva (tope), no ausente; el doc lo marca parcial correctamente. Para campo calculado no-editable se requiere modelo ROP (C-098) |
| M08 Cierre Fiscal | req 30 | Existen las piezas (RevisionMeta para MIR, POA, ROP en geobase) pero no un flujo que conecte una leccion/ASM con el ajuste del siguiente ejercicio | baja | ambos | `RevisionMeta.php` existe y UR coadyuvante en `MirEditor::asignarUrCoadyuvante:920`; pero ROP/ReglasOperacion NO existe en geobase (solo comentario C-098 en `MontoNoExcedeComponente.php`); el Asm tiene recomendacion_id (C-143) pero sin enlace formal leccion/ASM -> ajuste MIR/POA del ciclo siguiente | El doc subestima: la pieza ROP ni siquiera esta implementada en geobase aun. El flujo de ciclo completo requiere primero el modelo ROP y luego un vinculo ASM->RevisionMeta |
| M09 Presupuestacion | req 8 | ROP versionado (montos/criterios) no consumido: el PDF de ROP se sube en dte-juridico, pero el ROP estructurado/versionado vive en geobase (C-098) y aun no hay endpoint consumido por dte-spp | media | ambos | dte-spp: ROP como documento si existe (`TipoDocumentoNormativo::REGLAS_OPERACION`, `TipoSustentoLegal::REGLA_OPERACION`); `GeoBaseClient` sin metodo getRop/fetchRop; geobase: NO existe modelo ReglasOperacion (comentario C-098) | El doc es correcto: el PDF existe y el ROP estructurado no se consume. Decision arquitectonica documentada (C-098): el modelo se implementara en geobase y dte-spp lo consumira via API M2M; hasta entonces P-03/P-08 quedan latentes |
| M09 Presupuestacion | req 15 | Expediente tecnico sin vista consolidada ni checklist de completitud: los 8 documentos existen dispersos pero no hay pantalla 'Expediente tecnico' que liste presencia/ausencia | media | dte-spp | No existe ruta ni vista 'expediente'; lo mas cercano es `EstadoConsolidadoService::resumen()` que consolida solo 3 areas (planeacion/juridico/financiero, `total=3`), NO los 8 documentos; checklist juridico en `ValidacionJuridicaService` | La carencia del indice de los 8 documentos es real, pero el doc omite que ya existe `EstadoConsolidadoService` (consolidacion de 3 areas) que podria extenderse a un checklist de completitud documental por programa |
| M09 Presupuestacion | req 19 | Validacion de alineacion real (4 condiciones) no formalizada: la cadena y el embudo dan soporte parcial pero no hay validador de contribucion verificable / coherencia tematica / no duplicidad | baja | ambos | `rg 'contribucion verificable\|no duplicidad\|coherencia tematica\|validarAlineacion'` sin matches; existen `CadenaAlineacion.php` y `MatrizAlineacionManager` que muestran la cadena/embudo pero no validan las 4 condiciones | El soporte visual de la cadena existe pero no el validador formal. Implementar las 4 condiciones apoyandose en `vw_alineacion_completa` y el cruce con padron geobase |
| M10 Evaluacion | req 28 | Alerta de ASM no cumplidos en plazo: existe SemaforoAsm y filtros, pero no hay alerta/notificacion automatica por ASM vencido (vinculable a ASF) | baja | dte-spp | `SemaforoAsm.php:24` calcula estado VENCIDO y se muestra como badge por fila; `AsmReportService.php:35` cuenta 'vencidos'; PERO los KPIs de `AsmIndex.php:70-75` NO incluyen vencidos y no hay notification/job programado para ASM | El doc subestima: si existe el semaforo VENCIDO visual por fila y conteo en el export, pero falta (a) KPI de vencidos en el index UI y (b) notificacion/job programado. La derivacion a ASF es normativa |

---

## 4. Falsos positivos (ya implementado)

No se identifico ningun falso positivo limpio: ninguna afirmacion del Informe describio como totalmente ausente una capacidad que estuviera completamente implementada. El caso que mas se aproxima fue reclasificado como **parcial** y se documenta abajo para corregir el Informe.

| Modulo | Req | Afirmacion del doc | Evidencia de que SI existe (parcialmente) |
| --- | --- | --- | --- |
| M03 Arquitectura MIR | req 9 | "La codificacion de Actividades (A1.1, A1.2...) no se muestra; la fila solo tiene el badge generico 'Actividad'" | El codigo jerarquico SI existe y SI se renderiza fuera del MirEditor: `Trazabilidad::clave()` produce 'PROG-c1-a1', `Trazabilidad::claveActividad()` y `nivelCorto()` ('A{act} . C{comp}') lo computan (`app/Support/Mml/Trazabilidad.php:47-104`); se pinta como badge en `components/data/indicador-badge.blade.php:7` y en `tracking/detalle-indicador.blade.php`. La unica carencia real es que `mir-nivel-row.blade.php:11-16` no llama `trazabilidad()`. Correccion al Informe: la capacidad existe, solo falta inyectarla en esa vista |

> Recomendacion de saneamiento del Informe: cuando una afirmacion diga "no existe X", verificar primero si X existe en otra vista/servicio antes de marcarla ausente. La brecha M03 req 9 ilustra el patron de sobrestimacion: el artefacto existe globalmente y solo falta su renderizado en un punto especifico.

---

## 5. Decisiones arquitectonicas (no-brechas)

Las siguientes brechas reales-latentes NO son fallas de implementacion sino consecuencia de decisiones arquitectonicas cross-sistema documentadas. Su cierre depende de sprints o acuerdos externos, no de corregir codigo defectuoso.

### C-098 — ROP versionado vive en GEOBASE

El modelo `ReglasOperacion` (versionado por ejercicio, montos autorizados, criterios de elegibilidad) se implementara en geobase como sprint M, no en dte-spp. Hasta que exista el endpoint, dte-spp consume el ROP solo como documento PDF (`TipoDocumentoNormativo::REGLAS_OPERACION`), y `GeoBaseClient` no tiene metodo `getRop`. Brechas afectadas:

- **M09 req 8** (ROP estructurado no consumido) — parcial: el PDF existe, el modelo estructurado no.
- **M09 req 6/req 23** (candado cap. 4000) — depende del ROP versionado.
- **M07 req 28** (P-08 como campo calculado no-editable) — hoy cableado como tope proxy (`MontoNoExcedeComponente`), no como derivacion desde ROP.
- **M08 req 30** (flujo leccion/ASM -> ajuste del ciclo siguiente) — requiere primero el modelo ROP en geobase.

### P-03 / P-04 / P-06 latentes (`PadronErrorCode::estaCableado() == false`)

Las tres validaciones del padron permanecen no cableadas por dependencia de fuentes/modelos externos:

- **M07 req 23 — P-03 elegibilidad ROP**: requiere el modelo ROP (C-098, sprint M pendiente).
- **M07 req 24 — P-04 duplicidad interinstitucional PUBP**: requiere acuerdo y fuente de datos PUBP cross-institucional (dependencia externa, no implementable hasta tener el convenio/feed).
- **M07 req 26 / req 2 — P-06 verificacion CURP en RENAPO**: requiere regex 18-char + adapter RENAPO (sprint F2-01). Hoy CURP solo se valida por longitud.

`DuplicateDetectionService` ya cubre P-02 (duplicidad de CURP intra-padron); la elegibilidad cableada actual es exclusivamente geografica (lat/lng vs area del programa en `EnrollmentService` y `CheckEligibilityOnLocationUpdate`).

### Desagregacion demografica vive en geobase (DS-G02)

**M07 — la desagregacion por sexo/edad/etnia/discapacidad** vive en el padron (`pub_desagregacion_demografica`), no en la cuantificacion planeada de `PoblacionPrograma` (dte-spp). Es una decision arquitectonica: la desagregacion es dato de cobertura operativo. Cerrar la obligacion metodologica en planeacion requeriria anadir campos de desagregacion a `PoblacionPrograma` (brecha M02 req 26, parcial).

---

## 6. Registro de brechas resueltas

Bitacora de brechas cerradas con codigo verificado. Cada entrada apunta a los commits/artefactos que la cierran. Las filas de origen en §2/§3 quedan marcadas inline con `✅ RESUELTA`.

### Sprint "Completar la captura del editor MIR" (2026-07-11)

Rama `feat/captura-editor-mir`. Diseno: `docs/plans/2026-07-11-captura-editor-mir-design.md`. Plan: `docs/plans/2026-07-11-captura-editor-mir.md`. Suite completa 1600 passed / 14 skipped / 1 risky; verificacion en browser (read + edit mode + persistencia end-to-end).

| Modulo | Req | Brecha | Severidad | Cierre |
| --- | --- | --- | --- | --- |
| M05 Indicadores | #15, #12 | Sentido no editable en el editor MIR | alta | `<select>` Ascendente/Descendente en `mir-nivel-row.blade.php` + regla `sentido` en `MirEditor::guardarIndicador()`. `REGULAR` deprecado no se expone. |
| M05 Indicadores | #8, #13 | Valor de linea base no capturable | media | Setter `MirEditor::guardarLineaBase()` (`nullable\|numeric`) + input junto a "Ano de linea base". |
| M03 req9 / M04 #8 | Codigo A1.1 de Actividades | media / no_indicada | Accessor `MirNivel::codigoMir()` (F/P/C{orden}/A{comp}.{orden}) reusando `Trazabilidad`; render en fila del editor + `mir.blade.php` + `MirSheet` (columna "Codigo") + `ficha-tecnica.blade.php`. |
| M05 Indicadores | #17 | Falta frecuencia TRIANUAL | media | Case `TRIANUAL` en `FrecuenciaMedicion` (orden entre bianual y sexenal) + `frecuenciasPermitidas()` ampliado por nivel (FIN/PROPOSITO +Trianual, COMPONENTE +Anual, ACTIVIDAD +Semestral). |
| M05 Indicadores | #11 | Campo Definicion del indicador inexistente | media | Migracion `indicadores.definicion` (text nullable), fillable, setter `MirEditor::guardarDefinicion()` (`max:240`), textarea en editor + renglon en ficha tecnica. |

**Post-deploy:** `sail artisan migrate` (1 migracion privada, `2026_07_11_000001_add_definicion_to_indicadores`). No toca BD publica.

### Indicador de calidad del padrón (2026-07-11)

Ramas `feat/calidad-padron` (geobase + dte-spp). Diseño/plan:
`geobase/docs/plans/2026-07-11-calidad-padron{-design,}.md`. Tests: geobase
`PadronQualityServiceTest` + `ComponentCoverageTest`, dte-spp
`PadronProgramaCalidadTest`. Verificación E2E en browser con datos reales
(ISM-001: 0% completos / 87.5% verificados).

| Modulo | Req | Brecha | Severidad | Cierre |
| --- | --- | --- | --- | --- |
| M07 Padron | req 20 | Indicador de calidad del padron ausente (% completos/verificados) | alta | **geobase**: `PadronQualityService` (completos por tipo física/moral vía `whereNotNull`; verificados = status aprobado/observado/finalizado) + bloque `quality` en `ComponentController::coverage`. **dte-spp**: `PadronPrograma::mapearVivo()` mapea `kpis['calidad']` + panel "Calidad del padrón" en `kpi-cards` (solo modo vivo; degrada a "—" si el campo falta). |

**Definiciones normativas:** Completo — física: `curp_rfc, nombre, apellidos, fecha_nacimiento, genero, address_municipality, location`; moral: `curp_rfc, razon_social, address_municipality, location`. Verificado — enrollment `status ∈ {aprobado, observado_por_cambio_domicilio, finalizado}` (proxy mientras RENAPO/CURP real sigue bloqueado, F2-01).

**Deploy:** geobase **antes** que dte-spp (degradación elegante). Sin migraciones. Sin BD pública.

### Página de Ayuda: Glosario + Marco Normativo (2026-07-11)

Rama `feat/ayuda-glosario`. Diseño/plan: `docs/plans/2026-07-11-ayuda-glosario{-design,}.md`. Test `AyudaPageTest`. Verificación en browser (tabs Marco Normativo/Glosario con shell completo). **Primera brecha trabajada en orden de requerimiento** (M01), no por severidad.

| Modulo | Req | Brecha | Severidad | Cierre |
| --- | --- | --- | --- | --- |
| M01 Fundamentos | req 7 | Marco normativo no expuesto como página informativa | baja | Página `/ayuda` (Livewire `Ayuda`) tab "Marco Normativo": intro didáctica (LFPRH art.111, LGCG art.46-III-C, Lineamientos SHCP-CONEVAL) + `CatalogoOrdenamiento` agrupado por `nivel_jerarquia`. |
| M01 Fundamentos | req 27 | No existe página de glosario | baja | Tab "Glosario" renderiza `resources/markdown/glosario-mir.md` (copia del `glosario_MIR.md`) vía `Str::markdown()` en contenedor `prose`. Ítem "Ayuda" en el sidebar. |

**Nota:** ruta `GET /ayuda` en el grupo `auth` (sin permiso, todos los roles). Sin migraciones, sin BD pública.

### Ficha de Información Básica — 5 preguntas del diagnóstico (2026-07-11)

Rama `feat/ficha-informacion-basica`. Diseño/plan: `docs/plans/2026-07-11-ficha-informacion-basica{-design,}.md`. Test `DefinicionProblemaFichaTest` (6). Verificación E2E en browser (persistencia + badge de completitud).

| Modulo | Req | Brecha | Severidad | Cierre |
| --- | --- | --- | --- | --- |
| M02 Marco Lógico | req 4 | Cinco preguntas del diagnóstico no estructuradas | media | Tabla `fichas_informacion_basica` (1:1 programa) + modelo `FichaInformacionBasica`. Etapa 1 (`DefinicionProblema`) captura Q2–Q5 (magnitud/focalización/causas-efectos/bienes-servicios) vía `updateOrCreate`; Q1 reutiliza el problema central. Badge de completitud X/5. Q2–Q5 opcionales. |

**Post-deploy:** `sail artisan migrate` (1 migración privada: `2026_07_11_000002_create_fichas_informacion_basica_table`). Sin BD pública. Export "documento único" queda como M03 req 19 (pendiente).
