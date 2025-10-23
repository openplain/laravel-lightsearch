<?php

namespace Ktr\LightSearch\Core;

use Illuminate\Support\Facades\DB;
use Ktr\LightSearch\Core\Engines\DatabaseEngine;
use Ktr\LightSearch\Core\Engines\MySQLEngine;
use Ktr\LightSearch\Core\Engines\PostgreSQLEngine;
use Ktr\LightSearch\Core\Engines\SQLiteEngine;

class EngineFactory
{
    /**
     * Create the appropriate database engine based on the connection driver.
     */
    public static function create(string $table, ?string $connection = null): DatabaseEngine
    {
        $driver = DB::connection($connection)->getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => new MySQLEngine($table),
            'pgsql' => new PostgreSQLEngine($table),
            'sqlite' => new SQLiteEngine($table),
            default => new MySQLEngine($table), // Fallback to MySQL
        };
    }
}
