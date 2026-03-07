<?php

namespace App\Http\Middleware;

use App\Models\ProgramaPresupuestario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AislamientoMultiUR
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Si no hay usuario o es Admin, pasar (Admin tiene pase real)
        if (! $user || $user->hasRole('admin')) {
            return $next($request);
        }

        // 2. Obtener el programa de la ruta (Route Model Binding o ID directo)
        $programa = $request->route('programa');

        // Si la ruta no es de un programa, ignorar este middleware
        if (! $programa instanceof ProgramaPresupuestario) {
            return $next($request);
        }

        // 3. Validar Team Activo
        $teamActivo = $user->currentTeam;
        if (! $teamActivo) {
            abort(403, 'No tienes una Unidad Responsable activa asignada.');
        }

        // 4. Consultar relación en la BD
        $pivote = $programa->equipos()
            ->where('team_id', $teamActivo->id)
            ->first();

        if (! $pivote) {
            abort(403, 'Tu Unidad Responsable no tiene acceso a este programa.');
        }

        // 5. Inyectar rol en el request para uso en Controladores/Policies
        $request->attributes->set('ur_rol_en_programa', $pivote->pivot->rol);

        return $next($request);
    }
}
