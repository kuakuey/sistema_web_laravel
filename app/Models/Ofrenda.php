<?php

namespace App\Models;

class Ofrenda extends ModeloCda
{
    protected $table = 'ofrendas';

    protected $casts = [
        'fecha_ofrenda' => 'date',
        'monto' => 'decimal:2',
        'creado_en' => 'datetime',
    ];
}
