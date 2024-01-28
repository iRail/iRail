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
        Schema::create('composition_unit', function (Blueprint $table) {
            $table->bigInteger('uic_code')->primary()->comment('For example "508826960330"');
            $table->string('material_type_name', 16)->nullable(false)->comment('The vehicle type, for example "M7"');
            $table->string('material_subtype_name', 16)->nullable(false)->comment('The vehicle subtype, for example "M7BUH"');
            $table->integer('material_number')->nullable(false)->comment('The vehicle number, for example "72033"');
            $table->binary('has_toilet')->nullable(false)->default(false)->comment('Whether a toilet is available');
            $table->binary('has_prm_toilet')->nullable(false)->default(false)->comment('Whether a toilet accessible for passengers with reduced mobility is available');
            $table->binary('has_airco')->nullable(false)->default(false)->comment('Whether air conditioning is available');
            $table->binary('has_bike_section')->nullable(false)->default(false)->comment('Whether a section for bikes is present');
            $table->binary('has_prm_section')->nullable(false)->default(false)->comment('Whether a section for passengers with reduced mobility is present');
            $table->smallInteger('seats_first_class')->nullable(false)->comment('The number of seats in first class');
            $table->smallInteger('seats_second_class')->nullable(false)->comment('The number of seats in second class');
            $table->timestamp('created_at')->nullable(false)->useCurrent()->comment('The time when this unit was first seen');
            $table->timestamp('updated_at')->nullable(false)->useCurrent()->useCurrentOnUpdate()->comment('The time when this unit was last updated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('composition_unit');
    }
};
