<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #212529; line-height: 1.35; }
    h1 { font-size: 16px; margin: 0 0 6px; text-align: center; }
    .subtitulo { text-align: center; color: #6c757d; margin: 0 0 16px; }
    h2 { font-size: 12px; margin: 18px 0 8px; padding-bottom: 4px; border-bottom: 1px solid #dee2e6; }
    h3 { font-size: 11px; margin: 14px 0 6px; color: #495057; }
    .resumen, table.datos, table.meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .resumen td, table.datos th, table.datos td, table.meta td { border: 1px solid #dee2e6; padding: 6px 8px; vertical-align: top; text-align: left; }
    .resumen td { width: 33.33%; }
    .resumen-evento td { width: 25%; }
    .resumen-label { display: block; font-size: 9px; color: #6c757d; margin-bottom: 3px; }
    .resumen-valor { font-size: 12px; }
    table.datos th { background: #f8f9fa; font-size: 9px; text-transform: uppercase; }
    .informe-evento table.datos th, .informe-evento table.datos td { padding: 4px 5px; font-size: 8.5px; }
    .vacio { color: #6c757d; font-style: italic; }
    .estado-pendiente { color: #856404; }
    .destacado { font-weight: bold; }
    .salto-pagina { page-break-before: always; }
    table.meta td { width: 50%; }
  </style>
</head>
@php
  $seccion = \App\Support\Informes::normalizarSeccion($informe['seccion_exportacion'] ?? 'completo');
  $resumen = $informe['resumen'] ?? [];
  $esEvento = $seccion === 'eventos';
  $conOfrendas = in_array($seccion, ['completo', 'ofrendas'], true);
  $conEventos = $seccion === 'completo';
  $conValores = in_array($seccion, ['completo', 'valores'], true);
  $conPresentaciones = $seccion === 'presentaciones';
  $conBautismos = $seccion === 'bautismos';
@endphp
<body class="{{ $esEvento ? 'informe-evento' : '' }}">
@if ($esEvento)
  <h1>Informe de evento</h1>
  <p class="subtitulo">
    {{ $informe['evento']['nombre'] ?? 'Evento' }}
    · Periodo de registro: {{ $informe['periodo_etiqueta'] ?? '—' }}
    · Jornada: {{ $informe['turno_etiqueta'] ?? 'Todas' }}
    · Generado: {{ $informe['generado_en'] ?? '—' }}
  </p>

  <h2>Datos del evento</h2>
  <table class="meta">
    <tr>
      <td><span class="resumen-label">Fecha del evento</span><span class="resumen-valor">{{ $informe['evento_fecha_etiqueta'] ?? '—' }}</span></td>
      <td><span class="resumen-label">Estado</span><span class="resumen-valor">{{ $informe['evento_estado_etiqueta'] ?? '—' }}</span></td>
    </tr>
    <tr>
      <td><span class="resumen-label">Requiere numeración</span><span class="resumen-valor">{{ $informe['evento_numeracion_etiqueta'] ?? '—' }}</span></td>
      <td><span class="resumen-label">Total participantes</span><span class="resumen-valor">{{ (int) ($resumen['total_participantes'] ?? 0) }}</span></td>
    </tr>
  </table>

  <h2>Resumen financiero</h2>
  <table class="resumen resumen-evento">
    <tr>
      <td>
        <span class="resumen-label">Falta por cancelar ({{ (int) ($resumen['entradas_por_cancelar'] ?? 0) }})</span>
        <span class="resumen-valor destacado">{{ \App\Support\Informes::monto($resumen['monto_por_cancelar'] ?? 0) }}</span>
      </td>
      <td>
        <span class="resumen-label">Recaudado ({{ (int) ($resumen['entradas_recaudadas'] ?? 0) }})</span>
        <span class="resumen-valor destacado">{{ \App\Support\Informes::monto($resumen['monto_recaudado'] ?? 0) }}</span>
      </td>
      <td>
        <span class="resumen-label">Total</span>
        <span class="resumen-valor destacado">{{ \App\Support\Informes::monto($resumen['monto_total'] ?? 0) }}</span>
      </td>
      <td>
        <span class="resumen-label">Entradas registradas</span>
        <span class="resumen-valor">{{ (int) ($resumen['total_entradas'] ?? 0) }}</span>
      </td>
    </tr>
  </table>

  <h2>Entradas por tipo</h2>
  @if (empty($resumen['por_tipo_entrada']))
    <p class="vacio">No hay tipos de entrada configurados.</p>
  @else
    <table class="datos">
      <thead>
        <tr>
          <th>Tipo de entrada</th>
          <th>Cantidad vendida</th>
          <th>Valor catálogo</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($resumen['por_tipo_entrada'] as $tipo)
          <tr>
            <td>{{ $tipo['nombre'] ?? '—' }}</td>
            <td><strong>{{ (int) ($tipo['cantidad'] ?? 0) }}</strong></td>
            <td>{{ $tipo['valor_catalogo'] ?? '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <div class="salto-pagina">
    <h2>Entradas</h2>
    @if (empty($informe['registros_por_tipo']))
      <p class="vacio">No hay participantes registrados para este evento.</p>
    @else
      @foreach ($informe['registros_por_tipo'] as $grupo)
        <h3>{{ $grupo['tipo_entrada'] ?? 'Entrada' }} ({{ count($grupo['registros'] ?? []) }})</h3>
        <table class="datos">
          <thead>
            <tr>
              <th>N°</th>
              <th>Nombre</th>
              <th>Valor</th>
              <th>Estado</th>
              <th>Forma de pago</th>
              <th>Fecha</th>
              @foreach ($informe['columnas_adicionales'] ?? [] as $columna)
                <th>{{ $columna['etiqueta'] }}</th>
              @endforeach
              <th>Observación</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($grupo['registros'] as $registro)
              <tr>
                <td>{{ \App\Support\Informes::texto($registro['numeracion'] ?? '') }}</td>
                <td>{{ \App\Support\Informes::texto($registro['nombre'] ?? '') }}</td>
                <td><strong>{{ \App\Support\Informes::monto($registro['valor'] ?? 0) }}</strong></td>
                <td>{{ \App\Support\Informes::etiquetaPago($registro) }}</td>
                <td>{{ \App\Support\Informes::etiquetaForma($registro['forma_pago'] ?? null) }}</td>
                <td>{{ \App\Support\Informes::fecha($registro['fecha'] ?? null) }}</td>
                @foreach ($registro['datos_adicionales'] ?? [] as $dato)
                  <td>{{ $dato }}</td>
                @endforeach
                <td>{{ \App\Support\Informes::texto($registro['observacion'] ?? '') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endforeach
    @endif
  </div>
@else
  <h1>{{ \App\Support\Informes::titulo($seccion) }}</h1>
  <p class="subtitulo">
    Periodo de registro: {{ $informe['periodo_etiqueta'] ?? '—' }}
    · Jornada: {{ $informe['turno_etiqueta'] ?? 'Todas' }}
    @if ($conPresentaciones)
      · Estados: {{ $informe['estados_presentacion_etiqueta'] ?? '—' }}
    @endif
    @if ($conBautismos)
      · Estado: {{ $informe['estado_bautismo_etiqueta'] ?? '—' }}
    @endif
    · Generado: {{ $informe['generado_en'] ?? '—' }}
  </p>

  @if ($seccion === 'completo')
    <table class="resumen">
      <tr>
        <td><span class="resumen-label">Casas de vida</span><span class="resumen-valor">{{ (int) ($resumen['total_casas'] ?? 0) }}</span></td>
        <td><span class="resumen-label">Dieron ofrenda</span><span class="resumen-valor">{{ (int) ($resumen['casas_dieron'] ?? 0) }}</span></td>
        <td><span class="resumen-label">Aún no dieron</span><span class="resumen-valor">{{ (int) ($resumen['casas_no_dieron'] ?? 0) }}</span></td>
      </tr>
      <tr>
        <td><span class="resumen-label">Registros de ofrenda</span><span class="resumen-valor">{{ (int) ($resumen['cantidad_registros_ofrendas'] ?? 0) }}</span></td>
        <td><span class="resumen-label">Total ofrendas</span><span class="resumen-valor"><strong>{{ \App\Support\Informes::monto($resumen['total_monto_ofrendas'] ?? 0) }}</strong></span></td>
        <td><span class="resumen-label">Total eventos</span><span class="resumen-valor"><strong>{{ \App\Support\Informes::monto($resumen['total_monto_eventos'] ?? 0) }}</strong></span></td>
      </tr>
      <tr>
        <td><span class="resumen-label">Registros de eventos</span><span class="resumen-valor">{{ (int) ($resumen['cantidad_registros_eventos'] ?? 0) }}</span></td>
        <td><span class="resumen-label">Valores adicionales</span><span class="resumen-valor"><strong>{{ \App\Support\Informes::monto($resumen['total_monto_valores'] ?? 0) }}</strong></span></td>
        <td><span class="resumen-label">Total general</span><span class="resumen-valor"><strong>{{ \App\Support\Informes::monto($resumen['total_general'] ?? 0) }}</strong></span></td>
      </tr>
    </table>
  @elseif ($conOfrendas)
    <table class="resumen">
      <tr>
        <td><span class="resumen-label">Casas de vida</span><span class="resumen-valor">{{ (int) ($resumen['total_casas'] ?? 0) }}</span></td>
        <td><span class="resumen-label">Dieron ofrenda</span><span class="resumen-valor">{{ (int) ($resumen['casas_dieron'] ?? 0) }}</span></td>
        <td><span class="resumen-label">Total ofrendas</span><span class="resumen-valor"><strong>{{ \App\Support\Informes::monto($resumen['total_monto_ofrendas'] ?? 0) }}</strong></span></td>
      </tr>
    </table>
  @elseif ($conValores)
    <table class="resumen">
      <tr>
        <td><span class="resumen-label">Registros</span><span class="resumen-valor">{{ (int) ($resumen['cantidad_valores_adicionales'] ?? 0) }}</span></td>
        <td><span class="resumen-label">Total valores</span><span class="resumen-valor"><strong>{{ \App\Support\Informes::monto($resumen['total_monto_valores'] ?? 0) }}</strong></span></td>
      </tr>
    </table>
  @elseif ($conPresentaciones)
    <table class="resumen">
      <tr>
        <td><span class="resumen-label">Registros</span><span class="resumen-valor">{{ (int) ($resumen['cantidad_presentaciones'] ?? 0) }}</span></td>
        <td><span class="resumen-label">Estados incluidos</span><span class="resumen-valor">{{ $informe['estados_presentacion_etiqueta'] ?? '—' }}</span></td>
      </tr>
    </table>
  @elseif ($conBautismos)
    <table class="resumen">
      <tr>
        <td><span class="resumen-label">Registros</span><span class="resumen-valor">{{ (int) ($resumen['cantidad_bautismos'] ?? 0) }}</span></td>
        <td><span class="resumen-label">Estado</span><span class="resumen-valor">{{ $informe['estado_bautismo_etiqueta'] ?? '—' }}</span></td>
      </tr>
    </table>
  @endif

  @if ($conOfrendas)
    <h2>Ofrendas por fecha</h2>
    @if (empty($informe['ofrendas_por_fecha']))
      <p class="vacio">No hay ofrendas registradas en este periodo.</p>
    @else
      @foreach ($informe['ofrendas_por_fecha'] as $grupo)
        <h3>Fecha: {{ $grupo['fecha_etiqueta'] }}</h3>
        <table class="datos">
          <thead>
            <tr>
              <th>Casa de vida</th>
              <th>Territorio</th>
              <th>Líder</th>
              <th>Monto</th>
              <th>Registró</th>
              <th>Registrado el</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($grupo['ofrendas'] as $ofrenda)
              <tr>
                <td>{{ \App\Support\Informes::texto($ofrenda['casa_vida'] ?? '') }}</td>
                <td>{{ \App\Support\Informes::texto($ofrenda['territorio'] ?? '') }}</td>
                <td>{{ \App\Support\Informes::texto($ofrenda['lider'] ?? '') }}</td>
                <td><strong>{{ \App\Support\Informes::monto($ofrenda['monto'] ?? 0) }}</strong></td>
                <td>{{ \App\Support\Informes::texto($ofrenda['registrado_por_nombre'] ?? '') }}</td>
                <td>{{ \App\Support\Informes::fechaHora($ofrenda['creado_en'] ?? null) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endforeach
    @endif

    @if (! empty($informe['mostrar_sin_entregar']))
      <h2>Casas de vida que no entregaron</h2>
      @if (empty($informe['casas_no_dieron']))
        <p class="vacio">Todas las casas registraron al menos una ofrenda en este periodo.</p>
      @else
        <table class="datos">
          <thead>
            <tr>
              <th>Casa de vida</th>
              <th>Territorio</th>
              <th>Líder</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($informe['casas_no_dieron'] as $casa)
              <tr>
                <td>{{ \App\Support\Informes::texto($casa['nombre'] ?? '') }}</td>
                <td>{{ \App\Support\Informes::texto($casa['territorio'] ?? '') }}</td>
                <td>{{ \App\Support\Informes::texto($casa['lider'] ?? '') }}</td>
                <td class="estado-pendiente">Sin ofrenda</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    @endif
  @endif

  @if ($conEventos)
    <h2>Eventos</h2>
    @if (empty($informe['registros_eventos']))
      <p class="vacio">No hay registros de eventos en este periodo.</p>
    @else
      <table class="datos">
        <thead>
          <tr>
            <th>Evento</th>
            <th>Nombre</th>
            <th>Tipo entrada</th>
            <th>Numeración</th>
            <th>Estado</th>
            <th>Teléfono</th>
            <th>Valor</th>
            <th>Forma de pago</th>
            <th>Observación</th>
            <th>Registro</th>
            <th>Registrado el</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($informe['registros_eventos'] as $registro)
            <tr>
              <td>{{ \App\Support\Informes::texto($registro['evento_nombre'] ?? '') }}</td>
              <td>{{ \App\Support\Informes::texto($registro['nombre'] ?? '') }}</td>
              <td>{{ \App\Support\Informes::texto($registro['tipo_entrada'] ?? '') }}</td>
              <td>{{ \App\Support\Informes::texto($registro['numeracion'] ?? '') }}</td>
              <td>{{ \App\Support\Informes::etiquetaPago($registro) }}</td>
              <td>{{ \App\Support\Informes::texto($registro['telefono'] ?? '') }}</td>
              <td><strong>{{ \App\Support\Informes::monto($registro['valor'] ?? 0) }}</strong></td>
              <td>{{ \App\Support\Informes::etiquetaForma($registro['forma_pago'] ?? null) }}</td>
              <td>{{ \App\Support\Informes::texto($registro['observacion'] ?? '') }}</td>
              <td>{{ \App\Support\Informes::texto($registro['registrado_por_nombre'] ?? '') }}</td>
              <td>{{ \App\Support\Informes::fechaHora($registro['creado_en'] ?? null) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  @endif

  @if ($conValores)
    <h2>Valores adicionales</h2>
    @if (empty($informe['valores_adicionales']))
      <p class="vacio">No hay valores adicionales registrados en este periodo.</p>
    @else
      <table class="datos">
        <thead>
          <tr>
            <th>Tipo</th>
            <th>Nombre</th>
            <th>Fecha</th>
            <th>Teléfono</th>
            <th>Valor</th>
            <th>Observación</th>
            <th>Registró</th>
            <th>Registrado el</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($informe['valores_adicionales'] as $valor)
            <tr>
              <td>{{ \App\Support\Informes::etiquetaTipoValor((string) ($valor['tipo'] ?? '')) }}</td>
              <td>{{ \App\Support\Informes::texto($valor['nombre'] ?? '') }}</td>
              <td>{{ \App\Support\Informes::fecha($valor['fecha'] ?? null) }}</td>
              <td>{{ \App\Support\Informes::texto($valor['telefono'] ?? '') }}</td>
              <td><strong>{{ \App\Support\Informes::monto($valor['valor'] ?? 0) }}</strong></td>
              <td>{{ \App\Support\Informes::texto($valor['observacion'] ?? '') }}</td>
              <td>{{ \App\Support\Informes::texto($valor['registrado_por_nombre'] ?? '') }}</td>
              <td>{{ \App\Support\Informes::fechaHora($valor['creado_en'] ?? null) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  @endif

  @if ($conPresentaciones)
    <h2>Presentación de niños</h2>
    @if (empty($informe['presentaciones']))
      <p class="vacio">No hay registros de presentación con los filtros seleccionados.</p>
    @else
      <table class="datos">
        <thead>
          <tr>
            <th>Nombre niño/a</th>
            <th>Edad</th>
            <th>Representante 1</th>
            <th>Representante 2</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($informe['presentaciones'] as $presentacion)
            <tr>
              <td>{{ \App\Support\Informes::texto($presentacion['nombre_presentado'] ?? '') }}</td>
              <td>{{ $presentacion['edad_etiqueta'] ?? \App\Support\Informes::edad($presentacion['fecha_nacimiento'] ?? null) }}</td>
              <td>{{ \App\Support\Informes::representante($presentacion, 1) }}</td>
              <td>{{ \App\Support\Informes::tieneSegundoRepresentante($presentacion) ? \App\Support\Informes::representante($presentacion, 2) : '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  @endif

  @if ($conBautismos)
    <h2>Personas bautizadas</h2>
    @if (empty($informe['bautismos']))
      <p class="vacio">No hay registros de bautismo con los filtros seleccionados.</p>
    @else
      <table class="datos">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Teléfono</th>
            <th>Estado</th>
            <th>Fecha de bautizo</th>
            <th>Registrado el</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($informe['bautismos'] as $bautismo)
            @php $nombre = trim(($bautismo['nombre'] ?? '').' '.($bautismo['apellido'] ?? '')); @endphp
            <tr>
              <td>{{ $nombre !== '' ? $nombre : '—' }}</td>
              <td>{{ \App\Support\Informes::texto($bautismo['celular'] ?? '') }}</td>
              <td>{{ \App\Support\Informes::etiquetaBautismo((string) ($bautismo['estado_bautismo'] ?? 'ingresado')) }}</td>
              <td>{{ \App\Support\Informes::fecha($bautismo['fecha_bautismo'] ?? null) }}</td>
              <td>{{ \App\Support\Informes::fechaHora($bautismo['creado_en'] ?? null) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  @endif
@endif
</body>
</html>
