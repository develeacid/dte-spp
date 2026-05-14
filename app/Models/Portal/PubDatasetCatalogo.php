<?php

namespace App\Models\Portal;

use Database\Factories\Portal\PubDatasetCatalogoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PubDatasetCatalogo extends Model
{
    use HasFactory;

    protected $connection = 'pgsql_public_read';
    protected $table = 'pub_datasets_catalogo';
    protected $guarded = [];
    protected $casts = ['fecha_publicacion' => 'date'];

    protected static function newFactory()
    {
        return PubDatasetCatalogoFactory::new();
    }
}
