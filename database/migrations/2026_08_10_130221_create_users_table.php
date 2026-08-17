<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role')->default('manager');
            $table->string('avatar_url')->nullable();
            $table->string('github_id')->unique();
            $table->string('github_username')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
