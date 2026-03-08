<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActivated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Usuario desactivado por admin → cerrar sesión
        if (! $user->active) {
            auth('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Tu cuenta ha sido desactivada. Contacta al administrador.');
        }

        // Usuario que no ha completado onboarding → bloquear acceso
        if (! $user->isActivated()) {
            $allowedRoutes = ['logout', 'activar.*', 'onboarding.*'];

            if ($request->routeIs($allowedRoutes)) {
                return $next($request);
            }

            auth('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Debes completar la activación de tu cuenta. Revisa tu correo electrónico.');
        }

        return $next($request);
    }
}
