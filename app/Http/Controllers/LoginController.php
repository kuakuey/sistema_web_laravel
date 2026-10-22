<?php

namespace App\Http\Controllers;

use App\Support\Actividad;
use App\Support\Permisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class LoginController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect(Permisos::urlInicio(Auth::user()->rolNormalizado()));
        }

        return view('auth.login', ['tituloPagina' => 'Iniciar sesión']);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'usuario' => ['required', 'string', 'max:50'],
            'clave' => ['required', 'string'],
        ]);

        try {
            $valido = Auth::attempt([
                'usuario' => trim($datos['usuario']),
                'password' => $datos['clave'],
            ]);
        } catch (Throwable) {
            return back()
                ->withInput($request->only('usuario'))
                ->withErrors(['usuario' => 'No se pudo conectar con la base de datos. Revisa que MySQL esté iniciado.']);
        }

        if (! $valido) {
            return back()
                ->withInput($request->only('usuario'))
                ->withErrors(['usuario' => 'Usuario o contraseña incorrectos.']);
        }

        $request->session()->regenerate();

        Actividad::registrar(
            'login',
            'auth',
            'sesion',
            (int) Auth::id(),
            'Inicio de sesión'
        );

        return redirect()->intended(Permisos::urlInicio(Auth::user()->rolNormalizado()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Actividad::registrar(
            'logout',
            'auth',
            'sesion',
            (int) Auth::id(),
            'Cierre de sesión'
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
