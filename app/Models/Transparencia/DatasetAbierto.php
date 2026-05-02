<?php

namespace App\Models\Transparencia;

use App\Enums\EstadoDatasetAbierto;
use App\Models\User;
use Database\Factories\Transparencia\DatasetAbiertoFactory;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
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
        'creado_por',
        'motivo_cambio_estado',
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

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
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
                'creado_por',
                'motivo_cambio_estado',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "DatasetAbierto {$eventName}");
    }

    // -------------------------------------------------------------------------
    // Máquina de estados
    // -------------------------------------------------------------------------

    public function enviarARevision(): void
    {
        if ($this->status !== EstadoDatasetAbierto::BORRADOR) {
            throw new DomainException("No se puede enviar a revisión desde estado {$this->status->value}.");
        }
        $this->status = EstadoDatasetAbierto::REVISION;
        $this->save();
    }

    public function aprobar(User $aprobador): void
    {
        if ($this->status !== EstadoDatasetAbierto::REVISION) {
            throw new DomainException("No se puede aprobar desde estado {$this->status->value}.");
        }
        $this->status = EstadoDatasetAbierto::APROBADO;
        $this->aprobado_por = $aprobador->id;
        $this->aprobado_en = now();
        $this->save();
    }

    public function publicar(): void
    {
        if ($this->status !== EstadoDatasetAbierto::APROBADO) {
            throw new DomainException("No se puede publicar desde estado {$this->status->value}.");
        }
        $this->status = EstadoDatasetAbierto::PUBLICADO;
        $this->publicado_en = now();
        $this->save();
    }

    public function retirar(string $motivo): void
    {
        if ($this->status !== EstadoDatasetAbierto::PUBLICADO) {
            throw new DomainException("No se puede retirar desde estado {$this->status->value}.");
        }
        $this->status = EstadoDatasetAbierto::RETIRADO;
        $this->motivo_cambio_estado = $motivo;
        $this->save();
    }

    public function rechazar(string $motivo): void
    {
        if ($this->status !== EstadoDatasetAbierto::REVISION) {
            throw new DomainException("No se puede rechazar desde estado {$this->status->value}.");
        }
        $this->status = EstadoDatasetAbierto::BORRADOR;
        $this->motivo_cambio_estado = $motivo;
        $this->aprobado_por = null;
        $this->aprobado_en = null;
        $this->save();
    }

    public function clonarParaPeriodo(string $periodo, User $autor): self
    {
        if ($this->periodo !== null) {
            throw new DomainException('Solo se pueden clonar plantillas (periodo IS NULL).');
        }
        if (! preg_match('/^\d{4}(-Q[1-4])?$/', $periodo)) {
            throw new DomainException("Formato de periodo inválido: '{$periodo}'. Use YYYY o YYYY-Q[1-4].");
        }

        return self::create([
            'dataset_clave' => $this->dataset_clave,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'sistema_origen' => $this->sistema_origen,
            'periodo' => $periodo,
            'status' => EstadoDatasetAbierto::BORRADOR,
            'dcat_metadata' => $this->dcat_metadata,
            'creado_por' => $autor->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePlantillas(Builder $query): Builder
    {
        return $query->whereNull('periodo');
    }

    public function scopeEntregas(Builder $query): Builder
    {
        return $query->whereNotNull('periodo');
    }

    public function scopeAprobadas(Builder $query): Builder
    {
        return $query->where('status', EstadoDatasetAbierto::APROBADO);
    }

    public function scopePublicadas(Builder $query): Builder
    {
        return $query->where('status', EstadoDatasetAbierto::PUBLICADO);
    }

    protected static function newFactory(): DatasetAbiertoFactory
    {
        return DatasetAbiertoFactory::new();
    }
}
