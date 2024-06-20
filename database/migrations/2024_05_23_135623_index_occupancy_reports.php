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
        Schema::table('occupancy_reports', function (Blueprint $table) {
            $table->index(['source', 'journey_start_date', 'stop_id'], 'IX_occupancy_reports_source_date_stop');
            $table->index(['source', 'journey_start_date', 'vehicle_id'], 'IX_occupancy_reports_source_date_vehicle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('occupancy_reports', function (Blueprint $table) {
            $table->dropIndex('IX_occupancy_reports_source_date_stop');
            $table->dropIndex('IX_occupancy_reports_source_date_vehicle');
        });
    }
};
