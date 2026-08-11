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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('qr_code')->nullable()->after('remarks');
            $table->decimal('total_down_payment', 18, 3)->default(0)->after('discount_percent');
            $table->decimal('freight', 18, 3)->default(0)->after('total_after_discount');
            $table->boolean('rounding')->default(false)->after('freight');
            $table->decimal('tax', 18, 3)->default(0)->after('rounding');
            $table->decimal('applied_amount', 18, 3)->default(0)->after('tax');
            $table->decimal('balance_due', 18, 3)->default(0)->after('applied_amount');
            $table->boolean('payment_order_run')->default(false)->after('balance_due');
            $table->text('attachments')->nullable()->after('payment_order_run');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'qr_code',
                'total_down_payment',
                'freight',
                'rounding',
                'tax',
                'applied_amount',
                'balance_due',
                'payment_order_run',
                'attachments',
            ]);
        });
    }
};
