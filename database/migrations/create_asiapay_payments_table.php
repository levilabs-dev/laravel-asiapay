<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asiapay_payments', function (Blueprint $table) {
            $table->id();
            $table->string('merchant')->default('default');
            $table->string('merch_order_id')->unique();
            $table->string('prepay_id')->nullable()->index();
            $table->string('payment_order_id')->nullable()->index();
            $table->string('trans_id')->nullable()->index();
            $table->string('order_status')->default('UNKNOWN')->index();
            $table->string('trade_status')->nullable()->index();
            $table->decimal('amount', 12, 3);
            $table->string('currency', 3)->default('IQD');
            $table->string('title')->nullable();
            $table->string('redirect_url')->nullable();
            $table->json('meta')->nullable();
            $table->nullableMorphs('payable');
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asiapay_payments');
    }
};
