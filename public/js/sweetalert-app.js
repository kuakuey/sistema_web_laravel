(function () {
  'use strict';

  if (typeof window.Swal === 'undefined') {
    return;
  }

  var COLOR_PRIMARIO = '#E77E35';
  var PESTANAS_REGISTRO = ['nuevo', 'registrar', 'registros'];

  var SwalApp = Swal.mixin({
    scrollbarPadding: false,
    heightAuto: false
  });

  function esModoOscuro() {
    return document.documentElement.getAttribute('data-theme') === 'dark';
  }

  function opcionesBase() {
    return {
      confirmButtonColor: COLOR_PRIMARIO,
      cancelButtonColor: '#6c757d',
      background: esModoOscuro() ? '#171b22' : '#ffffff',
      color: esModoOscuro() ? '#e9ecef' : '#212529'
    };
  }

  function bloquearFormulario(form) {
    if (form.dataset.enviando === '1') {
      return false;
    }

    form.dataset.enviando = '1';
    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (boton) {
      if (boton.disabled) {
        return;
      }

      boton.dataset.cdaOriginalHtml = boton.innerHTML;
      boton.disabled = true;

      if (boton.tagName === 'BUTTON') {
        boton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Procesando…';
      }
    });

    return true;
  }

  function desbloquearFormulario(form) {
    form.dataset.enviando = '0';
    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (boton) {
      boton.disabled = false;

      if (boton.dataset.cdaOriginalHtml) {
        boton.innerHTML = boton.dataset.cdaOriginalHtml;
      }
    });
  }

  function limpiarParametrosFeedbackEnUrl(recargar) {
    try {
      var url = new URL(window.location.href);
      var cambio = false;

      ['ok', 'error', 'actualizado', 'asignacion'].forEach(function (param) {
        if (url.searchParams.has(param)) {
          url.searchParams.delete(param);
          cambio = true;
        }
      });

      if (!cambio) {
        return;
      }

      var destino = url.pathname + url.search + url.hash;

      if (recargar) {
        window.location.replace(destino);
      } else {
        window.history.replaceState({}, '', destino);
      }
    } catch (error) {
      return;
    }
  }

  function urlSinParametrosFeedback(destino) {
    var url = new URL(destino, window.location.origin);

    ['ok', 'error', 'actualizado', 'asignacion'].forEach(function (param) {
      url.searchParams.delete(param);
    });

    return url.pathname + url.search + url.hash;
  }

  function obtenerMensajeExitoRegistro(form) {
    return form.getAttribute('data-mensaje-exito') || 'Registro guardado correctamente.';
  }

  function mostrarExitoRegistro(mensaje, urlRecarga) {
    SwalApp.fire(Object.assign({}, opcionesBase(), {
      icon: 'success',
      title: 'Operación exitosa',
      text: mensaje,
      confirmButtonText: 'Aceptar'
    })).then(function () {
      window.location.replace(urlRecarga);
    });
  }

  function mostrarErrorRegistro(mensaje) {
    return SwalApp.fire(Object.assign({}, opcionesBase(), {
      icon: 'error',
      title: 'No se pudo completar',
      text: mensaje,
      confirmButtonText: 'Entendido'
    }));
  }

  function extraerErrorDesdeUrl(destino) {
    try {
      var url = new URL(destino, window.location.origin);
      return url.searchParams.get('error') || '';
    } catch (error) {
      return '';
    }
  }

  function esRedireccionRegistroExitosa(destino) {
    try {
      var url = new URL(destino, window.location.origin);

      if (!url.searchParams.has('ok')) {
        return false;
      }

      var pestaña = url.searchParams.get('pestaña') || '';
      return PESTANAS_REGISTRO.indexOf(pestaña) !== -1;
    } catch (error) {
      return false;
    }
  }

  function enviarFormularioRegistro(form) {
    if (!bloquearFormulario(form)) {
      return;
    }

    var actionUrl = form.getAttribute('action') || window.location.href;

    fetch(actionUrl, {
      method: 'POST',
      body: new FormData(form),
      credentials: 'same-origin',
      redirect: 'follow'
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('Respuesta no válida del servidor.');
      }

      var destino = response.url || '';

      if (!destino) {
        throw new Error('No se recibió respuesta del servidor.');
      }

      var mensajeError = extraerErrorDesdeUrl(destino);

      if (mensajeError !== '') {
        desbloquearFormulario(form);
        return mostrarErrorRegistro(mensajeError);
      }

      if (!esRedireccionRegistroExitosa(destino)) {
        window.location.href = destino;
        return;
      }

      mostrarExitoRegistro(
        obtenerMensajeExitoRegistro(form),
        urlSinParametrosFeedback(destino)
      );
    }).catch(function () {
      desbloquearFormulario(form);
      mostrarErrorRegistro('No se pudo guardar el registro. Intenta de nuevo.');
    });
  }

  function inicializarFormulariosRegistro() {
    document.querySelectorAll('form.js-form-registro').forEach(function (form) {
      form.addEventListener('submit', function (evento) {
        evento.preventDefault();

        if (form.dataset.enviando === '1') {
          return;
        }

        enviarFormularioRegistro(form);
      });
    });
  }

  function manejarRegistroGuardadoEnUrl() {
    try {
      var url = new URL(window.location.href);

      if (!url.searchParams.has('ok')) {
        return;
      }

      var pestaña = url.searchParams.get('pestaña') || '';

      if (PESTANAS_REGISTRO.indexOf(pestaña) === -1) {
        return;
      }

      var alerta = document.querySelector('.alert-success.alert-dismissible');
      var mensaje = alerta ? extraerTextoAlerta(alerta) : 'Registro guardado correctamente.';

      if (alerta) {
        alerta.remove();
      }

      mostrarExitoRegistro(mensaje, urlSinParametrosFeedback(window.location.href));
    } catch (error) {
      return;
    }
  }

  function inicializarConfirmaciones() {
    document.querySelectorAll('form.js-form-confirmar').forEach(function (form) {
      form.addEventListener('submit', function (evento) {
        if (form.dataset.confirmado === '1') {
          if (form.dataset.enviando === '1') {
            evento.preventDefault();
          } else {
            bloquearFormulario(form);
          }
          return;
        }

        evento.preventDefault();

        var mensaje = form.getAttribute('data-confirm') || '¿Deseas continuar?';
        var titulo = form.getAttribute('data-confirm-title') || 'Confirmar acción';

        SwalApp.fire(Object.assign({}, opcionesBase(), {
          icon: 'warning',
          title: titulo,
          text: mensaje,
          showCancelButton: true,
          confirmButtonText: 'Sí, continuar',
          cancelButtonText: 'Cancelar',
          reverseButtons: true,
          focusCancel: true
        })).then(function (resultado) {
          if (!resultado.isConfirmed) {
            return;
          }

          form.dataset.confirmado = '1';
          bloquearFormulario(form);
          form.submit();
        });
      });
    });
  }

  function inicializarBloqueoDobleEnvio() {
    document.querySelectorAll('form').forEach(function (form) {
      if (form.classList.contains('js-form-confirmar') || form.classList.contains('js-form-registro')) {
        return;
      }

      if (form.getAttribute('data-sin-bloqueo') === '1') {
        return;
      }

      var metodo = (form.getAttribute('method') || 'get').toLowerCase();
      if (metodo !== 'post') {
        return;
      }

      form.addEventListener('submit', function (evento) {
        if (form.dataset.enviando === '1') {
          evento.preventDefault();
          return;
        }

        bloquearFormulario(form);
      });
    });
  }

  function extraerTextoAlerta(elemento) {
    var clon = elemento.cloneNode(true);
    clon.querySelectorAll('.btn-close').forEach(function (boton) {
      boton.remove();
    });
    clon.querySelectorAll('i').forEach(function (icono) {
      icono.remove();
    });

    return clon.textContent.replace(/\s+/g, ' ').trim();
  }

  function mostrarAlertasPagina() {
    document.querySelectorAll('.alert-success.alert-dismissible').forEach(function (alerta) {
      var texto = extraerTextoAlerta(alerta);
      if (!texto) {
        return;
      }

      SwalApp.fire(Object.assign({}, opcionesBase(), {
        icon: 'success',
        title: 'Operación exitosa',
        text: texto,
        confirmButtonText: 'Aceptar'
      })).then(function () {
        limpiarParametrosFeedbackEnUrl(false);
      });

      alerta.remove();
    });

    document.querySelectorAll('.alert-danger.alert-dismissible').forEach(function (alerta) {
      var texto = extraerTextoAlerta(alerta);
      if (!texto) {
        return;
      }

      SwalApp.fire(Object.assign({}, opcionesBase(), {
        icon: 'error',
        title: 'No se pudo completar',
        text: texto,
        confirmButtonText: 'Entendido'
      })).then(function () {
        limpiarParametrosFeedbackEnUrl(false);
      });

      alerta.remove();
    });

    document.querySelectorAll('.alert-warning.alert-dismissible').forEach(function (alerta) {
      var texto = extraerTextoAlerta(alerta);
      if (!texto) {
        return;
      }

      SwalApp.fire(Object.assign({}, opcionesBase(), {
        icon: 'warning',
        title: 'Atención',
        text: texto,
        confirmButtonText: 'Aceptar'
      })).then(function () {
        limpiarParametrosFeedbackEnUrl(false);
      });

      alerta.remove();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    inicializarConfirmaciones();
    inicializarFormulariosRegistro();
    inicializarBloqueoDobleEnvio();
    manejarRegistroGuardadoEnUrl();
    mostrarAlertasPagina();
  });
})();
