<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TerritorioCategoria extends ModeloCda
{
    protected $table = 'territorio_categorias';

    protected $casts = [
        'orden' => 'integer',
        'creado_en' => 'datetime',
    ];

    public static function asegurarTablas(): void
    {
        if (! Schema::hasTable('territorio_categorias')) {
            Schema::create('territorio_categorias', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nombre', 100);
                $table->unsignedInteger('orden')->default(0);
                $table->timestamp('creado_en')->useCurrent();
            });
        }

        if (! Schema::hasTable('territorio_categoria')) {
            Schema::create('territorio_categoria', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('territorio_id');
                $table->unsignedInteger('categoria_id');
                $table->timestamp('creado_en')->useCurrent();
                $table->unique('territorio_id');
                $table->index('categoria_id');
            });
        }
    }

    public function vinculos(): HasMany
    {
        return $this->hasMany(TerritorioCategoriaVinculo::class, 'categoria_id');
    }
}
