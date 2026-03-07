# Esquema: Plan Nacional de Desarrollo (PND)

Catálogo inmutable de referencia para la Matriz de Alineación.

## Estructura Jerárquica

```
PndEje (Ejes)
└── PndObjetivo (Objetivos)
    └── PndEstrategia (Estrategias)
```

## Tabla: pnd_ejes

| Columna     | Tipo         | Notas                                        |
|-------------|--------------|----------------------------------------------|
| id          | bigint       | PK                                           |
| numero      | tinyint      | 1, 2, 3... UNIQUE                            |
| nombre      | string       | Nombre del eje                               |
| descripcion | text         | Descripción general (nullable)               |
| embedding   | vector(1536) | Búsqueda semántica (null hasta S2-T10)       |
| timestamps  | —            | —                                            |

## Tabla: pnd_objetivos

| Columna    | Tipo         | Notas                                        |
|------------|--------------|----------------------------------------------|
| id         | bigint       | PK                                           |
| pnd_eje_id | FK           | Cascade on delete                            |
| clave      | string(20)   | "1.1", "2.3" UNIQUE                          |
| descripcion| text         | Texto del objetivo (nullable)                |
| embedding  | vector(1536) | Búsqueda semántica                           |
| timestamps | —            | —                                            |

## Tabla: pnd_estrategias

| Columna         | Tipo         | Notas                                   |
|-----------------|--------------|-----------------------------------------|
| id              | bigint       | PK                                      |
| pnd_objetivo_id | FK           | Cascade on delete                       |
| clave           | string(30)   | "1.1.1", "2.3.4" UNIQUE                 |
| descripcion     | text         | Texto de la estrategia (nullable)       |
| embedding       | vector(1536) | Búsqueda semántica                      |
| timestamps      | —            | —                                       |

## Relaciones Eloquent

```php
// Eje
$eje->objetivos;    // HasMany → PndObjetivo
$eje->estrategias;  // HasManyThrough → PndEstrategia via PndObjetivo

// Objetivo
$objetivo->eje;         // BelongsTo → PndEje
$objetivo->estrategias; // HasMany → PndEstrategia

// Estrategia
$estrategia->objetivo;  // BelongsTo → PndObjetivo
```

## Notas

- El archivo `docs/data/pnd-vigente.md` debe actualizarse con los datos oficiales del PND del sexenio vigente.
- Los embeddings se generarán en S2-T10.
- El parser del seeder soporta 3 niveles jerárquicos: `## Eje`, `### Objetivo`, `#### Estrategia`.
