<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('shops_shop_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // ถ้าต้อง rollback ให้ลบคอลัมน์
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');
        });
    }
};
