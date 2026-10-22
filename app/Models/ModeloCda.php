<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class ModeloCda extends Model
{
    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = null;

    protected $guarded = ['id'];
}
