<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PiramideNodo extends ModeloCda
{
    protected $table = 'piramide_equipos';

    protected $casts = [
        'padre_id' => 'integer',
        'orden' => 'integer',
        'creado_en' => 'datetime',
    ];

    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'padre_id');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(self::class, 'padre_id')->orderBy('orden')->orderBy('id');
    }

    public function miembros(): HasMany
    {
        return $this->hasMany(PiramideMiembro::class, 'equipo_id')->orderBy('orden')->orderBy('id');
    }

    /**
     * @return array<int, int>
     */
    public function idsMiembros(): array
    {
        return $this->miembros
            ->pluck('lider_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
    }
}
