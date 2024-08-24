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
            $table->text('result')
                ->nullable()->comment('A description of the query result, as a json string')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_log', function (Blueprint $table) {
            $table->string('result', 4096)
                ->nullable()
                ->comment('A description of the query result, as a json string')
                ->change();
        });
    }
};
