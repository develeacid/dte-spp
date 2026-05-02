<?php

namespace App\Models\Transparencia;

use App\Enums\EstadoDatasetAbierto;
use App\Models\User;
use Database\Factories\Transparencia\DatasetAbiertoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DatasetAbierto extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'datasets_abiertos';

    protected $fillable = [
        'dataset_clave',
        'nombre',
        'descripcion',
        'sistema_origen',
        'periodo',
        'status',
        'aprobado_por',
        'aprobado_en',
        'publicado_en',
        'hash_sha256',
        'ruta_archivo',
        'dcat_metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => EstadoDatasetAbierto::class,
            'aprobado_en' => 'datetime',
            'publicado_en' => 'datetime',
            'dcat_metadata' => 'array',
        ];
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    /**
     * Audit scope: máquina de estados (clave, status, fechas) + integridad (hash, ruta).
     * NO se loggean cuerpo del DCAT (`descripcion`, `dcat_metadata`) ni clasificadores
     * (`sistema_origen`, `periodo`) porque la política RDA actual sólo audita el ciclo
     * de aprobación/publicación. Reabrir cuando N2-01 defina alcance editorial.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'dataset_clave',
                'nombre',
                'status',
                'hash_sha256',
                'ruta_archivo',
                'aprobado_por',
                'aprobado_en',
                'publicado_en',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "DatasetAbierto {$eventName}");
    }

    protected static function newFactory(): DatasetAbiertoFactory
    {
        return DatasetAbiertoFactory::new();
    }
}
