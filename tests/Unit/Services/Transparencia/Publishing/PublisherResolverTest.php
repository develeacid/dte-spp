<?php

namespace Tests\Unit\Services\Transparencia\Publishing;

use App\Services\Transparencia\Publishing\PublisherInterface;
use App\Services\Transparencia\Publishing\PublisherResolver;
use Tests\TestCase;

class PublisherResolverTest extends TestCase
{
    public function test_resolver_retorna_publisher_para_codigo_conocido(): void
    {
        $resolver = app(PublisherResolver::class);

        $publisher = $resolver->for('DS-01');

        $this->assertInstanceOf(PublisherInterface::class, $publisher);
        $this->assertSame('DS-01', $publisher->code());
    }

    public function test_resolver_retorna_null_para_codigo_no_mapeado(): void
    {
        $resolver = app(PublisherResolver::class);

        $this->assertNull($resolver->for('DS-00'));
        $this->assertNull($resolver->for('DS-G01'));
        $this->assertNull($resolver->for('CODIGO-INEXISTENTE'));
    }

    public function test_resolver_codigos_soportados(): void
    {
        $resolver = app(PublisherResolver::class);

        $expected = ['DS-01', 'DS-02', 'DS-03', 'DS-04', 'DS-05', 'DS-G04'];

        foreach ($expected as $code) {
            $this->assertInstanceOf(PublisherInterface::class, $resolver->for($code), "Falta publisher para $code");
        }
    }
}
