<?php

namespace Tests\Unit\Support\Tracking;

use App\Support\Tracking\TrackingOptions;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TrackingOptionsTest extends TestCase
{
    #[Test]
    public function programas_mapea_id_a_clave_nombre(): void
    {
        $programas = new Collection([
            (object) ['id' => 1, 'clave' => 'ISM-001', 'nombre' => 'Programa Uno'],
            (object) ['id' => 2, 'clave' => 'ISM-002', 'nombre' => 'Programa Dos'],
        ]);

        $opciones = TrackingOptions::programas($programas);

        $this->assertSame([
            1 => 'ISM-001 - Programa Uno',
            2 => 'ISM-002 - Programa Dos',
        ], $opciones);
    }

    #[Test]
    public function estados_devuelve_6_estados_pbr(): void
    {
        $estados = TrackingOptions::estados();

        $this->assertCount(6, $estados);
        $this->assertSame([
            'pendiente', 'en_captura', 'en_revision', 'aprobado', 'observado', 'vencido',
        ], array_keys($estados));
        $this->assertSame('Pendiente', $estados['pendiente']);
        $this->assertSame('Vencido', $estados['vencido']);
    }

    #[Test]
    public function semaforos_devuelve_4_valores(): void
    {
        $semaforos = TrackingOptions::semaforos();

        $this->assertCount(4, $semaforos);
        $this->assertSame(['verde', 'amarillo', 'rojo', 'gris'], array_keys($semaforos));
        $this->assertSame('Sin datos', $semaforos['gris']);
    }
}
