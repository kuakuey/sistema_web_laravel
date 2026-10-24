<?php

namespace App\Models;

class SesionApi extends ModeloCda
{
    protected $table = 'sesiones_api';

    protected $casts = [
        'usuario_id' => 'integer',
        'expira_en' => 'datetime',
        'creado_en' => 'datetime',
    ];
}
