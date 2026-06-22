<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('payments', 'tenant_id')) {
            Schema::table('payments', function (Blueprint $table) {
                try {
                    $table->dropForeign(['tenant_id']);
                } catch (\Throwable $e) {
                    // Skip when FK is already missing.
                }
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasColumn('payments', 'company_id')) {
            Schema::table('payments', function (Blueprint $table) {
                try {
                    $table->dropForeign(['company_id']);
                } catch (\Throwable $e) {
                    // Skip when FK is already missing.
                }
                $table->dropColumn('company_id');
            });
        }

        $columnsToDrop = [
            'payment_type',
            'bank_name',
            'account_number',
            'sort_code',
            'iban_number',
            'stripe_public_key',
            'stripe_secret_key',
            'paypal_client_id',
            'paypal_secret',
            'createdBy',
            'updatedBy',
        ];

        foreach ($columnsToDrop as $column) {
            if (Schema::hasColumn('payments', $column)) {
                Schema::table('payments', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        Schema::table('payments', function (Blueprint $table) {
            // Add nullable first for existing rows, then backfill and add unique index safely.
            if (! Schema::hasColumn('payments', 'payment_no')) {
                $table->string('payment_no')->nullable()->after('id');
            }
            if (! Schema::hasColumn('payments', 'driver_id')) {
                $table->foreignId('driver_id')->nullable()->after('payment_no')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('driver_id');
            }
            if (! Schema::hasColumn('payments', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('payments', 'amount')) {
                $table->decimal('amount', 12, 2)->default(0)->after('payment_date');
            }
            if (! Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')->nullable()->after('amount');
            }
        });

        $counter = 1;
        DB::table('payments')
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($payments) use (&$counter) {
                foreach ($payments as $payment) {
                    DB::table('payments')
                        ->where('id', $payment->id)
                        ->update(['payment_no' => 'Payment #'.$counter]);
                    $counter++;
                }
            });

        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->unique('payment_no');
            });
        } catch (\Throwable $e) {
            // Skip if unique index already exists.
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('driver_id');
            $table->dropColumn([
                'payment_no',
                'payment_method',
                'payment_date',
                'amount',
                'notes',
            ]);

            $table->string('payment_type')->after('id');
            $table->string('bank_name')->nullable()->after('payment_type');
            $table->string('account_number')->nullable()->after('bank_name');
            $table->string('sort_code')->nullable()->after('account_number');
            $table->string('iban_number')->nullable()->after('sort_code');
            $table->foreignId('company_id')->after('iban_number')->constrained()->onDelete('cascade');
            $table->string('stripe_public_key')->nullable()->after('company_id');
            $table->string('stripe_secret_key')->nullable()->after('stripe_public_key');
            $table->string('paypal_client_id')->nullable()->after('stripe_secret_key');
            $table->string('paypal_secret')->nullable()->after('paypal_client_id');
            $table->foreignId('tenant_id')->nullable()->after('paypal_secret')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('createdBy')->nullable()->after('tenant_id');
            $table->unsignedBigInteger('updatedBy')->nullable()->after('createdBy');
        });
    }
};
