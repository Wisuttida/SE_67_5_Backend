<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFkOnFragranceToneFilter extends Migration
{
    public function up()
    {
        Schema::table('fragrance_tone_filter', function (Blueprint $table) {
            // 1. ลบ Foreign Key เดิม โดยใช้ชื่อที่ถูกต้อง (fk_table1_products1)
            $table->dropForeign('fk_table1_products1');

            // 2. สร้าง Foreign Key ใหม่ พร้อม ON DELETE CASCADE
            //    และกำหนดชื่อ Foreign Key เป็น 'fk_table1_products1' เหมือนเดิม
            $table->foreign('products_product_id', 'fk_table1_products1')
                ->references('product_id')->on('products')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('fragrance_tone_filter', function (Blueprint $table) {
            // 1. ลบ Foreign Key ที่เพิ่งสร้าง (ชื่อ fk_table1_products1)
            $table->dropForeign('fk_table1_products1');

            // 2. สร้าง Foreign Key กลับไปเป็นแบบเดิม (ไม่มี onDelete('cascade'))
            $table->foreign('products_product_id', 'fk_table1_products1')
                ->references('product_id')->on('products');
        });
    }
}
