<?php

namespace Ktr\LightSearch;

use Illuminate\Support\Collection;
use Ktr\LightSearch\Core\EngineFactory;
use Ktr\LightSearch\Core\Engines\DatabaseEngine;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;

/**
 * Laravel Scout Engine powered by a lightweight database index.
 */
class LightSearchEngine extends Engine
{
    protected DatabaseEngine $engine;

    protected array $modelFieldWeights;

    protected array $stopwords;

    protected int $minTokenLength;

    protected string $table = 'lightsearch_index';

    public function __construct(array $config)
    {
        $this->modelFieldWeights = $config['model_field_weights'] ?? [];
        $this->stopwords = $config['stopwords'] ?? $this->getDefaultStopwords();
        $this->minTokenLength = $config['min_token_length'] ?? 2;
        $this->engine = EngineFactory::create($this->table);
    }

    /**
     * Get default English stopwords.
     */
    protected function getDefaultStopwords(): array
    {
        return [
            'a', 'an', 'and', 'are', 'as', 'at', 'be', 'but', 'by', 'for',
            'from', 'had', 'has', 'have', 'he', 'how', 'in', 'is', 'it',
            'its', 'of', 'on', 'that', 'the', 'they', 'this', 'to', 'was',
            'what', 'when', 'where', 'which', 'who', 'why', 'will', 'with',
        ];
    }

    /**
     * Normalize text, strip non-word chars, split into tokens.
     */
    protected function tokenize(string $text): array
    {
        $normalized = strtolower($text);
        $normalized = preg_replace('/[^\p{L}0-9\s]+/u', ' ', $normalized);
        $tokens = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        // Filter out stopwords and short tokens
        return array_filter($tokens, function ($token) {
            return strlen($token) >= $this->minTokenLength
                && !in_array($token, $this->stopwords);
        });
    }

    /**
     * Recursively extract unique tokens from the input data structure.
     */
    protected function extractTokens(array $data): array
    {
        $tokens = [];
        foreach ($data as $value) {
            $tokens = array_merge($tokens, is_array($value)
                ? $this->extractTokens($value)
                : $this->tokenize((string) $value)
            );
        }

        return array_unique($tokens);
    }

    /**
     * Update the search index for the given set of models.
     *
     * @param  iterable  $models
     */
    public function update($models): void
    {
        foreach ($models as $record) {
            $id = $record->getKey();
            $model = get_class($record);

            $this->engine->deleteByRecord($id, $model);

            $weights = $this->modelFieldWeights[$model] ?? [];
            $tokens = $this->getRecordWeightedTokens($record->toSearchableArray(), $weights);

            foreach ($tokens as $token) {
                $this->engine->insert($token, $id, $model);
            }
        }
    }

    /**
     * Delete the search index entries for the given models.
     *
     * @param  iterable  $models
     */
    public function delete($models): void
    {
        foreach ($models as $record) {
            $this->engine->deleteByRecord($record->getKey(), get_class($record));
        }
    }

    /**
     * Perform a search and return matched ids and hit count.
     */
    public function search(Builder $builder): array
    {
        $limit = $builder->limit ?: 10;

        return $this->performSearch($builder, $limit, 0);
    }

    /**
     * Paginate search results.
     *
     * @param  int  $perPage
     * @param  int  $page
     */
    public function paginate(Builder $builder, $perPage, $page): array
    {
        $offset = ($page - 1) * $perPage;

        return $this->performSearch($builder, $perPage, $offset);
    }

    /**
     * Execute the search with the given parameters.
     */
    private function performSearch(Builder $builder, int $limit, int $offset): array
    {
        $terms = $this->tokenize($builder->query ?? '');
        if (empty($terms)) {
            return ['ids' => [], 'hits' => 0];
        }

        $model = get_class($builder->model);
        $threshold = $builder->options['fuzzy_threshold'] ?? 0.3;

        // Automatically use fuzzy search if the engine supports it
        // PostgreSQL fuzzy search returns both IDs and total count in one query
        if ($this->engine->supportsFuzzySearch()) {
            $result = $this->engine->fuzzySearch($terms, $model, $threshold, $limit, $offset);
            $ids = $result['ids'] ?? [];
            $total = $result['total'] ?? $this->engine->fuzzyCount($terms, $model, $threshold);
        } else {
            $ids = $this->engine->search($terms, $model, $limit, $offset);
            $total = $this->engine->count($terms, $model);
        }

        return ['ids' => $ids, 'hits' => $total];
    }

    /**
     * Map raw result ids to a Laravel Collection.
     *
     * @param  array  $results
     */
    public function mapIds($results): Collection
    {
        return collect($results['ids']);
    }

    /**
     * Map search result IDs into their corresponding models, respecting result order.
     *
     * @param  array  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function map(Builder $builder, $results, $model): Collection
    {
        $ids = $results['ids'] ?? [];
        if (empty($ids)) {
            return collect();
        }

        $keyName = $model->getKeyName();
        $models = $model->whereIn($keyName, $ids)->get();

        // Sort by original search result order
        return $models->sortBy(function ($m) use ($ids, $keyName) {
            return array_search($m->$keyName, $ids);
        })->values();
    }

    /**
     * Get the number of hits from results.
     *
     * @param  array  $results
     */
    public function getTotalCount($results): int
    {
        return $results['hits'] ?? 0;
    }

    /**
     * Flush the search index for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function flush($model): void
    {
        $this->engine->deleteByModel(get_class($model));
    }

    /**
     * No-op: Index is handled by shared DB table.
     */
    public function createIndex($name, array $options = []): void
    {
        // Not required for this engine.
    }

    /**
     * Delete all tokens for the given index name (model).
     *
     * @param  string  $name
     */
    public function deleteIndex($name): void
    {
        $this->engine->deleteByModel($name);
    }

    /**
     * Lazily map results into model instances (same as map).
     */
    public function lazyMap(Builder $builder, $results, $model): Collection
    {
        return $this->map($builder, $results, $model);
    }

    /**
     * Check if the underlying database engine supports fuzzy search.
     */
    public function supportsFuzzySearch(): bool
    {
        return $this->engine->supportsFuzzySearch();
    }

    /**
     * Helper: Build a weighted flat token array for a model's search fields.
     */
    private function getRecordWeightedTokens(array $data, array $weights): array
    {
        $tokens = [];
        foreach ($data as $field => $value) {
            $rawTokens = is_array($value) ? $this->extractTokens($value) : $this->tokenize((string) $value);
            $weight = $weights[$field] ?? 1;
            if ($weight <= 0) {
                continue;
            }
            for ($i = 0; $i < $weight; $i++) {
                $tokens = array_merge($tokens, $rawTokens);
            }
        }

        return $tokens;
    }
}
