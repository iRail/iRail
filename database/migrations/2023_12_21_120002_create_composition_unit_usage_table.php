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
        Schema::create('CompositionUnitUsage', function (Blueprint $table) {
            $table->integer('uicCode')->nullable(false)->comment('The uic code of the unit');
            $table->date('date')->nullable(false)->nullable()->comment('The date on which the unit was used in the specified journey');
            $table->string('journeyType', 16)->nullable(false)->comment('The journey type, for example "IC" in IC 513');
            $table->integer('journeyNumber')->comment('The journey number, for example "513" in IC 513');

            $table->string('fromStationId',
                9)->nullable(false)->comment('The id of the station from which this unit has the given position in the composition. Typically the first station of the journey, but might differ in case of trains which split.');
            $table->string('toStationId',
                9)->nullable(false)->comment('The id of the station to which this unit has the given position in the composition. Typically the last station of the journey, but might differ in case of trains which split.');
            $table->tinyInteger('position')->nullable(false)->comment('The position of this unit in the composition on the specified segment of the specified journey');

            $table->primary(['uicCode', 'date']);
            $table->unique(['journeyType', 'journeyNumber', 'date', 'position', 'fromStationId'], 'uniquePositionInTrainOnDayAndSegment1');
            $table->unique(['journeyType', 'journeyNumber', 'date', 'position', 'toStationId'], 'uniquePositionInTrainOnDayAndSegment2');
            $table->foreign('uicCode')
                ->references('uicCode')
                ->on('CompositionUnit')
                ->onDelete('cascade');
            $table->index(['journeyType', 'journeyNumber', 'date'], 'DatedVehicleJourney');
            $table->foreign(['journeyType', 'journeyNumber', 'date'])
                ->references(['journeyType', 'journeyNumber', 'date'])
                ->on('CompositionHistory')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('CompositionUnitUsage');
    }
};
