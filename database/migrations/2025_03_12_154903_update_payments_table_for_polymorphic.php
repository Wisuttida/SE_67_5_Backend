<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePaymentsTableForPolymorphic extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            // ลบ orders_order_id หากมีอยู่
            if (Schema::hasColumn('payments', 'orders_order_id')) {
                try {
                    $table->dropForeign(['orders_order_id']);
                } catch (\Exception $e) {
                    // ถ้าไม่พบ foreign key ให้ข้ามไปได้เลย
                }
                $table->dropColumn('orders_order_id');
            }

            // เพิ่มคอลัมน์ paymentable_id หากไม่มีอยู่
            if (!Schema::hasColumn('payments', 'paymentable_id')) {
                $table->unsignedBigInteger('paymentable_id')->after('updated_at');
            }

            // เพิ่มคอลัมน์ paymentable_type หากไม่มีอยู่
            if (!Schema::hasColumn('payments', 'paymentable_type')) {
                $table->string('paymentable_type')->after('paymentable_id');
            }

            // เพิ่ม index (ถ้า index นี้ยังไม่มีอยู่)
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('payments');
            if (!array_key_exists('payments_paymentable_id_paymentable_type_index', $indexes)) {
                $table->index(['paymentable_id', 'paymentable_type']);
            }
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            // ลบ index ที่เพิ่มเข้ามา
            $table->dropIndex(['paymentable_id', 'paymentable_type']);

            // ลบคอลัมน์ paymentable_id และ paymentable_type
            if (Schema::hasColumn('payments', 'paymentable_id')) {
                $table->dropColumn('paymentable_id');
            }
            if (Schema::hasColumn('payments', 'paymentable_type')) {
                $table->dropColumn('paymentable_type');
            }

            // เพิ่ม orders_order_id กลับมา
            $table->unsignedBigInteger('orders_order_id')->after('updated_at');
            $table->foreign('orders_order_id')->references('order_id')->on('orders');
        });
    }
}
