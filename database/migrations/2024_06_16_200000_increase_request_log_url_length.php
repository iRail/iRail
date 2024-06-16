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
            $table->string('url',1024)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outgoing_request_log', function (Blueprint $table) {
            $table->string('url',255)->change();
        });
    }
};
