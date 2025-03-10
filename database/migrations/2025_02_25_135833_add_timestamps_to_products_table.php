<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // เปลี่ยน shops_shop_id ให้เป็น nullable
            $table->integer('shops_shop_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // เฉพาะการ revert การเปลี่ยนแปลง shops_shop_id เท่านั้น
            $table->integer('shops_shop_id')->nullable(false)->change();
            // ไม่ต้องลบ created_at และ updated_at เพราะไม่ได้เพิ่มเข้ามาใน migration นี้
        });
    }
};
