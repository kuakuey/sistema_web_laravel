<?php

namespace App\Models;

use App\Support\Eventos;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evento extends ModeloCda
{
    protected $table = 'eventos';

    protected $casts = [
        'fecha' => 'date',
        'valor' => 'decimal:2',
        'habilitado' => 'boolean',
        'requiere_numeracion' => 'boolean',
        'creado_en' => 'datetime',
    ];

    public function tiposEntrada(): HasMany
    {
        return $this->hasMany(EventoTipoEntrada::class, 'evento_id')->orderBy('orden')->orderBy('id');
    }

    public function camposAdicionales(): HasMany
    {
        return $this->hasMany(EventoCampoAdicional::class, 'evento_id')->orderBy('orden')->orderBy('id');
    }

    public function registros(): HasMany
    {
        return $this->hasMany(ValorAdicional::class, 'evento_id')->where('tipo', Eventos::TIPO);
    }

    public function slug(): string
    {
        return Eventos::slug($this);
    }

    public static function buscarPorSlug(string $slug): ?self
    {
        if (! preg_match('/-(\d+)$/', $slug, $partes)) {
            return null;
        }

        return static::query()->with(['tiposEntrada', 'camposAdicionales'])->find((int) $partes[1]);
    }
}
