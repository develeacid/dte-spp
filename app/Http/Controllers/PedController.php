<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePedEjeRequest;
use App\Http\Requests\StorePedEstrategiaRequest;
use App\Http\Requests\StorePedLineaAccionRequest;
use App\Http\Requests\StorePedObjetivoRequest;
use App\Http\Requests\StorePedPlanRequest;
use App\Http\Requests\StorePedTemaRequest;
use App\Models\PedEje;
use App\Models\PedEstrategia;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;

class PedController extends Controller
{
    public function index()
    {
        $planes = PedPlan::with([
            'ejes.temas.objetivosEstrategicos.estrategias.lineasAccion'
        ])->orderBy('activo', 'desc')->orderBy('periodo_inicio', 'desc')->get();

        return view('ped.index', compact('planes'));
    }

    // ============================================
    // PLAN
    // ============================================

    public function storePlan(StorePedPlanRequest $request)
    {
        $plan = PedPlan::create($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', "Plan '{$plan->nombre}' creado exitosamente.")
            ->with('flash.bannerStyle', 'success');
    }

    public function updatePlan(StorePedPlanRequest $request, PedPlan $plan)
    {
        $plan->update($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', "Plan actualizado exitosamente.")
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyPlan(PedPlan $plan)
    {
        $nombre = $plan->nombre;
        $hijos = $plan->ejes()->count();

        $plan->delete();

        $mensaje = "Plan '{$nombre}' eliminado.";
        if ($hijos > 0) {
            $mensaje .= " Se eliminaron {$hijos} ejes y todos sus descendientes.";
        }

        return redirect()->route('ped.index')
            ->with('flash.banner', $mensaje)
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // EJE
    // ============================================

    public function storeEje(StorePedEjeRequest $request)
    {
        PedEje::create($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Eje creado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function updateEje(StorePedEjeRequest $request, PedEje $eje)
    {
        $eje->update($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Eje actualizado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyEje(PedEje $eje)
    {
        $hijos = $eje->temas()->count();
        $eje->delete();

        $mensaje = "Eje '{$eje->numero}' eliminado.";
        if ($hijos > 0) {
            $mensaje .= " Se eliminaron {$hijos} temas y sus descendientes.";
        }

        return redirect()->route('ped.index')
            ->with('flash.banner', $mensaje)
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // TEMA
    // ============================================

    public function storeTema(StorePedTemaRequest $request)
    {
        PedTema::create($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Tema creado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function updateTema(StorePedTemaRequest $request, PedTema $tema)
    {
        $tema->update($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Tema actualizado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyTema(PedTema $tema)
    {
        $hijos = $tema->objetivosEstrategicos()->count();
        $tema->delete();

        $mensaje = "Tema eliminado.";
        if ($hijos > 0) {
            $mensaje .= " Se eliminaron {$hijos} objetivos y sus descendientes.";
        }

        return redirect()->route('ped.index')
            ->with('flash.banner', $mensaje)
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // OBJETIVO ESTRATÉGICO
    // ============================================

    public function storeObjetivo(StorePedObjetivoRequest $request)
    {
        PedObjetivoEstrategico::create($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Objetivo Estratégico creado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function updateObjetivo(StorePedObjetivoRequest $request, PedObjetivoEstrategico $objetivo)
    {
        $objetivo->update($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Objetivo Estratégico actualizado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyObjetivo(PedObjetivoEstrategico $objetivo)
    {
        $hijos = $objetivo->estrategias()->count();
        $objetivo->delete();

        $mensaje = "Objetivo Estratégico eliminado.";
        if ($hijos > 0) {
            $mensaje .= " Se eliminaron {$hijos} estrategias y sus líneas de acción.";
        }

        return redirect()->route('ped.index')
            ->with('flash.banner', $mensaje)
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // ESTRATEGIA
    // ============================================

    public function storeEstrategia(StorePedEstrategiaRequest $request)
    {
        PedEstrategia::create($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Estrategia creada exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function updateEstrategia(StorePedEstrategiaRequest $request, PedEstrategia $estrategia)
    {
        $estrategia->update($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Estrategia actualizada exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyEstrategia(PedEstrategia $estrategia)
    {
        $hijos = $estrategia->lineasAccion()->count();
        $estrategia->delete();

        $mensaje = "Estrategia eliminada.";
        if ($hijos > 0) {
            $mensaje .= " Se eliminaron {$hijos} líneas de acción.";
        }

        return redirect()->route('ped.index')
            ->with('flash.banner', $mensaje)
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // LÍNEA DE ACCIÓN
    // ============================================

    public function storeLinea(StorePedLineaAccionRequest $request)
    {
        PedLineaAccion::create($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Línea de Acción creada exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function updateLinea(StorePedLineaAccionRequest $request, PedLineaAccion $linea)
    {
        $linea->update($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Línea de Acción actualizada exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyLinea(PedLineaAccion $linea)
    {
        $linea->delete();

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Línea de Acción eliminada.')
            ->with('flash.bannerStyle', 'success');
    }
}
