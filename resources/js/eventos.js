function tokenCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function configEventosRegistro() {
    const nodo = document.getElementById('configEventosRegistro');

    if (!nodo) {
        return {};
    }

    try {
        return JSON.parse(nodo.textContent || '{}');
    } catch (e) {
        return {};
    }
}

function prepararRegistro(formulario) {
    const catalogo = configEventosRegistro();
    const selectEvento = formulario.querySelector('[name="evento_id"]');
    const selectTipo = formulario.querySelector('[name="tipo_entrada_id"]');
    const valor = formulario.querySelector('[name="valor"]');
    const numeracion = formulario.querySelector('[data-numeracion]');
    const prefijo = formulario.querySelector('[data-prefijo]');
    const bloqueNumeracion = formulario.querySelector('[data-bloque-numeracion]');
    const extras = formulario.querySelector('[data-campos-adicionales]');
    const bloqueAdicionales = formulario.querySelector('[data-bloque-adicionales]');
    const vacioAdicionales = formulario.querySelector('[data-adicionales-vacio]');
    const bloqueEstado = formulario.querySelector('[data-bloque-estado]');
    const bloquePago = formulario.querySelector('[data-bloque-pago]');
    const pagoPendiente = formulario.querySelector('[data-pago-pendiente]');
    const pagoMetodos = formulario.querySelector('[data-pago-metodos]');
    const enviar = formulario.querySelector('[data-submit-registro]')
        || (formulario.id ? document.querySelector(`[data-submit-registro][form="${formulario.id}"]`) : null);
    const puedeEstado = formulario.dataset.puedeEstado === '1';
    const soloPago = formulario.dataset.soloPago === '1';

    function habilitar(paso) {
        formulario.querySelectorAll('input:not([type="hidden"]), select, textarea, button[type="submit"]').forEach((nodo) => {
            if (nodo === selectEvento || nodo === numeracion || nodo.closest('[data-bloque-numeracion]')) {
                return;
            }

            nodo.disabled = !paso;
        });

        if (enviar) {
            enviar.disabled = !paso;
        }
    }

    function pintarCampos(campos) {
        if (!extras) {
            return;
        }

        extras.innerHTML = '';
        const lista = campos || [];
        const hayEvento = !!selectEvento?.value;

        if (vacioAdicionales) {
            vacioAdicionales.hidden = lista.length > 0;
            vacioAdicionales.textContent = !hayEvento
                ? 'Seleccione un evento para ver si pide datos extra.'
                : 'Este evento no pide datos adicionales.';
        }

        if (extras) {
            extras.classList.toggle('hidden', lista.length === 0);
        }

        lista.forEach((campo) => {
            const wrap = document.createElement('div');
            wrap.className = 'space-y-1.5';
            const label = document.createElement('label');
            label.className = 'block text-sm font-medium text-ink';
            label.textContent = campo.etiqueta || 'Dato';
            if (campo.obligatorio) {
                const marca = document.createElement('span');
                marca.className = 'text-danger';
                marca.textContent = ' *';
                label.appendChild(marca);
            }

            let control;
            if (campo.tipo === 'lista') {
                control = document.createElement('select');
                control.className = 'input';
                const vacia = document.createElement('option');
                vacia.value = '';
                vacia.textContent = 'Seleccione…';
                control.appendChild(vacia);
                (campo.opciones || []).forEach((opcion) => {
                    const item = document.createElement('option');
                    item.value = opcion;
                    item.textContent = opcion;
                    control.appendChild(item);
                });
            } else {
                control = document.createElement('input');
                control.className = 'input';
                control.type = campo.tipo === 'numero' ? 'number' : (campo.tipo === 'fecha' ? 'date' : 'text');
            }

            control.name = `campo_adicional[${campo.id}]`;
            control.required = !!campo.obligatorio;
            wrap.append(label, control);
            extras.appendChild(wrap);
        });
    }

    function sincronizarPago(tipo) {
        const gratis = !!tipo?.es_gratis;

        if (bloqueEstado) {
            bloqueEstado.hidden = gratis || !puedeEstado;
        }

        if (bloquePago) {
            bloquePago.hidden = false;
        }

        if (gratis) {
            if (bloquePago) {
                bloquePago.hidden = true;
            }
            return;
        }

        const estadoPagado = soloPago || formulario.querySelector('[name="estado_pago"][value="pagado"]')?.checked;
        if (pagoPendiente) {
            pagoPendiente.hidden = estadoPagado;
        }
        if (pagoMetodos) {
            pagoMetodos.hidden = !estadoPagado && !soloPago;
        }
        if (soloPago && bloquePago) {
            bloquePago.hidden = false;
            if (pagoPendiente) {
                pagoPendiente.hidden = true;
            }
            if (pagoMetodos) {
                pagoMetodos.hidden = false;
            }
        }
    }

    function aplicarEvento() {
        const id = selectEvento.value;
        const datos = catalogo[id] || catalogo[String(id)] || {};
        const tipos = datos.tipos || [];

        selectTipo.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = tipos.length ? 'Seleccione tipo de entrada…' : 'Este evento no tiene tipos de entrada';
        selectTipo.appendChild(placeholder);

        tipos.forEach((tipo) => {
            const opcion = document.createElement('option');
            opcion.value = String(tipo.id);
            opcion.textContent = tipo.es_gratis
                ? `${tipo.nombre} (Gratis)`
                : `${tipo.nombre} ($ ${Number(tipo.valor || 0).toFixed(2)})`;
            opcion.dataset.tipo = JSON.stringify(tipo);
            selectTipo.appendChild(opcion);
        });

        habilitar(!!id);
        selectTipo.disabled = !id || tipos.length === 0;
        pintarCampos(datos.campos || []);
        sincronizarNumeracion(!!id && !!datos.requiere_numeracion);

        if (enviar) {
            enviar.disabled = !id;
        }

        if (tipos.length === 1) {
            selectTipo.value = String(tipos[0].id);
        }

        aplicarTipo();
    }

    function aplicarTipo() {
        const opcion = selectTipo.options[selectTipo.selectedIndex];
        let tipo = {};
        try {
            tipo = JSON.parse(opcion?.dataset.tipo || '{}');
        } catch (e) {
            tipo = {};
        }

        if (valor) {
            valor.value = tipo.es_gratis ? '0' : (tipo.valor ?? '');
            valor.disabled = !!tipo.es_gratis;
        }

        sincronizarPago(tipo);
        aplicarPrefijo(tipo);
    }

    function sincronizarNumeracion(requiere) {
        if (bloqueNumeracion) {
            bloqueNumeracion.classList.toggle('hidden', !requiere);
        }

        if (numeracion) {
            numeracion.required = !!requiere;
            numeracion.disabled = !requiere;
            if (!requiere) {
                numeracion.value = '';
            }
        }

        if (!requiere) {
            aplicarPrefijo({});
        }
    }

    function aplicarPrefijo(tipo) {
        if (!prefijo || !numeracion) {
            return;
        }

        const tiene = !!tipo.prefijo;
        prefijo.textContent = tipo.prefijo || '';
        prefijo.classList.toggle('hidden', !tiene);
        prefijo.classList.toggle('flex', tiene);
        numeracion.classList.toggle('rounded-l-none', tiene);
    }

    selectEvento?.addEventListener('change', aplicarEvento);
    selectTipo?.addEventListener('change', aplicarTipo);

    if (selectEvento?.value) {
        aplicarEvento();
    }
    formulario.querySelectorAll('[name="estado_pago"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            const opcion = selectTipo.options[selectTipo.selectedIndex];
            let tipo = {};
            try {
                tipo = JSON.parse(opcion?.dataset.tipo || '{}');
            } catch (e) {
                tipo = {};
            }
            sincronizarPago(tipo);
        });
    });
}

function prepararCatalogo(formulario) {
    const listaTipos = formulario.querySelector('[data-lista-tipos]');
    const listaCampos = formulario.querySelector('[data-lista-campos]');

    formulario.addEventListener('click', (evento) => {
        if (evento.target.closest('[data-agregar-tipo]') && listaTipos) {
            const fila = listaTipos.querySelector('[data-fila-tipo]');
            if (!fila) {
                return;
            }
            const copia = fila.cloneNode(true);
            copia.querySelectorAll('input[type="text"], input[type="number"]').forEach((input) => {
                input.value = input.name.includes('valor') ? '0' : '';
            });
            copia.querySelectorAll('input[type="checkbox"]').forEach((input) => {
                input.checked = input.hasAttribute('data-check-visible');
            });
            copia.querySelector('[data-visible]').value = '1';
            copia.querySelector('[data-gratis]').value = '0';
            listaTipos.appendChild(copia);
        }

        if (evento.target.closest('[data-quitar-tipo]') && listaTipos?.querySelectorAll('[data-fila-tipo]').length > 1) {
            evento.target.closest('[data-fila-tipo]')?.remove();
        }

        if (evento.target.closest('[data-agregar-campo]') && listaCampos) {
            const plantilla = listaCampos.querySelector('[data-fila-campo]');
            const copia = plantilla
                ? plantilla.cloneNode(true)
                : (() => {
                    const div = document.createElement('div');
                    div.className = 'grid gap-3 rounded-lg border border-line p-3 sm:grid-cols-[1fr_8rem_1fr_auto]';
                    div.dataset.filaCampo = '';
                    div.innerHTML = `
                        <input class="input" name="campo_adicional[etiqueta][]" maxlength="100" placeholder="Ej. Iglesia, talla…">
                        <select class="input" name="campo_adicional[tipo][]" data-tipo-campo>
                            <option value="texto">Texto</option>
                            <option value="lista">Lista</option>
                            <option value="numero">Número</option>
                            <option value="fecha">Fecha</option>
                        </select>
                        <textarea class="input" name="campo_adicional[opciones][]" rows="2" placeholder="Una opción por línea" data-opciones-campo hidden></textarea>
                        <div class="space-y-1">
                            <input type="hidden" name="campo_adicional[obligatorio][]" value="0" data-obligatorio>
                            <label class="block text-xs"><input type="checkbox" data-check-obligatorio> Obligatorio</label>
                            <button type="button" class="text-danger text-xs" data-quitar-campo>Quitar</button>
                        </div>`;
                    return div;
                })();

            if (plantilla) {
                copia.querySelectorAll('input[type="text"], textarea').forEach((input) => {
                    input.value = '';
                });
                const obligatorio = copia.querySelector('[data-obligatorio]');
                const check = copia.querySelector('[data-check-obligatorio]');
                if (obligatorio) {
                    obligatorio.value = '0';
                }
                if (check) {
                    check.checked = false;
                }
            }

            listaCampos.appendChild(copia);
        }

        if (evento.target.closest('[data-quitar-campo]')) {
            evento.target.closest('[data-fila-campo]')?.remove();
        }
    });

    formulario.addEventListener('change', (evento) => {
        const filaTipo = evento.target.closest('[data-fila-tipo]');
        if (filaTipo && evento.target.matches('[data-check-visible]')) {
            filaTipo.querySelector('[data-visible]').value = evento.target.checked ? '1' : '0';
        }
        if (filaTipo && evento.target.matches('[data-check-gratis]')) {
            filaTipo.querySelector('[data-gratis]').value = evento.target.checked ? '1' : '0';
        }

        const filaCampo = evento.target.closest('[data-fila-campo]');
        if (filaCampo && evento.target.matches('[data-check-obligatorio]')) {
            filaCampo.querySelector('[data-obligatorio]').value = evento.target.checked ? '1' : '0';
        }
        if (filaCampo && evento.target.matches('[data-tipo-campo]')) {
            const opciones = filaCampo.querySelector('[data-opciones-campo]');
            if (opciones) {
                opciones.hidden = evento.target.value !== 'lista';
            }
        }
    });
}

function prepararCheckout(formulario) {
    const error = document.querySelector('[data-checkout-error]');
    const resultado = document.querySelector('[data-checkout-resultado]');

    async function enviar(url, cuerpo) {
        const datos = new URLSearchParams(cuerpo);
        datos.set('_token', tokenCsrf());
        const respuesta = await fetch(url, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': tokenCsrf() },
            body: datos,
        });
        return respuesta.json();
    }

    function pintarTickets(tickets) {
        resultado.hidden = false;
        resultado.classList.remove('hidden');
        resultado.innerHTML = tickets.map((ticket) => `
            <div class="rounded-xl border border-line p-4">
                <p class="font-medium">${ticket.nombre}</p>
                <p class="mt-1 text-sm text-muted">${ticket.numeracion || 'Sin numeración'} · ${ticket.tipo_entrada || '—'} · ${ticket.estado}</p>
                <p class="mt-1 text-sm">${ticket.asistencia}</p>
                <div class="mt-3 flex gap-2">
                    ${ticket.asistio
                        ? (ticket.puede_reversar ? `<button type="button" class="inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs" data-reversar="${ticket.id}">Reversar</button>` : '')
                        : `<button type="button" class="inline-flex h-8 items-center rounded-lg bg-accent px-3 text-xs text-accent-ink" data-marcar="${ticket.id}">Marcar asistencia</button>`}
                </div>
            </div>
        `).join('');
    }

    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        error.classList.add('hidden');
        resultado.classList.add('hidden');

        const datos = await enviar(formulario.dataset.consultar, {
            evento_id: formulario.evento_id.value,
            codigo: formulario.codigo.value,
        });

        if (!datos.ok) {
            error.textContent = datos.error || 'No se encontró el ticket.';
            error.classList.remove('hidden');
            return;
        }

        pintarTickets(datos.tickets || []);
    });

    resultado?.addEventListener('click', async (evento) => {
        const marcar = evento.target.closest('[data-marcar]');
        const reversar = evento.target.closest('[data-reversar]');
        const id = marcar?.dataset.marcar || reversar?.dataset.reversar;

        if (!id) {
            return;
        }

        const datos = await enviar(marcar ? formulario.dataset.marcar : formulario.dataset.reversar, { id });

        if (!datos.ok) {
            error.textContent = datos.error || 'No se pudo actualizar.';
            error.classList.remove('hidden');
            return;
        }

        pintarTickets([datos.ticket]);
    });
}

document.querySelectorAll('[data-registro-evento]').forEach(prepararRegistro);
document.querySelectorAll('[data-catalogo-evento]').forEach(prepararCatalogo);
document.querySelectorAll('[data-checkout]').forEach(prepararCheckout);
