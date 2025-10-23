<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Model Field Weights
    |--------------------------------------------------------------------------
    |
    | Customize the importance ('weight') of specific fields for each model.
    | The key is your model class (e.g., \App\Models\Post::class).
    | The value is an array of field names with their search relevance weight (1–10).
    | Default weight is 1 if not specified.
    |
    | Higher weights make fields more influential in search results.
    | For example, giving the 'title' field a weight of 3 makes matches on
    | the title field three times as significant as those with default weight.
    |
    | Example:
    | 'model_field_weights' => [
    |     \App\Models\Post::class => [
    |         'title' => 3,
    |         'excerpt' => 2,
    |         'content' => 1,
    |     ],
    |     \App\Models\Product::class => [
    |         'name' => 5,
    |         'sku' => 3,
    |         'description' => 1,
    |     ],
    | ],
    |
    */
    'model_field_weights' => [
        // Add your model field weights here
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimum Token Length
    |--------------------------------------------------------------------------
    |
    | The minimum length (in characters) for a token to be indexed.
    | Tokens shorter than this value will be ignored during indexing.
    | This helps reduce index size and improves search performance.
    |
    | Default: 2
    | Recommended: 2-3 characters
    |
    */
    'min_token_length' => env('LIGHTSEARCH_MIN_TOKEN_LENGTH', 2),

    /*
    |--------------------------------------------------------------------------
    | Stopwords
    |--------------------------------------------------------------------------
    |
    | Common words that should be excluded from the search index.
    | These are typically very common words that don't add value to searches
    | (e.g., "the", "and", "is").
    |
    | Set to an empty array [] to disable stopword filtering.
    | Set to null to use the default English stopwords list.
    |
    */
    'stopwords' => [
        'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from',
        'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the',
        'to', 'was', 'will', 'with', 'this', 'but', 'they', 'have',
        'had', 'what', 'when', 'where', 'who', 'which', 'why', 'how',
        'or', 'not', 'been', 'were', 'can', 'could', 'would', 'should',
        'may', 'might', 'must', 'shall', 'do', 'does', 'did', 'done',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection to use for the search index table.
    | Set to null to use your application's default database connection.
    |
    */
    'connection' => env('LIGHTSEARCH_DB_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Table Name
    |--------------------------------------------------------------------------
    |
    | The name of the table used to store the search index.
    | Only change this if you have a naming conflict.
    |
    */
    'table' => env('LIGHTSEARCH_TABLE', 'lightsearch_index'),
];
