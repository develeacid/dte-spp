<?php

namespace App\Models\Portal;

use Database\Factories\Portal\PubProgramaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PubPrograma extends Model
{
    use HasFactory;

    protected $connection = 'pgsql_public_read';

    protected $table = 'pub_programas';

    protected $guarded = [];

    protected $casts = ['ejercicio_fiscal' => 'integer', 'activo' => 'boolean'];

    protected static function newFactory()
    {
        return PubProgramaFactory::new();
    }
}
