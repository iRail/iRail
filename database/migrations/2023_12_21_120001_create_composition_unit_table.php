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
        Schema::create('CompositionUnit', function (Blueprint $table) {
            $table->bigInteger('uicCode')->primary()->comment('For example "508826960330"');
            $table->string('materialTypeName', 16)->nullable(false)->comment('The vehicle type, for example "M7"');
            $table->string('materialSubTypeName', 16)->nullable(false)->comment('The vehicle subtype, for example "M7BUH"');
            $table->integer('materialNumber')->nullable(false)->comment('The vehicle number, for example "72033"');
            $table->binary('hasToilet')->nullable(false)->default(false)->comment('Whether a toilet is available');
            $table->binary('hasPrmToilet')->nullable(false)->default(false)->comment('Whether a toilet accessible for passengers with reduced mobility is available');
            $table->binary('hasAirco')->nullable(false)->default(false)->comment('Whether air conditioning is available');
            $table->binary('hasBikeSection')->nullable(false)->default(false)->comment('Whether a section for bikes is present');
            $table->binary('hasPrmSection')->nullable(false)->default(false)->comment('Whether a section for passengers with reduced mobility is present');
            $table->smallInteger('seatsFirstClass')->nullable(false)->comment('The number of seats in first class');
            $table->smallInteger('seatsSecondClass')->nullable(false)->comment('The number of seats in second class');
            $table->timestamp('createdAt')->nullable(false)->useCurrent()->comment('The time when this unit was first seen');
            $table->timestamp('updatedAt')->nullable(false)->useCurrent()->useCurrentOnUpdate()->comment('The time when this unit was last updated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('CompositionUnit');
    }
};
