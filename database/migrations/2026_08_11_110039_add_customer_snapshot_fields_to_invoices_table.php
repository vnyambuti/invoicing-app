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
            if (!Schema::hasColumn('invoices', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('invoices', 'bp_currency')) {
                $table->string('bp_currency', 3)->nullable()->after('contact_person');
            }
            if (!Schema::hasColumn('invoices', 'kra_pin')) {
                $table->string('kra_pin')->nullable()->after('bp_currency');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['contact_person', 'bp_currency', 'kra_pin']);
        });
    }
};
