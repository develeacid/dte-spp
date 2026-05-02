<?php

namespace App\Console\Commands;

use App\Enums\EstadoDatasetAbierto;
use App\Models\Transparencia\DatasetAbierto;
use Illuminate\Console\Command;

class RegistrarAcuerdoLegalCommand extends Command
{
    protected $signature = 'legal:registrar-acuerdo
                            {ruta : Ruta al archivo del acuerdo (markdown borrador o PDF firmado)}
                            {--status= : Forzar status (borrador|publicado) cuando la ruta no encaje en el patrón conocido}';

    protected $description = 'Registra/actualiza el acuerdo institucional (DS-00) en datasets_abiertos con hash SHA-256';

    public function handle(): int
    {
        $ruta = $this->argument('ruta');

        if (! is_file($ruta)) {
            $this->error("El archivo {$ruta} no existe");
            return self::FAILURE;
        }

        if (filesize($ruta) === 0) {
            $this->error("El archivo {$ruta} está vacío");
            return self::FAILURE;
        }

        $hash = @hash_file('sha256', $ruta);
        if ($hash === false) {
            $this->error("No se pudo leer {$ruta} (¿permisos? ¿desapareció?)");
            return self::FAILURE;
        }

        $rutaRelativa = $this->rutaRelativa($ruta);
        $statusObjetivo = $this->resolverStatus($rutaRelativa);
        if ($statusObjetivo === null) {
            return self::FAILURE;
        }

        $existente = DatasetAbierto::where('dataset_clave', 'DS-00')->where('periodo', null)->first();

        if ($existente
            && $existente->hash_sha256 === $hash
            && $existente->status === $statusObjetivo
            && $existente->ruta_archivo === $rutaRelativa
        ) {
            $this->info('Sin cambios — el acuerdo ya está registrado con el mismo hash y status.');
            return self::SUCCESS;
        }

        $atributos = [
            'nombre' => 'Política institucional de clasificación de información',
            'sistema_origen' => 'spp',
            'hash_sha256' => $hash,
            'ruta_archivo' => $rutaRelativa,
            'status' => $statusObjetivo->value,
        ];

        if ($statusObjetivo === EstadoDatasetAbierto::PUBLICADO) {
            $atributos['publicado_en'] = now();
        }

        DatasetAbierto::updateOrCreate(
            ['dataset_clave' => 'DS-00', 'periodo' => null],
            $atributos
        );

        if (! $existente) {
            $this->warn('DS-00 no existía; creado.');
        }

        $this->info("Acuerdo registrado: {$rutaRelativa}");
        $this->line("  Hash SHA-256: {$hash}");
        $this->line("  Status: {$statusObjetivo->value}");

        return self::SUCCESS;
    }

    private function rutaRelativa(string $rutaAbsoluta): string
    {
        $base = base_path() . DIRECTORY_SEPARATOR;
        if (str_starts_with($rutaAbsoluta, $base)) {
            return str_replace(DIRECTORY_SEPARATOR, '/', substr($rutaAbsoluta, strlen($base)));
        }
        return $rutaAbsoluta;
    }

    private function resolverStatus(string $rutaRelativa): ?EstadoDatasetAbierto
    {
        $statusFlag = $this->option('status');
        if ($statusFlag !== null) {
            $status = EstadoDatasetAbierto::tryFrom($statusFlag);
            if ($status === null) {
                $valores = array_map(fn ($c) => $c->value, EstadoDatasetAbierto::cases());
                $this->error("--status inválido: '{$statusFlag}'. Valores: " . implode(', ', $valores));
                return null;
            }
            return $status;
        }

        if (str_contains($rutaRelativa, 'storage/app/legal/acuerdo-firmado')) {
            return EstadoDatasetAbierto::PUBLICADO;
        }

        if (str_starts_with($rutaRelativa, 'docs/legal/') && str_ends_with($rutaRelativa, '.md')) {
            return EstadoDatasetAbierto::BORRADOR;
        }

        // En modo no-interactivo (cron, CI), confirm() devuelve el default sin preguntar.
        // Forzar el uso de --status para evitar clasificación silenciosa por defecto.
        if (! $this->input->isInteractive()) {
            $this->error('Ruta no canónica y entrada no interactiva. Use --status=borrador|publicado.');
            return null;
        }

        if ($this->confirm('La ruta no encaja en patrones conocidos. ¿Marcar como publicado?', false)) {
            return EstadoDatasetAbierto::PUBLICADO;
        }
        return EstadoDatasetAbierto::BORRADOR;
    }
}
