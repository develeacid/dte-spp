<?php

namespace App\Http\Controllers\Cascade;

use App\Http\Controllers\Controller;
use App\Models\OdsMeta;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PndObjetivo;
use App\Models\ProgramaDerivadoObjetivo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MatrizAlineacionController extends Controller
{
    public function index()
    {
        $alineacionesPedPnd = PedObjetivoEstrategico::with(['pndObjetivos.eje', 'tema.eje.plan'])
            ->whereHas('pndObjetivos')
            ->get();

        $alineacionesPndOds = PndObjetivo::with(['odsMetas.objetivo', 'eje'])
            ->whereHas('odsMetas')
            ->get();

        $alineacionesLineaPrograma = PedLineaAccion::with([
            'programasDerivadosObjetivos.programa',
            'estrategia.objetivoEstrategico.tema.eje.plan',
        ])
            ->whereHas('programasDerivadosObjetivos')
            ->get();

        return view('cascade.alineacion.index', compact(
            'alineacionesPedPnd',
            'alineacionesPndOds',
            'alineacionesLineaPrograma'
        ));
    }

    // ============================================
    // PED ↔ PND
    // ============================================

    public function storePedPnd(Request $request)
    {
        $request->validate([
            'ped_objetivo_estrategico_id' => 'required|exists:ped_objetivos_estrategicos,id',
            'pnd_objetivo_id' => 'required|exists:pnd_objetivos,id',
        ]);

        $pedObjetivo = PedObjetivoEstrategico::find($request->ped_objetivo_estrategico_id);
        $pedObjetivo->pndObjetivos()->syncWithoutDetaching([$request->pnd_objetivo_id]);

        return redirect()->route('cascade.alineacion.index')
            ->with('flash.banner', 'Alineación PED ↔ PND creada exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyPedPnd(PedObjetivoEstrategico $pedObjetivo, PndObjetivo $pndObjetivo)
    {
        $pedObjetivo->pndObjetivos()->detach($pndObjetivo->id);

        return redirect()->route('cascade.alineacion.index')
            ->with('flash.banner', 'Alineación eliminada.')
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // PND ↔ ODS
    // ============================================

    public function storePndOds(Request $request)
    {
        $request->validate([
            'pnd_objetivo_id' => 'required|exists:pnd_objetivos,id',
            'ods_meta_id' => 'required|exists:ods_metas,id',
        ]);

        $pndObjetivo = PndObjetivo::find($request->pnd_objetivo_id);
        $pndObjetivo->odsMetas()->syncWithoutDetaching([$request->ods_meta_id]);

        return redirect()->route('cascade.alineacion.index')
            ->with('flash.banner', 'Alineación PND ↔ ODS creada exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyPndOds(PndObjetivo $pndObjetivo, OdsMeta $odsMeta)
    {
        $pndObjetivo->odsMetas()->detach($odsMeta->id);

        return redirect()->route('cascade.alineacion.index')
            ->with('flash.banner', 'Alineación eliminada.')
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // LÍNEA ↔ PROGRAMA DERIVADO
    // ============================================

    public function storeLineaPrograma(Request $request)
    {
        $request->validate([
            'ped_linea_accion_id' => 'required|exists:ped_lineas_accion,id',
            'programa_derivado_objetivo_id' => 'required|exists:programas_derivados_objetivos,id',
        ]);

        $linea = PedLineaAccion::find($request->ped_linea_accion_id);
        $linea->programasDerivadosObjetivos()->syncWithoutDetaching([$request->programa_derivado_objetivo_id]);

        return redirect()->route('cascade.alineacion.index')
            ->with('flash.banner', 'Alineación Línea ↔ Programa creada exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyLineaPrograma(PedLineaAccion $linea, ProgramaDerivadoObjetivo $programaObjetivo)
    {
        $linea->programasDerivadosObjetivos()->detach($programaObjetivo->id);

        return redirect()->route('cascade.alineacion.index')
            ->with('flash.banner', 'Alineación eliminada.')
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // BÚSQUEDAS PARA SELECTORES
    // ============================================

    public function searchPedObjetivos(Request $request)
    {
        $search = $request->get('q', '');

        $resultados = PedObjetivoEstrategico::with('tema.eje.plan')
            ->where('descripcion', 'ilike', "%{$search}%")
            ->orWhereHas('tema', fn ($q) => $q->where('nombre', 'ilike', "%{$search}%"))
            ->limit(20)
            ->get()
            ->map(fn ($obj) => [
                'id' => $obj->id,
                'text' => $obj->clave_completa.' - '.Str::limit($obj->descripcion, 60),
                'clave' => $obj->clave_completa,
                'plan' => $obj->tema->eje->plan->nombre ?? null,
            ]);

        return response()->json(['results' => $resultados]);
    }

    public function searchPndObjetivos(Request $request)
    {
        $search = $request->get('q', '');

        $resultados = PndObjetivo::with('eje')
            ->where('descripcion', 'ilike', "%{$search}%")
            ->orWhere('clave', 'ilike', "%{$search}%")
            ->limit(20)
            ->get()
            ->map(fn ($obj) => [
                'id' => $obj->id,
                'text' => $obj->clave.' - '.Str::limit($obj->descripcion, 60),
                'clave' => $obj->clave,
                'eje' => $obj->eje->nombre ?? null,
            ]);

        return response()->json(['results' => $resultados]);
    }

    public function searchOdsMetas(Request $request)
    {
        $search = $request->get('q', '');

        $resultados = OdsMeta::with('objetivo')
            ->where('descripcion', 'ilike', "%{$search}%")
            ->orWhere('clave', 'ilike', "%{$search}%")
            ->limit(20)
            ->get()
            ->map(fn ($meta) => [
                'id' => $meta->id,
                'text' => $meta->clave.' - '.Str::limit($meta->descripcion, 60),
                'clave' => $meta->clave,
                'ods' => 'ODS '.$meta->objetivo->numero.': '.$meta->objetivo->nombre,
            ]);

        return response()->json(['results' => $resultados]);
    }

    public function searchLineasAccion(Request $request)
    {
        $search = $request->get('q', '');

        $resultados = PedLineaAccion::with('estrategia.objetivoEstrategico.tema.eje.plan')
            ->where('descripcion', 'ilike', "%{$search}%")
            ->limit(20)
            ->get()
            ->map(fn ($linea) => [
                'id' => $linea->id,
                'text' => $linea->clave_completa.' - '.Str::limit($linea->descripcion, 60),
                'clave' => $linea->clave_completa,
                'plan' => $linea->estrategia->objetivoEstrategico->tema->eje->plan->nombre ?? null,
            ]);

        return response()->json(['results' => $resultados]);
    }

    public function searchProgramasObjetivos(Request $request)
    {
        $search = $request->get('q', '');

        $resultados = ProgramaDerivadoObjetivo::with('programa')
            ->where('descripcion', 'ilike', "%{$search}%")
            ->limit(20)
            ->get()
            ->map(fn ($obj) => [
                'id' => $obj->id,
                'text' => $obj->clave_completa.' - '.Str::limit($obj->descripcion, 60),
                'clave' => $obj->clave_completa,
                'programa' => $obj->programa->nombre,
                'tipo' => $obj->programa->tipo->label(),
            ]);

        return response()->json(['results' => $resultados]);
    }

    // ============================================
    // VISTA DE CADENA COMPLETA
    // ============================================

    public function showCadena(PedLineaAccion $lineaAccion)
    {
        $lineaAccion->load([
            'estrategia.objetivoEstrategico.pndObjetivos.odsMetas.objetivo',
            'estrategia.objetivoEstrategico.tema.eje.plan',
            'programasDerivadosObjetivos.programa',
        ]);

        $cadena = [
            'linea_accion' => $lineaAccion,
            'estrategia' => $lineaAccion->estrategia,
            'objetivo_estrategico' => $lineaAccion->estrategia->objetivoEstrategico,
            'tema' => $lineaAccion->estrategia->objetivoEstrategico->tema,
            'eje' => $lineaAccion->estrategia->objetivoEstrategico->tema->eje,
            'plan' => $lineaAccion->estrategia->objetivoEstrategico->tema->eje->plan,
            'pnd_objetivos' => $lineaAccion->estrategia->objetivoEstrategico->pndObjetivos,
            'ods_metas' => $lineaAccion->estrategia->objetivoEstrategico->pndObjetivos->flatMap->odsMetas->unique('id'),
            'programas_objetivos' => $lineaAccion->programasDerivadosObjetivos,
        ];

        return view('cascade.alineacion.cadena', compact('cadena'));
    }
}
