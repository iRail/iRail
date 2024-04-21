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
        // Table for debug purposes, logging outgoing requests when an irail API call fails (or always, depending on the configuration)
        Schema::create('outgoing_request_log', function (Blueprint $table) {
            $table->string('irail_request_id')->nullable(false)
                ->comment('A UUID identifying the iRail request which caused this request, in order to track requests which belong together. '
                    . 'Does not have to be unique as multiple requests can be needed in order to respond to one iRail request.');
            $table->string('irail_request_url')->nullable(false)
                ->comment('The URL through which the iRail api was called, and which can be used to recreate possible issues');
            $table->integer('irail_response_code')->nullable(false)
                ->comment('The response code which the iRail API returned while using data from this request');
            $table->integer('irail_request_outgoing_index')->nullable(false)
                ->comment('1-based index which indicates the order in which requests were made, within one iRail API call. If there are 5 requests made to fulfill 1 iRail API call, index will be 1-5.');

            $table->timestamp('timestamp', 3)->nullable(false)->useCurrent()
                ->comment('The timestamp, in milliseconds, when the request was sent');
            $table->string('method')->nullable(false)->comment('The HTTP method which was used on the outgoing request');
            $table->string('url')->nullable(false)->comment('The URL for the outgoing request');
            $table->text('request_body')->nullable()->comment('The request body for the outgoing request');
            $table->integer('response_code')->nullable(false)->comment('The response code for the outgoing request');
            $table->text('response_body')->nullable()->comment('The response body for the outgoing request');
            $table->integer('duration')->nullable(false)->comment('The request duration in milliseconds');
            $table->primary(['irail_request_id', 'irail_request_outgoing_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outgoing_request_log');
    }
};
