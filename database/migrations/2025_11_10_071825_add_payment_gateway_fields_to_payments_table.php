<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Stripe fields
            $table->string('stripe_public_key')->nullable()->after('iban_number');
            $table->string('stripe_secret_key')->nullable()->after('stripe_public_key');

            // PayPal fields
            $table->string('paypal_client_id')->nullable()->after('stripe_secret_key');
            $table->string('paypal_secret')->nullable()->after('paypal_client_id');

            // Make bank fields nullable for Cash/Stripe/PayPal
            $table->string('bank_name')->nullable()->change();
            $table->string('account_number')->nullable()->change();
            $table->string('sort_code')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['stripe_public_key', 'stripe_secret_key', 'paypal_client_id', 'paypal_secret']);
        });
    }
};
