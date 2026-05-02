<?php

namespace App\Livewire\Presupuesto;

use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class ImportarPresupuesto extends Component
{
    use WithFileUploads;

    public $archivo;

    public array $preview = [];

    public array $errores = [];

    public int $ejercicioFiscal;

    public string $paso = 'upload'; // upload | preview | resultado

    public int $creados = 0;

    public int $actualizados = 0;

    public int $erroresCount = 0;

    public function mount(): void
    {
        $this->ejercicioFiscal = config('presupuesto.ejercicio_default');
    }

    public function procesar(): void
    {
        $this->validate([
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $this->preview = [];
        $this->errores = [];

        $path = $this->archivo->getRealPath();
        $handle = fopen($path, 'r');

        // Leer encabezados
        $headers = fgetcsv($handle);
        if (! $headers) {
            $this->errores[] = 'El archivo CSV está vacío o no tiene encabezados.';
            fclose($handle);

            return;
        }

        // Normalizar encabezados
        $headers = array_map(fn ($h) => strtolower(trim($h)), $headers);
        $required = ['clave_programa', 'clave_partida', 'descripcion', 'monto_aprobado'];
        $missing = array_diff($required, $headers);

        if (! empty($missing)) {
            $this->errores[] = 'Columnas faltantes: '.implode(', ', $missing);
            fclose($handle);

            return;
        }

        $teamId = auth()->user()->currentTeam->id;
        $row = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            $mapped = array_combine($headers, $data);

            // Validar que el programa existe
            $programa = ProgramaPresupuestario::paraTeam($teamId)
                ->where('clave', trim($mapped['clave_programa']))
                ->first();

            if (! $programa) {
                $this->errores[] = "Fila {$row}: Programa '{$mapped['clave_programa']}' no encontrado.";

                continue;
            }

            // Verificar si ya existe
            $existe = PartidaPresupuestal::where('programa_presupuestario_id', $programa->id)
                ->where('clave_partida', trim($mapped['clave_partida']))
                ->where('ejercicio_fiscal', $this->ejercicioFiscal)
                ->exists();

            $this->preview[] = [
                'fila' => $row,
                'clave_programa' => $mapped['clave_programa'],
                'programa_id' => $programa->id,
                'clave_partida' => trim($mapped['clave_partida']),
                'descripcion' => trim($mapped['descripcion']),
                'monto_aprobado' => (float) $mapped['monto_aprobado'],
                'monto_modificado' => ! empty($mapped['monto_modificado']) ? (float) $mapped['monto_modificado'] : null,
                'accion' => $existe ? 'actualizar' : 'crear',
            ];
        }

        fclose($handle);
        $this->paso = 'preview';
    }

    public function confirmar(): void
    {
        $teamId = auth()->user()->currentTeam->id;
        $this->creados = 0;
        $this->actualizados = 0;
        $this->erroresCount = 0;

        foreach ($this->preview as $item) {
            try {
                PartidaPresupuestal::updateOrCreate(
                    [
                        'programa_presupuestario_id' => $item['programa_id'],
                        'clave_partida' => $item['clave_partida'],
                        'ejercicio_fiscal' => $this->ejercicioFiscal,
                    ],
                    [
                        'descripcion' => $item['descripcion'],
                        'monto_aprobado' => $item['monto_aprobado'],
                        'monto_modificado' => $item['monto_modificado'],
                        'team_id' => $teamId,
                        'registrado_por' => auth()->id(),
                    ]
                );

                $item['accion'] === 'crear' ? $this->creados++ : $this->actualizados++;
            } catch (\Exception $e) {
                $this->erroresCount++;
            }
        }

        $this->paso = 'resultado';
    }

    public function reiniciar(): void
    {
        $this->reset(['archivo', 'preview', 'errores', 'paso', 'creados', 'actualizados', 'erroresCount']);
        $this->paso = 'upload';
    }

    public function render(): View
    {
        return view('livewire.presupuesto.importar-presupuesto');
    }
}
