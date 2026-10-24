function tokenCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function bordePorTipo(tipo) {
    switch (tipo) {
        case 'error':
        case 'danger':
            return 'border-red-500';
        case 'warning':
            return 'border-yellow-500';
        case 'info':
            return 'border-blue-500';
        default:
            return 'border-green-500';
    }
}

function iconoPorTipo(tipo) {
    switch (tipo) {
        case 'error':
        case 'danger':
            return 'text-red-400';
        case 'warning':
            return 'text-yellow-500';
        case 'info':
            return 'text-blue-500';
        default:
            return 'text-green-400';
    }
}

function mostrarMensajeAjax(mensaje, tipo = 'success', opciones = {}) {
    const titulo = opciones.title || mensaje;
    const detalle = opciones.message || (opciones.title ? mensaje : '');
    const duracion = Number(opciones.duration) || 5000;
    const tipoNormalizado = tipo === 'danger' ? 'error' : tipo;

    document.querySelector('[data-flash-ajax-toast]')?.remove();

    const toast = document.createElement('div');
    toast.setAttribute('data-flash-ajax-toast', '');
    toast.className = 'notify';
    toast.innerHTML = `
      <div aria-live="assertive" class="pointer-events-none fixed inset-0 z-50 flex items-end px-4 py-6 sm:items-start sm:p-6">
        <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
          <div class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg border-l-4 bg-white shadow-lg outline-1 outline-black/5 dark:bg-zinc-800 dark:outline-white/10 ${bordePorTipo(tipoNormalizado)}">
            <div class="p-4">
              <div class="flex items-start gap-3">
                <span class="mt-0.5 size-2.5 shrink-0 rounded-full ${iconoPorTipo(tipoNormalizado)} bg-current"></span>
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-medium text-gray-900 dark:text-white" data-toast-title></p>
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 ${detalle ? '' : 'hidden'}" data-toast-message></p>
                </div>
                <button type="button" class="rounded-md text-gray-400 hover:text-gray-500 dark:hover:text-gray-300" data-toast-close aria-label="Cerrar">
                  <span class="text-lg leading-none">&times;</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;

    toast.querySelector('[data-toast-title]').textContent = titulo;
    const nodoMensaje = toast.querySelector('[data-toast-message]');
    if (detalle && detalle !== titulo) {
        nodoMensaje.textContent = detalle;
        nodoMensaje.classList.remove('hidden');
    }

    document.body.appendChild(toast);

    const cerrar = () => toast.remove();
    toast.querySelector('[data-toast-close]')?.addEventListener('click', cerrar);
    clearTimeout(mostrarMensajeAjax._timer);
    mostrarMensajeAjax._timer = setTimeout(cerrar, duracion);
}

function mostrarNotifyAjax(notify, respaldoMensaje, respaldoTipo = 'success') {
    if (notify && (notify.title || notify.message)) {
        mostrarMensajeAjax(notify.message || notify.title, notify.type || respaldoTipo, {
            title: notify.title || notify.message,
            message: notify.title && notify.message && notify.title !== notify.message ? notify.message : '',
            duration: notify.duration || 5000,
        });
        return;
    }

    mostrarMensajeAjax(respaldoMensaje, respaldoTipo);
}

function guardarFlash(notifyOMensaje, tipo = 'success') {
    try {
        if (typeof notifyOMensaje === 'object' && notifyOMensaje) {
            sessionStorage.setItem('cda-flash-ajax', JSON.stringify({
                title: notifyOMensaje.title || notifyOMensaje.message || 'Listo',
                message: notifyOMensaje.message || '',
                tipo: notifyOMensaje.type || tipo,
                duration: notifyOMensaje.duration || 5000,
            }));
            return;
        }

        sessionStorage.setItem('cda-flash-ajax', JSON.stringify({
            title: notifyOMensaje,
            tipo,
            duration: 5000,
        }));
    } catch (e) {
        /* sin almacenamiento */
    }
}

function mostrarFlashGuardado() {
    try {
        const crudo = sessionStorage.getItem('cda-flash-ajax');

        if (!crudo) {
            return;
        }

        sessionStorage.removeItem('cda-flash-ajax');
        const datos = JSON.parse(crudo);

        if (datos?.title || datos?.mensaje) {
            mostrarNotifyAjax({
                title: datos.title || datos.mensaje,
                message: datos.message || '',
                type: datos.tipo || 'success',
                duration: datos.duration || 5000,
            }, datos.title || datos.mensaje, datos.tipo || 'success');
        }
    } catch (e) {
        /* ignorar */
    }
}

function actualizarContadorResultados(delta) {
    const badge = document.querySelector('[data-resultados-total]');

    if (!badge) {
        return;
    }

    const actual = Number.parseInt(badge.textContent, 10);

    if (Number.isNaN(actual)) {
        return;
    }

    badge.textContent = `${Math.max(0, actual + delta)} resultado(s)`;
}

function quitarFila(fila) {
    if (!fila) {
        return;
    }

    fila.style.transition = 'opacity .2s ease, transform .2s ease';
    fila.style.opacity = '0';
    fila.style.transform = 'translateX(8px)';

    setTimeout(() => {
        const tbody = fila.parentElement;
        fila.remove();
        actualizarContadorResultados(-1);

        if (tbody && tbody.querySelectorAll('tr').length === 0) {
            window.location.reload();
        }
    }, 200);
}

function mensajeValidacion(datos) {
    if (datos?.mensaje) {
        return datos.mensaje;
    }

    const errores = datos?.errores || datos?.errors;

    if (errores && typeof errores === 'object') {
        const primero = Object.values(errores)[0];

        if (Array.isArray(primero) && primero[0]) {
            return String(primero[0]);
        }

        if (typeof primero === 'string') {
            return primero;
        }
    }

    return 'No se pudo completar la acción.';
}

function debeOmitirAjax(formulario) {
    if (formulario.hasAttribute('data-no-ajax')) {
        return true;
    }

    const accion = (formulario.getAttribute('action') || '').toLowerCase();

    return accion.includes('/login') || accion.includes('/logout');
}

function cerrarDialogosDelFormulario(formulario) {
    const dialogo = formulario.closest('dialog');

    if (dialogo?.open) {
        dialogo.close();
    }
}

async function enviarFormularioAjax(formulario, { alExito } = {}) {
    if (!(formulario instanceof HTMLFormElement) || formulario.dataset.enviando === '1') {
        return null;
    }

    formulario.dataset.enviando = '1';
    formulario.setAttribute('aria-busy', 'true');

    const botones = [
        ...formulario.querySelectorAll('button[type="submit"], input[type="submit"]'),
        ...(formulario.id
            ? [...document.querySelectorAll(`button[type="submit"][form="${formulario.id}"], input[type="submit"][form="${formulario.id}"]`)]
            : []),
    ];

    botones.forEach((boton) => {
        boton.disabled = true;
        boton.setAttribute('aria-busy', 'true');
    });

    try {
        const respuesta = await fetch(formulario.action || window.location.href, {
            method: (formulario.getAttribute('method') || 'POST').toUpperCase(),
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': tokenCsrf(),
            },
            body: new FormData(formulario),
            credentials: 'same-origin',
        });

        const datos = await respuesta.json().catch(() => ({}));

        if (!respuesta.ok || datos.exito === false) {
            mostrarNotifyAjax(datos.notify, mensajeValidacion(datos), 'error');
            return datos;
        }

        if (typeof alExito === 'function') {
            const manejado = await alExito(datos, formulario);

            if (manejado === true) {
                mostrarNotifyAjax(datos.notify, datos.mensaje || 'Listo.');
                return datos;
            }
        }

        if (formulario.hasAttribute('data-ajax-remove-row') || datos.salir_lista) {
            mostrarNotifyAjax(datos.notify, datos.mensaje || 'Listo.');
            quitarFila(formulario.closest('tr'));
            return datos;
        }

        cerrarDialogosDelFormulario(formulario);

        const destino = datos.redirect || window.location.href;
        const mismoDestino = destino === window.location.href
            || destino === `${window.location.origin}${window.location.pathname}${window.location.search}`;

        guardarFlash(datos.notify || datos.mensaje || 'Listo.');

        if (mismoDestino) {
            window.location.reload();
            return datos;
        }

        window.location.assign(destino);
        return datos;
    } catch (error) {
        mostrarMensajeAjax('No se pudo completar la acción. Intenta de nuevo.', 'error');
        return null;
    } finally {
        if (formulario.isConnected) {
            delete formulario.dataset.enviando;
            formulario.removeAttribute('aria-busy');
        }

        botones.forEach((boton) => {
            if (boton.isConnected) {
                boton.disabled = false;
                boton.removeAttribute('aria-busy');
            }
        });
    }
}

mostrarFlashGuardado();

window.cdaAjax = {
    enviar: enviarFormularioAjax,
    mensaje: mostrarMensajeAjax,
    omitir: debeOmitirAjax,
    quitarFila,
    tokenCsrf,
};

export {
    enviarFormularioAjax,
    mostrarMensajeAjax,
    debeOmitirAjax,
    quitarFila,
    tokenCsrf,
};
