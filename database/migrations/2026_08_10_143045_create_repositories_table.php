<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->UUID('id')->primary();
            $table->foreignUuid('project_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('github_repo_id')->unique();
            //github_connection_id?
            $table->string('name');
            // $table->text('description')->nullable();
            $table->string('owner');
            $table->text('github_url');
            $table->timestamp('github_created_at')->nullable();
            $table->timestamp('github_updated_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['project_id', 'github_repo_id']);

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
