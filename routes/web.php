<?php

use App\Http\Controllers\AvanzadoController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\EstructuraController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\RegistroController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'create'])->name('inicio');
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/registros-generales', [RegistroController::class, 'generales'])->name('generales');
    Route::get('/presentacion-ninos', [RegistroController::class, 'inicio'])->defaults('seccion', 'presentaciones')->name('presentaciones');
    Route::get('/presentacion-ninos/nuevos', [RegistroController::class, 'index'])->defaults('seccion', 'presentaciones')->defaults('pestana', 'nuevos')->name('presentaciones.nuevos');
    Route::get('/presentacion-ninos/registros', [RegistroController::class, 'index'])->defaults('seccion', 'presentaciones')->defaults('pestana', 'registros')->name('presentaciones.registros');
    Route::post('/presentacion-ninos', [RegistroController::class, 'guardarPresentacion'])->name('presentaciones.guardar');
    Route::put('/presentacion-ninos/{presentacion}', [RegistroController::class, 'actualizarPresentacion'])->name('presentaciones.actualizar');
    Route::post('/presentacion-ninos/{presentacion}/estado', [RegistroController::class, 'estadoPresentacion'])->name('presentaciones.estado');
    Route::delete('/presentacion-ninos/{presentacion}', [RegistroController::class, 'eliminarPresentacion'])->name('presentaciones.eliminar');

    Route::post('/registros', [RegistroController::class, 'guardarInscripcion'])->name('registros.guardar');
    Route::put('/registros/{inscripcion}', [RegistroController::class, 'actualizarInscripcion'])->name('registros.actualizar');
    Route::post('/registros/{inscripcion}/estado', [RegistroController::class, 'estadoInscripcion'])->name('registros.estado');
    Route::delete('/registros/{inscripcion}', [RegistroController::class, 'eliminarInscripcion'])->name('registros.eliminar');

    Route::get('/ofrendas', [ModuloController::class, 'ofrendas'])->name('ofrendas');
    Route::post('/ofrendas', [ModuloController::class, 'guardarOfrenda'])->name('ofrendas.guardar');
    Route::put('/ofrendas/{ofrenda}', [ModuloController::class, 'actualizarOfrenda'])->name('ofrendas.actualizar');
    Route::delete('/ofrendas/{ofrenda}', [ModuloController::class, 'eliminarOfrenda'])->name('ofrendas.eliminar');

    Route::get('/consejeria', [ModuloController::class, 'consejeria'])->name('consejeria');
    Route::post('/consejeria', [ModuloController::class, 'guardarConsejeria'])->name('consejeria.guardar');
    Route::put('/consejeria/{consejeria}', [ModuloController::class, 'actualizarConsejeria'])->name('consejeria.actualizar');
    Route::delete('/consejeria/{consejeria}', [ModuloController::class, 'eliminarConsejeria'])->name('consejeria.eliminar');

    Route::get('/valores-adicionales', [ModuloController::class, 'valores'])->name('valores');
    Route::post('/valores-adicionales', [ModuloController::class, 'guardarValor'])->name('valores.guardar');
    Route::put('/valores-adicionales/{valor}', [ModuloController::class, 'actualizarValor'])->name('valores.actualizar');

    Route::get('/networking', [ModuloController::class, 'networking'])->name('networking');

    Route::get('/eventos', [EventoController::class, 'inicio'])->name('eventos');
    Route::get('/eventos/registros', [EventoController::class, 'index'])->defaults('seccion', 'registros')->name('eventos.registros');
    Route::get('/eventos/catalogo', [EventoController::class, 'index'])->defaults('seccion', 'eventos')->name('eventos.catalogo');
    Route::post('/eventos', [EventoController::class, 'guardarEvento'])->name('eventos.guardar');
    Route::put('/eventos/{evento}', [EventoController::class, 'actualizarEvento'])->name('eventos.actualizar');
    Route::delete('/eventos/{evento}', [EventoController::class, 'eliminarEvento'])->name('eventos.eliminar');
    Route::delete('/eventos/{evento}/registros', [EventoController::class, 'vaciarRegistros'])->name('eventos.vaciar');
    Route::get('/eventos/{evento}/informe', [EventoController::class, 'informe'])->name('eventos.informe');
    Route::post('/eventos/participantes', [EventoController::class, 'guardarParticipante'])->name('eventos.participantes.guardar');
    Route::put('/eventos/participantes/{registro}', [EventoController::class, 'actualizarParticipante'])->name('eventos.participantes.actualizar');
    Route::delete('/eventos/participantes/{registro}', [EventoController::class, 'eliminarParticipante'])->name('eventos.participantes.eliminar');
    Route::post('/eventos/participantes/{registro}/pago', [EventoController::class, 'estadoPago'])->name('eventos.participantes.pago');
    Route::get('/eventos/{slug}', [EventoController::class, 'participantes'])->name('eventos.participantes');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout/consultar', [CheckoutController::class, 'consultar'])->name('checkout.consultar');
    Route::post('/checkout/marcar', [CheckoutController::class, 'marcar'])->name('checkout.marcar');
    Route::post('/checkout/reversar', [CheckoutController::class, 'reversar'])->name('checkout.reversar');

    Route::get('/calendario', [ModuloController::class, 'calendario'])->name('calendario');
    Route::get('/estructura', [EstructuraController::class, 'inicio'])->name('estructura');
    Route::get('/estructura/miembros', [EstructuraController::class, 'index'])->defaults('seccion', 'lideres')->name('estructura.miembros');
    Route::get('/estructura/ministerios', [EstructuraController::class, 'index'])->defaults('seccion', 'territorios')->name('estructura.ministerios');
    Route::get('/estructura/casas', [EstructuraController::class, 'index'])->defaults('seccion', 'casas')->name('estructura.casas');
    Route::get('/estructura/piramide', [EstructuraController::class, 'index'])->defaults('seccion', 'piramide')->name('estructura.piramide');
    Route::post('/estructura/miembros', [EstructuraController::class, 'guardarMiembro'])->name('estructura.miembros.guardar');
    Route::put('/estructura/miembros/{lider}', [EstructuraController::class, 'actualizarMiembro'])->name('estructura.miembros.actualizar');
    Route::delete('/estructura/miembros/{lider}', [EstructuraController::class, 'eliminarMiembro'])->name('estructura.miembros.eliminar');
    Route::post('/estructura/territorios', [EstructuraController::class, 'guardarTerritorio'])->name('estructura.territorios.guardar');
    Route::put('/estructura/territorios/{territorio}', [EstructuraController::class, 'actualizarTerritorio'])->name('estructura.territorios.actualizar');
    Route::delete('/estructura/territorios/{territorio}', [EstructuraController::class, 'eliminarTerritorio'])->name('estructura.territorios.eliminar');
    Route::post('/estructura/casas', [EstructuraController::class, 'guardarCasa'])->name('estructura.casas.guardar');
    Route::put('/estructura/casas/{casa}', [EstructuraController::class, 'actualizarCasa'])->name('estructura.casas.actualizar');
    Route::delete('/estructura/casas/{casa}', [EstructuraController::class, 'eliminarCasa'])->name('estructura.casas.eliminar');
    Route::post('/estructura/piramide', [EstructuraController::class, 'guardarPiramide'])->name('estructura.piramide.guardar');
    Route::put('/estructura/piramide/{nodo}', [EstructuraController::class, 'actualizarPiramide'])->name('estructura.piramide.actualizar');
    Route::delete('/estructura/piramide/{nodo}', [EstructuraController::class, 'eliminarPiramide'])->name('estructura.piramide.eliminar');
    Route::get('/generar-informe', [ModuloController::class, 'informes'])->name('informes');
    Route::get('/avanzado', [AvanzadoController::class, 'inicio'])->name('avanzado');
    Route::get('/avanzado/usuarios', [AvanzadoController::class, 'index'])->defaults('seccion', 'usuarios')->name('avanzado.usuarios.ver');
    Route::get('/avanzado/permisos', [AvanzadoController::class, 'index'])->defaults('seccion', 'permisos')->name('avanzado.permisos.ver');
    Route::get('/avanzado/bd', [AvanzadoController::class, 'index'])->defaults('seccion', 'bd')->name('avanzado.bd');
    Route::get('/avanzado/importar', [AvanzadoController::class, 'index'])->defaults('seccion', 'importar')->name('avanzado.importar.ver');
    Route::get('/avanzado/logs', [AvanzadoController::class, 'index'])->defaults('seccion', 'logs')->name('avanzado.logs');
    Route::post('/avanzado/usuarios', [AvanzadoController::class, 'guardarUsuario'])->name('avanzado.usuarios');
    Route::put('/avanzado/usuarios/{usuario}/clave', [AvanzadoController::class, 'cambiarClave'])->name('avanzado.usuarios.clave');
    Route::delete('/avanzado/usuarios/{usuario}', [AvanzadoController::class, 'eliminarUsuario'])->name('avanzado.usuarios.eliminar');
    Route::post('/avanzado/permisos', [AvanzadoController::class, 'guardarPermisos'])->name('avanzado.permisos');
    Route::get('/avanzado/importar/plantilla', [AvanzadoController::class, 'plantillaImport'])->name('avanzado.importar.plantilla');
    Route::post('/avanzado/importar', [AvanzadoController::class, 'importar'])->name('avanzado.importar');
    Route::post('/avanzado/logs/limpiar', [AvanzadoController::class, 'limpiarLogs'])->name('avanzado.logs.limpiar');

    Route::get('/escol', [RegistroController::class, 'index'])->defaults('seccion', 'escol')->defaults('pestana', 'registros')->name('escol');
    Route::get('/academia', [RegistroController::class, 'index'])->defaults('seccion', 'academia')->defaults('pestana', 'registros')->name('academia');
    Route::get('/bautismo', [RegistroController::class, 'inicio'])->defaults('seccion', 'bautismo')->name('bautismo');
    Route::get('/bautismo/nuevos', [RegistroController::class, 'index'])->defaults('seccion', 'bautismo')->defaults('pestana', 'nuevos')->name('bautismo.nuevos');
    Route::get('/bautismo/registros', [RegistroController::class, 'index'])->defaults('seccion', 'bautismo')->defaults('pestana', 'registros')->name('bautismo.registros');
    Route::get('/conexion', [RegistroController::class, 'inicio'])->defaults('seccion', 'conexion')->name('conexion');
    Route::get('/conexion/nuevos', [RegistroController::class, 'index'])->defaults('seccion', 'conexion')->defaults('pestana', 'nuevos')->name('conexion.nuevos');
    Route::get('/conexion/registros', [RegistroController::class, 'index'])->defaults('seccion', 'conexion')->defaults('pestana', 'registros')->name('conexion.registros');
});
