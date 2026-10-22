(function () {
  var form = document.getElementById('formNuevoPresentacion');
  if (!form) {
    return;
  }

  var contenedor = form.querySelector('#presentadosPresentacion .row.g-3');
  if (!contenedor) {
    return;
  }

  function limpiarBloquePresentado(bloque) {
    bloque.querySelectorAll('input').forEach(function (input) {
      input.value = '';
    });
    bloque.querySelectorAll('select').forEach(function (select) {
      select.selectedIndex = 0;
    });
  }

  function actualizarPresentados() {
    var bloques = contenedor.querySelectorAll('.js-bloque-presentado');

    bloques.forEach(function (bloque, indice) {
      var etiqueta = bloque.querySelector('.js-etiqueta-presentado');
      if (etiqueta) {
        etiqueta.textContent = 'Presentado ' + (indice + 1);
      }

      bloque.querySelectorAll('[name]').forEach(function (campo) {
        var nombre = campo.getAttribute('name');
        if (!nombre || nombre.indexOf('presentados[') !== 0) {
          return;
        }

        campo.setAttribute('name', nombre.replace(/presentados\[\d+\]/, 'presentados[' + indice + ']'));
      });

      bloque.querySelectorAll('[id]').forEach(function (campo) {
        var id = campo.getAttribute('id');
        if (!id || id.indexOf('presentado_') !== 0) {
          return;
        }

        campo.setAttribute('id', id.replace(/^presentado_\d+_/, 'presentado_' + indice + '_'));
      });

      bloque.querySelectorAll('label[for]').forEach(function (label) {
        var referencia = label.getAttribute('for');
        if (!referencia || referencia.indexOf('presentado_') !== 0) {
          return;
        }

        label.setAttribute('for', referencia.replace(/^presentado_\d+_/, 'presentado_' + indice + '_'));
      });

      var botonQuitar = bloque.querySelector('.js-quitar-presentado');
      if (botonQuitar) {
        botonQuitar.style.display = bloques.length > 1 ? '' : 'none';
      }
    });
  }

  form.querySelector('.js-agregar-presentado')?.addEventListener('click', function () {
    var bloques = contenedor.querySelectorAll('.js-bloque-presentado');
    if (!bloques.length) {
      return;
    }

    var nuevoBloque = bloques[0].cloneNode(true);
    limpiarBloquePresentado(nuevoBloque);
    contenedor.appendChild(nuevoBloque);
    actualizarPresentados();
  });

  contenedor.addEventListener('click', function (evento) {
    var boton = evento.target.closest('.js-quitar-presentado');
    if (!boton) {
      return;
    }

    var bloques = contenedor.querySelectorAll('.js-bloque-presentado');
    if (bloques.length <= 1) {
      return;
    }

    boton.closest('.js-bloque-presentado')?.remove();
    actualizarPresentados();
  });

  actualizarPresentados();
})();
