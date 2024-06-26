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
        Schema::create('occupancy_reports', function (Blueprint $table) {
            $table->comment('A table containing all individual occupancy reports. There may be multiple reports for the same departure');
            $table->id();
            $table->string('vehicle_id')->nullable(false)->comment('The id of the vehicle for which occupancy was reported');
            $table->integer('stop_id')->nullable(false)->comment('The id of the stop at which this occupancy was reported');
            $table->date('journey_start_date')->nullable(false)->comment('The date on which the vehicle started its journey');
            $table->string('source')->nullable(false)->comment('The source, such as Spitsgids or NMBS');
            $table->integer('occupancy')->nullable(false)->comment('The reported occupancy level');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('occupancy_reports');
    }
};
