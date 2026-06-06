<?php

namespace Tests\Feature\Livewire\Concerns;

use App\Livewire\Concerns\HasTraceableTable;
use App\Models\Mml\MirNivel;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

class FakeTraceableComponent extends Component
{
    use HasTraceableTable;

    public function testApplyFilters($query)
    {
        return $this->applyTraceableFilters(
            $query,
            'fake.col',
            'programa_presupuestarios.clave',
            'fake.fecha',
            'mir_niveles.orden',
        );
    }

    public function render()
    {
        return '<div></div>';
    }
}

class HasTraceableTableTest extends TestCase
{
    public function test_group_by_programa_default_true(): void
    {
        Livewire::test(FakeTraceableComponent::class)
            ->assertSet('groupByPrograma', true);
    }

    public function test_togglear_group_by_programa_resetea_pagina(): void
    {
        Livewire::test(FakeTraceableComponent::class)
            ->set('paginators.page', 5)
            ->set('groupByPrograma', false)
            ->assertSet('paginators.page', 1);
    }

    public function test_apply_traceable_filters_ordena_por_programa_primero_cuando_group_on(): void
    {
        $c = new FakeTraceableComponent;
        $c->groupByPrograma = true;
        $c->sortBy = 'indicador';
        $c->sortDir = 'asc';

        $query = MirNivel::query();
        $c->testApplyFilters($query);

        $sql = $query->toSql();
        $orderByPos = stripos($sql, 'order by');
        $this->assertNotFalse($orderByPos, 'debe existir cláusula order by');

        $orderClause = substr($sql, $orderByPos);
        $pos1 = strpos($orderClause, 'programa_presupuestarios');
        $pos2 = strpos($orderClause, 'mir_niveles');

        $this->assertNotFalse($pos1, 'programa_presupuestarios debe aparecer en ORDER BY');
        $this->assertNotFalse($pos2, 'mir_niveles debe aparecer en ORDER BY');
        $this->assertLessThan($pos2, $pos1, 'programa_presupuestarios debe ordenar antes que mir_niveles');
    }

    public function test_apply_traceable_filters_no_ordena_por_programa_cuando_group_off(): void
    {
        $c = new FakeTraceableComponent;
        $c->groupByPrograma = false;
        $c->sortBy = 'fecha';

        $query = MirNivel::query();
        $c->testApplyFilters($query);

        $this->assertStringNotContainsString('programa_presupuestarios.clave', $query->toSql());
    }
}
