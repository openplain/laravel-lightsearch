<?php

namespace Ktr\LightSearch\Core\Engines;

use Illuminate\Support\Facades\DB;

class PostgreSQLEngine extends DatabaseEngine
{
    protected ?bool $hasTrgm = null;

    /**
     * Search using PostgreSQL's native text search capabilities.
     * Uses ILIKE for case-insensitive prefix matching.
     */
    public function search(array $terms, string $model, int $limit = 10, int $offset = 0): array
    {
        $query = DB::table($this->table)
            ->select('record_id', DB::raw('COUNT(*) as occurrences'))
            ->where('model', $model)
            ->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    // PostgreSQL ILIKE is case-insensitive and optimized
                    $q->orWhere('token', 'ilike', $term.'%');
                }
            })
            ->groupBy('record_id')
            ->orderByDesc('occurrences')
            ->orderBy('record_id')  // Secondary sort for consistent ordering when scores are tied
            ->offset($offset)
            ->limit($limit);

        return $query->pluck('record_id')->toArray();
    }

    /**
     * Get total count of matching records.
     */
    public function count(array $terms, string $model): int
    {
        $subQuery = DB::table($this->table)
            ->select('record_id')
            ->where('model', $model)
            ->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhere('token', 'ilike', $term.'%');
                }
            })
            ->groupBy('record_id');

        return DB::table(DB::raw("({$subQuery->toSql()}) as search_results"))
            ->mergeBindings($subQuery)
            ->count();
    }

    /**
     * Check if this engine supports fuzzy search.
     */
    public function supportsFuzzySearch(): bool
    {
        return $this->hasTrgmExtension();
    }

    /**
     * Perform fuzzy search using PostgreSQL's pg_trgm extension.
     * Falls back to regular prefix search if pg_trgm is not available.
     *
     * @param  float  $threshold  Similarity threshold (0.0 to 1.0)
     * @return array Array with 'ids' and 'total' keys
     */
    public function fuzzySearch(array $terms, string $model, float $threshold = 0.3, int $limit = 10, int $offset = 0): array
    {
        if (!$this->hasTrgmExtension()) {
            // Fallback to regular prefix search
            return ['ids' => $this->search($terms, $model, $limit, $offset), 'total' => null];
        }

        try {
            // Build CASE statements for each search term to score matching tokens
            $caseStatements = [];
            $selectBindings = [];

            foreach ($terms as $term) {
                // For each term, calculate its best similarity match across all tokens
                $caseStatements[] = 'MAX(CASE WHEN similarity(token, ?) > ? THEN similarity(token, ?) ELSE 0 END)';
                $selectBindings[] = $term;  // For similarity check
                $selectBindings[] = $threshold;  // For threshold comparison
                $selectBindings[] = $term;  // For similarity calculation
            }

            // Sum all the best matches for each term (rewards matching multiple terms)
            $scoreExpression = '('.implode(' + ', $caseStatements).')';

            // Build WHERE conditions with bindings
            $whereConditions = [];
            $whereBindings = [];
            foreach ($terms as $term) {
                $whereConditions[] = 'similarity(token, ?) > ?';
                $whereBindings[] = $term;
                $whereBindings[] = $threshold;
            }

            // Use COUNT(*) OVER() window function to get total count in same query
            // This eliminates the need for a separate count query
            $sql = sprintf(
                'SELECT record_id, total_score, total_count FROM (
                    SELECT record_id, total_score, COUNT(*) OVER() as total_count
                    FROM (
                        SELECT record_id, %s as total_score
                        FROM %s
                        WHERE model = ?
                        AND (%s)
                        GROUP BY record_id
                        HAVING %s > 0
                    ) as scored_results
                ) as fuzzy_results
                ORDER BY total_score DESC
                LIMIT ? OFFSET ?',
                $scoreExpression,  // For inner SELECT scoring
                $this->table,
                implode(' OR ', $whereConditions),
                $scoreExpression  // Repeat expression in HAVING
            );

            // Combine all bindings in correct order
            $allBindings = array_merge(
                $selectBindings,  // For inner SELECT score calculation
                [$model],  // For WHERE model =
                $whereBindings,  // FOR WHERE similarity conditions
                $selectBindings,  // For HAVING score calculation (repeat)
                [$limit, $offset]  // For LIMIT and OFFSET
            );

            $results = DB::select($sql, $allBindings);

            return [
                'ids' => array_column($results, 'record_id'),
                'total' => !empty($results) ? (int) $results[0]->total_count : 0,
            ];
        } catch (\Exception $e) {
            // If similarity() function fails, the extension was likely disabled
            // Clear the stale cache and fall back to regular search
            if (str_contains($e->getMessage(), 'similarity')) {
                $this->clearTrgmCache();
                $this->hasTrgm = false;
            }

            // Fall back to regular prefix search
            return ['ids' => $this->search($terms, $model, $limit, $offset), 'total' => null];
        }
    }

    /**
     * Get total count of fuzzy matching records.
     */
    public function fuzzyCount(array $terms, string $model, float $threshold = 0.3): int
    {
        if (!$this->hasTrgmExtension()) {
            // Fallback to regular count
            return $this->count($terms, $model);
        }

        try {
            $subQuery = DB::table($this->table)
                ->select('record_id')
                ->where('model', $model)
                ->where(function ($q) use ($terms, $threshold) {
                    foreach ($terms as $term) {
                        $q->orWhereRaw('similarity(token, ?) > ?', [$term, $threshold]);
                    }
                })
                ->groupBy('record_id');

            return DB::table(DB::raw("({$subQuery->toSql()}) as search_results"))
                ->mergeBindings($subQuery)
                ->count();
        } catch (\Exception $e) {
            // If similarity() function fails, the extension was likely disabled
            // Clear the stale cache and fall back to regular count
            if (str_contains($e->getMessage(), 'similarity')) {
                $this->clearTrgmCache();
                $this->hasTrgm = false;
            }

            // Fall back to regular count
            return $this->count($terms, $model);
        }
    }

    /**
     * Check if PostgreSQL has the pg_trgm extension installed.
     * Result is cached indefinitely to avoid repeated database queries.
     */
    protected function hasTrgmExtension(): bool
    {
        if ($this->hasTrgm !== null) {
            return $this->hasTrgm;
        }

        // Use Laravel's cache to persist the result across requests
        // Cache key includes connection name to support multiple databases
        $connectionName = DB::connection()->getName();
        $cacheKey = "lightsearch_pgtrgm_{$connectionName}";

        $this->hasTrgm = \Illuminate\Support\Facades\Cache::rememberForever($cacheKey, function () {
            try {
                $result = DB::selectOne("SELECT EXISTS(SELECT 1 FROM pg_extension WHERE extname = 'pg_trgm') as exists");

                return (bool) $result->exists;
            } catch (\Exception $e) {
                return false;
            }
        });

        return $this->hasTrgm;
    }

    /**
     * Clear the cached pg_trgm extension check.
     * Useful when the extension is installed/uninstalled at runtime.
     */
    protected function clearTrgmCache(): void
    {
        $connectionName = DB::connection()->getName();
        $cacheKey = "lightsearch_pgtrgm_{$connectionName}";
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
    }
}
