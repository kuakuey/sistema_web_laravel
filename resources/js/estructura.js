function catalogoMiembros() {
    const nodo = document.getElementById('configMiembrosEstructura')
        || document.getElementById('configMiembrosTerritorio');

    if (!nodo) {
        return [];
    }

    try {
        const datos = JSON.parse(nodo.textContent || '[]');

        return Array.isArray(datos) ? datos : [];
    } catch (e) {
        return [];
    }
}

function idsDesde(valor) {
    return String(valor || '')
        .split(',')
        .map((id) => parseInt(id, 10))
        .filter((id) => id > 0);
}

function escapar(texto) {
    return String(texto || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function iniciarAsignador(raiz) {
    const miembros = catalogoMiembros();
    const buscar = raiz.querySelector('[data-asignar-buscar]');
    const lista = raiz.querySelector('[data-asignar-lista]');
    const chips = {
        coordinador: raiz.querySelector('[data-chips="coordinador"]'),
        encargado: raiz.querySelector('[data-chips="encargado"]'),
    };
    const inputs = {
        coordinador: raiz.querySelector('[data-inputs="coordinador"]'),
        encargado: raiz.querySelector('[data-inputs="encargado"]'),
    };
    const nombres = {
        coordinador: 'coordinador_ids[]',
        encargado: 'encargado_ids[]',
    };

    let seleccion = {
        coordinador: idsDesde(raiz.dataset.coordinador),
        encargado: idsDesde(raiz.dataset.encargado),
    };

    function miembroPorId(id) {
        return miembros.find((miembro) => Number(miembro.id) === id) || null;
    }

    function tiene(rol, id) {
        return seleccion[rol].includes(id);
    }

    function quitar(rol, id) {
        seleccion[rol] = seleccion[rol].filter((actual) => actual !== id);
    }

    function marcar(rol, id) {
        const otro = rol === 'coordinador' ? 'encargado' : 'coordinador';

        quitar(otro, id);

        if (tiene(rol, id)) {
            quitar(rol, id);
        } else {
            seleccion[rol].push(id);
        }

        render();
    }

    function renderChips(rol) {
        const contenedor = chips[rol];

        if (!contenedor) {
            return;
        }

        contenedor.innerHTML = '';

        if (!seleccion[rol].length) {
            contenedor.innerHTML = '<span class="px-1 text-xs text-muted">Ninguno</span>';
            return;
        }

        seleccion[rol].forEach((id) => {
            const miembro = miembroPorId(id);
            const chip = document.createElement('span');
            chip.className = 'inline-flex items-center gap-1 rounded-full border border-line bg-surface px-2 py-1 text-xs text-ink';
            chip.innerHTML = `<span>${escapar(miembro?.nombre || ('#' + id))}</span>`;

            const quitarChip = document.createElement('button');
            quitarChip.type = 'button';
            quitarChip.className = 'grid size-4 place-items-center rounded-full text-muted hover:bg-surface-2 hover:text-ink';
            quitarChip.setAttribute('aria-label', 'Quitar');
            quitarChip.innerHTML = window.cdaIcon('x', 'text-[0.6rem]');
            quitarChip.addEventListener('click', () => {
                quitar(rol, id);
                render();
            });

            chip.appendChild(quitarChip);
            contenedor.appendChild(chip);
        });
    }

    function renderInputs(rol) {
        const contenedor = inputs[rol];

        if (!contenedor) {
            return;
        }

        contenedor.innerHTML = '';
        seleccion[rol].forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = nombres[rol];
            input.value = String(id);
            contenedor.appendChild(input);
        });
    }

    function coincide(miembro, consulta) {
        if (!consulta) {
            return true;
        }

        return `${miembro.nombre} ${miembro.cedula || ''}`.toLowerCase().includes(consulta);
    }

    function claseBoton(activo) {
        return activo
            ? 'h-7 rounded-md bg-accent px-2 text-xs font-medium text-accent-ink'
            : 'h-7 rounded-md border border-line bg-surface px-2 text-xs font-medium text-ink hover:bg-surface-2';
    }

    function renderLista() {
        if (!lista) {
            return;
        }

        const consulta = (buscar?.value || '').trim().toLowerCase();
        const visibles = miembros.filter((miembro) => coincide(miembro, consulta));

        lista.innerHTML = '';

        if (!miembros.length) {
            lista.innerHTML = '<p class="px-3 py-3 text-sm text-muted">No hay miembros registrados.</p>';
            return;
        }

        if (!visibles.length) {
            lista.innerHTML = '<p class="px-3 py-3 text-sm text-muted">No hay miembros que coincidan.</p>';
            return;
        }

        visibles.forEach((miembro) => {
            const id = Number(miembro.id);
            const fila = document.createElement('div');
            fila.className = 'flex items-center justify-between gap-3 border-b border-line px-3 py-2 last:border-b-0';
            fila.innerHTML = `
                <div class="min-w-0">
                    <p class="truncate text-sm text-ink">${escapar(miembro.nombre)}</p>
                    ${miembro.cedula ? `<p class="truncate text-xs text-muted">${escapar(miembro.cedula)}</p>` : ''}
                </div>
            `;

            const acciones = document.createElement('div');
            acciones.className = 'flex shrink-0 gap-1';

            [
                ['coordinador', 'Coord.'],
                ['encargado', 'Enc.'],
            ].forEach(([rol, etiqueta]) => {
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = claseBoton(tiene(rol, id));
                boton.textContent = etiqueta;
                boton.setAttribute('aria-pressed', tiene(rol, id) ? 'true' : 'false');
                boton.addEventListener('click', () => marcar(rol, id));
                acciones.appendChild(boton);
            });

            fila.appendChild(acciones);
            lista.appendChild(fila);
        });
    }

    function dialogoAbierto() {
        const dialogo = raiz.closest('dialog');

        return !dialogo || dialogo.open;
    }

    function render(forzarLista = false) {
        renderChips('coordinador');
        renderChips('encargado');
        renderInputs('coordinador');
        renderInputs('encargado');

        if (forzarLista || dialogoAbierto()) {
            renderLista();
        }
    }

    buscar?.addEventListener('input', renderLista);
    buscar?.addEventListener('focus', renderLista);
    raiz.addEventListener('asignar-abrir', () => render(true));

    raiz.closest('form')?.addEventListener('reset', () => {
        seleccion = {
            coordinador: idsDesde(raiz.dataset.coordinador),
            encargado: idsDesde(raiz.dataset.encargado),
        };

        if (buscar) {
            buscar.value = '';
        }

        render(dialogoAbierto());
    });

    render(dialogoAbierto());
}

function iniciarMarcador(raiz) {
    const miembros = catalogoMiembros();
    const buscar = raiz.querySelector('[data-marcar-buscar]');
    const lista = raiz.querySelector('[data-marcar-lista]');
    const chips = raiz.querySelector('[data-marcar-chips]');
    const inputs = raiz.querySelector('[data-marcar-inputs]');
    const name = raiz.dataset.name || 'lider_ids[]';
    const excluidos = new Set(idsDesde(raiz.dataset.excluidos));
    let seleccion = idsDesde(raiz.dataset.seleccion);

    function miembroPorId(id) {
        return miembros.find((miembro) => Number(miembro.id) === id) || null;
    }

    function disponible(miembro) {
        return !excluidos.has(Number(miembro.id));
    }

    function toggle(id) {
        if (excluidos.has(id)) {
            return;
        }

        if (seleccion.includes(id)) {
            seleccion = seleccion.filter((actual) => actual !== id);
        } else {
            seleccion.push(id);
        }

        render();
    }

    function renderChips() {
        if (!chips) {
            return;
        }

        chips.innerHTML = '';

        if (!seleccion.length) {
            chips.innerHTML = '<span class="px-1 text-xs text-muted">Ninguno</span>';
            return;
        }

        seleccion.forEach((id) => {
            const miembro = miembroPorId(id);
            const chip = document.createElement('span');
            chip.className = 'inline-flex items-center gap-1 rounded-full border border-line bg-surface px-2 py-1 text-xs text-ink';
            chip.innerHTML = `<span>${escapar(miembro?.nombre || ('#' + id))}</span>`;

            const quitarChip = document.createElement('button');
            quitarChip.type = 'button';
            quitarChip.className = 'grid size-4 place-items-center rounded-full text-muted hover:bg-surface-2 hover:text-ink';
            quitarChip.setAttribute('aria-label', 'Quitar');
            quitarChip.innerHTML = window.cdaIcon('x', 'text-[0.6rem]');
            quitarChip.addEventListener('click', () => toggle(id));
            chip.appendChild(quitarChip);
            chips.appendChild(chip);
        });
    }

    function renderInputs() {
        if (!inputs) {
            return;
        }

        inputs.innerHTML = '';
        seleccion.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = String(id);
            inputs.appendChild(input);
        });
    }

    function renderLista() {
        if (!lista) {
            return;
        }

        const consulta = (buscar?.value || '').trim().toLowerCase();
        const visibles = miembros.filter((miembro) => disponible(miembro) && `${miembro.nombre} ${miembro.cedula || ''}`.toLowerCase().includes(consulta));

        lista.innerHTML = '';

        if (!miembros.filter(disponible).length) {
            lista.innerHTML = '<p class="px-3 py-3 text-sm text-muted">No hay miembros disponibles para asignar.</p>';
            return;
        }

        if (!visibles.length) {
            lista.innerHTML = '<p class="px-3 py-3 text-sm text-muted">No hay miembros que coincidan.</p>';
            return;
        }

        visibles.forEach((miembro) => {
            const id = Number(miembro.id);
            const activo = seleccion.includes(id);
            const fila = document.createElement('div');
            fila.className = 'flex items-center justify-between gap-3 border-b border-line px-3 py-2 last:border-b-0';
            fila.innerHTML = `
                <div class="min-w-0">
                    <p class="truncate text-sm text-ink">${escapar(miembro.nombre)}</p>
                    ${miembro.cedula ? `<p class="truncate text-xs text-muted">${escapar(miembro.cedula)}</p>` : ''}
                </div>
            `;

            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = activo
                ? 'h-7 rounded-md bg-accent px-2 text-xs font-medium text-accent-ink'
                : 'h-7 rounded-md border border-line bg-surface px-2 text-xs font-medium text-ink hover:bg-surface-2';
            boton.textContent = activo ? 'Marcado' : 'Marcar';
            boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
            boton.addEventListener('click', () => toggle(id));
            fila.appendChild(boton);
            lista.appendChild(fila);
        });
    }

    function dialogoAbierto() {
        const dialogo = raiz.closest('dialog');

        return !dialogo || dialogo.open;
    }

    function render(forzarLista = false) {
        renderChips();
        renderInputs();

        if (forzarLista || dialogoAbierto()) {
            renderLista();
        }
    }

    buscar?.addEventListener('input', renderLista);
    buscar?.addEventListener('focus', renderLista);
    raiz.addEventListener('marcar-abrir', () => {
        seleccion = idsDesde(raiz.dataset.seleccion);
        render(true);
    });

    raiz.closest('form')?.addEventListener('reset', () => {
        seleccion = idsDesde(raiz.dataset.seleccion);

        if (buscar) {
            buscar.value = '';
        }

        render(dialogoAbierto());
    });

    render(dialogoAbierto());
}

document.querySelectorAll('[data-asignar-miembros]').forEach(iniciarAsignador);
document.querySelectorAll('[data-marcar-miembros]').forEach(iniciarMarcador);

(function iniciarEdicionPiramide() {
    const vista = document.querySelector('[data-piramide-vista]');
    const toggle = document.querySelector('[data-piramide-toggle]');

    if (!vista || !toggle) {
        return;
    }

    const clasesActivo = ['bg-accent', 'text-accent-ink', 'shadow-sm', 'hover:bg-accent-strong'];
    const clasesInactivo = ['border', 'border-line', 'bg-surface', 'text-ink', 'hover:bg-surface-2'];

    function aplicar(activo) {
        vista.dataset.piramideEditar = activo ? '1' : '0';
        toggle.setAttribute('aria-pressed', activo ? 'true' : 'false');
        toggle.classList.remove(...(activo ? clasesInactivo : clasesActivo));
        toggle.classList.add(...(activo ? clasesActivo : clasesInactivo));

        vista.querySelectorAll('[data-piramide-acciones]').forEach((nodo) => {
            nodo.hidden = !activo;
        });
    }

    toggle.addEventListener('click', () => {
        aplicar(vista.dataset.piramideEditar !== '1');
    });

    aplicar(vista.dataset.piramideEditar === '1');
}());

(function iniciarZoomPiramide() {
    const lienzo = document.querySelector('[data-piramide-escala]');
    const acercar = document.querySelector('[data-piramide-zoom="in"]');
    const alejar = document.querySelector('[data-piramide-zoom="out"]');

    if (!lienzo || !acercar || !alejar) {
        return;
    }

    const minimo = 0.5;
    const maximo = 1.8;
    const paso = 0.1;
    let actual = 1;

    function aplicar() {
        lienzo.style.transform = `scale(${actual})`;
        acercar.disabled = actual >= maximo;
        alejar.disabled = actual <= minimo;
    }

    acercar.addEventListener('click', () => {
        actual = Math.min(maximo, Number((actual + paso).toFixed(2)));
        aplicar();
    });

    alejar.addEventListener('click', () => {
        actual = Math.max(minimo, Number((actual - paso).toFixed(2)));
        aplicar();
    });

    aplicar();
}());

function prepararModalPiramide(boton, dialogo) {
    if (!dialogo || boton.dataset.modalOpen !== 'dialogPiramideNodo') {
        return;
    }

    const formulario = dialogo.querySelector('#formPiramideNodo');
    const padre = boton.dataset.piramidePadre ?? '';
    const equipoId = boton.dataset.piramideId || '';
    const nombreEquipo = boton.dataset.piramideNombre || '';
    const miembros = boton.dataset.piramideMiembros || '';
    const campoPadre = dialogo.querySelector('#piramidePadreId');
    const campoNombre = dialogo.querySelector('#piramideNombre');
    const campoMetodo = dialogo.querySelector('#piramideMetodo');
    const contexto = dialogo.querySelector('[data-piramide-contexto]');
    const marcador = dialogo.querySelector('[data-marcar-miembros]');

    if (campoPadre) {
        campoPadre.value = padre;
    }

    if (campoNombre) {
        campoNombre.value = equipoId ? nombreEquipo : '';
        campoNombre.placeholder = padre ? 'Ej. Equipo de conexión' : 'Ej. Cabezas de la iglesia';
    }

    if (formulario && campoMetodo) {
        const base = formulario.dataset.guardar;
        campoMetodo.value = equipoId ? 'PUT' : 'POST';
        formulario.action = equipoId ? `${base.replace(/\/$/, '')}/${equipoId}` : base;
    }

    if (marcador) {
        marcador.dataset.seleccion = equipoId ? miembros : '';
    }

    if (contexto) {
        if (equipoId) {
            contexto.textContent = `Editar «${nombreEquipo}»`;
        } else if (padre) {
            contexto.textContent = `Nuevo equipo debajo de «${nombreEquipo}»`;
        } else {
            contexto.textContent = 'Equipo del primer nivel';
        }
    }
}

document.addEventListener('click', (evento) => {
    const abrir = evento.target.closest('[data-modal-open], [data-detalle-editar]');

    if (!abrir) {
        return;
    }

    const dialogo = abrir.dataset.modalOpen
        ? document.getElementById(abrir.dataset.modalOpen)
        : abrir.closest('dialog');

    prepararModalPiramide(abrir, dialogo);

    window.requestAnimationFrame(() => {
        dialogo?.querySelectorAll('[data-asignar-miembros]').forEach((nodo) => {
            nodo.dispatchEvent(new Event('asignar-abrir'));
        });
        dialogo?.querySelectorAll('[data-marcar-miembros]').forEach((nodo) => {
            nodo.dispatchEvent(new Event('marcar-abrir'));
        });
    });
});
