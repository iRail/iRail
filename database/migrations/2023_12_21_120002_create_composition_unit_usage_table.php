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
            $table->bigInteger('historicCompositionId')->unsigned()->nullable(false)->comment('Reference to an entry in the CompositionHistory table, where the journey is described.');
            $table->tinyInteger('position')->nullable(false)->comment('The position of this unit in the composition on the specified segment of the specified journey');

            $table->primary(['uicCode', 'historicCompositionId']);
            $table->foreign('uicCode','rolling_stock_reference')
                ->references('uicCode')
                ->on('CompositionUnit')
                ->onDelete('cascade');
            $table->foreign('historicCompositionId','history_reference')
                ->references('id')
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
