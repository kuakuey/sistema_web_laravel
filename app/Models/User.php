<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'usuarios';

    protected $authPasswordName = 'clave';

    protected $rememberTokenName = '';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = null;

    protected $fillable = [
        'usuario',
        'clave',
        'nombre',
        'rol',
    ];

    protected $hidden = [
        'clave',
    ];

    protected $casts = [
        'creado_en' => 'datetime',
    ];

    public function rolNormalizado(): string
    {
        return $this->rol === 'contador' ? 'counter' : (string) $this->rol;
    }

    public function nombreVisible(): string
    {
        $nombre = trim((string) $this->nombre);

        return $nombre !== '' ? $nombre : (string) $this->usuario;
    }
}
