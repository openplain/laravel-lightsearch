<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lightsearch_index', function (Blueprint $table) {
            $table->id();
            $table->string('token', 191);
            $table->string('record_id', 191); // Support UUIDs and string IDs
            $table->string('model', 191);
            $table->timestamps();

            // Composite indexes for efficient querying
            // Note: No unique constraint - duplicates are allowed for field weighting
            $table->index(['model', 'token'], 'lightsearch_model_token_idx');
            $table->index(['model', 'record_id'], 'lightsearch_model_record_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lightsearch_index');
    }
};
