(function () {
  'use strict';

  var form = document.querySelector('.js-checkout-form');
  if (!form) {
    return;
  }

  var evento = form.querySelector('#evento_id');
  var codigo = form.querySelector('#codigo');
  var boton = form.querySelector('.js-checkout-submit');
  var alerta = document.querySelector('.js-checkout-error');
  var modalEl = document.getElementById('checkoutResultado');
  var modalTitulo = document.getElementById('checkoutResultadoTitulo');
  var modalCuerpo = document.querySelector('.js-checkout-modal-cuerpo');
  var modal = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl) : null;

  function escapeHtml(texto) {
    return String(texto == null ? '' : texto)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function mostrarError(mensaje) {
    if (!alerta) {
      return;
    }

    alerta.textContent = mensaje || '';
    alerta.classList.toggle('d-none', !mensaje);
  }

  function filaDato(etiqueta, valor) {
    return (
      '<div class="checkout-modal__fila">' +
        '<dt>' + escapeHtml(etiqueta) + '</dt>' +
        '<dd>' + escapeHtml(valor || '—') + '</dd>' +
      '</div>'
    );
  }

  function htmlBotonAsistencia(ticket) {
    if (ticket.asistio) {
      var html =
        '<button type="button" class="btn btn-success w-100" disabled>' +
          '<i class="bi bi-check2-circle me-1"></i>Asistencia marcada' +
        '</button>';

      if (ticket.puede_reversar) {
        html +=
          '<button type="button" class="btn btn-outline-warning w-100 mt-2 js-reversar-asistencia" data-id="' +
            escapeHtml(ticket.id) +
          '">' +
            '<i class="bi bi-arrow-counterclockwise me-1"></i>Reversar asistencia' +
          '</button>';
      }

      return html;
    }

    return (
      '<button type="button" class="btn btn-primary w-100 js-marcar-asistencia" data-id="' +
        escapeHtml(ticket.id) +
      '">' +
        '<i class="bi bi-person-check me-1"></i>Marcar asistencia' +
      '</button>'
    );
  }

  function htmlTicket(ticket) {
    return (
      '<div class="checkout-modal__ticket js-checkout-ticket" data-id="' + escapeHtml(ticket.id) + '">' +
        '<dl class="checkout-modal__datos mb-3">' +
          filaDato('Nombre', ticket.nombre) +
          filaDato('Código', ticket.numeracion) +
          filaDato('Tipo de entrada', ticket.tipo_entrada) +
          filaDato('Estado', ticket.estado) +
          filaDato('Asistencia', ticket.asistencia) +
        '</dl>' +
        '<div class="checkout-modal__acciones">' +
          htmlBotonAsistencia(ticket) +
        '</div>' +
      '</div>'
    );
  }

  function mostrarPopup(datos) {
    var tickets = datos.tickets || [];
    var repetido = !!datos.repetido;
    var nombres = (datos.nombres || []).filter(Boolean);
    var html = '';

    if (modalTitulo) {
      modalTitulo.textContent = repetido ? 'Código repetido' : 'Ticket';
    }

    if (repetido) {
      html +=
        '<div class="alert alert-warning mb-3">' +
          '<strong>Este código está repetido.</strong> ' +
          (nombres.length
            ? 'Corresponde a: ' + nombres.map(escapeHtml).join(', ') + '.'
            : 'Hay más de un registro con el mismo número.') +
        '</div>';
    }

    tickets.forEach(function (ticket, indice) {
      if (indice > 0) {
        html += '<hr class="my-3">';
      }
      html += htmlTicket(ticket);
    });

    if (modalCuerpo) {
      modalCuerpo.innerHTML = html;
    }

    if (modal) {
      modal.show();
    }
  }

  function actualizarTicketEnPopup(ticket) {
    if (!modalCuerpo || !ticket) {
      return;
    }

    var bloque = modalCuerpo.querySelector('.js-checkout-ticket[data-id="' + ticket.id + '"]');
    if (!bloque) {
      return;
    }

    var wrapper = document.createElement('div');
    wrapper.innerHTML = htmlTicket(ticket);
    var nuevo = wrapper.firstElementChild;
    if (nuevo) {
      bloque.replaceWith(nuevo);
    }
  }

  function enviarAccionAsistencia(boton, accion, mensajeError) {
    var ticketId = boton.getAttribute('data-id');
    if (!ticketId) {
      return;
    }

    boton.disabled = true;

    var cuerpo = new FormData();
    cuerpo.append('accion', accion);
    cuerpo.append('id', ticketId);

    fetch('checkout.php', {
      method: 'POST',
      body: cuerpo,
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    })
      .then(function (respuesta) {
        return respuesta.json();
      })
      .then(function (datos) {
        if (!datos || !datos.ok || !datos.ticket) {
          boton.disabled = false;
          window.alert((datos && datos.error) || mensajeError);
          return;
        }

        actualizarTicketEnPopup(datos.ticket);
      })
      .catch(function () {
        boton.disabled = false;
        window.alert(mensajeError);
      });
  }

  function marcarAsistencia(botonAsistencia) {
    enviarAccionAsistencia(botonAsistencia, 'marcar_asistencia', 'No se pudo marcar la asistencia.');
  }

  function reversarAsistencia(botonReversar) {
    if (!window.confirm('¿Reversar la asistencia de este ticket? Quedará como no asistió.')) {
      return;
    }

    enviarAccionAsistencia(botonReversar, 'reversar_asistencia', 'No se pudo reversar la asistencia.');
  }

  function sincronizarCodigo() {
    if (!codigo) {
      return;
    }

    var tieneEvento = !!(evento && evento.value);
    codigo.disabled = !tieneEvento;
    codigo.placeholder = tieneEvento
      ? 'Ej. G301'
      : 'Seleccione evento primero…';
  }

  if (evento) {
    evento.addEventListener('change', function () {
      sincronizarCodigo();
      mostrarError('');

      if (evento.value && codigo) {
        codigo.value = '';
        codigo.focus();
      }
    });
  }

  if (modalEl) {
    modalEl.addEventListener('hidden.bs.modal', function () {
      if (codigo && !codigo.disabled) {
        codigo.value = '';
        codigo.focus();
      }
    });
  }

  if (modalCuerpo) {
    modalCuerpo.addEventListener('click', function (e) {
      var botonAsistencia = e.target.closest('.js-marcar-asistencia');
      if (botonAsistencia) {
        marcarAsistencia(botonAsistencia);
        return;
      }

      var botonReversar = e.target.closest('.js-reversar-asistencia');
      if (botonReversar) {
        reversarAsistencia(botonReversar);
      }
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    mostrarError('');

    if (!evento || !evento.value) {
      mostrarError('Selecciona un evento.');
      return;
    }

    if (!codigo || codigo.value.trim() === '') {
      mostrarError('Ingresa el código del ticket.');
      return;
    }

    var cuerpo = new FormData(form);
    cuerpo.append('accion', 'consultar');
    if (boton) {
      boton.disabled = true;
    }

    fetch('checkout.php', {
      method: 'POST',
      body: cuerpo,
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    })
      .then(function (respuesta) {
        return respuesta.json();
      })
      .then(function (datos) {
        if (!datos || !datos.ok) {
          mostrarError((datos && datos.error) || 'No se encontró el ticket.');
          return;
        }

        mostrarPopup(datos);
      })
      .catch(function () {
        mostrarError('No se pudo consultar el ticket. Intenta de nuevo.');
      })
      .then(function () {
        if (boton) {
          boton.disabled = false;
        }
      });
  });

  sincronizarCodigo();
})();
