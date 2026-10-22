(function () {
  'use strict';

  function obtenerOpcionEvento(contenedor) {
    var eventoSelect = contenedor.querySelector('select[name="evento_id"]');
    if (!eventoSelect || !eventoSelect.value) {
      return null;
    }

    return eventoSelect.options[eventoSelect.selectedIndex] || null;
  }

  function obtenerCamposAdicionalesEvento(contenedor) {
    var opcion = obtenerOpcionEvento(contenedor);
    if (!opcion) {
      return null;
    }

    var raw = opcion.getAttribute('data-campos-adicionales') || '[]';
    try {
      var campos = JSON.parse(raw);
      return Array.isArray(campos) ? campos : [];
    } catch (e) {
      return [];
    }
  }

  function obtenerValoresInfoAdicional(contenedor) {
    var destino = contenedor.querySelector('.js-campos-adicionales-evento');
    if (!destino) {
      return [];
    }

    var raw = destino.getAttribute('data-valores') || '[]';
    try {
      var valores = JSON.parse(raw);
      return Array.isArray(valores) ? valores : [];
    } catch (e) {
      return [];
    }
  }

  function valorInfoAdicionalCampo(respuestas, campo) {
    var i;
    for (i = 0; i < respuestas.length; i++) {
      if (String(respuestas[i].id || '') === String(campo.id || '') && String(campo.id || '') !== '0') {
        return respuestas[i].valor || '';
      }
    }
    for (i = 0; i < respuestas.length; i++) {
      if ((respuestas[i].etiqueta || '').toLowerCase() === (campo.etiqueta || '').toLowerCase()) {
        return respuestas[i].valor || '';
      }
    }
    return '';
  }

  function actualizarCamposAdicionales(contenedor) {
    var destino = contenedor.querySelector('.js-campos-adicionales-evento');
    if (!destino) {
      return;
    }

    var campos = obtenerCamposAdicionalesEvento(contenedor);
    var respuestas = obtenerValoresInfoAdicional(contenedor);

    destino.innerHTML = '';

    if (!campos || campos.length === 0) {
      destino.style.display = 'none';
      return;
    }

    destino.style.display = '';

    var filaCampos = document.createElement('div');
    filaCampos.className = 'row g-3';

    campos.forEach(function (campo) {
      var campoId = String(campo.id || '');
      if (!campoId || campoId === '0') {
        return;
      }

      var obligatorio = String(campo.obligatorio) === '1' || campo.obligatorio === true || campo.obligatorio === 1;
      var tipo = campo.tipo || 'texto';
      var opciones = Array.isArray(campo.opciones) ? campo.opciones : [];
      var valorActual = valorInfoAdicionalCampo(respuestas, campo);
      var col = document.createElement('div');
      col.className = 'col-md-6';

      var label = document.createElement('label');
      label.className = 'form-label';
      label.textContent = campo.etiqueta || 'Dato adicional';
      if (obligatorio) {
        var marca = document.createElement('span');
        marca.className = 'text-danger';
        marca.textContent = ' *';
        label.appendChild(marca);
      }

      var control;
      if (tipo === 'lista') {
        control = document.createElement('select');
        control.className = 'form-select js-paso-despues-tipo js-input-info-adicional';
        var vacia = document.createElement('option');
        vacia.value = '';
        vacia.textContent = 'Seleccione…';
        control.appendChild(vacia);
        opciones.forEach(function (opcionTexto) {
          var opt = document.createElement('option');
          opt.value = String(opcionTexto);
          opt.textContent = String(opcionTexto);
          if (String(opcionTexto) === String(valorActual)) {
            opt.selected = true;
          }
          control.appendChild(opt);
        });
      } else {
        control = document.createElement('input');
        control.className = 'form-control js-paso-despues-tipo js-input-info-adicional';
        if (tipo === 'numero') {
          control.type = 'number';
          control.step = 'any';
          control.placeholder = '0';
        } else if (tipo === 'fecha') {
          control.type = 'date';
        } else {
          control.type = 'text';
          control.maxLength = 255;
          control.placeholder = 'Ingrese ' + (campo.etiqueta || 'dato').toLowerCase() + '…';
        }
        control.value = valorActual;
      }

      control.name = 'info_adicional[' + campoId + ']';
      control.required = obligatorio;

      col.appendChild(label);
      col.appendChild(control);
      filaCampos.appendChild(col);
    });

    destino.appendChild(filaCampos);
  }

  function sincronizarObligatorioFila(fila) {
    var hidden = fila.querySelector('.js-obligatorio-valor');
    var check = fila.querySelector('.js-obligatorio-check');
    if (hidden && check) {
      hidden.value = check.checked ? '1' : '0';
    }
  }

  function enlazarObligatorioFila(fila) {
    var check = fila.querySelector('.js-obligatorio-check');
    if (!check || check.dataset.boundObligatorio) {
      return;
    }

    check.dataset.boundObligatorio = '1';
    check.addEventListener('change', function () {
      sincronizarObligatorioFila(fila);
    });
    sincronizarObligatorioFila(fila);
  }

  function sincronizarTipoCampoFila(fila) {
    var tipoSelect = fila.querySelector('.js-tipo-campo-adicional');
    var bloqueOpciones = fila.querySelector('.js-bloque-opciones-campo');
    if (!bloqueOpciones) {
      return;
    }
    bloqueOpciones.style.display = tipoSelect && tipoSelect.value === 'lista' ? '' : 'none';
  }

  function enlazarTipoCampoFila(fila) {
    var tipoSelect = fila.querySelector('.js-tipo-campo-adicional');
    if (!tipoSelect || tipoSelect.dataset.boundTipoCampo) {
      return;
    }

    tipoSelect.dataset.boundTipoCampo = '1';
    tipoSelect.addEventListener('change', function () {
      sincronizarTipoCampoFila(fila);
    });
    sincronizarTipoCampoFila(fila);
  }

  function resetearFilaCampoAdicional(fila) {
    var uid = 'campo_adicional_' + Date.now() + '_' + Math.floor(Math.random() * 10000);

    fila.querySelectorAll('input').forEach(function (input) {
      if (input.classList.contains('js-obligatorio-valor')) {
        input.value = '1';
      } else if (input.classList.contains('js-obligatorio-check')) {
        input.checked = true;
        input.removeAttribute('data-bound-obligatorio');
        input.id = uid;
      } else if (input.type === 'text') {
        input.value = '';
      }
    });

    var tipoSelect = fila.querySelector('.js-tipo-campo-adicional');
    if (tipoSelect) {
      tipoSelect.value = 'texto';
      tipoSelect.removeAttribute('data-bound-tipo-campo');
    }

    var opciones = fila.querySelector('.js-opciones-campo-adicional');
    if (opciones) {
      opciones.value = '';
    }

    fila.querySelectorAll('label[for]').forEach(function (label) {
      label.setAttribute('for', uid);
    });

    sincronizarTipoCampoFila(fila);
  }

  function crearFilaCampoAdicional(contenedor) {
    var lista = contenedor.querySelector('.js-campos-adicionales-lista');
    var plantilla = lista ? lista.querySelector('.js-campo-adicional-fila') : null;
    if (!lista || !plantilla) {
      return;
    }

    var nueva = plantilla.cloneNode(true);
    resetearFilaCampoAdicional(nueva);
    lista.appendChild(nueva);
    enlazarObligatorioFila(nueva);
    enlazarTipoCampoFila(nueva);
  }

  function obtenerTiposEntradaEvento(contenedor) {
    var opcion = obtenerOpcionEvento(contenedor);
    if (!opcion) {
      return null;
    }

    var raw = opcion.getAttribute('data-tipos-entrada') || '[]';
    try {
      var tipos = JSON.parse(raw);
      return Array.isArray(tipos) ? tipos : [];
    } catch (e) {
      return [];
    }
  }

  function obtenerValorEventoSeleccionado(contenedor) {
    var tipoSelect = contenedor.querySelector('.js-tipo-entrada-evento');
    if (tipoSelect && tipoSelect.value) {
      var opcionTipo = tipoSelect.options[tipoSelect.selectedIndex];
      if (opcionTipo) {
        return parseFloat(opcionTipo.getAttribute('data-valor') || '0');
      }
    }

    var tipos = obtenerTiposEntradaEvento(contenedor);
    if (tipos === null) {
      return null;
    }

    if (tipos.length === 1) {
      return parseFloat(tipos[0].valor || 0);
    }

    if (tipos.length > 1) {
      return null;
    }

    var opcion = obtenerOpcionEvento(contenedor);
    return opcion ? parseFloat(opcion.getAttribute('data-valor') || '0') : 0;
  }

  function tipoEntradaEsGratis(contenedor) {
    var tipoSelect = contenedor.querySelector('.js-tipo-entrada-evento');
    if (!tipoSelect || !tipoSelect.value) {
      return false;
    }

    var opcionTipo = tipoSelect.options[tipoSelect.selectedIndex];
    return !!(opcionTipo && opcionTipo.getAttribute('data-es-gratis') === '1');
  }

  function sincronizarVisiblePublicoFila(fila) {
    var hidden = fila.querySelector('.js-visible-publico-valor');
    var check = fila.querySelector('.js-visible-publico-check');
    if (hidden && check) {
      hidden.value = check.checked ? '1' : '0';
    }
  }

  function sincronizarEsGratisFila(fila) {
    var hidden = fila.querySelector('.js-es-gratis-valor');
    var check = fila.querySelector('.js-es-gratis-check');
    var valorInput = fila.querySelector('.js-valor-tipo-entrada');

    if (hidden && check) {
      hidden.value = check.checked ? '1' : '0';
    }

    if (valorInput && check) {
      valorInput.disabled = check.checked;
      valorInput.required = !check.checked;
      if (check.checked) {
        valorInput.value = '0';
      }
    }
  }

  function enlazarVisiblePublicoFila(fila) {
    var check = fila.querySelector('.js-visible-publico-check');
    if (!check || check.dataset.boundVisiblePublico) {
      return;
    }

    check.dataset.boundVisiblePublico = '1';
    check.addEventListener('change', function () {
      sincronizarVisiblePublicoFila(fila);
    });
    sincronizarVisiblePublicoFila(fila);
  }

  function enlazarEsGratisFila(fila) {
    var check = fila.querySelector('.js-es-gratis-check');
    if (!check || check.dataset.boundEsGratis) {
      return;
    }

    check.dataset.boundEsGratis = '1';
    check.addEventListener('change', function () {
      sincronizarEsGratisFila(fila);
    });
    sincronizarEsGratisFila(fila);
  }

  function enlazarPrefijoTipoEntradaFila(fila) {
    var input = fila.querySelector('.js-prefijo-tipo-entrada');
    if (!input || input.dataset.boundPrefijoTipo) {
      return;
    }

    input.dataset.boundPrefijoTipo = '1';
    input.addEventListener('input', function () {
      input.value = String(input.value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 10);
    });
  }

  function leerValorNumerico(input, fallback) {
    if (!input || input.value === '') {
      return fallback;
    }
    var n = parseFloat(input.value);
    return isNaN(n) ? fallback : n;
  }

  function puedeGestionarEstadoPago(contenedor) {
    var bloque = contenedor.querySelector('.js-form-registro-evento');
    var fuente = bloque || contenedor;

    if (fuente.getAttribute('data-solo-metodos-pago') === '1') {
      return false;
    }

    return fuente.getAttribute('data-puede-estado-pago') === '1';
  }

  function soloMetodosPago(contenedor) {
    var bloque = contenedor.querySelector('.js-form-registro-evento');
    var fuente = bloque || contenedor;
    return fuente.getAttribute('data-solo-metodos-pago') === '1';
  }

  function obtenerEstadoPagoSeleccionado(contenedor) {
    if (!puedeGestionarEstadoPago(contenedor)) {
      return 'por_cancelar';
    }

    var marcado = contenedor.querySelector('.js-estado-pago-evento:checked');
    return marcado ? marcado.value : 'por_cancelar';
  }

  function setVisible(elemento, visible) {
    if (!elemento) {
      return;
    }
    elemento.style.display = visible ? '' : 'none';
  }

  function normalizarNombreTipoEntrada(texto) {
    return String(texto || '').trim().toLowerCase();
  }

  function esFormularioEdicionRegistro(contenedor) {
    var accion = contenedor.querySelector('input[name="accion"]');
    return !!(accion && accion.value === 'actualizar_registro_evento');
  }

  function obtenerTipoEntradaGuardado(contenedor) {
    var bloque = contenedor.querySelector('.js-form-registro-evento') || contenedor;
    var tipoSelect = contenedor.querySelector('.js-tipo-entrada-evento');
    var id = bloque.getAttribute('data-tipo-entrada-id') || '';
    var nombre = bloque.getAttribute('data-tipo-entrada-nombre') || '';
    var opcionActual = tipoSelect && tipoSelect.selectedIndex >= 0
      ? tipoSelect.options[tipoSelect.selectedIndex]
      : null;

    if (opcionActual && opcionActual.value) {
      if (!id) {
        id = opcionActual.value;
      }
      if (!nombre) {
        nombre = opcionActual.textContent || '';
      }
    }

    return {
      id: String(id || ''),
      nombre: String(nombre || '').trim(),
      valor: opcionActual ? opcionActual.getAttribute('data-valor') : null,
      esGratis: opcionActual ? opcionActual.getAttribute('data-es-gratis') : null
    };
  }

  function tipoCoincideConGuardado(tipo, guardado) {
    if (!tipo || !guardado) {
      return false;
    }

    if (guardado.nombre && normalizarNombreTipoEntrada(tipo.nombre) === normalizarNombreTipoEntrada(guardado.nombre)) {
      return true;
    }

    return !!(!guardado.nombre && guardado.id && String(tipo.id || '') === String(guardado.id));
  }

  function actualizarSelectTiposEntrada(contenedor) {
    var campoTipo = contenedor.querySelector('.js-campo-tipo-entrada-evento');
    var tipoSelect = contenedor.querySelector('.js-tipo-entrada-evento');
    var tipos = obtenerTiposEntradaEvento(contenedor);
    var guardado = obtenerTipoEntradaGuardado(contenedor);

    if (!campoTipo || !tipoSelect) {
      return;
    }

    setVisible(campoTipo, true);

    var sinEvento = tipos === null;
    var tieneTipos = !sinEvento && tipos.length > 0;

    if (sinEvento) {
      tipoSelect.innerHTML = '<option value="">Seleccione evento primero…</option>';
    } else if (!tieneTipos) {
      tipoSelect.innerHTML = '<option value="">Sin tipos de entrada</option>';
    } else {
      tipoSelect.innerHTML = '<option value="">Seleccione tipo…</option>';
      tipos.forEach(function (tipo) {
        var option = document.createElement('option');
        option.value = String(tipo.id || '');
        option.textContent = tipo.nombre || '';
        option.setAttribute('data-valor', String(tipo.valor != null ? tipo.valor : 0));
        option.setAttribute('data-es-gratis', String(tipo.es_gratis ? 1 : 0));
        option.setAttribute('data-prefijo', String(tipo.prefijo || '').toUpperCase());
        if (tipoCoincideConGuardado(tipo, guardado)) {
          option.selected = true;
        }
        tipoSelect.appendChild(option);
      });

      if (!tipoSelect.value && guardado.id) {
        var extra = document.createElement('option');
        extra.value = String(guardado.id);
        extra.textContent = guardado.nombre || 'Tipo actual';
        extra.setAttribute('data-valor', String(guardado.valor != null ? guardado.valor : 0));
        extra.setAttribute('data-es-gratis', String(guardado.esGratis === '1' ? 1 : 0));
        extra.setAttribute('data-prefijo', String(guardado.prefijo || '').toUpperCase());
        extra.selected = true;
        tipoSelect.appendChild(extra);
      }

      if (!tipoSelect.value && tipos.length === 1) {
        tipoSelect.value = String(tipos[0].id || '');
      }
    }

    tipoSelect.required = tieneTipos;
    tipoSelect.disabled = !tieneTipos;
  }

  function actualizarNumeracion(contenedor, opciones) {
    opciones = opciones || {};
    var eventoSelect = contenedor.querySelector('select[name="evento_id"]');
    var campoNumeracion = contenedor.querySelector('.js-campo-numeracion-evento');
    var inputNumeracion = contenedor.querySelector('input[name="numeracion"]');

    if (!campoNumeracion || !inputNumeracion) {
      return;
    }

    setVisible(campoNumeracion, true);

    var hayEvento = !!(eventoSelect && eventoSelect.value);
    var requiere = false;

    if (hayEvento) {
      var opcion = eventoSelect.options[eventoSelect.selectedIndex];
      requiere = !!(opcion && opcion.getAttribute('data-requiere-numeracion') === '1');
    }

    inputNumeracion.disabled = !requiere;
    inputNumeracion.required = requiere;
    inputNumeracion.placeholder = !hayEvento
      ? 'Seleccione evento primero…'
      : (requiere ? 'Número del ticket' : 'No aplica para este evento');

    if (!requiere && opciones.resetearCamposEvento) {
      inputNumeracion.value = '';
    }

    var label = campoNumeracion.querySelector('.form-label');
    if (label) {
      var marca = label.querySelector('.text-danger');
      if (requiere && !marca) {
        label.insertAdjacentHTML('beforeend', ' <span class="text-danger">*</span>');
      } else if (!requiere && marca) {
        marca.remove();
      }
    }

    actualizarPrefijoNumeracion(contenedor);
  }

  function obtenerPrefijoTipoSeleccionado(contenedor) {
    var tipoSelect = contenedor.querySelector('.js-tipo-entrada-evento');
    if (!tipoSelect || !tipoSelect.value) {
      return '';
    }

    var opcion = tipoSelect.options[tipoSelect.selectedIndex];
    return String((opcion && opcion.getAttribute('data-prefijo')) || '').toUpperCase();
  }

  function extraerNumeroSinPrefijo(valor, prefijo) {
    valor = String(valor || '').trim();
    prefijo = String(prefijo || '').toUpperCase();
    if (!prefijo || !valor) {
      return valor;
    }

    var escapado = prefijo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return valor.replace(new RegExp('^' + escapado + '-?', 'i'), '');
  }

  function actualizarPrefijoNumeracion(contenedor) {
    var addon = contenedor.querySelector('.js-prefijo-numeracion');
    var inputNumeracion = contenedor.querySelector('input[name="numeracion"]');
    if (!addon || !inputNumeracion) {
      return;
    }

    var prefijoAnterior = String(addon.getAttribute('data-prefijo') || '');
    var prefijo = obtenerPrefijoTipoSeleccionado(contenedor);

    if (prefijoAnterior) {
      inputNumeracion.value = extraerNumeroSinPrefijo(inputNumeracion.value, prefijoAnterior);
    }
    if (prefijo) {
      inputNumeracion.value = extraerNumeroSinPrefijo(inputNumeracion.value, prefijo);
      addon.textContent = prefijo;
      addon.classList.remove('d-none');
    } else {
      addon.textContent = '';
      addon.classList.add('d-none');
    }

    addon.setAttribute('data-prefijo', prefijo);
  }

  function actualizarBloquePagoEvento(contenedor, opciones) {
    opciones = opciones || {};
    var sugerirValor = !!opciones.sugerirValor;
    var puedeEstado = puedeGestionarEstadoPago(contenedor);
    var esSoloMetodos = soloMetodosPago(contenedor);
    var valorCatalogo = obtenerValorEventoSeleccionado(contenedor);
    var tipos = obtenerTiposEntradaEvento(contenedor);
    var sinEvento = tipos === null;
    var tipoSelect = contenedor.querySelector('.js-tipo-entrada-evento');
    var sinTipo = !sinEvento && tipos.length > 0 && (!tipoSelect || !tipoSelect.value);
    var tipoListo = !sinEvento && !sinTipo;
    var campoValor = contenedor.querySelector('.js-campo-valor-evento');
    var bloqueFormaPago = contenedor.querySelector('.js-bloque-forma-pago-evento');
    var bloqueEstado = contenedor.querySelector('.js-bloque-estado-pago-evento');
    var bloquePagoLegacy = contenedor.querySelector('.js-bloque-pago-evento');
    var bloquePendienteTexto = contenedor.querySelector('.js-bloque-forma-pago-pendiente');
    var bloqueMetodos = contenedor.querySelector('.js-bloque-metodos-pago');
    var hiddenFormaPago = contenedor.querySelector('.js-forma-pago-gratuito');
    var hiddenFormaPagoPendiente = contenedor.querySelector('.js-forma-pago-pendiente');
    var hiddenValor = contenedor.querySelector('.js-valor-gratuito');
    var hiddenEstado = contenedor.querySelector('.js-estado-pago-gratuito');
    var hiddenEstadoOculto = contenedor.querySelector('.js-estado-pago-oculto');
    var hiddenEstadoCounter = contenedor.querySelector('.js-estado-pago-counter');
    var valorInput = contenedor.querySelector('.js-valor-evento');
    var metodosPago = contenedor.querySelectorAll('.js-metodo-pago-evento');
    var estadosPago = contenedor.querySelectorAll('.js-estado-pago-evento');
    var pasosDespuesTipo = contenedor.querySelectorAll('.js-paso-despues-tipo');
    var botonSubmit = contenedor.querySelector('.js-submit-registro-evento');

    if (valorInput && sugerirValor && tipoListo && valorCatalogo !== null) {
      valorInput.value = valorCatalogo;
    } else if (valorInput && sugerirValor && (sinEvento || sinTipo)) {
      valorInput.value = '';
    }

    var esGratisTipo = tipoListo && tipoEntradaEsGratis(contenedor);
    var mostrarPago = tipoListo && !esGratisTipo;
    var estadoActual = esGratisTipo ? 'pagado' : (tipoListo ? obtenerEstadoPagoSeleccionado(contenedor) : 'por_cancelar');
    var esPagado = estadoActual === 'pagado';
    var esPorCancelar = estadoActual === 'por_cancelar';
    var usarPendiente = mostrarPago && !esSoloMetodos && (esPorCancelar || !puedeEstado);
    var usarMetodos = mostrarPago && (esSoloMetodos || (esPagado && puedeEstado));

    setVisible(campoValor, true);
    pasosDespuesTipo.forEach(function (campo) {
      if (campo.classList.contains('js-valor-evento')) {
        campo.disabled = !tipoListo || esGratisTipo;
        campo.required = tipoListo && !esGratisTipo;
        campo.min = '0';
        campo.placeholder = esGratisTipo ? 'Gratuito' : '0.00';
        if (esGratisTipo) {
          campo.value = '0';
        }
        return;
      }

      campo.disabled = !tipoListo;
    });

    if (hiddenValor) {
      hiddenValor.disabled = !esGratisTipo;
      hiddenValor.value = '0';
    }

    if (valorInput) {
      if (esGratisTipo) {
        valorInput.removeAttribute('name');
      } else {
        valorInput.setAttribute('name', 'valor');
      }
    }

    setVisible(bloqueEstado, mostrarPago && puedeEstado);
    setVisible(bloqueFormaPago, mostrarPago);
    if (bloquePagoLegacy) {
      setVisible(bloquePagoLegacy, mostrarPago);
    }

    setVisible(bloquePendienteTexto, usarPendiente);
    setVisible(bloqueMetodos, usarMetodos);

    if (hiddenFormaPago) {
      hiddenFormaPago.disabled = !esGratisTipo;
    }

    if (hiddenFormaPagoPendiente) {
      hiddenFormaPagoPendiente.disabled = !usarPendiente;
    }

    if (hiddenEstado) {
      hiddenEstado.disabled = !esGratisTipo;
    }

    if (hiddenEstadoOculto) {
      hiddenEstadoOculto.disabled = esGratisTipo || puedeEstado || esSoloMetodos;
    }

    if (hiddenEstadoCounter) {
      hiddenEstadoCounter.disabled = !esSoloMetodos || esGratisTipo;
    }

    metodosPago.forEach(function (radio) {
      radio.disabled = !usarMetodos;
      radio.required = usarMetodos;
    });

    estadosPago.forEach(function (radio) {
      radio.disabled = !mostrarPago || !puedeEstado;
      radio.required = mostrarPago && puedeEstado;
    });

    if (botonSubmit) {
      botonSubmit.disabled = !tipoListo;
    }
  }

  function actualizarBotonesQuitarTipos(contenedor) {
    var filas = contenedor.querySelectorAll('.js-tipo-entrada-fila');
    filas.forEach(function (fila) {
      var boton = fila.querySelector('.js-quitar-tipo-entrada');
      if (boton) {
        boton.disabled = filas.length <= 1;
      }
    });
  }

  function actualizarFilasTipoEntrada(contenedor) {
    contenedor.querySelectorAll('.js-tipo-entrada-fila').forEach(function (fila) {
      sincronizarVisiblePublicoFila(fila);
      sincronizarEsGratisFila(fila);
    });
    actualizarBotonesQuitarTipos(contenedor);
  }

  function crearFilaTipoEntrada(contenedor) {
    var lista = contenedor.querySelector('.js-tipos-entrada-lista');
    var plantilla = lista ? lista.querySelector('.js-tipo-entrada-fila') : null;
    if (!lista || !plantilla) {
      return;
    }

    var nueva = plantilla.cloneNode(true);
    nueva.querySelectorAll('input').forEach(function (input) {
      if (input.classList.contains('js-valor-tipo-entrada')) {
        input.value = '0';
        input.min = '0';
        input.disabled = false;
        input.required = true;
      } else if (input.classList.contains('js-visible-publico-valor')) {
        input.value = '1';
      } else if (input.classList.contains('js-es-gratis-valor')) {
        input.value = '0';
      } else if (input.classList.contains('js-visible-publico-check')) {
        input.checked = true;
        input.removeAttribute('data-bound-visible-publico');
        input.removeAttribute('id');
      } else if (input.classList.contains('js-es-gratis-check')) {
        input.checked = false;
        input.removeAttribute('data-bound-es-gratis');
        input.removeAttribute('id');
      } else if (input.type === 'text') {
        input.value = '';
      }
    });

    nueva.querySelectorAll('label[for]').forEach(function (label) {
      label.removeAttribute('for');
    });

    lista.appendChild(nueva);
    enlazarVisiblePublicoFila(nueva);
    enlazarEsGratisFila(nueva);
    enlazarPrefijoTipoEntradaFila(nueva);
    actualizarFilasTipoEntrada(contenedor);
  }

  function refrescarFormularioRegistro(contenedor, opciones) {
    actualizarSelectTiposEntrada(contenedor);
    actualizarNumeracion(contenedor, opciones || {});
    actualizarCamposAdicionales(contenedor);
    actualizarBloquePagoEvento(contenedor, opciones || {});
  }

  function inicializarContenedorRegistro(contenedor) {
    var eventoSelect = contenedor.querySelector('select[name="evento_id"]');
    var tipoSelect = contenedor.querySelector('.js-tipo-entrada-evento');
    var esEdicion = esFormularioEdicionRegistro(contenedor);

    if (eventoSelect) {
      eventoSelect.addEventListener('change', function () {
        refrescarFormularioRegistro(contenedor, { sugerirValor: true, resetearCamposEvento: true });
      });
    }

    if (tipoSelect) {
      tipoSelect.addEventListener('change', function () {
        var hiddenNombre = contenedor.querySelector('input[name="tipo_entrada"]');
        if (hiddenNombre) {
          var opcionTipo = tipoSelect.options[tipoSelect.selectedIndex];
          hiddenNombre.value = opcionTipo ? String(opcionTipo.textContent || '').trim() : '';
        }
        actualizarPrefijoNumeracion(contenedor);
        actualizarBloquePagoEvento(contenedor, { sugerirValor: true });
      });
    }

    var valorInput = contenedor.querySelector('.js-valor-evento');
    if (valorInput && !valorInput.dataset.boundValorEvento) {
      valorInput.dataset.boundValorEvento = '1';
      valorInput.addEventListener('input', function () {
        actualizarBloquePagoEvento(contenedor, { sugerirValor: false });
      });
    }

    contenedor.querySelectorAll('.js-estado-pago-evento').forEach(function (radio) {
      if (radio.dataset.boundEstadoPagoEvento) {
        return;
      }
      radio.dataset.boundEstadoPagoEvento = '1';
      radio.addEventListener('change', function () {
        actualizarBloquePagoEvento(contenedor, { sugerirValor: false });
      });
    });

    var sugerirAlInicio = !esEdicion && (!valorInput || valorInput.value === '');
    refrescarFormularioRegistro(contenedor, { sugerirValor: sugerirAlInicio, resetearCamposEvento: false });

    if (esEdicion && tipoSelect) {
      var hiddenNombreInicio = contenedor.querySelector('input[name="tipo_entrada"]');
      if (hiddenNombreInicio && tipoSelect.selectedIndex >= 0) {
        hiddenNombreInicio.value = String(tipoSelect.options[tipoSelect.selectedIndex].textContent || '').trim();
      }
    }
  }

  function inicializarContenedorCatalogo(contenedor) {
    var botonAgregar = contenedor.querySelector('.js-agregar-tipo-entrada');
    if (botonAgregar && !botonAgregar.dataset.boundTipos) {
      botonAgregar.dataset.boundTipos = '1';
      botonAgregar.addEventListener('click', function () {
        crearFilaTipoEntrada(contenedor);
      });
    }

    var botonAgregarCampo = contenedor.querySelector('.js-agregar-campo-adicional');
    if (botonAgregarCampo && !botonAgregarCampo.dataset.boundCampos) {
      botonAgregarCampo.dataset.boundCampos = '1';
      botonAgregarCampo.addEventListener('click', function () {
        crearFilaCampoAdicional(contenedor);
      });
    }

    if (!contenedor.dataset.boundQuitarTipos) {
      contenedor.dataset.boundQuitarTipos = '1';
      contenedor.addEventListener('click', function (event) {
        var boton = event.target.closest('.js-quitar-tipo-entrada');
        if (!boton || !contenedor.contains(boton)) {
          return;
        }

        var fila = boton.closest('.js-tipo-entrada-fila');
        var filas = contenedor.querySelectorAll('.js-tipo-entrada-fila');
        if (fila && filas.length > 1) {
          fila.remove();
          actualizarBotonesQuitarTipos(contenedor);
        }
      });
    }

    if (!contenedor.dataset.boundQuitarCampos) {
      contenedor.dataset.boundQuitarCampos = '1';
      contenedor.addEventListener('click', function (event) {
        var boton = event.target.closest('.js-quitar-campo-adicional');
        if (!boton || !contenedor.contains(boton)) {
          return;
        }

        var fila = boton.closest('.js-campo-adicional-fila');
        var filas = contenedor.querySelectorAll('.js-campo-adicional-fila');
        if (!fila) {
          return;
        }

        if (filas.length > 1) {
          fila.remove();
          return;
        }

        resetearFilaCampoAdicional(fila);
        enlazarObligatorioFila(fila);
        enlazarTipoCampoFila(fila);
      });
    }

    contenedor.querySelectorAll('.js-tipo-entrada-fila').forEach(function (fila) {
      enlazarVisiblePublicoFila(fila);
      enlazarEsGratisFila(fila);
      enlazarPrefijoTipoEntradaFila(fila);
    });
    actualizarFilasTipoEntrada(contenedor);

    contenedor.querySelectorAll('.js-campo-adicional-fila').forEach(function (fila) {
      enlazarObligatorioFila(fila);
      enlazarTipoCampoFila(fila);
    });
  }

  document.querySelectorAll('#formRegistroEvento, .modal-editar-registro form').forEach(function (form) {
    if (
      form.querySelector('select[name="evento_id"]')
      && (
        form.querySelector('.js-campo-valor-evento')
        || form.querySelector('.js-bloque-forma-pago-evento')
        || form.querySelector('.js-bloque-pago-evento')
        || form.querySelector('.js-campo-tipo-entrada-evento')
      )
    ) {
      inicializarContenedorRegistro(form);
    }
  });

  document.querySelectorAll('#formAgregarEvento, .modal').forEach(function (contenedor) {
    if (contenedor.querySelector('.js-tipos-entrada-lista') || contenedor.querySelector('.js-campos-adicionales-lista')) {
      inicializarContenedorCatalogo(contenedor);
    }
  });
})();
