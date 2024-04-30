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
        Schema::create('composition_history', function (Blueprint $table) {
            $table->id();
            $table->string('journey_type', 16)->nullable(false)->comment('The journey type, for example "IC" in IC 513');
            $table->integer('journey_number')->comment('The journey number, for example "513" in IC 513');
            $table->date('journey_start_date')->nullable()->comment('The date on which this journey ran');

            $table->string(
                'from_station_id',
                9
            )->nullable(false)->comment('The id of the station from which this unit has the given position in the composition. Typically the first station of the journey, but might differ in case of trains which split.');
            $table->string(
                'to_station_id',
                9
            )->nullable(false)->comment('The id of the station to which this unit has the given position in the composition. Typically the last station of the journey, but might differ in case of trains which split.');

            $table->string('primary_material_type', 16)->comment('The type of the majority of the used vehicles, such as I10, M6 or AM96');
            $table->tinyInteger('passenger_unit_count')->comment('The number of units in which passengers can be seated'); // 0 - 255
            $table->timestamp('created_at')->useCurrent()->comment('The time when this composition was recorded');
            $table->unique(['journey_type', 'journey_number', 'journey_start_date', 'from_station_id', 'to_station_id'], 'unique_journey_identifier');
            $table->index(['journey_type', 'journey_number', 'journey_start_date'], 'dated_vehicle_journey');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('composition_history');
    }
};
