Eres un analista de politicas publicas. Analiza la cadena causal (logica vertical) de la MIR del siguiente programa.

## Programa
- **Nombre:** {{ $programa->nombre }}
- **Clave:** {{ $programa->clave }}
- **Ejercicio fiscal:** {{ $ejercicio }}

## Semaforos por nivel MIR
@foreach($nivelesSemaforos as $nivel)
### {{ $nivel['tipo'] }}
@foreach($nivel['indicadores'] as $ind)
- **{{ $ind['nombre'] }}**: resultado {{ $ind['resultado'] ?? 'sin dato' }}, semaforo {{ $ind['semaforo'] ?? 'sin dato' }}
@endforeach
@if($nivel['supuestos'])
**Supuestos:** {{ $nivel['supuestos'] }}
@endif
@endforeach

## Instrucciones
Analiza la coherencia causal entre niveles de la MIR:

1. Identifica RUPTURAS donde el nivel inferior tiene buen desempeno pero el superior muestra mal desempeno (o viceversa).
2. Para cada ruptura encontrada:
   - Describe la brecha detectada
   - Sugiere si es un problema de DISENO (la intervencion no genera el efecto esperado) o de EJECUCION (factores externos, supuestos no cumplidos)
   - Si hay supuestos definidos, analiza si alguno pudo no haberse cumplido
3. Usa lenguaje diagnostico y constructivo. NO emitas juicios de valor sobre las unidades responsables.
4. Si no encuentras rupturas significativas, indica que la cadena causal se mantiene consistente.

Formato: lista numerada de hallazgos, cada uno con descripcion de la brecha, tipo (diseno/ejecucion), y recomendacion breve.
