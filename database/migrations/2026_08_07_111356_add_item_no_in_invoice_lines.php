<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {

            $table->string('item_no')->nullable()->after('item_id');
        });


        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->foreignId('item_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropColumn('item_no');
        });
    }
};
