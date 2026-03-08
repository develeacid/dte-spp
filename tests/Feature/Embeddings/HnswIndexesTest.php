<?php

namespace Tests\Feature\Embeddings;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HnswIndexesTest extends TestCase
{
    use RefreshDatabase;

    public function test_indices_hnsw_creados_correctamente(): void
    {
        $this->assertIndexExists('ods_objetivos_embedding_idx');
        $this->assertIndexExists('ods_metas_embedding_idx');
        $this->assertIndexExists('pnd_ejes_embedding_idx');
        $this->assertIndexExists('pnd_objetivos_embedding_idx');
        $this->assertIndexExists('pnd_estrategias_embedding_idx');
        $this->assertIndexExists('ped_ejes_embedding_idx');
        $this->assertIndexExists('ped_temas_embedding_idx');
        $this->assertIndexExists('ped_objetivos_estrategicos_embedding_idx');
        $this->assertIndexExists('ped_estrategias_embedding_idx');
        $this->assertIndexExists('ped_lineas_accion_embedding_idx');
        $this->assertIndexExists('programas_derivados_objetivos_embedding_idx');
    }

    public function test_indices_usan_hnsw(): void
    {
        $result = DB::selectOne("
            SELECT am.amname AS index_method
            FROM pg_index i
            JOIN pg_class c ON c.oid = i.indexrelid
            JOIN pg_am am ON am.oid = c.relam
            WHERE c.relname = 'ods_objetivos_embedding_idx'
        ");

        $this->assertEquals('hnsw', $result->index_method);
    }

    public function test_indices_tienen_parametros_correctos(): void
    {
        $result = DB::selectOne("
            SELECT pg_catalog.pg_get_indexdef(c.oid) AS indexdef
            FROM pg_class c
            WHERE c.relname = 'ods_objetivos_embedding_idx'
        ");

        $this->assertStringContainsString('hnsw', $result->indexdef);
        $this->assertStringContainsString('vector_cosine_ops', $result->indexdef);
    }

    protected function assertIndexExists(string $indexName): void
    {
        $result = DB::selectOne("
            SELECT 1
            FROM pg_class
            WHERE relname = ?
              AND relkind = 'i'
        ", [$indexName]);

        $this->assertNotNull($result, "Index {$indexName} does not exist");
    }
}
