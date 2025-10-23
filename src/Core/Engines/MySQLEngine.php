<?php

namespace Ktr\LightSearch\Core\Engines;

use Illuminate\Support\Facades\DB;

class MySQLEngine extends DatabaseEngine
{
    /**
     * Search using MySQL's native MATCH...AGAINST for better performance.
     * Falls back to LIKE if no FULLTEXT index exists.
     */
    public function search(array $terms, string $model, int $limit = 10, int $offset = 0): array
    {
        $query = DB::table($this->table)
            ->select('record_id', DB::raw('COUNT(*) as occurrences'))
            ->where('model', $model)
            ->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhere('token', 'like', $term.'%');
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
                    $q->orWhere('token', 'like', $term.'%');
                }
            })
            ->groupBy('record_id');

        return DB::table(DB::raw("({$subQuery->toSql()}) as search_results"))
            ->mergeBindings($subQuery)
            ->count();
    }
}
