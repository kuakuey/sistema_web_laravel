import './bootstrap';
import './eventos';
import './estructura';

const raiz = document.documentElement;

/* Tema claro / oscuro */
document.querySelectorAll('[data-theme-toggle]').forEach((boton) => {
    boton.addEventListener('click', () => {
        const oscuro = raiz.getAttribute('data-theme') !== 'dark';

        if (oscuro) {
            raiz.setAttribute('data-theme', 'dark');
        } else {
            raiz.removeAttribute('data-theme');
        }

        try {
            localStorage.setItem('cda-theme', oscuro ? 'dark' : 'light');
        } catch (e) {
            /* sin almacenamiento disponible */
        }
    });
});

/* Menú lateral colapsable (escritorio) */
function aplicarSidebar(colapsado) {
    if (colapsado) {
        raiz.setAttribute('data-sidebar', 'collapsed');
    } else {
        raiz.removeAttribute('data-sidebar');
    }

    document.querySelectorAll('[data-sidebar-collapse]').forEach((boton) => {
        boton.setAttribute('aria-pressed', colapsado ? 'true' : 'false');
        boton.setAttribute('aria-label', colapsado ? 'Expandir menú' : 'Colapsar menú');
        boton.setAttribute('title', colapsado ? 'Expandir menú' : 'Colapsar menú');
    });
}

document.querySelectorAll('[data-sidebar-collapse]').forEach((boton) => {
    boton.addEventListener('click', () => {
        const colapsado = raiz.getAttribute('data-sidebar') !== 'collapsed';

        aplicarSidebar(colapsado);

        try {
            localStorage.setItem('cda-sidebar', colapsado ? 'collapsed' : 'expanded');
        } catch (e) {
            /* sin almacenamiento disponible */
        }
    });
});

aplicarSidebar(raiz.getAttribute('data-sidebar') === 'collapsed');

/* Menú lateral en móvil */
const menu = document.getElementById('appSidebar');
const velo = document.getElementById('sidebarOverlay');

function abrirMenu(abrir) {
    if (!menu || !velo) {
        return;
    }

    menu.classList.toggle('-translate-x-full', !abrir);
    velo.classList.toggle('hidden', !abrir);
    document.body.classList.toggle('overflow-y-hidden', abrir);
}

document.querySelectorAll('[data-sidebar-toggle]').forEach((boton) => {
    boton.addEventListener('click', () => {
        abrirMenu(menu?.classList.contains('-translate-x-full') ?? false);
    });
});

velo?.addEventListener('click', () => abrirMenu(false));
menu?.querySelectorAll('a').forEach((enlace) => enlace.addEventListener('click', () => abrirMenu(false)));
window.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape') {
        abrirMenu(false);
    }
});
window.matchMedia('(min-width: 1024px)').addEventListener('change', (evento) => {
    if (evento.matches) {
        abrirMenu(false);
    }
});

/* Filtros colapsables en móvil */
document.querySelectorAll('[data-filters-toggle]').forEach((boton) => {
    const panel = boton.closest('.filters-panel');

    boton.addEventListener('click', () => {
        const abierto = boton.getAttribute('aria-expanded') === 'true';

        boton.setAttribute('aria-expanded', abierto ? 'false' : 'true');
        panel?.classList.toggle('is-open', !abierto);
    });
});

/* Mostrar / ocultar contraseña */
document.querySelectorAll('[data-password-toggle]').forEach((boton) => {
    const campo = document.getElementById(boton.dataset.passwordToggle);
    const icono = boton.querySelector('i');

    if (!campo) {
        return;
    }

    boton.addEventListener('click', () => {
        const mostrar = campo.type === 'password';

        campo.type = mostrar ? 'text' : 'password';
        boton.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
        boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');

        if (icono) {
            icono.className = mostrar ? 'bi bi-eye-slash' : 'bi bi-eye';
        }
    });
});

/* Avisos descartables */
document.addEventListener('click', (evento) => {
    const cerrar = evento.target.closest('[data-dismiss]');

    if (cerrar) {
        cerrar.closest('[data-alert]')?.remove();
    }
});

function campoVisibleDialogo(dialogo, selector) {
    return [...dialogo.querySelectorAll(selector)].find((nodo) => !nodo.closest('[hidden]')) ?? null;
}

function modoEdicionDetalle(dialogo, editar) {
    if (!dialogo) {
        return;
    }

    const vista = dialogo.querySelector('[data-detalle-vista]');
    const formulario = dialogo.querySelector('[data-detalle-form]');
    const botonEditar = dialogo.querySelector('[data-detalle-editar]');
    const botonGuardar = dialogo.querySelector('[data-detalle-guardar]');
    const botonCancelar = dialogo.querySelector('[data-detalle-cancelar]');
    const botonCerrar = dialogo.querySelector('[data-modal-close]:not([aria-label])');

    if (vista) {
        vista.hidden = editar;
    }

    if (formulario) {
        formulario.hidden = !editar;
    }

    if (botonEditar) {
        botonEditar.hidden = editar;
    }

    if (botonGuardar) {
        botonGuardar.hidden = !editar;
    }

    if (botonCancelar) {
        botonCancelar.hidden = !editar;
    }

    if (botonCerrar) {
        botonCerrar.hidden = editar;
    }
}

function enfocarCampoDialogo(dialogo) {
    const invalido = campoVisibleDialogo(dialogo, '[aria-invalid="true"], .text-danger');
    const primerCampo = campoVisibleDialogo(dialogo, 'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])');
    const foco = invalido?.closest('div')?.querySelector('input, select, textarea') ?? primerCampo;

    window.requestAnimationFrame(() => foco?.focus());
}

/* Popups nativos */
function abrirDialogo(dialogo) {
    if (!dialogo || typeof dialogo.showModal !== 'function') {
        return;
    }

    dialogo.showModal();
    enfocarCampoDialogo(dialogo);
}

document.querySelectorAll('[data-modal-open]').forEach((boton) => {
    boton.addEventListener('click', () => {
        const dialogo = document.getElementById(boton.dataset.modalOpen);
        const formularioClave = dialogo?.querySelector('[data-form-clave]');

        if (formularioClave && boton.dataset.usuarioId) {
            formularioClave.action = formularioClave.action.replace(/\/usuarios\/[^/]+\/clave/, `/usuarios/${boton.dataset.usuarioId}/clave`);
            const nombre = dialogo.querySelector('[data-clave-nombre]');
            if (nombre) {
                nombre.textContent = boton.dataset.usuarioNombre || '';
            }
            formularioClave.querySelectorAll('input[type="password"]').forEach((input) => {
                input.value = '';
            });
        }

        abrirDialogo(dialogo);
    });
});

document.querySelectorAll('[data-permisos-toggle]').forEach((boton) => {
    boton.addEventListener('click', () => {
        const checks = document.querySelectorAll(`[data-modulo="${boton.dataset.permisosToggle}"]`);
        const todos = Array.from(checks).every((nodo) => nodo.checked);

        checks.forEach((nodo) => {
            nodo.checked = !todos;
        });
        boton.textContent = todos ? 'Marcar todo' : 'Desmarcar todo';
    });
});

document.querySelectorAll('[data-modal-close]').forEach((boton) => {
    boton.addEventListener('click', () => boton.closest('dialog')?.close());
});

document.querySelectorAll('dialog[data-open-on-load]').forEach((dialogo) => {
    if (dialogo.getAttribute('data-detalle-abrir') === 'editar') {
        modoEdicionDetalle(dialogo, true);
    }

    abrirDialogo(dialogo);
});

document.querySelectorAll('dialog').forEach((dialogo) => {
    dialogo.addEventListener('click', (evento) => {
        if (evento.target === dialogo) {
            dialogo.close();
        }
    });

    dialogo.addEventListener('close', () => {
        const formulario = dialogo.querySelector('form[data-dirty], form[data-detalle-form]');

        if (formulario && formulario.dataset.enviando !== '1') {
            formulario.reset();
            sincronizarSucio(formulario);
        }

        modoEdicionDetalle(dialogo, false);
    });
});

document.addEventListener('click', (evento) => {
    const editar = evento.target.closest('[data-detalle-editar]');

    if (editar) {
        const dialogo = editar.closest('dialog');
        modoEdicionDetalle(dialogo, true);
        enfocarCampoDialogo(dialogo);
        return;
    }

    const cancelar = evento.target.closest('[data-detalle-cancelar]');

    if (cancelar) {
        const dialogo = cancelar.closest('dialog');
        const formulario = dialogo?.querySelector('[data-detalle-form]');

        if (formulario) {
            formulario.reset();
            sincronizarSucio(formulario);
        }

        modoEdicionDetalle(dialogo, false);
    }
});

const camposIgnorados = new Set(['_token', '_method', '_detalle']);

function firmaFormulario(formulario) {
    return [...new FormData(formulario).entries()]
        .filter(([clave]) => !camposIgnorados.has(clave))
        .map(([clave, valor]) => `${clave}=${valor}`)
        .join('&');
}

function sincronizarSucio(formulario) {
    const boton = (formulario.id
        ? document.querySelector(`[data-dirty-submit][form="${formulario.id}"]`)
        : null) ?? formulario.querySelector('[data-dirty-submit]');

    if (!boton) {
        return;
    }

    boton.hidden = firmaFormulario(formulario) === (formulario.dataset.firmaInicial || '');
}

document.querySelectorAll('form[data-dirty]').forEach((formulario) => {
    formulario.dataset.firmaInicial = firmaFormulario(formulario);
    formulario.addEventListener('input', () => sincronizarSucio(formulario));
    formulario.addEventListener('change', () => sincronizarSucio(formulario));
    sincronizarSucio(formulario);
});

document.addEventListener('click', (evento) => {
    const boton = evento.target.closest('button[type="submit"], input[type="submit"]');
    const formulario = boton?.form;

    if (formulario?.dataset.enviando === '1') {
        evento.preventDefault();
        evento.stopPropagation();
    }
}, true);

/* Un solo envío: el segundo clic no vuelve a mandar el formulario */
function bloquearFormulario(formulario) {
    if (formulario.dataset.enviando === '1') {
        return false;
    }

    formulario.dataset.enviando = '1';
    formulario.setAttribute('aria-busy', 'true');

    const internos = [...formulario.querySelectorAll('button[type="submit"], input[type="submit"]')];
    const externos = formulario.id
        ? [...document.querySelectorAll(`button[type="submit"][form="${formulario.id}"], input[type="submit"][form="${formulario.id}"]`)]
        : [];

    [...new Set([...internos, ...externos])].forEach((boton) => {
        boton.disabled = true;
        boton.setAttribute('aria-busy', 'true');

        const icono = boton.querySelector('i');

        if (icono) {
            icono.className = 'bi bi-hourglass-split';
        }
    });

    return true;
}

/* Confirmación elegante para formularios con data-confirm */
const dialogo = document.getElementById('confirmDialog');
let formularioPendiente = null;

document.addEventListener('submit', (evento) => {
    const formulario = evento.target;

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    const metodo = (formulario.getAttribute('method') || 'get').toLowerCase();

    if (metodo !== 'post') {
        return;
    }

    if (formulario.dataset.enviando === '1') {
        evento.preventDefault();
        evento.stopPropagation();
        return;
    }

    const mensaje = formulario.dataset.confirm;

    if (mensaje && dialogo) {
        evento.preventDefault();
        formularioPendiente = formulario;
        dialogo.querySelector('[data-confirm-message]').textContent = mensaje;
        dialogo.showModal();
        return;
    }

    bloquearFormulario(formulario);
});

if (dialogo) {
    dialogo.querySelector('[data-confirm-accept]')?.addEventListener('click', () => {
        const formulario = formularioPendiente;

        formularioPendiente = null;
        dialogo.close();

        if (formulario && bloquearFormulario(formulario)) {
            formulario.submit();
        }
    });

    dialogo.querySelector('[data-confirm-cancel]')?.addEventListener('click', () => {
        formularioPendiente = null;
        dialogo.close();
    });

    dialogo.addEventListener('close', () => {
        formularioPendiente = null;
    });
}
