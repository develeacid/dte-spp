<?php

namespace Database\Seeders;

use App\Enums\DimensionIndicador;
use App\Enums\FrecuenciaMedicion;
use App\Enums\SentidoIndicador;
use App\Enums\TipoArbol;
use App\Enums\TipoIndicador;
use App\Enums\TipoNivelMir;
use App\Enums\TipoNodo;
use App\Models\Mml\Alternativa;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\Mml\CremaaValidacion;
use App\Models\Mml\Indicador;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MedioVerificacion;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\Mml\PoblacionPrograma;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use App\Services\Mml\MirSnapshotService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class Fase1PlaneacionMmlSeeder extends Seeder
{
    private int $indicadorGlobalIndex = 0;
    private array $sentidoPool;
    private array $pedObjetivoIds;
    private array $pedLineaIds;

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('No se puede ejecutar este seeder en producción.');
            return;
        }

        $this->command->info('Fase 1: Creando 16 programas con datos MIR completos...');

        // Pre-load PED alignment data
        $this->pedObjetivoIds = PedObjetivoEstrategico::pluck('id')->toArray();
        $this->pedLineaIds = PedLineaAccion::pluck('id')->toArray();

        // Build sentido pool: ~60% asc, ~25% desc, ~15% regular (128 indicators)
        $this->sentidoPool = array_merge(
            array_fill(0, 77, SentidoIndicador::ASCENDENTE),
            array_fill(0, 32, SentidoIndicador::DESCENDENTE),
            array_fill(0, 19, SentidoIndicador::REGULAR),
        );
        shuffle($this->sentidoPool);

        $teams = [
            'SE-001'     => Team::where('clave_ur', 'SE-001')->first(),
            'SS-002'     => Team::where('clave_ur', 'SS-002')->first(),
            'SEG-003'    => Team::where('clave_ur', 'SEG-003')->first(),
            'SECTUR-004' => Team::where('clave_ur', 'SECTUR-004')->first(),
        ];

        $slugMap = ['SE-001' => 'se', 'SS-002' => 'ss', 'SEG-003' => 'seg', 'SECTUR-004' => 'sectur'];

        foreach ($this->programDefinitions() as $def) {
            $team = $teams[$def['ur']];
            $slug = $slugMap[$def['ur']];
            $planeador = User::where('email', "planeador.{$slug}@sistema.test")->first()
                ?? User::where('email', 'like', "planeador.%@sistema.test")->first();

            $this->crearPrograma($def, $team, $planeador, $teams);
        }

        $this->command->info('Fase 1 completada: 16 programas con MIR.');
    }

    // ─── Program Definitions ────────────────────────────────────────────

    private function programDefinitions(): array
    {
        return [
            // SE-001
            ['ur' => 'SE-001', 'clave' => 'ISM-001', 'nombre' => 'Impulso al Sector Mezcalero', 'transversal' => 'SECTUR-004',
             'problema' => 'Baja competitividad del sector mezcalero estatal',
             'objetivo' => 'Incrementar la competitividad del sector mezcalero estatal',
             'poblacion' => ['ref' => 85000, 'pot' => 32000, 'obj' => 8500, 'unidad' => 'Productores'],
             'padron_geobase_activo' => true],
            ['ur' => 'SE-001', 'clave' => 'EDU-002', 'nombre' => 'Educación Básica de Calidad',
             'problema' => 'Bajo rendimiento académico en educación básica',
             'objetivo' => 'Mejorar el rendimiento académico en educación básica',
             'poblacion' => ['ref' => 1200000, 'pot' => 450000, 'obj' => 120000, 'unidad' => 'Estudiantes']],
            ['ur' => 'SE-001', 'clave' => 'EDU-003', 'nombre' => 'Becas para Educación Superior',
             'problema' => 'Alta deserción en educación superior por falta de recursos económicos',
             'objetivo' => 'Reducir la deserción en educación superior mediante apoyo económico',
             'poblacion' => ['ref' => 280000, 'pot' => 95000, 'obj' => 25000, 'unidad' => 'Estudiantes'],
             'padron_geobase_activo' => true],
            ['ur' => 'SE-001', 'clave' => 'EDU-004', 'nombre' => 'Infraestructura Escolar',
             'problema' => 'Deterioro de la infraestructura en planteles educativos',
             'objetivo' => 'Rehabilitar la infraestructura de planteles educativos',
             'poblacion' => ['ref' => 4500, 'pot' => 1800, 'obj' => 600, 'unidad' => 'Planteles']],
            // SS-002
            ['ur' => 'SS-002', 'clave' => 'PEC-001', 'nombre' => 'Prevención de Enfermedades Crónicas',
             'problema' => 'Alta incidencia de enfermedades crónico-degenerativas en la población adulta',
             'objetivo' => 'Reducir la incidencia de enfermedades crónico-degenerativas',
             'poblacion' => ['ref' => 2500000, 'pot' => 800000, 'obj' => 200000, 'unidad' => 'Personas'],
             'padron_geobase_activo' => true],
            ['ur' => 'SS-002', 'clave' => 'SAL-002', 'nombre' => 'Vacunación Universal',
             'problema' => 'Cobertura de vacunación insuficiente en menores de 5 años',
             'objetivo' => 'Ampliar la cobertura de vacunación en menores de 5 años',
             'poblacion' => ['ref' => 350000, 'pot' => 180000, 'obj' => 150000, 'unidad' => 'Menores']],
            ['ur' => 'SS-002', 'clave' => 'SAL-003', 'nombre' => 'Salud Materna e Infantil',
             'problema' => 'Mortalidad materna e infantil por encima de la media nacional',
             'objetivo' => 'Disminuir la mortalidad materna e infantil',
             'poblacion' => ['ref' => 120000, 'pot' => 45000, 'obj' => 30000, 'unidad' => 'Mujeres embarazadas'],
             'padron_geobase_activo' => true],
            ['ur' => 'SS-002', 'clave' => 'SAL-004', 'nombre' => 'Atención Hospitalaria',
             'problema' => 'Saturación de servicios hospitalarios de segundo nivel',
             'objetivo' => 'Mejorar la capacidad de atención hospitalaria de segundo nivel',
             'poblacion' => ['ref' => 3200000, 'pot' => 900000, 'obj' => 350000, 'unidad' => 'Personas']],
            // SEG-003
            ['ur' => 'SEG-003', 'clave' => 'FSP-001', 'nombre' => 'Fortalecimiento de la Seguridad Pública Municipal',
             'problema' => 'Debilidad institucional de los cuerpos de seguridad municipal',
             'objetivo' => 'Fortalecer las capacidades institucionales de seguridad municipal',
             'poblacion' => ['ref' => 570, 'pot' => 250, 'obj' => 120, 'unidad' => 'Municipios']],
            ['ur' => 'SEG-003', 'clave' => 'SEG-002', 'nombre' => 'Prevención del Delito',
             'problema' => 'Incremento de la incidencia delictiva en zonas urbanas',
             'objetivo' => 'Reducir la incidencia delictiva mediante acciones de prevención social',
             'poblacion' => ['ref' => 1800000, 'pot' => 600000, 'obj' => 150000, 'unidad' => 'Personas']],
            ['ur' => 'SEG-003', 'clave' => 'SEG-003P', 'nombre' => 'Reinserción Social',
             'problema' => 'Alta reincidencia delictiva por falta de programas de reinserción',
             'objetivo' => 'Disminuir la reincidencia delictiva mediante programas de reinserción',
             'poblacion' => ['ref' => 12000, 'pot' => 5500, 'obj' => 2800, 'unidad' => 'Personas privadas de libertad']],
            ['ur' => 'SEG-003', 'clave' => 'SEG-004', 'nombre' => 'Protección Civil',
             'problema' => 'Insuficiente capacidad de respuesta ante desastres naturales',
             'objetivo' => 'Fortalecer la capacidad estatal de respuesta ante desastres naturales',
             'poblacion' => ['ref' => 4200000, 'pot' => 1200000, 'obj' => 500000, 'unidad' => 'Personas']],
            // SECTUR-004
            ['ur' => 'SECTUR-004', 'clave' => 'DDT-001', 'nombre' => 'Destinos Turísticos Sustentables',
             'problema' => 'Degradación ambiental de destinos turísticos prioritarios',
             'objetivo' => 'Conservar y rehabilitar los destinos turísticos prioritarios',
             'poblacion' => ['ref' => 45, 'pot' => 20, 'obj' => 12, 'unidad' => 'Destinos turísticos'],
             'padron_geobase_activo' => true],
            ['ur' => 'SECTUR-004', 'clave' => 'TUR-002', 'nombre' => 'Promoción Turística Digital',
             'problema' => 'Baja visibilidad del estado como destino turístico en medios digitales',
             'objetivo' => 'Incrementar la visibilidad turística del estado en plataformas digitales',
             'poblacion' => ['ref' => 5000000, 'pot' => 2000000, 'obj' => 800000, 'unidad' => 'Visitantes potenciales']],
            ['ur' => 'SECTUR-004', 'clave' => 'TUR-003', 'nombre' => 'Turismo Comunitario',
             'problema' => 'Escasa participación de comunidades rurales en la actividad turística',
             'objetivo' => 'Incorporar comunidades rurales a la cadena de valor turística',
             'poblacion' => ['ref' => 2500, 'pot' => 800, 'obj' => 250, 'unidad' => 'Comunidades']],
            ['ur' => 'SECTUR-004', 'clave' => 'TUR-004', 'nombre' => 'Capacitación Sector Hotelero',
             'problema' => 'Baja calidad en el servicio del sector hotelero estatal',
             'objetivo' => 'Elevar la calidad del servicio en el sector hotelero estatal',
             'poblacion' => ['ref' => 18000, 'pot' => 8000, 'obj' => 3500, 'unidad' => 'Trabajadores del sector']],
        ];
    }

    // ─── Main Program Creator ───────────────────────────────────────────

    private function crearPrograma(array $def, Team $team, User $planeador, array $teams): void
    {
        $clave = $def['clave'];
        $this->command->info("  → {$clave}: {$def['nombre']}");

        // A. ProgramaPresupuestario
        $programa = ProgramaPresupuestario::firstOrCreate(
            ['clave' => $clave, 'team_id' => $team->id],
            ['nombre' => $def['nombre'], 'ejercicio_fiscal' => 2025, 'estado' => 'borrador', 'created_by' => $planeador->id]
        );
        $programa->equipos()->syncWithoutDetaching([$team->id => ['rol' => 'coordinadora']]);

        if (isset($def['padron_geobase_activo']) && $def['padron_geobase_activo']) {
            $programa->update(['padron_geobase_activo' => true]);
        }

        if (!empty($def['transversal'])) {
            $coadTeam = $teams[$def['transversal']];
            $programa->equipos()->syncWithoutDetaching([$coadTeam->id => ['rol' => 'coadyuvante']]);
        }

        // B-C. Arboles
        $arbolProblema = $this->crearArbolProblema($programa, $def);
        $problemacentral = $arbolProblema->nodos()->where('tipo_nodo', TipoNodo::PROBLEMA_CENTRAL)->first();
        $this->crearArbolObjetivos($programa, $arbolProblema, $def);

        // D. Alternativas
        $this->crearAlternativas($programa, $def);

        // E. Poblacion
        $pob = $def['poblacion'];
        PoblacionPrograma::firstOrCreate(
            ['programa_id' => $programa->id],
            [
                'unidad_medida' => $pob['unidad'], 'referencia_cantidad' => $pob['ref'],
                'referencia_fuente' => 'INEGI Censo 2020', 'potencial_cantidad' => $pob['pot'],
                'potencial_fuente' => 'Padrón estatal de beneficiarios', 'objetivo_cantidad' => $pob['obj'],
                'objetivo_justificacion' => 'Meta del programa para el ejercicio fiscal 2025',
                'anio_ejercicio' => 2025,
            ]
        );

        // F-K. MIR Niveles, Indicadores, Variables, Medios, CREMAA, Anexos
        $this->crearMir($programa, $def, $team, $teams, $planeador);

        // L. MetaPeriodos
        $this->crearMetaPeriodos($programa);

        // M. Snapshots
        $this->crearSnapshots($programa, $planeador);

        // N. Mark completed
        $programa->update(['planeacion_completada_at' => now(), 'estado' => 'activo']);
    }

    // ─── B. Arbol de Problemas ──────────────────────────────────────────

    private function crearArbolProblema(ProgramaPresupuestario $programa, array $def): Arbol
    {
        $arbol = Arbol::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo' => TipoArbol::PROBLEMA]
        );

        if ($arbol->nodos()->exists()) {
            return $arbol;
        }

        $central = ArbolNodo::create([
            'arbol_id' => $arbol->id, 'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL,
            'descripcion' => $def['problema'], 'orden' => 1,
        ]);

        $causas = $this->causasYEfectos($def['clave']);

        foreach ($causas['causas_directas'] as $i => $desc) {
            $cd = ArbolNodo::create([
                'arbol_id' => $arbol->id, 'parent_id' => $central->id,
                'tipo_nodo' => TipoNodo::CAUSA_DIRECTA, 'descripcion' => $desc, 'orden' => $i + 1,
            ]);
            ArbolNodo::create([
                'arbol_id' => $arbol->id, 'parent_id' => $cd->id,
                'tipo_nodo' => TipoNodo::CAUSA_INDIRECTA, 'descripcion' => $causas['causas_indirectas'][$i], 'orden' => 1,
            ]);
        }

        foreach ($causas['efectos_directos'] as $i => $desc) {
            $ed = ArbolNodo::create([
                'arbol_id' => $arbol->id, 'parent_id' => $central->id,
                'tipo_nodo' => TipoNodo::EFECTO_DIRECTO, 'descripcion' => $desc, 'orden' => $i + 1,
            ]);
            ArbolNodo::create([
                'arbol_id' => $arbol->id, 'parent_id' => $ed->id,
                'tipo_nodo' => TipoNodo::EFECTO_INDIRECTO, 'descripcion' => $causas['efectos_indirectos'][$i], 'orden' => 1,
            ]);
        }

        return $arbol;
    }

    // ─── C. Arbol de Objetivos ──────────────────────────────────────────

    private function crearArbolObjetivos(ProgramaPresupuestario $programa, Arbol $arbolProblema, array $def): Arbol
    {
        $arbol = Arbol::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo' => TipoArbol::OBJETIVOS]
        );

        if ($arbol->nodos()->exists()) {
            return $arbol;
        }

        $problemaCentral = $arbolProblema->nodos()->where('tipo_nodo', TipoNodo::PROBLEMA_CENTRAL)->first();

        $central = ArbolNodo::create([
            'arbol_id' => $arbol->id, 'tipo_nodo' => TipoNodo::OBJETIVO_CENTRAL,
            'descripcion' => $def['objetivo'], 'nodo_origen_id' => $problemaCentral->id, 'orden' => 1,
        ]);

        $causasDirectas = $arbolProblema->nodos()->where('tipo_nodo', TipoNodo::CAUSA_DIRECTA)->orderBy('orden')->get();
        foreach ($causasDirectas as $i => $cd) {
            $md = ArbolNodo::create([
                'arbol_id' => $arbol->id, 'parent_id' => $central->id,
                'tipo_nodo' => TipoNodo::MEDIO_DIRECTO, 'descripcion' => $this->invertirDescripcion($cd->descripcion),
                'nodo_origen_id' => $cd->id, 'orden' => $i + 1,
            ]);
            $ci = $arbolProblema->nodos()->where('parent_id', $cd->id)->where('tipo_nodo', TipoNodo::CAUSA_INDIRECTA)->first();
            if ($ci) {
                ArbolNodo::create([
                    'arbol_id' => $arbol->id, 'parent_id' => $md->id,
                    'tipo_nodo' => TipoNodo::MEDIO_INDIRECTO, 'descripcion' => $this->invertirDescripcion($ci->descripcion),
                    'nodo_origen_id' => $ci->id, 'orden' => 1,
                ]);
            }
        }

        $efectosDirectos = $arbolProblema->nodos()->where('tipo_nodo', TipoNodo::EFECTO_DIRECTO)->orderBy('orden')->get();
        foreach ($efectosDirectos as $i => $ed) {
            $fd = ArbolNodo::create([
                'arbol_id' => $arbol->id, 'parent_id' => $central->id,
                'tipo_nodo' => TipoNodo::FIN_DIRECTO, 'descripcion' => $this->invertirDescripcion($ed->descripcion),
                'nodo_origen_id' => $ed->id, 'orden' => $i + 1,
            ]);
            $ei = $arbolProblema->nodos()->where('parent_id', $ed->id)->where('tipo_nodo', TipoNodo::EFECTO_INDIRECTO)->first();
            if ($ei) {
                ArbolNodo::create([
                    'arbol_id' => $arbol->id, 'parent_id' => $fd->id,
                    'tipo_nodo' => TipoNodo::FIN_INDIRECTO, 'descripcion' => $this->invertirDescripcion($ei->descripcion),
                    'nodo_origen_id' => $ei->id, 'orden' => 1,
                ]);
            }
        }

        return $arbol;
    }

    // ─── D. Alternativas ────────────────────────────────────────────────

    private function crearAlternativas(ProgramaPresupuestario $programa, array $def): void
    {
        Alternativa::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'nombre' => "Fortalecimiento institucional para {$def['nombre']}"],
            ['seleccionada' => true, 'justificacion_seleccion' => 'Alternativa con mayor viabilidad técnica y presupuestal para el ejercicio fiscal.']
        );
        Alternativa::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'nombre' => "Estrategia comunitaria para {$def['nombre']}"],
            ['seleccionada' => false, 'justificacion_seleccion' => null]
        );
    }

    // ─── F-K. MIR ──────────────────────────────────────────────────────

    private function crearMir(ProgramaPresupuestario $programa, array $def, Team $team, array $teams, User $planeador): void
    {
        $mirData = $this->mirDefinitions($def);
        $isTransversal = !empty($def['transversal']);
        $coadTeam = $isTransversal ? $teams[$def['transversal']] : null;

        $pedObjId = $this->pedObjetivoIds ? $this->pedObjetivoIds[array_rand($this->pedObjetivoIds)] : null;
        $pedLineaId = $this->pedLineaIds ? $this->pedLineaIds[array_rand($this->pedLineaIds)] : null;

        $componenteIds = [];

        // Create COMPONENTES first (needed for ACTIVIDAD componente_id)
        foreach ($mirData as $key => $nivel) {
            if ($nivel['tipo'] !== TipoNivelMir::COMPONENTE) continue;

            $teamId = null;
            if ($isTransversal && $nivel['orden'] === 2) {
                $teamId = $coadTeam->id;
            }

            $mirNivel = MirNivel::firstOrCreate(
                ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => $nivel['tipo'], 'orden' => $nivel['orden']],
                [
                    'resumen_narrativo' => $nivel['resumen'], 'supuestos' => $nivel['supuesto'],
                    'ped_linea_accion_id' => $pedLineaId, 'team_id' => $teamId,
                ]
            );
            $componenteIds[$nivel['orden']] = $mirNivel->id;
            $this->crearIndicadorCompleto($mirNivel, $nivel, $programa);
        }

        // Create FIN, PROPOSITO, ACTIVIDAD
        foreach ($mirData as $nivel) {
            if ($nivel['tipo'] === TipoNivelMir::COMPONENTE) continue;

            $attrs = [
                'resumen_narrativo' => $nivel['resumen'], 'supuestos' => $nivel['supuesto'],
            ];

            if (in_array($nivel['tipo'], [TipoNivelMir::FIN, TipoNivelMir::PROPOSITO])) {
                $attrs['ped_objetivo_estrategico_id'] = $pedObjId;
            } else {
                $attrs['ped_linea_accion_id'] = $pedLineaId;
            }

            if ($nivel['tipo'] === TipoNivelMir::ACTIVIDAD) {
                $attrs['componente_id'] = $componenteIds[$nivel['comp_orden']] ?? null;

                // For transversal: C2's activities get coadyuvante team
                if ($isTransversal && $nivel['comp_orden'] === 2) {
                    $attrs['team_id'] = $coadTeam->id;
                }
            }

            $mirNivel = MirNivel::firstOrCreate(
                ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => $nivel['tipo'], 'orden' => $nivel['orden']],
                $attrs
            );

            $this->crearIndicadorCompleto($mirNivel, $nivel, $programa);
        }
    }

    private function crearIndicadorCompleto(MirNivel $mirNivel, array $nivelDef, ProgramaPresupuestario $programa): void
    {
        if ($mirNivel->indicadores()->exists()) return;

        $idx = $this->indicadorGlobalIndex++;
        $sentido = $this->sentidoPool[$idx % count($this->sentidoPool)];
        $frecuencia = $nivelDef['frecuencia'];
        $activoSeguimiento = !($nivelDef['tipo'] === TipoNivelMir::FIN && $frecuencia === FrecuenciaMedicion::SEXENAL);

        $indAttrs = [
            'mir_nivel_id' => $mirNivel->id,
            'nombre' => $nivelDef['indicador_nombre'],
            'formula_texto' => $nivelDef['formula'],
            'tipo' => $nivelDef['tipo_ind'],
            'dimension' => $nivelDef['dimension'],
            'frecuencia' => $frecuencia,
            'sentido' => $sentido,
            'linea_base' => $nivelDef['linea_base'],
            'meta' => $nivelDef['meta'],
            'unidad_medida_id' => $nivelDef['unidad_medida_id'],
            'orden' => 1,
            'activo_seguimiento' => $activoSeguimiento,
        ];

        // Set explicit ranges for some indicators
        if (in_array($idx, [0, 15, 42, 90])) {
            $indAttrs['rango_verde_min'] = 90.0;
            $indAttrs['rango_verde_max'] = 100.0;
            $indAttrs['rango_amarillo_min'] = 70.0;
            $indAttrs['rango_amarillo_max'] = 89.99;
        }

        $indicador = Indicador::create($indAttrs);

        // Variables
        foreach ($nivelDef['variables'] as $var) {
            IndicadorVariable::firstOrCreate(
                ['indicador_id' => $indicador->id, 'simbolo' => $var['simbolo']],
                ['nombre' => $var['nombre'], 'orden' => $var['orden']]
            );
        }

        // GeoBase linking for component-level and proposito-level variables
        if ($programa->geobase_program_id) {
            if ($mirNivel->tipo_nivel === TipoNivelMir::COMPONENTE) {
                $firstVar = $indicador->variables()->where('orden', 1)->first();
                if ($firstVar) {
                    $firstVar->update([
                        'geobase_endpoint_type' => 'component_coverage',
                        'geobase_reference_id' => $mirNivel->id,
                        'geobase_value_key' => 'count',
                    ]);
                }
            }

            if ($mirNivel->tipo_nivel === TipoNivelMir::PROPOSITO && $programa->clave === 'ISM-001') {
                $firstVar = $indicador->variables()->where('orden', 1)->first();
                if ($firstVar) {
                    $firstVar->update([
                        'geobase_endpoint_type' => 'program_coverage',
                        'geobase_reference_id' => $programa->geobase_program_id,
                        'geobase_value_key' => 'count',
                    ]);
                }
            }
        }

        // Medio de verificacion
        MedioVerificacion::firstOrCreate(
            ['indicador_id' => $indicador->id, 'nombre' => $nivelDef['medio_nombre']],
            ['fuente' => $nivelDef['medio_fuente'], 'frecuencia' => $frecuencia->value, 'orden' => 1]
        );

        // CREMAA (~50%)
        if ($idx % 2 === 0) {
            $allTrue = $idx % 4 === 0;
            CremaaValidacion::firstOrCreate(
                ['indicador_id' => $indicador->id],
                [
                    'claro' => true, 'relevante' => true,
                    'economico' => $allTrue, 'economico_observacion' => $allTrue ? null : 'Requiere validar costo de obtención del dato',
                    'monitoreable' => true, 'adecuado' => true,
                    'aportante' => $allTrue, 'aportante_observacion' => $allTrue ? null : 'Verificar alineación con objetivo del programa',
                ]
            );
        }

        // Anexos transversales (spread across programs, ~20 total)
        $anexoMap = [
            0 => 1,  2 => 2,  5 => 1,  8 => 3,  10 => 4,  // first 5
            16 => 1, 20 => 2, 25 => 3, 30 => 4, 35 => 1,  // next 5
            40 => 2, 50 => 3, 60 => 4, 70 => 1, 80 => 2,  // next 5
            88 => 3, 96 => 4, 104 => 1, 112 => 2, 120 => 3, // next 5
        ];
        if (isset($anexoMap[$idx])) {
            $indicador->anexosTransversales()->syncWithoutDetaching([$anexoMap[$idx]]);
        }
    }

    // ─── L. MetaPeriodos ────────────────────────────────────────────────

    private function crearMetaPeriodos(ProgramaPresupuestario $programa): void
    {
        $indicadores = Indicador::whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $programa->id))
            ->where('activo_seguimiento', true)
            ->whereNotNull('meta')
            ->get();

        foreach ($indicadores as $ind) {
            if ($ind->metasPeriodo()->exists()) continue;

            $this->crearPeriodosParaAnio($ind, 2025);
            $this->crearPeriodosParaAnio($ind, 2026, true);
        }
    }

    private function crearPeriodosParaAnio(Indicador $ind, int $anio, bool $parcial = false): void
    {
        $frecuencia = $ind->frecuencia;
        $numPeriodos = match ($frecuencia) {
            FrecuenciaMedicion::MENSUAL => 12,
            FrecuenciaMedicion::TRIMESTRAL => 4,
            FrecuenciaMedicion::SEMESTRAL => 2,
            FrecuenciaMedicion::ANUAL, FrecuenciaMedicion::BIANUAL, FrecuenciaMedicion::SEXENAL => 1,
        };

        $metaPorPeriodo = round((float) $ind->meta / $numPeriodos, 4);
        $maxPeriodo = $parcial ? min($numPeriodos, match ($frecuencia) {
            FrecuenciaMedicion::MENSUAL => 3,    // Jan-Mar
            FrecuenciaMedicion::TRIMESTRAL => 1,  // Q1 only
            default => 1,
        }) : $numPeriodos;

        for ($p = 1; $p <= $maxPeriodo; $p++) {
            [$apertura, $cierre] = $this->fechasPeriodo($frecuencia, $anio, $p);

            MetaPeriodo::firstOrCreate(
                ['indicador_id' => $ind->id, 'periodo' => $p, 'ejercicio_fiscal' => $anio],
                [
                    'meta_periodo' => $metaPorPeriodo,
                    'activo' => true,
                    'fecha_apertura' => $apertura,
                    'fecha_cierre' => $cierre,
                ]
            );
        }
    }

    private function fechasPeriodo(FrecuenciaMedicion $freq, int $anio, int $periodo): array
    {
        return match ($freq) {
            FrecuenciaMedicion::MENSUAL => [
                Carbon::create($anio, $periodo, 1),
                Carbon::create($anio, $periodo, 1)->endOfMonth()->startOfDay(),
            ],
            FrecuenciaMedicion::TRIMESTRAL => [
                Carbon::create($anio, ($periodo - 1) * 3 + 1, 1),
                Carbon::create($anio, $periodo * 3, 1)->endOfMonth()->startOfDay(),
            ],
            FrecuenciaMedicion::SEMESTRAL => [
                Carbon::create($anio, ($periodo - 1) * 6 + 1, 1),
                Carbon::create($anio, $periodo * 6, 1)->endOfMonth()->startOfDay(),
            ],
            default => [
                Carbon::create($anio, 1, 1),
                Carbon::create($anio, 12, 31),
            ],
        };
    }

    // ─── M. Snapshots ───────────────────────────────────────────────────

    private function crearSnapshots(ProgramaPresupuestario $programa, User $planeador): void
    {
        if ($programa->mirVersiones()->exists()) return;

        $snapshotService = app(MirSnapshotService::class);
        $snapshotService->crear($programa, 'Versión inicial', $planeador->id);

        $fin = $programa->mirNiveles()->where('tipo_nivel', TipoNivelMir::FIN)->first();
        if ($fin) {
            $original = $fin->resumen_narrativo;
            $fin->update(['resumen_narrativo' => $original . ' — alineado con PED']);
            $snapshotService->crear($programa, 'Post-alineación PED', $planeador->id);
            $fin->update(['resumen_narrativo' => $original]);
        }
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    private function invertirDescripcion(string $desc): string
    {
        $replacements = [
            'Insuficiente' => 'Suficiente', 'insuficiente' => 'suficiente',
            'Falta de' => 'Disponibilidad de', 'falta de' => 'disponibilidad de',
            'Escasa' => 'Amplia', 'escasa' => 'amplia',
            'Bajo' => 'Alto', 'bajo' => 'alto', 'Baja' => 'Alta', 'baja' => 'alta',
            'Débil' => 'Sólida', 'débil' => 'sólida',
            'Deterioro' => 'Mejora', 'deterioro' => 'mejora',
            'Limitada' => 'Amplia', 'limitada' => 'amplia',
            'Ausencia' => 'Presencia', 'ausencia' => 'presencia',
            'Incremento de la incidencia' => 'Reducción de la incidencia',
            'Alta reincidencia' => 'Baja reincidencia',
            'Saturación' => 'Operación óptima', 'saturación' => 'operación óptima',
            'Degradación' => 'Conservación', 'degradación' => 'conservación',
        ];
        $result = str_replace(array_keys($replacements), array_values($replacements), $desc);
        return $result !== $desc ? $result : 'Se contribuye a resolver: ' . lcfirst($desc);
    }

    private function mirDefinitions(array $def): array
    {
        $mirTemplates = $this->mirTemplates();
        return $mirTemplates[$def['clave']] ?? $this->mirGenericTemplate($def);
    }

    private function mirTemplates(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $programs = [];

        // ── ISM-001: Impulso al Sector Mezcalero ──
        $programs['ISM-001'] = $this->buildMirLevels(
            fin: ['Contribuir al desarrollo económico del sector mezcalero estatal', 'Las condiciones macroeconómicas se mantienen estables',
                  'Tasa de crecimiento del PIB mezcalero estatal', '(A / B) x 100', FrecuenciaMedicion::ANUAL, DimensionIndicador::EFICACIA, 2.5, 5.0, 1,
                  'PIB mezcalero actual', 'PIB mezcalero esperado', 'Cuentas estatales INEGI', 'Sistema de Cuentas Nacionales INEGI'],
            proposito: ['Productores mezcaleros incrementan su competitividad y acceso a mercados', 'Los productores participan activamente en las capacitaciones',
                        'Porcentaje de productores con certificación de calidad', '(A / B) x 100', FrecuenciaMedicion::SEMESTRAL, DimensionIndicador::EFICACIA, 15.0, 35.0, 2,
                        'Productores certificados', 'Total de productores registrados', 'Padrón de productores mezcaleros', 'Registro estatal de productores'],
            c1: ['Programa de capacitación y certificación mezcalera implementado', 'Se cuenta con instructores especializados disponibles',
                 'Porcentaje de capacitaciones realizadas conforme a programa', '(A / B) x 100', FrecuenciaMedicion::TRIMESTRAL, DimensionIndicador::EFICACIA, 80.0, 95.0, 3,
                 'Capacitaciones realizadas', 'Capacitaciones programadas', 'Informes trimestrales de capacitación', 'Sistema de control de capacitaciones'],
            c2: ['Estrategia de promoción turístico-mezcalera diseñada e implementada', 'Existe coordinación efectiva con SECTUR',
                 'Índice de satisfacción en rutas mezcaleras', '(A / B) x 100', FrecuenciaMedicion::SEMESTRAL, DimensionIndicador::CALIDAD, 70.0, 90.0, 4,
                 'Visitantes satisfechos', 'Total de visitantes encuestados', 'Encuesta de satisfacción turística', 'Dirección de Turismo Alternativo'],
            a11: ['Realizar talleres de certificación en denominación de origen', 'Los productores asisten a los talleres convocados',
                  'Número de talleres impartidos', 'A', FrecuenciaMedicion::MENSUAL, DimensionIndicador::EFICACIA, 0, 48, 5, 'Talleres realizados', null, 'Listas de asistencia y minutas', 'Coordinación de Capacitación'],
            a12: ['Otorgar asistencia técnica para mejora de procesos productivos', 'Los productores implementan las recomendaciones técnicas',
                  'Porcentaje de asistencias técnicas completadas', '(A / B) x 100', FrecuenciaMedicion::TRIMESTRAL, DimensionIndicador::EFICIENCIA, 60.0, 90.0, 6,
                  'Asistencias completadas', 'Asistencias programadas', 'Bitácora de asistencia técnica', 'Subdirección de Desarrollo Productivo'],
            a21: ['Diseñar rutas turístico-mezcaleras', 'Las comunidades mezcaleras participan en el diseño de rutas',
                  'Número de rutas turísticas diseñadas', 'A', FrecuenciaMedicion::TRIMESTRAL, DimensionIndicador::EFICACIA, 0, 6, 7, 'Rutas diseñadas', null, 'Documentos de diseño de rutas', 'SECTUR Dirección de Planeación'],
            a22: ['Ejecutar campañas de difusión de la cultura mezcalera', 'Los medios de comunicación difunden las campañas',
                  'Costo promedio por campaña ejecutada', '(A / B)', FrecuenciaMedicion::MENSUAL, DimensionIndicador::ECONOMIA, 0, 150000, 8,
                  'Presupuesto ejercido en campañas', 'Número de campañas ejecutadas', 'Reportes financieros de campañas', 'Coordinación de Comunicación Social'],
        );

        // ── EDU-002: Educación Básica de Calidad ──
        $programs['EDU-002'] = $this->buildMirLevels(
            fin: ['Contribuir a mejorar la calidad educativa en el nivel básico del estado', 'La política educativa federal mantiene sus lineamientos',
                  'Variación del puntaje promedio estatal en pruebas estandarizadas', '(A - B) / B x 100', FrecuenciaMedicion::ANUAL, DimensionIndicador::EFICACIA, 1.2, 3.5, 1,
                  'Puntaje promedio actual', 'Puntaje promedio año anterior', 'Resultados pruebas PLANEA', 'SEP Dirección de Evaluación'],
            proposito: ['Estudiantes de educación básica mejoran su rendimiento académico', 'Los docentes aplican las metodologías de enseñanza actualizadas',
                        'Porcentaje de estudiantes con rendimiento satisfactorio', '(A / B) x 100', FrecuenciaMedicion::ANUAL, DimensionIndicador::EFICACIA, 55.0, 70.0, 2,
                        'Estudiantes con nivel satisfactorio', 'Total estudiantes evaluados', 'Evaluaciones estatales de aprendizaje', 'IEEPO Sistema de Evaluación'],
            c1: ['Programa de actualización docente implementado', 'Los docentes cuentan con disponibilidad para capacitarse',
                 'Porcentaje de docentes capacitados', '(A / B) x 100', FrecuenciaMedicion::SEMESTRAL, DimensionIndicador::EFICACIA, 40.0, 80.0, 3,
                 'Docentes capacitados', 'Total docentes del sistema', 'Constancias de capacitación', 'Dirección de Formación Continua'],
            c2: ['Materiales didácticos distribuidos en planteles prioritarios', 'Existe suficiencia presupuestal para adquisición de materiales',
                 'Eficiencia en la distribución de materiales didácticos', '(A / B) x 100', FrecuenciaMedicion::TRIMESTRAL, DimensionIndicador::EFICIENCIA, 70.0, 95.0, 5,
                 'Materiales entregados', 'Materiales programados', 'Acuses de recibo de materiales', 'Almacén Central Educativo'],
            a11: ['Impartir cursos de formación pedagógica continua', 'Se cuenta con facilitadores certificados',
                  'Número de cursos impartidos', 'A', FrecuenciaMedicion::MENSUAL, DimensionIndicador::EFICACIA, 0, 120, 3, 'Cursos realizados', null, 'Programa anual de formación', 'Instituto de Formación Docente'],
            a12: ['Aplicar evaluaciones diagnósticas a docentes', 'Los docentes participan voluntariamente',
                  'Costo por evaluación docente aplicada', '(A / B)', FrecuenciaMedicion::TRIMESTRAL, DimensionIndicador::ECONOMIA, 0, 500, 4,
                  'Presupuesto en evaluaciones', 'Evaluaciones aplicadas', 'Informes de evaluación docente', 'Coordinación de Evaluación'],
            a21: ['Adquirir materiales didácticos para planteles prioritarios', 'Los proveedores cumplen con los tiempos de entrega',
                  'Porcentaje del presupuesto ejercido en materiales', '(A / B) x 100', FrecuenciaMedicion::TRIMESTRAL, DimensionIndicador::EFICIENCIA, 50.0, 95.0, 6,
                  'Presupuesto ejercido', 'Presupuesto asignado', 'Reportes de ejercicio presupuestal', 'Subdirección de Recursos Materiales'],
            a22: ['Distribuir materiales a planteles según calendario establecido', 'Las vías de comunicación permiten la distribución oportuna',
                  'Porcentaje de planteles que reciben materiales en tiempo', '(A / B) x 100', FrecuenciaMedicion::MENSUAL, DimensionIndicador::EFICACIA, 60.0, 90.0, 7,
                  'Planteles atendidos en tiempo', 'Total planteles programados', 'Bitácora de distribución', 'Logística Educativa'],
        );

        // Remaining 14 programs use generic but realistic templates
        $genericPrograms = [
            'EDU-003' => ['Becas para Educación Superior', 'becarios', 'becas otorgadas', 'deserción en educación superior'],
            'EDU-004' => ['Infraestructura Escolar', 'planteles rehabilitados', 'obras ejecutadas', 'infraestructura educativa'],
            'PEC-001' => ['Prevención de Enfermedades Crónicas', 'personas tamizadas', 'tamizajes realizados', 'enfermedades crónico-degenerativas'],
            'SAL-002' => ['Vacunación Universal', 'menores vacunados', 'dosis aplicadas', 'cobertura de vacunación'],
            'SAL-003' => ['Salud Materna e Infantil', 'mujeres atendidas', 'consultas prenatales', 'mortalidad materna'],
            'SAL-004' => ['Atención Hospitalaria', 'pacientes atendidos', 'consultas de especialidad', 'saturación hospitalaria'],
            'FSP-001' => ['Fortalecimiento Seguridad Municipal', 'elementos capacitados', 'cursos impartidos', 'seguridad municipal'],
            'SEG-002' => ['Prevención del Delito', 'personas beneficiadas', 'acciones preventivas', 'incidencia delictiva'],
            'SEG-003P' => ['Reinserción Social', 'personas en programa', 'talleres de reinserción', 'reincidencia delictiva'],
            'SEG-004' => ['Protección Civil', 'municipios atendidos', 'simulacros realizados', 'capacidad de respuesta'],
            'DDT-001' => ['Destinos Turísticos Sustentables', 'destinos certificados', 'proyectos de conservación', 'degradación ambiental turística'],
            'TUR-002' => ['Promoción Turística Digital', 'interacciones digitales', 'campañas digitales', 'visibilidad turística'],
            'TUR-003' => ['Turismo Comunitario', 'comunidades integradas', 'proyectos comunitarios', 'participación comunitaria turística'],
            'TUR-004' => ['Capacitación Sector Hotelero', 'trabajadores capacitados', 'cursos de calidad', 'calidad del servicio hotelero'],
        ];

        foreach ($genericPrograms as $clave => [$nombre, $beneficiario, $entregable, $tema]) {
            $programs[$clave] = $this->buildGenericMir($nombre, $beneficiario, $entregable, $tema);
        }

        return $cached = $programs;
    }

    private function buildMirLevels(array $fin, array $proposito, array $c1, array $c2, array $a11, array $a12, array $a21, array $a22): array
    {
        $build = fn (TipoNivelMir $tipo, int $orden, array $d, ?int $compOrden = null) => [
            'tipo' => $tipo, 'orden' => $orden, 'comp_orden' => $compOrden,
            'resumen' => $d[0], 'supuesto' => $d[1],
            'indicador_nombre' => $d[2], 'formula' => $d[3],
            'frecuencia' => $d[4], 'dimension' => $d[5],
            'tipo_ind' => in_array($tipo, [TipoNivelMir::FIN, TipoNivelMir::PROPOSITO]) ? TipoIndicador::ESTRATEGICO
                : ($tipo === TipoNivelMir::ACTIVIDAD ? TipoIndicador::GESTION
                    : (($orden <= 1) ? TipoIndicador::ESTRATEGICO : TipoIndicador::GESTION)),
            'linea_base' => $d[6], 'meta' => $d[7], 'unidad_medida_id' => $d[8],
            'variables' => $d[10] !== null
                ? [['simbolo' => 'A', 'nombre' => $d[9], 'orden' => 1], ['simbolo' => 'B', 'nombre' => $d[10], 'orden' => 2]]
                : [['simbolo' => 'A', 'nombre' => $d[9], 'orden' => 1]],
            'medio_nombre' => $d[11], 'medio_fuente' => $d[12],
        ];

        return [
            $build(TipoNivelMir::FIN, 1, $fin),
            $build(TipoNivelMir::PROPOSITO, 1, $proposito),
            $build(TipoNivelMir::COMPONENTE, 1, $c1),
            $build(TipoNivelMir::COMPONENTE, 2, $c2),
            $build(TipoNivelMir::ACTIVIDAD, 1, $a11, 1),
            $build(TipoNivelMir::ACTIVIDAD, 2, $a12, 1),
            $build(TipoNivelMir::ACTIVIDAD, 3, $a21, 2),
            $build(TipoNivelMir::ACTIVIDAD, 4, $a22, 2),
        ];
    }

    private function buildGenericMir(string $nombre, string $beneficiario, string $entregable, string $tema): array
    {
        // Cycle through dimensions: index determines which dimension combo to use
        static $dimCycle = 0;
        $dimCycle++;

        $propDim = $dimCycle % 2 === 0 ? DimensionIndicador::EFICIENCIA : DimensionIndicador::EFICACIA;
        $c1Dim = match ($dimCycle % 3) { 0 => DimensionIndicador::CALIDAD, 1 => DimensionIndicador::EFICACIA, 2 => DimensionIndicador::EFICIENCIA };
        $c2Dim = match ($dimCycle % 3) { 0 => DimensionIndicador::EFICACIA, 1 => DimensionIndicador::CALIDAD, 2 => DimensionIndicador::EFICACIA };
        $a1Dim = match ($dimCycle % 3) { 0 => DimensionIndicador::ECONOMIA, 1 => DimensionIndicador::EFICACIA, 2 => DimensionIndicador::EFICIENCIA };
        $a2Dim = match ($dimCycle % 3) { 0 => DimensionIndicador::EFICACIA, 1 => DimensionIndicador::ECONOMIA, 2 => DimensionIndicador::EFICACIA };

        $finFreq = $dimCycle % 3 === 0 ? FrecuenciaMedicion::SEXENAL : ($dimCycle % 3 === 1 ? FrecuenciaMedicion::BIANUAL : FrecuenciaMedicion::ANUAL);
        $propFreq = $dimCycle % 2 === 0 ? FrecuenciaMedicion::ANUAL : FrecuenciaMedicion::SEMESTRAL;
        $c1Freq = $dimCycle % 2 === 0 ? FrecuenciaMedicion::SEMESTRAL : FrecuenciaMedicion::TRIMESTRAL;
        $c2Freq = $dimCycle % 2 === 0 ? FrecuenciaMedicion::TRIMESTRAL : FrecuenciaMedicion::SEMESTRAL;
        $aFreq1 = $dimCycle % 2 === 0 ? FrecuenciaMedicion::MENSUAL : FrecuenciaMedicion::TRIMESTRAL;
        $aFreq2 = $dimCycle % 2 === 0 ? FrecuenciaMedicion::TRIMESTRAL : FrecuenciaMedicion::MENSUAL;

        $lb = 30 + $dimCycle * 3;
        $mt = 60 + $dimCycle * 2.5;

        return $this->buildMirLevels(
            fin: ["Contribuir a la mejora de {$tema} en el estado", 'Las condiciones socioeconómicas se mantienen favorables',
                  "Tasa de variación en {$tema}", '(A - B) / B x 100', $finFreq, DimensionIndicador::EFICACIA, round($lb * 0.1, 1), round($mt * 0.15, 1), 1,
                  "Valor actual de {$tema}", "Valor anterior de {$tema}", "Informe anual de {$tema}", "Sistema estatal de información"],
            proposito: ["Los {$beneficiario} reciben servicios de calidad del programa", 'La población objetivo participa activamente',
                        "Porcentaje de {$beneficiario} atendidos respecto a la meta", '(A / B) x 100', $propFreq, $propDim, $lb * 1.0, $mt * 1.0, 2,
                        ucfirst($beneficiario) . ' atendidos', ucfirst($beneficiario) . ' programados', "Padrón de {$beneficiario}", 'Sistema de registro de beneficiarios'],
            c1: [ucfirst($entregable) . ' del programa realizados conforme a plan', 'Se cuenta con recursos humanos suficientes',
                 "Porcentaje de {$entregable} completados", '(A / B) x 100', $c1Freq, $c1Dim, $lb * 0.8, $mt * 0.9, 3,
                 ucfirst($entregable) . ' completados', ucfirst($entregable) . ' programados', "Reportes de avance de {$entregable}", 'Coordinación operativa del programa'],
            c2: ['Informes técnicos y de seguimiento generados', 'Los sistemas de información operan correctamente',
                 'Porcentaje de informes entregados en tiempo', '(A / B) x 100', $c2Freq, $c2Dim, $lb * 0.7, $mt * 0.85, 5,
                 'Informes entregados en tiempo', 'Informes programados', 'Control de gestión documental', 'Sistema de gestión de calidad'],
            a11: ["Ejecutar las actividades operativas de {$entregable}", 'El personal operativo está disponible',
                  "Número de {$entregable} ejecutados en el periodo", 'A', $aFreq1, $a1Dim, 0, round($mt * 2), 6,
                  ucfirst($entregable) . ' ejecutados', null, "Bitácora operativa de {$entregable}", 'Subdirección operativa'],
            a12: ['Dar seguimiento y supervisión a las actividades operativas', 'Las condiciones logísticas permiten la supervisión',
                  'Porcentaje de supervisiones realizadas', '(A / B) x 100', $aFreq2, $a2Dim, $lb * 0.5, $mt * 0.8, 7,
                  'Supervisiones realizadas', 'Supervisiones programadas', 'Informes de supervisión', 'Área de supervisión y control'],
            a21: ['Elaborar informes técnicos del programa', 'La información base está disponible oportunamente',
                  'Porcentaje de informes técnicos elaborados', '(A / B) x 100', $aFreq1, DimensionIndicador::EFICACIA, $lb * 0.6, $mt * 0.9, 8,
                  'Informes elaborados', 'Informes requeridos', 'Control de informes técnicos', 'Área de planeación y evaluación'],
            a22: ['Integrar expedientes documentales de las acciones realizadas', 'El marco normativo se mantiene vigente',
                  'Costo promedio de integración documental', '(A / B)', $aFreq2, DimensionIndicador::ECONOMIA, 0, round($mt * 100), 4,
                  'Presupuesto ejercido en integración', 'Expedientes integrados', 'Sistema de gestión documental', 'Archivo institucional'],
        );
    }

    private function mirGenericTemplate(array $def): array
    {
        return $this->buildGenericMir($def['nombre'], $def['poblacion']['unidad'], 'acciones del programa', strtolower($def['nombre']));
    }

    // ─── Causas y Efectos ───────────────────────────────────────────────

    private function causasYEfectos(string $clave): array
    {
        $templates = [
            'ISM-001' => [
                'causas_directas' => ['Insuficiente capacitación técnica de productores mezcaleros', 'Escasa vinculación con mercados nacionales e internacionales'],
                'causas_indirectas' => ['Falta de centros de capacitación especializados en mezcal', 'Ausencia de estrategias de comercialización y marca'],
                'efectos_directos' => ['Bajo valor agregado en la cadena productiva del mezcal', 'Pérdida de variedades de agave y técnicas tradicionales'],
                'efectos_indirectos' => ['Migración de productores a otras actividades económicas', 'Deterioro del patrimonio cultural mezcalero'],
            ],
            'EDU-002' => [
                'causas_directas' => ['Insuficiente formación pedagógica de docentes en servicio', 'Limitada disponibilidad de materiales didácticos actualizados'],
                'causas_indirectas' => ['Escasa oferta de programas de actualización docente', 'Bajo presupuesto destinado a materiales educativos'],
                'efectos_directos' => ['Bajo aprovechamiento académico de los estudiantes', 'Desigualdad en resultados educativos entre regiones'],
                'efectos_indirectos' => ['Incremento de la deserción escolar en nivel básico', 'Rezago educativo acumulado en la entidad'],
            ],
        ];

        if (isset($templates[$clave])) {
            return $templates[$clave];
        }

        // Generic fallback based on program definition
        $defs = collect($this->programDefinitions())->firstWhere('clave', $clave);
        $prob = $defs['problema'] ?? 'problema identificado';

        return [
            'causas_directas' => [
                "Insuficiente asignación de recursos para atender {$prob}",
                "Escasa coordinación interinstitucional en materia de {$prob}",
            ],
            'causas_indirectas' => [
                "Falta de diagnósticos actualizados sobre la situación",
                "Ausencia de mecanismos formales de coordinación entre dependencias",
            ],
            'efectos_directos' => [
                "Persistencia del problema y afectación a la población objetivo",
                "Ineficiencia en el uso de recursos públicos destinados al programa",
            ],
            'efectos_indirectos' => [
                "Deterioro de la confianza ciudadana en las instituciones públicas",
                "Ampliación de brechas de desigualdad en la entidad",
            ],
        ];
    }

}
