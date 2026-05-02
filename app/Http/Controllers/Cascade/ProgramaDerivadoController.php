<?php

namespace App\Http\Controllers\Cascade;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramaDerivadoObjetivoRequest;
use App\Http\Requests\StoreProgramaDerivadoRequest;
use App\Models\PedPlan;
use App\Models\ProgramaDerivado;
use App\Models\ProgramaDerivadoObjetivo;

class ProgramaDerivadoController extends Controller
{
    /**
     * Vista principal de programas derivados.
     */
    public function index()
    {
        $planActivo = PedPlan::where('activo', true)->first();

        $programas = ProgramaDerivado::with(['objetivos'])
            ->when($planActivo, fn ($q) => $q->where('ped_plan_id', $planActivo->id))
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get();

        return view('cascade.programas-derivados.index', compact('programas', 'planActivo'));
    }

    /**
     * Crear nuevo programa derivado.
     */
    public function store(StoreProgramaDerivadoRequest $request)
    {
        $planActivo = PedPlan::where('activo', true)->first();

        if (! $planActivo) {
            return back()
                ->with('flash.banner', 'No existe un Plan Estatal de Desarrollo activo. Active uno antes de crear programas.')
                ->with('flash.bannerStyle', 'danger');
        }

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $planActivo->id,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'tipo' => $request->tipo,
        ]);

        return redirect()->route('cascade.programas-derivados.index')
            ->with('flash.banner', "Programa '{$programa->nombre}' creado exitosamente.")
            ->with('flash.bannerStyle', 'success');
    }

    /**
     * Actualizar programa derivado.
     */
    public function update(StoreProgramaDerivadoRequest $request, ProgramaDerivado $programa)
    {
        $programa->update($request->validated());

        return redirect()->route('cascade.programas-derivados.index')
            ->with('flash.banner', 'Programa actualizado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    /**
     * Eliminar programa derivado.
     */
    public function destroy(ProgramaDerivado $programa)
    {
        $nombre = $programa->nombre;
        $objetivos = $programa->objetivos()->count();

        $programa->delete();

        $mensaje = "Programa '{$nombre}' eliminado.";
        if ($objetivos > 0) {
            $mensaje .= " Se eliminaron {$objetivos} objetivos.";
        }

        return redirect()->route('cascade.programas-derivados.index')
            ->with('flash.banner', $mensaje)
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // OBJETIVOS
    // ============================================

    /**
     * Crear objetivo para un programa derivado.
     */
    public function storeObjetivo(StoreProgramaDerivadoObjetivoRequest $request, ProgramaDerivado $programa)
    {
        ProgramaDerivadoObjetivo::create([
            'programa_derivado_id' => $programa->id,
            'clave' => $request->clave,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('cascade.programas-derivados.index')
            ->with('flash.banner', 'Objetivo agregado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    /**
     * Actualizar objetivo.
     */
    public function updateObjetivo(StoreProgramaDerivadoObjetivoRequest $request, ProgramaDerivado $programa, ProgramaDerivadoObjetivo $objetivo)
    {
        $objetivo->update($request->validated());

        return redirect()->route('cascade.programas-derivados.index')
            ->with('flash.banner', 'Objetivo actualizado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    /**
     * Eliminar objetivo.
     */
    public function destroyObjetivo(ProgramaDerivado $programa, ProgramaDerivadoObjetivo $objetivo)
    {
        $objetivo->delete();

        return redirect()->route('cascade.programas-derivados.index')
            ->with('flash.banner', 'Objetivo eliminado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }
}
