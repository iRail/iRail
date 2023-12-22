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
            $table->string('journeyType', 16)->nullable(false)->comment('The journey type, for example "IC" in IC 513');
            $table->integer('journeyNumber')->comment('The journey number, for example "513" in IC 513');
            $table->date('date')->nullable()->comment('The date on which this journey ran');
            $table->string('primaryMaterialType', 16)->comment('The type of the majority of the used vehicles, such as I10, M6 or AM96');
            $table->tinyInteger('passengerUnitCount')->comment('The number of units in which passengers can be seated'); // 0 - 255
            $table->timestamp('createdAt')->comment('The time when this composition was recorded');
            $table->primary(['journeyType', 'journeyNumber', 'date']);
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
