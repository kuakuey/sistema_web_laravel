import { quitarFila } from './ajax';

function pestanaPresentaciones() {
    return document.querySelector('[data-presentaciones-pestana]')?.dataset.presentacionesPestana || '';
}

function nodoProgreso(formulario) {
    return formulario.closest('[data-progreso-presentacion]')
        || formulario.closest('tr')?.querySelector('[data-progreso-presentacion]');
}

function actualizarContadorDiploma(delta) {
    const badge = document.querySelector('[data-resultados-total]');

    if (!badge) {
        return;
    }

    const actual = Number.parseInt(badge.textContent, 10);

    if (Number.isNaN(actual)) {
        return;
    }

    badge.textContent = String(Math.max(0, actual + delta));
}

function quitarItemDiploma(item) {
    if (!item) {
        return;
    }

    item.style.transition = 'opacity .2s ease, transform .2s ease';
    item.style.opacity = '0';
    item.style.transform = 'translateX(8px)';

    setTimeout(() => {
        const lista = item.parentElement;
        item.remove();
        actualizarContadorDiploma(-1);

        if (lista && lista.querySelectorAll('[data-presentacion-item]').length === 0) {
            window.location.reload();
        }
    }, 200);
}

async function manejarEstadoPresentacion(datos, formulario) {
    const accion = new FormData(formulario).get('accion');
    const pestana = pestanaPresentaciones();
    const itemDiploma = formulario.closest('[data-presentacion-item]');

    if (accion === 'entregar_diploma' && (datos.salir_lista || itemDiploma)) {
        quitarItemDiploma(itemDiploma);
        return true;
    }

    const debeSalir = Boolean(datos.salir_lista)
        && (
            (accion === 'avanzar' && pestana === 'nuevos')
            || (accion === 'retroceder' && pestana === 'registros')
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
