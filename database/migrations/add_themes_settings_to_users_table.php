<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('team_user', function (Blueprint $table) {
            $table->string('theme')->nullable()->default('default');
            $table->string('theme_color')->nullable();
        });
    }

    public function down()
    {
        Schema::table('team_user', function (Blueprint $table) {
            $table->dropColumn(['theme', 'theme_color']);
        });
    }
};
