<?php

namespace Tests\Unit\Models\Transparencia;

use App\Enums\EstadoDatasetAbierto;
use App\Events\Transparencia\DatasetAbiertoPublicado;
use App\Events\Transparencia\DatasetAbiertoRetirado;
use App\Models\Transparencia\DatasetAbierto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DatasetAbiertoEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_publicar_dispara_evento(): void
    {
        Event::fake();
        $dataset = DatasetAbierto::factory()->create(['status' => EstadoDatasetAbierto::APROBADO]);

        $dataset->publicar();

        Event::assertDispatched(DatasetAbiertoPublicado::class, function ($event) use ($dataset) {
            return $event->dataset->is($dataset);
        });
    }

    public function test_retirar_dispara_evento(): void
    {
        Event::fake();
        $dataset = DatasetAbierto::factory()->create(['status' => EstadoDatasetAbierto::PUBLICADO]);

        $dataset->retirar('motivo de prueba');

        Event::assertDispatched(DatasetAbiertoRetirado::class, function ($event) use ($dataset) {
            return $event->dataset->is($dataset);
        });
    }

    public function test_publicar_no_dispara_evento_si_falla_state_machine(): void
    {
        Event::fake();
        $dataset = DatasetAbierto::factory()->create(['status' => EstadoDatasetAbierto::BORRADOR]);

        try {
            $dataset->publicar();
        } catch (\DomainException) {
            // expected
        }

        Event::assertNotDispatched(DatasetAbiertoPublicado::class);
    }
}
