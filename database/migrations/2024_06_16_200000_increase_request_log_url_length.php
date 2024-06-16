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
        Schema::table('outgoing_request_log', function (Blueprint $table) {
            // Keep the other existing fields so all databases will accept this change, otherwise eg pgsql will complain about column being null
            $table->string('url', 1024)
                ->nullable(false)
                ->comment('The URL for the outgoing request')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outgoing_request_log', function (Blueprint $table) {
            $table->string('url', 255)
                ->nullable(false)
                ->comment('The URL for the outgoing request')
                ->change();
        });
    }
};
