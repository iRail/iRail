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
        Schema::table('request_log', function (Blueprint $table) {
            // Add a sorted index allowing quick queries on created_at
            $table->index('created_at','ix_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_log', function (Blueprint $table) {
            $table->dropIndex('ix_created_at');
        });
    }
};
