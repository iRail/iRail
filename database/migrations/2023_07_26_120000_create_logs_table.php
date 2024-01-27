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
        Schema::create('RequestLog', function (Blueprint $table) {
            $table->id();
            $table->string('queryType', 64)->nullable(false)->comment('The query type, typically the endpoint type of the request');
            $table->string('query', 1024)->comment('The query, as a json string');
            $table->string('result', 4096)->nullable()->comment('A description of the query result, as a json string');
            $table->string('userAgent', 512)->comment('The user agent submitted along with this request');
            $table->timestamp('createdAt')->useCurrent()->comment('The time when this query was recorded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('RequestLog');
    }
};
