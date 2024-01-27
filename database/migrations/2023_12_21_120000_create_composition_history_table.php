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
        Schema::create('CompositionHistory', function (Blueprint $table) {
            $table->id();
            $table->string('journeyType', 16)->nullable(false)->comment('The journey type, for example "IC" in IC 513');
            $table->integer('journeyNumber')->comment('The journey number, for example "513" in IC 513');
            $table->date('journeyStartDate')->nullable()->comment('The date on which this journey ran');

            $table->string('fromStationId',
                9)->nullable(false)->comment('The id of the station from which this unit has the given position in the composition. Typically the first station of the journey, but might differ in case of trains which split.');
            $table->string('toStationId',
                9)->nullable(false)->comment('The id of the station to which this unit has the given position in the composition. Typically the last station of the journey, but might differ in case of trains which split.');

            $table->string('primaryMaterialType', 16)->comment('The type of the majority of the used vehicles, such as I10, M6 or AM96');
            $table->tinyInteger('passengerUnitCount')->comment('The number of units in which passengers can be seated'); // 0 - 255
            $table->timestamp('createdAt')->useCurrent()->comment('The time when this composition was recorded');
            $table->unique(['journeyType', 'journeyNumber', 'journeyStartDate', 'fromStationId', 'toStationId'],'UniqueJourneyIdentifier');
            $table->index(['journeyType', 'journeyNumber', 'journeyStartDate'], 'DatedVehicleJourney');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('CompositionHistory');
    }
};
