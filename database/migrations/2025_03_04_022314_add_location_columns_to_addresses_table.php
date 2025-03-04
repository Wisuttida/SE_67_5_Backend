<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('province')->nullable();
            $table->string('amphoe')->nullable();
            $table->string('tambon')->nullable();
            $table->string('zipcode')->nullable();
        });
    }

    public function down()
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['province', 'amphoe', 'tambon', 'zipcode']);
        });
    }

};
