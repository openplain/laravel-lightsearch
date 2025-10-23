# Laravel LightSearch

[![Latest Version on Packagist](https://img.shields.io/packagist/v/openplain/laravel-lightsearch.svg?style=flat-square)](https://packagist.org/packages/openplain/laravel-lightsearch)
[![Total Downloads](https://img.shields.io/packagist/dt/openplain/laravel-lightsearch.svg?style=flat-square)](https://packagist.org/packages/openplain/laravel-lightsearch)

Fast, database-backed search for Laravel Scout. No external services, no monthly fees, no infrastructure complexity.

## Why This Package?

We created LightSearch because most Laravel applications don't need the complexity of external search services like Algolia or Meilisearch. For small to medium datasets, your existing database is perfectly capable of delivering fast, relevant search results.

**Our Goal:** Make search simple enough for MVPs, powerful enough for production, and cost-effective for bootstrapped projects.

**Real-world performance**: 2.95ms average search time on 26,000+ records ([view benchmark](BENCHMARK_RESULTS.md))

### Built on Proven Technology

Rather than reinventing the wheel, LightSearch leverages:

- **Laravel Scout** - Familiar API that works across all search drivers
- **Inverted Index Pattern** - Classic search architecture used by Meilisearch, Algolia, and Typesense
- **Database-Specific Optimizations** - PostgreSQL `pg_trgm` for fuzzy search, MySQL/SQLite optimized queries

## Features

- 🚀 **Database-Backed** - Uses your existing MySQL, PostgreSQL, or SQLite database
- 💰 **Zero Cost** - No external services, no monthly fees
- ⚡ **Fast Setup** - From install to working search in under 5 minutes
- 🎯 **Field Weighting** - Boost relevance of important fields (title > content)
- 🔍 **Fuzzy Search** - Typo-tolerant search on PostgreSQL with `pg_trgm`
- 🌍 **Unicode Support** - Perfect handling of special characters (ø, á, ñ, etc.)
- 🔧 **Customizable** - Stopwords, token length, field weights all configurable
- 📦 **Scout Compatible** - Drop-in replacement for Scout drivers
- 🔑 **UUID Support** - Works with string primary keys, not just integers

## When to Use LightSearch

**Perfect for:**
- Small to medium datasets (1K-50K records)
- Budget-constrained projects
- MVPs and prototypes
- Applications already using Laravel Scout
- Simple hosting environments (no Docker required)

**Not Ideal for:**
- Large datasets (>100K records) → use Meilisearch, Algolia, or Typesense
- Complex multi-language search → use Algolia or Typesense
- Real-time autocomplete with sub-10ms response times
- Advanced features like faceted search, geo-search, or AI-powered ranking

## Requirements

- PHP 8.2 or higher
- Laravel 11 or 12
- Laravel Scout 10 or higher

## Installation

Install the package via Composer:

```bash
composer require openplain/laravel-lightsearch
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag=lightsearch-migrations
php artisan migrate
```

Optionally publish the configuration file:

```bash
php artisan vendor:publish --tag=lightsearch-config
```

## Quick Start

### 1. Configure Scout Driver

Set LightSearch as your Scout driver in `.env`:

```env
SCOUT_DRIVER=lightsearch
```

### 2. Make Your Model Searchable

Add the `Searchable` trait and define searchable fields:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Post extends Model
{
    use Searchable;

    protected $fillable = ['title', 'excerpt', 'content'];

    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
        ];
    }
}
```

### 3. Import Existing Data

Import your existing records into the search index:

```bash
php artisan scout:import "App\Models\Post"
```

For large datasets, use chunking:

```bash
php artisan scout:import "App\Models\Post" --chunk=500
```

### 4. Start Searching

Use Scout's familiar API to search your models:

```php
// Basic search
$posts = Post::search('laravel')->get();

// Paginated results
$posts = Post::search('laravel')->paginate(15);

// With query constraints
$posts = Post::search('laravel')
    ->where('published', true)
    ->orderBy('created_at', 'desc')
    ->get();

// Fuzzy search (PostgreSQL only)
$posts = Post::search('laravle')->fuzzy(0.3)->get();
```

## Configuration

LightSearch is configured via `config/lightsearch.php`. All settings are optional with sensible defaults.

### Field Weights

Boost relevance of specific fields by giving them higher weights. Fields with higher weights appear multiple times in the index, making matches more significant.

```php
'model_field_weights' => [
    \App\Models\Post::class => [
        'title' => 3,    // Title matches ranked 3x higher
        'excerpt' => 2,  // Excerpt matches ranked 2x higher
        'content' => 1,  // Content has default weight
    ],
],
```

**How it works**: A post with "Laravel" in the title gets 3 index entries for "Laravel", while the same word in content gets only 1. This makes title matches score higher in results.

### Stopwords

Common words to exclude from the search index. This reduces index size and improves performance.

```php
'stopwords' => [
    'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from',
    'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the',
    'to', 'was', 'will', 'with', // ... add your own
],
```

Set to `[]` to disable stopword filtering.

### Minimum Token Length

Minimum character length for a token to be indexed:

```php
'min_token_length' => 2,  // Default: 2 characters
```

**Note**: Setting this too high (e.g., 4) prevents searching for short terms like "API" or "PHP".

### Database Connection

Override the default database connection:

```php
'connection' => env('LIGHTSEARCH_DB_CONNECTION', null),
```

Useful if your search index should live on a different database than your application data.

## How It Works

LightSearch uses an **inverted index** pattern (like Meilisearch, Algolia, and Typesense) implemented in your database:

1. **Indexing**: Text is tokenized, normalized, and stored with field weights
2. **Search**: Queries use prefix matching (or fuzzy matching on PostgreSQL)
3. **Ranking**: Results ordered by occurrence count - more matches rank higher

Field weighting example: with `title` weight 3 and `content` weight 1, a match in the title counts 3× more than content.

## Advanced Features

### Fuzzy Search (PostgreSQL)

PostgreSQL users get **automatic typo-tolerant search** when the `pg_trgm` extension is enabled. It handles typos, missing accents, and character substitutions.

**Enable pg_trgm** (one-time setup):

```sql
CREATE EXTENSION pg_trgm;
```

**That's it!** Fuzzy search is now automatic:

```php
// Automatically finds "Laravel" even with typo
Post::search('laravle')->get();

// Automatically finds "Tórshavn" without accents
Address::search('Torshavn')->get();
```

**Adjust threshold** (optional):

```php
Post::search('laravle')->fuzzy(0.5)->get();  // Stricter (default: 0.3)
Post::search('laravle')->fuzzy(0.2)->get();  // Looser
```

See [FUZZY_SEARCH_RESULTS.md](FUZZY_SEARCH_RESULTS.md) for benchmarks.


## Performance

Benchmarked with 26,191 addresses featuring special characters (ø, á, ð):

- **Average search time**: 2.95ms
- **Dataset**: 26,191 records
- **Fuzzy search**: ~110ms (PostgreSQL with pg_trgm)

[Full benchmark report →](BENCHMARK_RESULTS.md)

## Comparison

| Solution | Setup | Speed | Cost | Typo Tolerance |
|----------|-------|-------|------|----------------|
| **LightSearch** | **5 min** | **~3ms** | **$0** | **Yes (PostgreSQL)** |
| Meilisearch | 30 min | ~1-5ms | $0-$$ | Yes |
| Algolia | 15 min | ~1-3ms | $$$ | Yes |
| Typesense | 30 min | ~1-5ms | $0-$ | Yes |

## Limitations

Be aware of these limitations when choosing LightSearch:

- **Prefix matching only** - "search" finds "searching" but not "research"
- **No typo tolerance on MySQL/SQLite** - Only PostgreSQL with `pg_trgm` supports fuzzy search
- **Index size growth** - ~4 index entries per record with default field weights
- **Not optimized for autocomplete** - Better solutions exist for real-time suggestions
- **No faceted search** - Can't filter by category/price/etc. in search results
- **Single-language only** - No multi-language stemming or morphology

## Maintenance

```bash
# Re-index after bulk updates
php artisan scout:import "App\Models\Post"

# Clear all index entries
php artisan scout:flush "App\Models\Post"
```

Models are automatically indexed on create/update/delete.

## Testing

```bash
composer test
composer pint
```

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover a security vulnerability, please email security@openplain.dev. All security vulnerabilities will be promptly addressed.

**Please do not** open public issues for security vulnerabilities.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

Built with ❤️ by [Openplain](https://openplain.dev)
