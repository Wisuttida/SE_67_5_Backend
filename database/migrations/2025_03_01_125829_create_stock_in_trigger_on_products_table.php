<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateStockInTriggerOnProductsTable extends Migration
{
    public function up()
    {
        // ลบ Trigger เก่าถ้ามี
        DB::unprepared("DROP TRIGGER IF EXISTS stock_in_trigger_after_insert");
        DB::unprepared("DROP TRIGGER IF EXISTS stock_in_trigger_after_update");

        // สร้าง Trigger หลังการ INSERT ในตาราง products
        DB::unprepared("
            CREATE TRIGGER stock_in_trigger_after_insert
            AFTER INSERT ON products
            FOR EACH ROW
            BEGIN
                IF NEW.stock_quantity > 0 THEN
                    INSERT INTO stock_transaction (transaction_type, quantity, transaction_date, products_product_id)
                    VALUES ('In', NEW.stock_quantity, NOW(), NEW.product_id);
                END IF;
            END
        ");

        // สร้าง Trigger หลังการ UPDATE ในตาราง products
        DB::unprepared("
            CREATE TRIGGER stock_in_trigger_after_update
            AFTER UPDATE ON products
            FOR EACH ROW
            BEGIN
                IF NEW.stock_quantity > OLD.stock_quantity THEN
                    INSERT INTO stock_transaction (transaction_type, quantity, transaction_date, products_product_id)
                    VALUES ('In', NEW.stock_quantity - OLD.stock_quantity, NOW(), NEW.product_id);
                END IF;
            END
        ");
    }

    public function down()
    {
        DB::unprepared("DROP TRIGGER IF EXISTS stock_in_trigger_after_insert");
        DB::unprepared("DROP TRIGGER IF EXISTS stock_in_trigger_after_update");
    }
}
