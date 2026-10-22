<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TerritorioSubcategoria extends ModeloCda
{
    protected $table = 'territorio_subcategorias';

    protected $casts = [
        'territorio_id' => 'integer',
        'orden' => 'integer',
        'creado_en' => 'datetime',
    ];

    public static function asegurarTabla(): void
    {
        if (Schema::hasTable('territorio_subcategorias')) {
            return;
        }

        Schema::create('territorio_subcategorias', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('territorio_id');
            $table->string('nombre', 100);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamp('creado_en')->useCurrent();
            $table->index('territorio_id');
        });
    }

    public function territorio(): BelongsTo
    {
        return $this->belongsTo(Territorio::class, 'territorio_id');
    }
}
