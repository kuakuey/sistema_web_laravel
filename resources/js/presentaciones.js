import { quitarFila } from './ajax';

function pestanaPresentaciones() {
    return document.querySelector('[data-presentaciones-pestana]')?.dataset.presentacionesPestana || '';
}

function nodoProgreso(formulario) {
    return formulario.closest('[data-progreso-presentacion]')
        || formulario.closest('tr')?.querySelector('[data-progreso-presentacion]');
}

async function manejarEstadoPresentacion(datos, formulario) {
    const accion = new FormData(formulario).get('accion');
    const pestana = pestanaPresentaciones();
    const debeSalir = Boolean(datos.salir_lista)
        && (
            (accion === 'avanzar' && pestana === 'nuevos')
            || (accion === 'restablecer' && pestana === 'registros')
        );

    if (debeSalir) {
        quitarFila(formulario.closest('tr'));
        return true;
    }

    const progreso = nodoProgreso(formulario);

    if (progreso && datos.html) {
        const envoltorio = document.createElement('div');
        envoltorio.innerHTML = datos.html.trim();
        const nuevo = envoltorio.firstElementChild;

        if (nuevo) {
            progreso.replaceWith(nuevo);
        }

        return true;
    }

    return Boolean(datos.html);
}

async function enviarEstadoPresentacion(formulario) {
    return window.cdaAjax.enviar(formulario, { alExito: manejarEstadoPresentacion });
}

window.cdaEnviarEstadoPresentacion = enviarEstadoPresentacion;

export { enviarEstadoPresentacion };
