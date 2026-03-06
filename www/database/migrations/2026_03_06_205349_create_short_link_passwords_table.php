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
        Schema::create('short_link_passwords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_link_id')
                ->constrained('short_links')
                ->cascadeOnDelete();
            $table->string('password', 128);
            $table->string('target_url', 2048)->default('');
            $table->string('extra_query', 2048)->default('');
            $table->string('extra_path', 512)->default('');
            $table->unsignedInteger('hit_count')->default(0);
            $table->unsignedInteger('max_uses')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('short_link_passwords');
    }
};
