<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Territorio extends ModeloCda
{
    protected $table = 'territorios';

    protected $casts = [
        'orden' => 'integer',
        'creado_en' => 'datetime',
    ];

    public function asignaciones(): HasMany
    {
        return $this->hasMany(TerritorioAsignacion::class, 'territorio_id');
    }

    public function vinculoCategoria(): HasOne
    {
        return $this->hasOne(TerritorioCategoriaVinculo::class, 'territorio_id');
    }

    public function categoriaId(): int
    {
        return (int) ($this->vinculoCategoria?->categoria_id ?: 0);
    }

    public function nombreCategoria(): string
    {
        return $this->vinculoCategoria?->categoria?->nombre ?: 'Sin asignar';
    }

    public function scopeDeCategoria($consulta, string $nombre)
    {
        $nombre = mb_strtolower(trim($nombre));

        return $consulta->whereHas('vinculoCategoria.categoria', function ($categoria) use ($nombre) {
            $categoria->whereRaw('LOWER(nombre) = ?', [$nombre]);
        });
    }

    public function casas(): HasMany
    {
        return $this->hasMany(CasaVida::class, 'territorio_id');
    }

    /**
     * @return array<int, string>
     */
    public function nombresRol(string $rol): array
    {
        return $this->asignaciones
            ->where('rol', $rol)
            ->map(fn (TerritorioAsignacion $asignacion) => $asignacion->miembro?->nombreCompleto())
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function idsRol(string $rol): array
    {
        return $this->asignaciones
            ->where('rol', $rol)
            ->pluck('miembro_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
    }
}
