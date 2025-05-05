<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_service_reviews', function (Blueprint $table) {
            // Add the hr_evaluation_score column as a separate column
            $table->integer('hr_evaluation_score')->default(0)->comment('تقييم HR');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_service_reviews', function (Blueprint $table) {
            $table->dropColumn('hr_evaluation_score');
        });
    }
};
