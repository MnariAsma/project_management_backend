<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE code_chunks SET embedding = NULL, embedding_status = 'pending' WHERE embedding IS NOT NULL");
        DB::statement('DROP INDEX IF EXISTS code_chunks_embedding_idx');
        DB::statement('ALTER TABLE code_chunks ALTER COLUMN embedding TYPE extensions.vector(384)');
        DB::statement('CREATE INDEX code_chunks_embedding_idx ON code_chunks USING hnsw (embedding extensions.vector_cosine_ops)');
    }

    public function down(): void
    {
        DB::statement("UPDATE code_chunks SET embedding = NULL, embedding_status = 'pending' WHERE embedding IS NOT NULL");
        DB::statement('DROP INDEX IF EXISTS code_chunks_embedding_idx');
        DB::statement('ALTER TABLE code_chunks ALTER COLUMN embedding TYPE extensions.vector(384)');
        DB::statement('CREATE INDEX code_chunks_embedding_idx ON code_chunks USING hnsw (embedding extensions.vector_cosine_ops)');
    }
};
