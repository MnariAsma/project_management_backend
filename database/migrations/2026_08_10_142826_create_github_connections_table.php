<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('github_connections', function (Blueprint $table) {
            $table->UUID('id')->primary();
            $table->foreignUuid('project_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignUuid('user_id')
                ->constrained()
                ->restrictOnDelete();

            // $table->unsignedBigInteger('github_user_id')->nullable();
            // $table->string('github_username')->nullable();
            $table->text('access_token'); 
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->unique(['project_id', 'user_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('github_connections');
    }
};
