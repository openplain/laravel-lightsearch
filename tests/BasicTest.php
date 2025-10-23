<?php

namespace Ktr\LightSearch\Tests;

use Ktr\LightSearch\Core\EngineFactory;

class BasicTest extends TestCase
{
    /** @test */
    public function it_can_create_database_engine()
    {
        $engine = EngineFactory::create('lightsearch_index');

        $this->assertNotNull($engine);
    }

    /** @test */
    public function it_loads_service_provider()
    {
        $providers = $this->app->getLoadedProviders();

        $this->assertArrayHasKey('Ktr\LightSearch\LightSearchServiceProvider', $providers);
    }

    /** @test */
    public function it_creates_lightsearch_index_table()
    {
        $this->assertTrue(\Schema::hasTable('lightsearch_index'));
        $this->assertTrue(\Schema::hasColumn('lightsearch_index', 'token'));
        $this->assertTrue(\Schema::hasColumn('lightsearch_index', 'record_id'));
        $this->assertTrue(\Schema::hasColumn('lightsearch_index', 'model'));
    }
}
