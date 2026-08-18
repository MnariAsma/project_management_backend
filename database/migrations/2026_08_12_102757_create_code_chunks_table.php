<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('code_chunks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('repository_source_id')
                ->constrained()
                ->restrictOnDelete();

            $table->longText('chunk_content'); 
            $table->unsignedInteger('token_count')->nullable();
            $table->unsignedInteger('chunk_index')->default(0);
            $table->json('metadata')->nullable(); // lignes début/fin, etc.

            // $table->string('embedding_model')->nullable();
            $table->string('embedding_status')->default('pending'); // pending / completed / failed

            $table->timestamps();
            $table->softDeletes();

            $table->index('embedding_status');
        });

        DB::statement('ALTER TABLE code_chunks ADD COLUMN embedding extensions.vector(1536)');
        DB::statement('CREATE INDEX code_chunks_embedding_idx ON code_chunks USING hnsw (embedding extensions.vector_cosine_ops)');
    }


    public function down(): void
    {
        Schema::dropIfExists('code_chunks');
    }
};
