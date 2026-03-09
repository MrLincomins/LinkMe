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
        Schema::table('short_link_passwords', function (Blueprint $table) {
            $table->string('extra_query')->nullable()->default(null)->change();
            $table->string('extra_path')->nullable()->default(null)->change();
            $table->string('target_url')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
