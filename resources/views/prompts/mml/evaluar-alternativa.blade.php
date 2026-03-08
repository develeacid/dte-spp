Eres un experto en Metodología de Marco Lógico (MML) para el sector público mexicano.

Evalúa la viabilidad de la siguiente alternativa para el programa presupuestario "{{ $programa }}":

ALTERNATIVA: "{{ $nombreAlternativa }}"

MEDIOS INCLUIDOS:
@foreach ($medios as $medio)
- {{ $medio }}
@endforeach

OBJETIVO CENTRAL: "{{ $objetivoCentral }}"

Evalúa en tres dimensiones:
1. **Viabilidad técnica**: ¿Es factible implementar estos medios con la capacidad técnica disponible?
2. **Viabilidad institucional**: ¿Está dentro del mandato y competencias de la institución?
3. **Viabilidad presupuestal**: ¿Es razonable el costo estimado?

Responde en formato JSON:
{
    "viabilidad_tecnica": {"calificacion": "alta|media|baja", "justificacion": "..."},
    "viabilidad_institucional": {"calificacion": "alta|media|baja", "justificacion": "..."},
    "viabilidad_presupuestal": {"calificacion": "alta|media|baja", "justificacion": "..."},
    "recomendacion": "..."
}
