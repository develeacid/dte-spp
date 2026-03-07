# Tablas Stub — Deuda Técnica

## `programas_presupuestarios`

La tabla `programas_presupuestarios` generada en S1-T7 es un stub con los campos mínimos (`clave`, `nombre`).
Será expandida con todos los campos MIR, de alineación y lógica de negocio mediante un alter table en el **Sprint 3 (S3-T1)**.

## `programa_team`

La tabla pivote `programa_team` (generada en S1-T5/S1-T7) usa `string(20)` para el campo `rol` en lugar de un `enum` nativo de PostgreSQL, facilitando extensiones futuras sin necesidad de alterar el tipo de columna.

Valores permitidos actualmente: `coordinadora`, `coadyuvante`. La validación se realiza a nivel de aplicación.
