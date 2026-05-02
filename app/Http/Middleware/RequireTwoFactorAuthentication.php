<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactorAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Si no hay usuario, seguir.
        if (! $user) {
            return $next($request);
        }

        // 2. ESCAPE HATCH para desarrollo y pruebas
        // Permite trabajar ágilmente en local/testing sin configurar 2FA cada vez.
        // En producción (APP_ENV=production), esta condición será false y se ejecutará la validación.
        if (app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        // 3. Validación de 2FA
        if (Features::enabled(Features::twoFactorAuthentication())
            && ! $user->hasEnabledTwoFactorAuthentication()
        ) {
            // Permitir rutas necesarias para configurar el 2FA y para salir (Logout)
            // Fortify/Jetstream usan rutas como 'profile.show', 'user-two-factor.*', etc.
            // IMPORTANTE: 'logout' debe estar permitido para no atrapar al usuario en un bucle.
            $allowedRoutes = [
                'profile.show',
                'user-profile-information.update',
                'user-two-factor.enable',
                'user-two-factor.confirm',
                'logout',
            ];

            if ($request->routeIs($allowedRoutes)) {
                return $next($request);
            }

            return redirect()->route('profile.show')
                ->with('flash.banner', 'Por seguridad, debes activar la autenticación de dos factores para acceder al sistema.')
                ->with('flash.bannerStyle', 'warning');
        }

        return $next($request);
    }
}
