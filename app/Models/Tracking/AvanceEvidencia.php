<?php

namespace App\Models\Tracking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AvanceEvidencia extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'avance_id', 'nombre_archivo', 'ruta_archivo', 'mime_type',
                'tamano_bytes', 'hash_archivo', 'geobase_snapshot_id',
                'nombre_documento', 'area_generadora', 'fecha_documento',
                'subido_por',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "AvanceEvidencia {$eventName}");
    }

    protected static function booted(): void
    {
        static::deleting(function (AvanceEvidencia $evidencia) {
            if ($evidencia->ruta_archivo) {
                Storage::disk('local')->delete($evidencia->ruta_archivo);
            }
        });
    }

    protected $fillable = [
        'avance_id', 'nombre_archivo', 'ruta_archivo', 'mime_type',
        'tamano_bytes', 'hash_archivo', 'geobase_snapshot_id',
        'nombre_documento', 'area_generadora', 'fecha_documento',
        'subido_por',
    ];

    protected function casts(): array
    {
        return [
            'tamano_bytes' => 'integer',
            'fecha_documento' => 'date',
            'geobase_snapshot_id' => 'integer',
        ];
    }

    public function avance(): BelongsTo
    {
        return $this->belongsTo(Avance::class);
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
