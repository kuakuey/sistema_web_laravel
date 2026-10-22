(function () {
  'use strict';

  var input = document.getElementById('clave');
  var toggle = document.getElementById('toggleClave');

  if (!input || !toggle) {
    return;
  }

  var icon = toggle.querySelector('i');

  toggle.addEventListener('click', function () {
    var mostrar = input.type === 'password';

    input.type = mostrar ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
    toggle.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
    toggle.title = mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña';

    if (icon) {
      icon.className = mostrar ? 'bi bi-eye-slash' : 'bi bi-eye';
    }
  });
})();
