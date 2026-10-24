<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalendarioController;
use App\Http\Controllers\Api\EstructuraController;
use App\Http\Controllers\Api\NetworkingController;
use App\Http\Controllers\Api\PresentacionesController;
use App\Http\Controllers\Api\SubmitController;
use Illuminate\Support\Facades\Route;

/*
| API pública del plugin WordPress / formularios del sitio.
| Compatible con las rutas del sistema PHP: /api/*.php
| Autenticación: encabezado X-API-KEY (y X-Token-Sesion cuando aplique).
*/

Route::middleware('api.key')->group(function () {
    $endpoints = [
        'auth' => AuthController::class,
        'calendario' => CalendarioController::class,
        'estructura' => EstructuraController::class,
        'networking' => NetworkingController::class,
        'presentaciones' => PresentacionesController::class,
        'submit' => SubmitController::class,
    ];

    foreach ($endpoints as $ruta => $controlador) {
        Route::match(['GET', 'POST', 'OPTIONS'], $ruta, $controlador);
        Route::match(['GET', 'POST', 'OPTIONS'], $ruta.'.php', $controlador);
    }
});
