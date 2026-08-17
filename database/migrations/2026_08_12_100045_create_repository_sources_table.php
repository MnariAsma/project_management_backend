<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('repository_sources', function (Blueprint $table) {

            $table->uuid('id')->primary();
            $table->foreignUuid('repository_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('source_type'); // file / commit / pull_request
            $table->string('source_identifier'); // chemin fichier, sha, ou numéro PR

            
            $table->string('language')->nullable(); // php, js... (surtout pertinent pour 'file')
            $table->string('content_hash')->nullable(); // pour détecter les changements (réindexation incrémentale)
            $table->timestamp('last_indexed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['repository_id', 'source_type', 'source_identifier']);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('repository_sources');
    }
};
