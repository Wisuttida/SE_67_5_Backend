<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateStockInTriggerOnProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared('
            CREATE TRIGGER stock_in_trigger AFTER UPDATE ON products
            FOR EACH ROW
            BEGIN
                -- ตรวจสอบว่าค่า stock_quantity เพิ่มขึ้นหรือไม่
                IF NEW.stock_quantity > OLD.stock_quantity THEN
                    INSERT INTO stock_transaction (transaction_type, quantity, transaction_date, products_product_id)
                    VALUES (\'In\', NEW.stock_quantity - OLD.stock_quantity, NOW(), NEW.product_id);
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // ลบ trigger เมื่อ rollback migration
        DB::unprepared('DROP TRIGGER IF EXISTS stock_in_trigger');
    }
}
