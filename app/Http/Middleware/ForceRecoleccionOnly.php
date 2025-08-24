<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceRecoleccionOnly
{
    /**
     * Handle an incoming request.
     *
     * Usuarios con rol 'user' (recolector) y sin permisos avanzados solo pueden ver rutas recoleccion.*
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user) {
            // Es recolector básico si tiene rol 'user' y NO tiene ninguno de los permisos avanzados
            $isRecolectorBasico = $user->hasRole('user') && ! $user->hasAnyPermission([
                'supervisor.recoleccion','manage.recoleccion','edit.recoleccion'
            ]);

            if ($isRecolectorBasico) {
                // Permitir rutas de recolección, logout y assets
                if (! $request->routeIs('recoleccion.*') && ! $request->is('logout') && ! $request->is('evidencias/*')) {
                    return redirect()->route('recoleccion.index');
                }
            }
        }
        return $next($request);
    }
}
