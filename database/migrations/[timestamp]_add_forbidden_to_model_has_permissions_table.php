<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
  {
    Schema::table('model_has_permissions', function (Blueprint $table) {
      $table->boolean('forbidden')->default(false)->after('model_id');
    });
  }

  public function down()
  {
    Schema::table('model_has_permissions', function (Blueprint $table) {
      $table->dropColumn('forbidden');
    });
  }
};
