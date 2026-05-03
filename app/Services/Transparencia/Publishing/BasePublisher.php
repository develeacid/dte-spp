<?php

namespace App\Services\Transparencia\Publishing;

use App\Models\Transparencia\DatasetAbierto;
use Illuminate\Support\Facades\DB;

abstract class BasePublisher implements PublisherInterface
{
    /** Nombre de la tabla pub_* destino. */
    abstract protected function tabla(): string;

    /** Código DS que este publisher maneja. */
    abstract public function code(): string;

    /**
     * Construye las filas a insertar desde la BD privada.
     *
     * @return array<int, array<string, mixed>>
     */
    abstract protected function buildRows(DatasetAbierto $dataset): array;

    /**
     * Columnas excluidas del cálculo de hash. id y timestamps cambian entre
     * publish() consecutivos (autoincrement no resetea con DELETE), así que
     * no son parte del payload "lógico" para integridad.
     *
     * @return array<int, string>
     */
    protected function columnasExcluidasDelHash(): array
    {
        return ['id', 'created_at', 'updated_at'];
    }

    public function publish(DatasetAbierto $dataset): array
    {
        return DB::connection('pgsql_public')->transaction(function () use ($dataset) {
            DB::connection('pgsql_public')->table($this->tabla())->delete();

            $rows = $this->buildRows($dataset);
            if (! empty($rows)) {
                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::connection('pgsql_public')->table($this->tabla())->insert($chunk);
                }
            }

            $payload = DB::connection('pgsql_public')->table($this->tabla())->orderBy('id')->get();

            $excluidas = $this->columnasExcluidasDelHash();
            $payloadParaHash = $payload->map(function ($row) use ($excluidas) {
                $arr = (array) $row;
                foreach ($excluidas as $col) {
                    unset($arr[$col]);
                }
                ksort($arr);

                return $arr;
            })->values();

            return [
                'count' => $payload->count(),
                'hash' => hash('sha256', $payloadParaHash->toJson()),
            ];
        });
    }

    public function retire(DatasetAbierto $dataset): void
    {
        DB::connection('pgsql_public')->table($this->tabla())->delete();
    }
}
