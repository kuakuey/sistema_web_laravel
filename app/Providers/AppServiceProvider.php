<?php

namespace App\Providers;

use App\Support\Permisos;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('pagination.cda');

        View::composer(['layouts.app', 'layouts.guest', 'layouts.standalone'], function (): void {
            if (session()->has('notify')) {
                return;
            }

            if (session()->has('ok')) {
                notify()->success()->title((string) session()->pull('ok'))->send();
            }

            if (session()->has('error')) {
                notify()->error()->title((string) session()->pull('error'))->send();
            }
        });

        View::composer(['layouts.app', 'layouts.standalone'], function ($view): void {
            $usuario = auth()->user();
            $rol = $usuario?->rolNormalizado() ?? '';

            $datos = [
                'usuarioActual' => $usuario,
                'rolActual' => $rol,
                'etiquetaRol' => Permisos::etiquetasRoles()[$rol] ?? $rol,
            ];

            if ($view->name() === 'layouts.app') {
                $datos = array_merge($datos, [
                    'itemsMenu' => $usuario ? Permisos::itemsMenu($rol) : [],
                    'menuEstructura' => $usuario ? Permisos::menuEstructura($rol) : [],
                    'menuAvanzado' => $usuario ? Permisos::menuAvanzado($rol) : [],
                    'menusLaterales' => $usuario ? Permisos::menusLaterales($rol) : [],
                    'etiquetasMenu' => Permisos::etiquetas(),
                    'iconosMenu' => Permisos::iconos(),
                ]);
            }

            $view->with($datos);
        });
    }
}
