@props([
    'id',
    'title',
    'filas' => [],
    'campos' => [],
    'action' => null,
])

@php
    $editable = $action && $campos !== [];
    $reabrir = $editable && $errors->any() && old('_detalle') === $id;
@endphp

<x-ui.modal :id="$id" :title="$title" size="md" :open="$reabrir" :data-detalle-abrir="$reabrir ? 'editar' : 'ver'">
    <div class="divide-y divide-line text-left" data-detalle-vista {{ $reabrir ? 'hidden' : '' }}>
        @forelse ($filas as $fila)
            <div class="block py-2.5 text-left first:pt-0 last:pb-0">
                <p class="mb-1 block text-left text-xs font-medium text-muted">{{ $fila['etiqueta'] }}</p>
                <p class="m-0 block text-left text-sm text-ink">
                    @if (!empty($fila['html']))
                        {!! $fila['valor'] !!}
                    @else
                        {{ $fila['valor'] }}
                    @endif
                </p>
            </div>
        @empty
            <p class="text-sm text-muted">No hay detalle para este registro.</p>
        @endforelse
    </div>

    @if ($editable)
        <form id="form-{{ $id }}" method="POST" action="{{ $action }}" class="space-y-0" data-detalle-form data-dirty {{ $reabrir ? '' : 'hidden' }}>
            @csrf
            @method('PUT')
            <input type="hidden" name="_detalle" value="{{ $id }}">
            <div class="divide-y divide-line text-left">
                @foreach ($campos as $campo)
                    @php
                        $nombre = $campo['name'] ?? null;
                        $valor = ($reabrir && $nombre) ? old($nombre, $campo['value']) : $campo['value'];
                        $inputId = $nombre ? $id.'-'.$nombre : null;
                    @endphp
                    <div class="space-y-1.5 py-2.5 first:pt-0 last:pb-0">
                        <label @if ($inputId) for="{{ $inputId }}" @endif class="block text-xs font-medium text-muted">
                            {{ $campo['label'] }}
                            @if (!empty($campo['required']))<span class="text-danger" aria-hidden="true"> *</span>@endif
                        </label>
                        <div class="min-w-0">
                            @if (($campo['type'] ?? '') === 'static' || ! $nombre)
                                <p class="whitespace-pre-wrap break-words text-sm text-ink">{{ $valor }}</p>
                            @elseif (($campo['type'] ?? '') === 'select')
                                <select class="input" id="{{ $inputId }}" name="{{ $nombre }}" @required(!empty($campo['required']))>
                                    @foreach ($campo['options'] ?? [] as $opcion => $etiqueta)
                                        <option value="{{ $opcion }}" @selected((string) $valor === (string) $opcion)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                            @elseif (($campo['type'] ?? '') === 'textarea')
                                <textarea class="input" id="{{ $inputId }}" name="{{ $nombre }}" rows="2" @required(!empty($campo['required'])) @if ($campo['maxlength'] ?? null) maxlength="{{ $campo['maxlength'] }}" @endif>{{ $valor }}</textarea>
                            @else
                                <input
                                    class="input{{ !empty($campo['readonly']) ? ' bg-surface-2' : '' }}"
                                    id="{{ $inputId }}"
                                    name="{{ $nombre }}"
                                    type="{{ $campo['type'] ?? 'text' }}"
                                    value="{{ $valor }}"
                                    @required(!empty($campo['required']))
                                    @readonly(!empty($campo['readonly']))
                                    @if ($campo['maxlength'] ?? null) maxlength="{{ $campo['maxlength'] }}" @endif
                                    @if (($campo['type'] ?? '') === 'number') step="{{ ($campo['name'] ?? '') === 'anio_en_cda' ? '1' : '0.01' }}" min="0" @endif
                                    @if (($campo['type'] ?? '') === 'tel') inputmode="tel" @endif
                                >
                            @endif
                            @if ($nombre && $reabrir && $errors->has($nombre))
                                <p class="mt-1 text-xs text-danger">{{ $errors->first($nombre) }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            @isset($extra)
                @if ($extra->isNotEmpty())
                    <div class="mt-4 border-t border-line pt-4">{{ $extra }}</div>
                @endif
            @endisset
        </form>
    @endif

    <x-slot:footer>
        <x-ui.button type="button" variant="secondary" data-modal-close :hidden="$reabrir">Cerrar</x-ui.button>
        @if ($editable)
            <x-ui.button type="button" variant="secondary" data-detalle-cancelar :hidden="! $reabrir">Cancelar</x-ui.button>
            <x-ui.button type="button" icon="bi-pencil" data-detalle-editar :hidden="$reabrir">Editar</x-ui.button>
            <x-ui.button type="submit" form="form-{{ $id }}" icon="bi-check2" data-detalle-guardar :hidden="! $reabrir">Guardar</x-ui.button>
        @endif
    </x-slot:footer>
</x-ui.modal>
