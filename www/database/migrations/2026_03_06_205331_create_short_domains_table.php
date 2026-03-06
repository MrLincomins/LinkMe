<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('short_domains', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->string('target_url', 2048)->nullable();
            $table->string('redirect_type', 3)->default('301');
            $table->boolean('forward_query')->default(false);
            $table->string('extra_query', 2048)->default('');
            $table->string('extra_path', 512)->default('');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('short_domains');
    }
};
