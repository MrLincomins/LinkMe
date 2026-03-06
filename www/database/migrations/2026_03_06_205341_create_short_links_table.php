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
        Schema::create('short_links', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->index();
            $table->foreignId('domain_id')
                ->constrained('short_domains')
                ->restrictOnDelete();
            $table->string('target_url', 2048);
            $table->string('redirect_type', 3)->default('301');
            $table->boolean('forward_query')->default(false);
            $table->string('extra_query', 2048)->default('');
            $table->string('extra_path', 512)->default('');
            $table->unsignedInteger('hit_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['domain_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('short_links');
    }
};
