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
        Schema::table('composition_unit_usage', function (Blueprint $table) {
            $table->dropPrimary();
            // Keep the same primary key, but change the order of the fields. This way the primary key can be used in queries where only
            // historic_composition_id is present, for example when looking up all units in a certain composition or deleting a composition
            $table->primary(['historic_composition_id', 'uic_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('composition_unit_usage', function (Blueprint $table) {
            $table->dropPrimary();
            $table->primary(['uic_code', 'historic_composition_id']);
        });
    }
};
