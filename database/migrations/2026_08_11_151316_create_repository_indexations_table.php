<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('repository_indexations', function (Blueprint $table) {

            $table->uuid('id')->primary();
            $table->foreignUuid('repository_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('status')->default('pending'); // pending / running / completed / failed
            $table->string('trigger_type')->default('initial'); // initial / incremental
            $table->unsignedInteger('sources_discovered')->default(0);
            $table->unsignedInteger('sources_processed')->default(0);
            $table->unsignedInteger('chunks_created')->default(0);
            $table->text('error_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->unique(['repository_id', 'started_at']);

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('repository_indexations');
    }
};
