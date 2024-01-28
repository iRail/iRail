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
        Schema::create('composition_unit_usage', function (Blueprint $table) {
            $table->bigInteger('uic_code')->nullable(false)->comment('The uic code of the unit');
            $table->bigInteger('historic_composition_id')->unsigned()->nullable(false)->comment('Reference to an entry in the CompositionHistory table, where the journey is described.');
            $table->tinyInteger('position')->nullable(false)->comment('The position of this unit in the composition on the specified segment of the specified journey');

            $table->primary(['uic_code', 'historic_composition_id']);
            $table->foreign('uic_code', 'rolling_stock_reference')
                ->references('uic_code')
                ->on('composition_unit')
                ->onDelete('cascade');
            $table->foreign('historic_composition_id', 'history_reference')
                ->references('id')
                ->on('composition_history')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('composition_unit_usage');
    }
};
