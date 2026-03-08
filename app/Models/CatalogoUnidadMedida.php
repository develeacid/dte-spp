<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogoUnidadMedida extends Model
{
    protected $table = 'catalogo_unidades_medida';

    protected $fillable = [
        'clave', 'nombre',
    ];
}
