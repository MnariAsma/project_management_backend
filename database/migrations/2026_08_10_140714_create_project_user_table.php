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
        Schema::create('project_user', function (Blueprint $table) {
            $table->UUID('id')->primary();
            $table->foreignUuid('project_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignUuid('user_id')
                ->constrained()
                ->restrictOnDelete();
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
        Schema::dropIfExists('project_user');
    }
};
