<?php

namespace Ktr\LightSearch\Core\Engines;

use Illuminate\Support\Facades\DB;

abstract class DatabaseEngine
{
    protected string $table;

    /**
     * DatabaseEngine constructor.
     *
     * @param  string  $table  The table name to operate on.
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Insert a token posting.
     * Note: Duplicate tokens for the same record are allowed (for field weighting).
     */
    public function insert(string $token, string|int $recordId, string $model): void
    {
        $now = now();

        DB::table($this->table)->insert([
            'token' => $token,
            'record_id' => $recordId,
            'model' => $model,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Remove all postings for a record.
     */
    public function deleteByRecord(string|int $recordId, string $model): void
    {
        DB::table($this->table)
            ->where('record_id', $recordId)
            ->where('model', $model)
            ->delete();
    }

    /**
     * Delete all postings for a specific model.
     */
    public function deleteByModel(string $model): void
    {
        DB::table($this->table)
            ->where('model', $model)
            ->delete();
    }

    /**
     * Search postings by terms.
     * Must be implemented by database-specific engines.
     */
    abstract public function search(array $terms, string $model, int $limit = 10, int $offset = 0): array;

    /**
     * Get total count of matching records.
     * Must be implemented by database-specific engines.
     */
    abstract public function count(array $terms, string $model): int;

    /**
     * Check if this engine supports fuzzy search.
     */
    public function supportsFuzzySearch(): bool
    {
        return false;
    }

    /**
     * Perform fuzzy search with similarity matching.
     * Falls back to regular search if fuzzy search is not supported.
     *
     * @param  float  $threshold  Similarity threshold (0.0 to 1.0)
     */
    public function fuzzySearch(array $terms, string $model, float $threshold = 0.3, int $limit = 10, int $offset = 0): array
    {
        // Default implementation: fallback to regular prefix search
        return $this->search($terms, $model, $limit, $offset);
    }

    /**
     * Get total count of fuzzy matching records.
     * Falls back to regular count if fuzzy search is not supported.
     */
    public function fuzzyCount(array $terms, string $model, float $threshold = 0.3): int
    {
        // Default implementation: fallback to regular count
        return $this->count($terms, $model);
    }
}
