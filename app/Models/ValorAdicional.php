<?php

namespace App\Models;

use App\Support\Eventos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValorAdicional extends ModeloCda
{
    protected $table = 'valores_adicionales';

    protected $casts = [
        'fecha' => 'date',
        'valor' => 'decimal:2',
        'asistio' => 'boolean',
        'asistio_en' => 'datetime',
        'creado_en' => 'datetime',
    ];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }

    public function esRegistroEvento(): bool
    {
        return $this->tipo === Eventos::TIPO;
    }

    public function scopeSinEventos(Builder $consulta): Builder
    {
        return $consulta->where('tipo', '!=', Eventos::TIPO);
    }

    /**
     * @return array<int, array{id?: int, etiqueta?: string, valor?: string}>
     */
    public function respuestasAdicionales(): array
    {
        $crudo = trim((string) $this->info_adicional);

        if ($crudo === '') {
            return [];
        }

        $json = json_decode($crudo, true);

        return is_array($json) ? $json : [];
    }
}
