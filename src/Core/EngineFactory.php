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
     * Cache of engine instances to avoid recreating them.
     * Key format: "{driver}:{table}:{connection}"
     */
    protected static array $engines = [];

    /**
     * Create the appropriate database engine based on the connection driver.
     * Returns cached instance if already created.
     */
    public static function create(string $table, ?string $connection = null): DatabaseEngine
    {
        $driver = DB::connection($connection)->getDriverName();
        $cacheKey = "{$driver}:{$table}:".($connection ?? 'default');

        // Return cached instance if available
        if (isset(self::$engines[$cacheKey])) {
            return self::$engines[$cacheKey];
        }

        // Create new instance and cache it
        $engine = match ($driver) {
            'mysql', 'mariadb' => new MySQLEngine($table),
            'pgsql' => new PostgreSQLEngine($table),
            'sqlite' => new SQLiteEngine($table),
            default => new MySQLEngine($table), // Fallback to MySQL
        };

        self::$engines[$cacheKey] = $engine;

        return $engine;
    }
}
