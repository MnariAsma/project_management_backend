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

            $table->string('source_type');
            $table->string('source_identifier'); 

            
            $table->string('language')->nullable();
            $table->string('content_hash')->nullable(); 
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
