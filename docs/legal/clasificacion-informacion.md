# Política institucional de clasificación de información y datos abiertos

**Documento:** DS-00
**Versión:** 1.0 (borrador)
**Fecha:** 2026-05-02
**Estado:** BORRADOR — pendiente de firma institucional

---

## 1. Marco legal

Esta política se emite con fundamento en:

- **LGTAIP** (Ley General de Transparencia y Acceso a la Información Pública), arts. 70, 113, 114
- **LGPDPPSO** (Ley General de Protección de Datos Personales en Posesión de Sujetos Obligados), arts. 3, 16, 22
- **LGCG** (Ley General de Contabilidad Gubernamental), arts. 56, 58
- **PEF** vigente, Anexos transversales (Anexo 11)

## 2. Niveles de clasificación

### 2.1 Público
Datos que pueden publicarse sin restricciones en datos abiertos. Ejemplos: MIR completas, avances trimestrales agregados, alineación estratégica, catálogos de programas.

### 2.2 Reservado
Datos que se publican con vigencia diferida o con anonimización obligatoria. Ejemplos: cobertura municipal con k-anonimato (k≥5), padrón con identificadores hasheados.

### 2.3 Confidencial
Datos que NO se publican y solo se entregan a autoridades autorizadas (SHCP, ASF, CONEVAL) por canal directo. Ejemplos: padrón con CURP en claro, datos personales completos del beneficiario.

## 3. Criterios de anonimización

- **k-anonimato:** k ≥ 5 obligatorio en toda agregación pública. Celdas con `COUNT < 5` se publican como `"<5"` (literal, no supresión).
- **Sin identificadores directos:** `beneficiary_id`, `curp`, `nombre_completo`, `domicilio` quedan PROHIBIDOS en datos abiertos.
- **Sin identificadores cuasi-directos:** combinaciones que permitan re-identificación (fecha_nacimiento + municipio + sexo) se evalúan caso por caso.

## 4. Vigencia de reserva

- Reserva por defecto: **5 años** desde la fecha de generación del dato.
- Reservas mayores requieren justificación documentada por dataset (vía `retention_days` por programa).
- Después de la reserva, el dato pasa a Público o se destruye según LGPDPPSO.

## 5. Designación del Responsable de Datos Abiertos (RDA)

**Nombre y cargo del RDA:** `[NOMBRE Y CARGO — A COMPLETAR POR EL ENTE]`

**Funciones:**
- Aprobar la liberación de cada dataset antes de su publicación.
- Verificar el cumplimiento de los criterios de anonimización.
- Mantener el catálogo público de datos abiertos actualizado.
- Coordinarse con la Unidad de Transparencia para responder solicitudes de información.

## 6. Catálogo de datasets sujetos a esta política

Esta política aplica a los datasets identificados en el sistema con claves DS-01 a DS-06 (originados en SPP) y DS-G01 a DS-G04 (originados en GeoBase). DS-00 es la presente política institucional.

## 7. Firma

**Titular del Ente Público:**
Nombre: `[A COMPLETAR]`
Cargo: `[A COMPLETAR]`
Fecha de firma: `[A COMPLETAR]`
Firma: ___________________________

**Responsable de Datos Abiertos:**
Nombre: `[A COMPLETAR]`
Cargo: `[A COMPLETAR]`
Fecha de firma: `[A COMPLETAR]`
Firma: ___________________________
