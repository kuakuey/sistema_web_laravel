(function () {
  'use strict';

  var sidebar = document.getElementById('appSidebarMobile');
  var overlay = document.getElementById('sidebarOverlay');
  var toggle = document.getElementById('sidebarToggle');

  if (!sidebar || !overlay || !toggle) {
    return;
  }

  function abrirSidebar() {
    sidebar.classList.add('is-open');
    overlay.classList.add('is-visible');
    document.body.style.overflow = 'hidden';
  }

  function cerrarSidebar() {
    sidebar.classList.remove('is-open');
    overlay.classList.remove('is-visible');
    document.body.style.overflow = '';
  }

  function alternarSidebar() {
    if (sidebar.classList.contains('is-open')) {
      cerrarSidebar();
    } else {
      abrirSidebar();
    }
  }

  toggle.addEventListener('click', alternarSidebar);
  overlay.addEventListener('click', cerrarSidebar);

  sidebar.querySelectorAll('.nav-link').forEach(function (link) {
    link.addEventListener('click', function () {
      cerrarSidebar();
    });
  });

  window.addEventListener('resize', function () {
    if (window.matchMedia('(min-width: 992px)').matches) {
      cerrarSidebar();
    }
  });
})();

(function () {
  'use strict';

  var casas = window.cdaOfrendasCasas;
  if (!Array.isArray(casas) || !casas.length) {
    return;
  }

  var territorioSelect = document.getElementById('territorio_id');
  var casaSelect = document.getElementById('casa_id');
  var liderInput = document.getElementById('lider');

  if (!territorioSelect || !casaSelect || !liderInput) {
    return;
  }

  function llenarCasas(idTerritorio) {
    casaSelect.innerHTML = '';
    liderInput.value = '';

    if (!idTerritorio) {
      casaSelect.appendChild(new Option('Primero elija un territorio', ''));
      casaSelect.disabled = true;
      return;
    }

    var filtradas = casas.filter(function (casa) {
      return String(casa.territorio_id) === String(idTerritorio);
    });

    filtradas.sort(function (a, b) {
      return String(a.nombre).localeCompare(String(b.nombre), 'es', { sensitivity: 'base' });
    });

    casaSelect.appendChild(new Option('Seleccione casa de vida…', ''));

    if (!filtradas.length) {
      casaSelect.appendChild(new Option('No hay casas en este territorio', ''));
      casaSelect.disabled = true;
      return;
    }

    filtradas.forEach(function (casa) {
      casaSelect.appendChild(new Option(casa.nombre, casa.id));
    });
    casaSelect.disabled = false;
  }

  territorioSelect.addEventListener('change', function () {
    llenarCasas(territorioSelect.value);
  });

  casaSelect.addEventListener('change', function () {
    var id = casaSelect.value;
    if (!id) {
      liderInput.value = '';
      return;
    }
    var fila = casas.find(function (casa) {
      return String(casa.id) === String(id);
    });
    liderInput.value = fila && fila.lider ? fila.lider : '';
  });

  llenarCasas(territorioSelect.value);
})();
