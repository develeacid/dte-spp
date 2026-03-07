<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramaPresupuestario extends Model
{
    protected $fillable = ['nombre', 'clave'];

    public function equipos()
    {
        return $this->belongsToMany(Team::class, 'programa_team')
                    ->withPivot('rol')
                    ->withTimestamps();
    }
}
