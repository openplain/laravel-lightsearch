<?php

namespace Ktr\LightSearch\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ktr\LightSearch\LightSearchServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            LightSearchServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('scout.driver', 'lightsearch');
    }

    protected function setUpDatabase()
    {
        // Create lightsearch_index table
        Schema::create('lightsearch_index', function (Blueprint $table) {
            $table->id();
            $table->string('token', 191);
            $table->string('record_id', 191);
            $table->string('model', 191);
            $table->timestamps();

            $table->unique(['token', 'record_id', 'model'], 'lightsearch_unique_idx');
            $table->index(['model', 'token'], 'lightsearch_model_token_idx');
            $table->index(['model', 'record_id'], 'lightsearch_model_record_idx');
        });
    }
}
