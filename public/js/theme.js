(function () {
  'use strict';

  var STORAGE_KEY = 'cda-theme';
  var toggle = document.getElementById('themeToggle');

  function esModoOscuro() {
    return document.documentElement.getAttribute('data-theme') === 'dark';
  }

  function aplicarTema(oscuro) {
    var root = document.documentElement;

    if (oscuro) {
      root.setAttribute('data-theme', 'dark');
      root.setAttribute('data-bs-theme', 'dark');
    } else {
      root.removeAttribute('data-theme');
      root.removeAttribute('data-bs-theme');
    }

    if (toggle) {
      toggle.setAttribute('aria-label', oscuro ? 'Activar modo claro' : 'Activar modo oscuro');
      toggle.setAttribute('title', oscuro ? 'Modo claro' : 'Modo oscuro');
    }
  }

  function guardarTema(oscuro) {
    try {
      localStorage.setItem(STORAGE_KEY, oscuro ? 'dark' : 'light');
    } catch (e) {}
  }

  if (toggle) {
    toggle.addEventListener('click', function () {
      var oscuro = !esModoOscuro();
      aplicarTema(oscuro);
      guardarTema(oscuro);
    });

    aplicarTema(esModoOscuro());
  }
})();
