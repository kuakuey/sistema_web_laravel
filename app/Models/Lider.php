<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Lider extends ModeloCda
{
    protected $table = 'lideres';

    protected $casts = [
        'fecha_bautismo' => 'date',
        'creado_en' => 'datetime',
    ];

    public function nombreCompleto(): string
    {
        return trim($this->nombre.' '.$this->apellido);
    }

    public function iniciales(): string
    {
        return mb_strtoupper(mb_substr((string) $this->nombre, 0, 1).mb_substr((string) $this->apellido, 0, 1));
    }

    public function casasComoLider(): HasMany
    {
        return $this->hasMany(CasaVida::class, 'lider_id');
    }

    public function asignacionesMinisterio(): HasMany
    {
        return $this->hasMany(TerritorioAsignacion::class, 'miembro_id');
    }

    /**
     * @return array<int, string>
     */
    public function nombresMinisterios(): array
    {
        return $this->asignacionesMinisterio
            ->map(fn (TerritorioAsignacion $asignacion) => $asignacion->territorio?->nombre)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
