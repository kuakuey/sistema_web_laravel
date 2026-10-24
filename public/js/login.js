(function () {
  'use strict';

  var input = document.getElementById('clave');
  var toggle = document.getElementById('toggleClave');

  if (!input || !toggle || typeof window.cdaIcon !== 'function') {
    return;
  }

  toggle.addEventListener('click', function () {
    var mostrar = input.type === 'password';

    input.type = mostrar ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
    toggle.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
    toggle.title = mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña';
    toggle.innerHTML = window.cdaIcon(mostrar ? 'eye-off' : 'eye');
  });
})();
