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
        Schema::table('coordination_reviews', function (Blueprint $table) {
            $table->decimal('total_salary', 10, 2)->default(0)->comment('الإجمالي النهائي للمرتب');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coordination_reviews', function (Blueprint $table) {
            $table->dropColumn('total_salary');
        });
    }
};
