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
        Schema::create('link_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_link_id')
                ->constrained('short_links')
                ->cascadeOnDelete();
            $table->foreignId('short_link_password_id')
                ->nullable()
                ->constrained('short_link_passwords')
                ->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('referer', 2048)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['short_link_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('link_visits');
    }
};
