@props([
    'name',
    'catalogo' => 'miembros',
    'value' => '',
    'texto' => '',
    'required' => false,
    'placeholder' => 'Buscar por nombre',
])

<div class="relative" data-buscar-uno data-catalogo="{{ $catalogo }}">
  <input type="hidden" name="{{ $name }}" value="{{ $value }}" @required($required)>
  <div class="relative">
    <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
    <input
      type="search"
      class="input pl-9"
      data-buscar-texto
      placeholder="{{ $placeholder }}"
      autocomplete="off"
      value="{{ $texto }}"
    >
  </div>
  <ul class="absolute z-30 mt-1 hidden max-h-56 w-full overflow-auto rounded-lg border border-line bg-surface p-1 shadow-[0_8px_24px_rgb(15_23_42/0.12)]" data-buscar-lista></ul>
</div>

@once
  <script>
    (function () {
      function leerCatalogo(id) {
        var nodo = document.getElementById(id);
        if (!nodo) return [];
        try {
          var datos = JSON.parse(nodo.textContent || '[]');
          return Array.isArray(datos) ? datos : [];
        } catch (e) {
          return [];
        }
      }

      function catalogoDe(tipo) {
        return tipo === 'territorios' ? leerCatalogo('configCasasTerritorios') : leerCatalogo('configMiembrosEstructura');
      }

      document.querySelectorAll('[data-buscar-uno]').forEach(function (raiz) {
        var oculto = raiz.querySelector('input[type="hidden"]');
        var buscar = raiz.querySelector('[data-buscar-texto]');
        var lista = raiz.querySelector('[data-buscar-lista]');
        var items = catalogoDe(raiz.getAttribute('data-catalogo'));

        function cerrar() {
          lista.classList.add('hidden');
        }

        function coincide(item, consulta) {
          if (!consulta) return true;
          var texto = ((item.nombre || '') + ' ' + (item.cedula || '')).toLowerCase();
          return texto.indexOf(consulta) !== -1;
        }

        function vaciar() {
          oculto.value = oculto.required ? '' : '0';
        }

        function escapar(texto) {
          return String(texto || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
        }

        function elegir(item) {
          oculto.value = item.id;
          buscar.value = item.nombre || '';
          cerrar();
        }

        function pintar() {
          var consulta = (buscar.value || '').trim().toLowerCase();
          var visibles = items.filter(function (item) { return coincide(item, consulta); });
          lista.innerHTML = '';

          if (!visibles.length) {
            lista.innerHTML = '<li class="px-2.5 py-2 text-sm text-muted">No hay resultados</li>';
            lista.classList.remove('hidden');
            return;
          }

          visibles.slice(0, 40).forEach(function (item) {
            var boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'flex w-full flex-col rounded-md px-2.5 py-2 text-left text-sm text-ink hover:bg-surface-2';
            boton.innerHTML = item.cedula
              ? '<span>' + escapar(item.nombre) + '</span><span class="text-xs text-muted">' + escapar(item.cedula) + '</span>'
              : '<span>' + escapar(item.nombre) + '</span>';
            boton.addEventListener('click', function () { elegir(item); });
            var li = document.createElement('li');
            li.appendChild(boton);
            lista.appendChild(li);
          });

          lista.classList.remove('hidden');
        }

        buscar.addEventListener('focus', pintar);
        buscar.addEventListener('input', function () {
          vaciar();
          pintar();
        });
        buscar.addEventListener('keydown', function (evento) {
          if (evento.key === 'Escape') {
            cerrar();
            return;
          }
          if (evento.key !== 'Enter') return;
          var primero = items.find(function (item) { return coincide(item, (buscar.value || '').trim().toLowerCase()); });
          if (primero) {
            evento.preventDefault();
            elegir(primero);
          }
        });

        document.addEventListener('click', function (evento) {
          if (!raiz.contains(evento.target)) cerrar();
        });
      });
    })();
  </script>
@endonce
