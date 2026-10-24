@props([
    'registro',
    'editable' => true,
])

@php
    use App\Support\ApiPresentaciones;

    $pasos = ApiPresentaciones::flujoPanel();
    $total = count($pasos);
    $estado = (string) ($registro->estado ?? 'recibido');
    $indice = ApiPresentaciones::indiceFlujo($estado);
    $siguiente = ApiPresentaciones::siguienteEstado($estado);
    $etiqueta = ApiPresentaciones::etiquetaEstado($estado);
    $porcentaje = (int) round((($indice + 1) / $total) * 100);

    $colores = match ($estado) {
        'presentado' => ['barra' => 'bg-success', 'texto' => 'text-success'],
        'confirmado', 'entregar_diploma' => ['barra' => 'bg-warning', 'texto' => 'text-warning'],
        'contactado' => ['barra' => 'bg-accent', 'texto' => 'text-accent'],
        default => ['barra' => 'bg-muted', 'texto' => 'text-muted'],
    };

    $puedeAvanzar = $editable && $siguiente !== null;
    $etiquetaSiguiente = $siguiente ? ApiPresentaciones::etiquetaEstado($siguiente) : '';
@endphp

@if ($estado === 'presentado')
  <div {{ $attributes->merge(['data-progreso-presentacion' => '']) }}>
    <x-ui.badge tone="success">Presentado</x-ui.badge>
  </div>
@else
  <div {{ $attributes->class('flex min-w-[12rem] max-w-[16rem] items-center gap-2')->merge(['data-progreso-presentacion' => '']) }}>
    <div class="min-w-0 flex-1">
      <div class="mb-1 flex items-center justify-between gap-2">
        <span class="truncate text-[0.7rem] font-semibold leading-none {{ $colores['texto'] }}">{{ $etiqueta }}</span>
        <span class="shrink-0 text-[0.65rem] tabular-nums text-muted">{{ $porcentaje }}%</span>
      </div>

      <div class="h-2 overflow-hidden rounded-full bg-line" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $porcentaje }}" aria-valuetext="{{ $etiqueta }}">
        <div class="h-full rounded-full transition-all duration-300 {{ $colores['barra'] }}" style="width: {{ $porcentaje }}%"></div>
      </div>
    </div>

    @if ($puedeAvanzar)
      <form method="POST" action="{{ route('presentaciones.estado', $registro) }}" class="m-0 shrink-0" data-ajax-estado-presentacion>
        @csrf
        <input type="hidden" name="accion" value="avanzar">
        <x-ui.button
          type="submit"
          size="sm"
          variant="secondary"
          icon="chevron-right"
          title="Avanzar a {{ $etiquetaSiguiente }}"
          aria-label="Avanzar a {{ $etiquetaSiguiente }}"
        />
      </form>
    @endif
  </div>
@endif
